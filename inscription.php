 
<?php

require_once 'config/database.php';

$messageErreur = '';
$messageSucces = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $gsm = trim($_POST['gsm'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (
        $nom === '' ||
        $prenom === '' ||
        $gsm === '' ||
        $email === '' ||
        $adresse === '' ||
        $motDePasse === '' ||
        $confirmation === ''
    ) {
        $messageErreur = "Tous les champs sont obligatoires.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messageErreur = "Veuillez saisir une adresse e-mail valide.";

    } elseif (strlen($motDePasse) < 10) {
        $messageErreur = "Le mot de passe doit contenir au moins 10 caractères.";

    } elseif (
        !preg_match('/[A-Z]/', $motDePasse) ||
        !preg_match('/[a-z]/', $motDePasse) ||
        !preg_match('/[0-9]/', $motDePasse) ||
        !preg_match('/[^a-zA-Z0-9]/', $motDePasse)
    ) {
        $messageErreur = "Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.";

    } elseif ($motDePasse !== $confirmation) {
        $messageErreur = "Les deux mots de passe ne correspondent pas.";
    }

    if ($messageErreur === '') {

        $requete = $pdo->prepare(
            "SELECT id_utilisateur FROM utilisateur WHERE email = ?"
        );

        $requete->execute([$email]);

        if ($requete->fetch()) {
            $messageErreur = "Cette adresse e-mail est déjà utilisée.";
        }
    }

    if ($messageErreur === '') {

        $motDePasseHash = password_hash(
            $motDePasse,
            PASSWORD_DEFAULT
        );

        $requete = $pdo->prepare("
            INSERT INTO utilisateur
                (nom, prenom, gsm, email, adresse, mot_de_passe, role)
            VALUES
                (?, ?, ?, ?, ?, ?, 'utilisateur')
        ");

        $requete->execute([
            $nom,
            $prenom,
            $gsm,
            $email,
            $adresse,
            $motDePasseHash
        ]);

        $messageSucces = "Votre compte a été créé avec succès.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="site-header">
        <h1>Vite & Gourmand</h1>
    </header>

    <main class="contenu-principal">
        <article class="carte-menu">
            <h2>Créer un compte</h2>

            <?php if ($messageErreur !== '') : ?>
    <p role="alert">
        <?= htmlspecialchars($messageErreur) ?>
    </p>
<?php endif; ?>

<?php if ($messageSucces !== '') : ?>
    <p>
        <?= htmlspecialchars($messageSucces) ?>
    </p>
<?php endif; ?>

            <p>Remplissez le formulaire pour créer votre espace client.</p>

            <form action="inscription.php" method="POST">

                <div class="champ-formulaire">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" autocomplete="family-name" required>
                </div>

                <div class="champ-formulaire">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" autocomplete="given-name" required>
                </div>

                <div class="champ-formulaire">
                    <label for="gsm">Téléphone</label>
                    <input type="tel" id="gsm" name="gsm" autocomplete="tel" required>
                </div>

                <div class="champ-formulaire">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" autocomplete="email" required>
                </div>

                <div class="champ-formulaire">
                    <label for="adresse">Adresse postale</label>
                    <input type="text" id="adresse" name="adresse" autocomplete="street-address" required>
                </div>

                <div class="champ-formulaire">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password" required>
                </div>

                <div class="champ-formulaire">
                    <label for="confirmation">Confirmer le mot de passe</label>
                    <input type="password" id="confirmation" name="confirmation" autocomplete="new-password" required>
                </div>

                <p>
                    <button type="submit" class="bouton">Créer mon compte</button>
                </p>

            </form>

            <p>Vous avez déjà un compte ?
                <a href="connexion.php">Se connecter</a>
            </p>
        </article>
    </main>

</body>
</html>