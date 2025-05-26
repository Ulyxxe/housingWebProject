<?php
// public/admin_add_news.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['login_error'] = "Admin access required.";
    header('Location: login.php');
    exit;
}

$pageTitle = "Admin: Add News Article - CROUS-X";
$isLoggedIn = true;
$current_admin_id = $_SESSION['user_id'];

$errors = [];
$form_data = $_SESSION['form_data'] ?? []; // For re-populating form on error
unset($_SESSION['form_data']);

// Define categories and statuses for dropdowns
$categories = ['Platform Updates', 'New Listings', 'Community', 'Events', 'Tips & Tricks', 'Announcements'];
$statuses = ['draft', 'published', 'archived'];

// Slug generation function (basic)
function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug); // Remove special chars except space and hyphen
    $slug = preg_replace('/[\s-]+/', '-', $slug);      // Replace spaces and multiple hyphens with single hyphen
    $slug = trim($slug, '-');
    return $slug ?: 'news-article-' . time(); // Fallback slug
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = $_POST; // Keep submitted data for re-population

    $title = trim($_POST['title'] ?? '');
    $slug_provided = trim($_POST['slug'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? ''); // Consider using a WYSIWYG editor that posts HTML
    $category = in_array($_POST['category'] ?? '', $categories) ? $_POST['category'] : null;
    $tags = trim($_POST['tags'] ?? '');
    $status = in_array($_POST['status'] ?? '', $statuses) ? $_POST['status'] : 'draft';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $published_at_str = trim($_POST['published_at'] ?? '');
    $cover_image_url = trim($_POST['cover_image_url'] ?? ''); // Simple URL input for now

    // --- Validations ---
    if (empty($title)) $errors['title'] = "Title is required.";
    if (strlen($title) > 255) $errors['title'] = "Title is too long (max 255 chars).";
    
    $slug = !empty($slug_provided) ? generateSlug($slug_provided) : generateSlug($title);
    if (empty($slug)) $errors['slug'] = "Slug could not be generated. Provide a title or slug.";
    if (strlen($slug) > 270) $errors['slug'] = "Slug is too long (max 270 chars).";

    if (empty($content)) $errors['content'] = "Content is required.";
    // Add more validations: summary length, category validity if using FK, tags format, etc.

    $published_at = null;
    if (!empty($published_at_str)) {
        try {
            $dt = new DateTime($published_at_str);
            $published_at = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $errors['published_at'] = "Invalid 'Published At' date/time format. Use YYYY-MM-DD HH:MM:SS or similar.";
        }
    } elseif ($status === 'published' && empty($published_at_str)) {
        $published_at = date('Y-m-d H:i:s'); // Default to now if publishing and not set
    }
    
    // Check if slug already exists
    if (empty($errors['slug'])) {
        try {
            $stmt_check_slug = $pdo->prepare("SELECT article_id FROM news_articles WHERE slug = :slug");
            $stmt_check_slug->bindParam(':slug', $slug);
            $stmt_check_slug->execute();
            if ($stmt_check_slug->fetch()) {
                $errors['slug'] = "This slug is already in use. Please choose another or modify the title.";
            }
        } catch (PDOException $e) {
            $errors['database'] = "Error checking slug uniqueness: " . $e->getMessage();
        }
    }


    // --- TODO: File Upload for Cover Image ---
    // If implementing file uploads for 'cover_image_url', add that logic here.
    // For now, it's assumed to be a manually entered URL.

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO news_articles 
                        (user_id, title, slug, summary, content, category, tags, status, is_featured, cover_image_url, published_at, created_at, updated_at)
                    VALUES 
                        (:user_id, :title, :slug, :summary, :content, :category, :tags, :status, :is_featured, :cover_image_url, :published_at, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $current_admin_id, PDO::PARAM_INT);
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

            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "News article '<strong>" . htmlspecialchars($title) . "</strong>' added successfully!";
                $_SESSION['admin_message_type'] = "success";
                unset($form_data); // Clear form data on success
                header("Location: admin_manage_news.php");
                exit;
            } else {
                $errors['database'] = "Failed to add news article to the database.";
            }
        } catch (PDOException $e) {
            error_log("Admin Add News Error: " . $e->getMessage());
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
    // If errors, store them to display on the form
    $_SESSION['form_errors_news_add'] = $errors; // Store errors specifically for this form
    $_SESSION['form_data'] = $form_data; // Store submitted data for re-population
    header("Location: admin_add_news.php"); // Redirect back to the form
    exit;
}

