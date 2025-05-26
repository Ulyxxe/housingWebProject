<?php
// public/admin_edit_news.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['login_error'] = "Admin access required.";
    header('Location: login.php');
    exit;
}

$pageTitle = "Admin: Edit News Article - CROUS-X";
$isLoggedIn = true;
$current_admin_id = $_SESSION['user_id']; // For tracking who edited, if needed

$article_id_to_edit = isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) ? (int)$_GET['id'] : null;

if (!$article_id_to_edit) {
    $_SESSION['admin_message'] = "No article ID provided for editing.";
    $_SESSION['admin_message_type'] = "error";
    header("Location: admin_manage_news.php");
    exit;
}

$errors = [];
$article_data = null;

// Fetch existing article data
try {
    $stmt_fetch = $pdo->prepare("SELECT * FROM news_articles WHERE article_id = :article_id");
    $stmt_fetch->bindParam(':article_id', $article_id_to_edit, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $article_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$article_data) {
        $_SESSION['admin_message'] = "Article with ID $article_id_to_edit not found.";
        $_SESSION['admin_message_type'] = "error";
        header("Location: admin_manage_news.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Admin Edit News - Fetch Error: " . $e->getMessage());
    $errors['database_fetch'] = "Could not load article data: " . $e->getMessage();
    // Let the form display this error below
}

// For re-populating form on error, prioritize session data, then POST, then fetched
$form_data = $_SESSION['form_data'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $article_data);
unset($_SESSION['form_data']);


$categories = ['Platform Updates', 'New Listings', 'Community', 'Events', 'Tips & Tricks', 'Announcements'];
$statuses = ['draft', 'published', 'archived'];

function generateSlugForEdit($title, $currentSlug = '') { // Slightly modified for edit
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: ($currentSlug ?: 'news-article-' . time());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $article_data) { // Ensure article_data was fetched
    // Update logic
    $title = trim($_POST['title'] ?? '');
    $slug_provided = trim($_POST['slug'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = in_array($_POST['category'] ?? '', $categories) ? $_POST['category'] : $article_data['category'];
    $tags = trim($_POST['tags'] ?? '');
    $status = in_array($_POST['status'] ?? '', $statuses) ? $_POST['status'] : $article_data['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $published_at_str = trim($_POST['published_at'] ?? '');
    $cover_image_url = trim($_POST['cover_image_url'] ?? '');

    // --- Validations (similar to add, but consider existing slug) ---
    if (empty($title)) $errors['title'] = "Title is required.";
    
    $slug = !empty($slug_provided) ? generateSlugForEdit($slug_provided, $article_data['slug']) : generateSlugForEdit($title, $article_data['slug']);
    if (empty($slug)) $errors['slug'] = "Slug could not be generated.";

    if (empty($content)) $errors['content'] = "Content is required.";

    $published_at = $article_data['published_at']; // Keep existing unless changed
    if (!empty($published_at_str)) {
        try {
            $dt = new DateTime($published_at_str);
            $published_at = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $errors['published_at'] = "Invalid 'Published At' date/time format.";
        }
    } elseif ($status === 'published' && empty($article_data['published_at']) && empty($published_at_str)) {
        $published_at = date('Y-m-d H:i:s'); // Set to now if publishing for the first time and not set
    } elseif ($status !== 'published' && $article_data['status'] === 'published' && empty($published_at_str)) {
         // If unpublishing and published_at was set, keep it, or nullify if you want to reset
         // $published_at = null; // Optional: reset publish date if unpublishing
    }


    // Check if new slug (if changed) already exists for a DIFFERENT article
    if (empty($errors['slug']) && $slug !== $article_data['slug']) {
        try {
            $stmt_check_slug = $pdo->prepare("SELECT article_id FROM news_articles WHERE slug = :slug AND article_id != :current_article_id");
            $stmt_check_slug->execute(['slug' => $slug, 'current_article_id' => $article_id_to_edit]);
            if ($stmt_check_slug->fetch()) {
                $errors['slug'] = "This slug is already in use by another article. Please choose a different one.";
            }
        } catch (PDOException $e) {
            $errors['database'] = "Error checking slug uniqueness: " . $e->getMessage();
        }
    }

    // --- TODO: File Upload for Cover Image (Update/Delete old if new one is uploaded) ---

    if (empty($errors)) {
        try {
            $sql = "UPDATE news_articles SET
                        user_id = :user_id, 
                        title = :title,
                        slug = :slug,
                        summary = :summary,
                        content = :content,
                        category = :category,
                        tags = :tags,
                        status = :status,
                        is_featured = :is_featured,
                        cover_image_url = :cover_image_url,
                        published_at = :published_at,
                        updated_at = NOW()
                    WHERE article_id = :article_id_to_edit";
            $stmt = $pdo->prepare($sql);
            // Although user_id (author) might not change often, good to include if an admin is editing another admin's post
            // Or, you might want a separate 'last_edited_by_user_id' column. For now, keep original author or update.
            $author_id_for_update = $article_data['user_id'] ?: $current_admin_id; // Keep original author unless null

            $stmt->bindParam(':user_id', $author_id_for_update, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':summary', $summary);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':tags', $tags);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':is_featured', $is_featured, PDO::PARAM_INT);
            $stmt->bindParam(':cover_image_url', $cover_image_url);
            $stmt->bindParam(':published_at', $published_at);
            $stmt->bindParam(':article_id_to_edit', $article_id_to_edit, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "News article '<strong>" . htmlspecialchars($title) . "</strong>' updated successfully!";
                $_SESSION['admin_message_type'] = "success";
                unset($form_data); // Clear form data on success
                header("Location: admin_manage_news.php"); // Or back to edit page: "admin_edit_news.php?id=$article_id_to_edit&update=success"
                exit;
            } else {
                $errors['database'] = "Failed to update news article.";
            }
        } catch (PDOException $e) {
            error_log("Admin Edit News Error: " . $e->getMessage());
            $errors['database'] = "Database error during update: " . $e->getMessage();
        }
    }
     // If errors, store them to display on the form
    $_SESSION['form_errors_news_edit'] = $errors;
    $_SESSION['form_data'] = $_POST; // Store submitted data for re-population
    header("Location: admin_edit_news.php?id=" . $article_id_to_edit); // Redirect back to the form
    exit;
}

// Retrieve errors from session if redirected from POST
$page_errors = $_SESSION['form_errors_news_edit'] ?? [];
unset($_SESSION['form_errors_news_edit']);

?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/admin_panel.css"> 
    <link rel="stylesheet" href="css/add-housing.css"> <!-- Reusing styles -->
</head>
<body>
    <?php require 'header.php'; ?>

    <main class="app-container">
        <div class="add-housing-form-container">
            <h2 class="add-housing-form-title">Edit News Article (ID: <?php echo htmlspecialchars($article_id_to_edit); ?>)</h2>

            <?php if (isset($_GET['update']) && $_GET['update'] === 'success' && isset($_SESSION['admin_message'])): ?>
                 <div class="form-message success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
                 </div>
                <?php unset($_SESSION['admin_message']); ?>
            <?php elseif (!empty($page_errors)): ?>
                <div class="form-message error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Please correct the following errors:</strong>
                    <ul>
                        <?php foreach ($page_errors as $field => $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($article_data && empty($errors['database_fetch'])): // Show form if article data loaded ?>
            <form action="admin_edit_news.php?id=<?php echo htmlspecialchars($article_id_to_edit); ?>" method="post" id="editNewsForm" class="add-housing-form" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="slug">Slug (URL-friendly)</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($form_data['slug'] ?? ''); ?>" placeholder="e.g., my-awesome-news-article">
                </div>

                <div class="form-group">
                    <label for="summary">Summary (Optional)</label>
                    <textarea id="summary" name="summary" rows="3"><?php echo htmlspecialchars($form_data['summary'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($form_data['content'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($form_data['category']) && $form_data['category'] == $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($form_data['tags'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $stat): ?>
                                <option value="<?php echo htmlspecialchars($stat); ?>" <?php echo (isset($form_data['status']) && $form_data['status'] == $stat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($stat)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="published_at">Publish At (YYYY-MM-DD HH:MM:SS)</label>
                        <input type="text" id="published_at" name="published_at" class="flatpickr-datetime" 
                               value="<?php echo htmlspecialchars(isset($form_data['published_at']) ? ( (new DateTime($form_data['published_at']))->format('Y-m-d H:i:s') ) : ''); ?>" 
                               placeholder="Leave blank to keep current or publish now">
                    </div>
                </div>

                <div class="form-group">
                    <label for="cover_image_url">Cover Image URL (Optional)</label>
                    <input type="url" id="cover_image_url" name="cover_image_url" value="<?php echo htmlspecialchars($form_data['cover_image_url'] ?? ''); ?>">
                    <?php if (!empty($form_data['cover_image_url'])): ?>
                        <small>Current: <img src="<?php echo htmlspecialchars($form_data['cover_image_url']); ?>" alt="Current Cover" style="max-height: 50px; vertical-align: middle; margin-left: 10px;"></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo (isset($form_data['is_featured']) && $form_data['is_featured']) ? 'checked' : ''; ?>>
                        <label for="is_featured">Mark as Featured Article?</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit-listing">Update Article</button>
                <a href="admin_manage_news.php" class="btn btn-secondary" style="margin-top: 10px; display: block; text-align:center;">Cancel</a>
            </form>
            <?php elseif(isset($errors['database_fetch'])): ?>
                 <p>Error loading article data. Please try again or contact support.</p>
                 <p><a href="admin_manage_news.php" class="btn btn-secondary">Back to News Management</a></p>
            <?php endif; ?>
        </div>
    </main>

    <?php require 'chat-widget.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".flatpickr-datetime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i:S",
                altInput: true,
                altFormat: "F j, Y H:i",
                time_24hr: true,
                defaultDate: document.getElementById('published_at').value || null // Pre-fill flatpickr
            });
             // Slug generation (same as add page)
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            if (titleInput && slugInput) {
                titleInput.addEventListener('keyup', function() {
                    if (slugInput.dataset.userModified !== 'true') {
                        let slug = this.value.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').trim('-');
                        slugInput.value = slug;
                    }
                });
                slugInput.addEventListener('input', function() {
                    slugInput.dataset.userModified = 'true'; // User is manually editing slug
                });
            }
        });
    </script>
</body>
</html>