
<?php
session_start();

require_once 'config/database.php';

$message = '';
$lienReinitialisation = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Récupérer et nettoyer l'adresse e-mail
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Veuillez saisir une adresse e-mail valide.";

    } else {

        // 2. Rechercher l'utilisateur dans la base de données
        $requete = $pdo->prepare("
            SELECT id_utilisateur
            FROM utilisateur
            WHERE email = ? AND actif = 1
        ");

        $requete->execute([$email]);
        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {

            // 3. Générer un jeton sécurisé
            $token = bin2hex(random_bytes(32));

            // 4. Calculer son empreinte pour le stockage
            $tokenHash = hash('sha256', $token);

            // 5. Définir une expiration de 30 minutes
            $expiration = date('Y-m-d H:i:s', time() + 1800);

            // 6. Enregistrer le jeton et son expiration
            $miseAJour = $pdo->prepare("
                UPDATE utilisateur
                SET token_reinitialisation = ?,
                    expiration_reinitialisation = ?
                WHERE id_utilisateur = ?
            ");

            $miseAJour->execute([
                $tokenHash,
                $expiration,
                $utilisateur['id_utilisateur']
            ]);

            // 7. Créer le lien de réinitialisation local
            $lienReinitialisation =
                'http://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF'])
                . '/reinitialiser_mot_de_passe.php?token='
                . urlencode($token);

        }

        // Message identique pour éviter de révéler si l'adresse existe
        $message = "Si un compte actif correspond à cette adresse, "
                 . "un lien de réinitialisation a été préparé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <h1>Vite & Gourmand</h1>
    </header>

    <main>
        <section class="page-mot-de-passe">

            <h2>Mot de passe oublié</h2>

            <p>
                Indiquez votre adresse e-mail pour demander
                la réinitialisation de votre mot de passe.
            </p>

            <?php if ($message): ?>
                <p><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <?php if ($lienReinitialisation): ?>
                <p>
                    <strong>Lien de test local :</strong>
                </p>

                <a href="<?= htmlspecialchars($lienReinitialisation) ?>">
                    Réinitialiser mon mot de passe
                </a>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="email">Adresse e-mail :</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                >

                <button type="submit">Envoyer</button>
            </form>

            <a href="connexion.php">Retour à la connexion</a>

        </section>
    </main>

</body>
</html>