// Retrieve errors from session if redirected from POST
$page_errors = $_SESSION['form_errors_news_add'] ?? [];
unset($_SESSION['form_errors_news_add']);

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
    <!-- Reusing add-housing.css form structure for consistency, or create specific styles -->
    <link rel="stylesheet" href="css/add-housing.css"> 
</head>
<body>
    <?php require 'header.php'; ?>

    <main class="app-container">
        <div class="add-housing-form-container"> <!-- Reusing class for layout -->
            <h2 class="add-housing-form-title">Add New News Article</h2>

            <?php if (!empty($page_errors)): ?>
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

            <form action="admin_add_news.php" method="post" id="addNewsForm" class="add-housing-form" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="slug">Slug (URL-friendly, auto-generated if blank)</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($form_data['slug'] ?? ''); ?>" placeholder="e.g., my-awesome-news-article">
                </div>

                <div class="form-group">
                    <label for="summary">Summary (Optional)</label>
                    <textarea id="summary" name="summary" rows="3"><?php echo htmlspecialchars($form_data['summary'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Content * (HTML or Markdown supported depending on display logic)</label>
                    <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($form_data['content'] ?? ''); ?></textarea>
                    <small>If using HTML, ensure it's properly formatted and sanitized.</small>
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
                        <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($form_data['tags'] ?? ''); ?>" placeholder="e.g., update, students, important">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $stat): ?>
                                <option value="<?php echo htmlspecialchars($stat); ?>" <?php echo (isset($form_data['status']) && $form_data['status'] == $stat || (!isset($form_data['status']) && $stat == 'draft')) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($stat)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="published_at">Publish At (Optional, YYYY-MM-DD HH:MM:SS)</label>
                        <input type="text" id="published_at" name="published_at" class="flatpickr-datetime" value="<?php echo htmlspecialchars($form_data['published_at'] ?? ''); ?>" placeholder="Defaults to now if publishing">
                    </div>
                </div>

                <div class="form-group">
                    <label for="cover_image_url">Cover Image URL (Optional)</label>
                    <input type="url" id="cover_image_url" name="cover_image_url" value="<?php echo htmlspecialchars($form_data['cover_image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                    <!-- Or implement file upload here -->
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo (isset($form_data['is_featured']) && $form_data['is_featured']) ? 'checked' : ''; ?>>
                        <label for="is_featured">Mark as Featured Article?</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit-listing">Add Article</button>
            </form>
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
                dateFormat: "Y-m-d H:i:S", // Match MySQL TIMESTAMP format
                altInput: true,
                altFormat: "F j, Y H:i", // Human-readable format
                time_24hr: true
            });

            // Basic slug generation preview (optional)
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            if (titleInput && slugInput) {
                titleInput.addEventListener('keyup', function() {
                    if (slugInput.value === '' || slugInput.dataset.autoGenerated === 'true') {
                        let slug = this.value.toLowerCase();
                        slug = slug.replace(/[^a-z0-9\s-]/g, '');
                        slug = slug.replace(/[\s-]+/g, '-');
                        slug = slug.trim('-');
                        slugInput.value = slug;
                        slugInput.dataset.autoGenerated = 'true';
                    }
                });
                slugInput.addEventListener('input', function() {
                     // If user types in slug field, stop auto-generation
                    slugInput.dataset.autoGenerated = 'false';
                });
            }
        });
    </script>
</body>
</html>