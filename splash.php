<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Splash - NomDuSite</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Insertion de la feuille de style principale respectant la charte graphique -->
  <link rel="stylesheet" href="css/main.css">
  <style>
    /* Styles spécifiques pour l'effet parallax */
    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow-x: hidden;
      font-family: inherit; /* Utilise la police définie dans main.css */
    }
    
    .parallax {
      perspective: 1px;
      height: 100vh;
      overflow-x: hidden;
      overflow-y: auto;
    }
    
    .parallax__group {
      position: relative;
      height: 100vh;
      transform-style: preserve-3d;
    }
    
    .parallax__layer {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      background-size: cover;
      background-position: center;
    }
    
    /* Calque lointain (mouvement lent) */
    .parallax__layer--deep {
      transform: translateZ(-2px) scale(3);
      background-image: url('images/deep-layer.jpg'); /* A adapter à vos images */
    }
    
    /* Calque intermédiaire */
    .parallax__layer--back {
      transform: translateZ(-1px) scale(2);
      background-image: url('images/back-layer.jpg'); /* A adapter à vos images */
    }
    
    /* Calque de base (contenu principal) */
    .parallax__layer--base {
      transform: translateZ(0);
      background-image: url('images/base-layer.jpg'); /* Optionnel, ou une couleur de fond si besoin */
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    /* Zone de contenu centrale */
    .content {
      position: relative;
      z-index: 1;
      text-align: center;
      background: rgba(255, 255, 255, 0.85); /* Peut être ajusté en fonction de votre charte graphique */
      padding: 20px;
      border-radius: 8px;
    }
    
    .content h1 {
      margin-bottom: 20px;
      font-size: 2.5em;
    }
    
    .content p {
      font-size: 1.2em;
    }
    
    .content a {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      padding: 10px 20px;
      background: #333;
      color: #fff;
      border-radius: 5px;
      transition: background 0.3s;
    }
    
    .content a:hover {
      background: #555;
    }
  </style>
</head>
<body>
  <div class="parallax">
    <div class="parallax__group">
      <!-- Calques d'arrière-plan avec effet parallax -->
      <div class="parallax__layer parallax__layer--deep"></div>
      <div class="parallax__layer parallax__layer--back"></div>
      
      <!-- Calque de base contenant le contenu principal -->
      <div class="parallax__layer parallax__layer--base">
        <div class="content">
          <h1>Bienvenue sur NomDuSite</h1>
          <p>Découvrez l'univers de [Votre Entreprise]</p>
          <!-- Lien redirigeant vers l'accueil -->
          <a href="index.php">Entrer</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
