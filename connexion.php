<?php
session_start();

require_once 'config/database.php';

$messageErreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    $requete = $pdo->prepare("
    SELECT id_utilisateur, nom, prenom, email, mot_de_passe, role, actif
    FROM utilisateur
    WHERE email = ?
");

    $requete->execute([$email]);

    $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

   if (!$utilisateur || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {

    $messageErreur = "Adresse e-mail ou mot de passe incorrect.";

} elseif ((int) $utilisateur['actif'] !== 1) {

    $messageErreur = "Ce compte est désactivé. Merci de contacter l'administrateur.";

} else {

    session_regenerate_id(true);

    $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
    $_SESSION['nom'] = $utilisateur['nom'];
    $_SESSION['prenom'] = $utilisateur['prenom'];
    $_SESSION['role'] = $utilisateur['role'];

  // Redirection selon le rôle de l'utilisateur
if ($utilisateur['role'] === 'utilisateur') {
    header("Location: espace-utilisateur.php");
} elseif ($utilisateur['role'] === 'employe') {
    header("Location: espace-employe.php");
} elseif ($utilisateur['role'] === 'administrateur') {
    header("Location: espace-administrateur.php");
} else {
    // Rôle non reconnu : déconnexion et retour à la connexion
    session_unset();
    session_destroy();
    header("Location: connexion.php");
}

exit;
}
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
</head>

<body>

    <header class="site-header">
      <img
    src="images/logo-vite-et-gourmand.png"
    alt="Vite & Gourmand - Traiteur"
    class="logo-site"
>
    </header>

    <main class="contenu-principal">
        <article class="carte-menu">
            <h2>Connexion</h2>

            <p>Connectez-vous à votre espace client.</p>

            <?php if ($messageErreur !== '') : ?>
    <p role="alert">
        <?= htmlspecialchars($messageErreur) ?>
    </p>
<?php endif; ?>

             <form action="connexion.php" method="POST">

    <div class="champ-formulaire">
        <label for="email">Adresse e-mail</label>
        <input
            type="email"
            id="email"
            name="email"
            autocomplete="email"
            required
        >
    </div>

    <div class="champ-formulaire">
        <label for="mot_de_passe">Mot de passe</label>
        <input
            type="password"
            id="mot_de_passe"
            name="mot_de_passe"
            autocomplete="current-password"
            required
        >
    </div>

    <p>
        <button type="submit" class="bouton">Se connecter</button>
    </p>

    <p>
        Vous n'avez pas encore de compte ?
        <a href="inscription.php">Créer un compte</a>
    </p>

    <p>
        <a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
    </p>

</form>

        </article>
    </main>

</body>
</html>