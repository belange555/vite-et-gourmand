
<?php
session_start();

require_once 'config/database.php';

$message = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenValide = false;
$idUtilisateur = null;
$tokenHash = null;

// 1. Vérifier que le jeton existe
if (!empty($token) && strlen($token) === 64) {

    // Calculer l'empreinte du jeton reçu
    $tokenHash = hash('sha256', $token);

    // Rechercher un jeton valide et non expiré
    $requete = $pdo->prepare("
        SELECT id_utilisateur
        FROM utilisateur
        WHERE token_reinitialisation = ?
        AND expiration_reinitialisation > NOW()
    ");

    $requete->execute([$tokenHash]);
    $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur) {
        $tokenValide = true;
        $idUtilisateur = $utilisateur['id_utilisateur'];
    }
}

// 2. Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValide) {

    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
if (strlen($motDePasse) < 8 || strlen($motDePasse) > 72) {

    $message = "Le mot de passe doit contenir entre 8 et 72 caractères.";

} elseif ($motDePasse !== $confirmation) {

    $message = "Les deux mots de passe ne correspondent pas.";

} else {

        // 3. Hacher le nouveau mot de passe
        $nouveauHash = password_hash($motDePasse, PASSWORD_DEFAULT);

        // 4. Enregistrer le nouveau mot de passe
        // et supprimer le jeton pour empêcher sa réutilisation
        $miseAJour = $pdo->prepare("
            UPDATE utilisateur
            SET mot_de_passe = ?,
                token_reinitialisation = NULL,
                expiration_reinitialisation = NULL          
            WHERE id_utilisateur = ?
            AND token_reinitialisation = ?
            AND expiration_reinitialisation > NOW()
        ");

        $miseAJour->execute([
            $nouveauHash,
            $idUtilisateur,
            $tokenHash
        ]);

        if ($miseAJour->rowCount() === 1) {

            $message = "Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.";

            $tokenValide = false;

        } else {

            $message = "Le lien est invalide ou a expiré.";
            $tokenValide = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Réinitialiser mon mot de passe - Vite & Gourmand</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <header>
        <h1>Vite & Gourmand</h1>
    </header>

    <main>

        <section class="page-mot-de-passe">

            <h2>Réinitialiser mon mot de passe</h2>

            <?php if ($message): ?>

                <p><?= htmlspecialchars($message) ?></p>

            <?php endif; ?>

            <?php if ($tokenValide): ?>

                <p>Choisissez votre nouveau mot de passe.</p>

                <form method="POST" action="">

                    <input
                        type="hidden"
                        name="token"
                        value="<?= htmlspecialchars($token) ?>"
                    >

                    <label for="mot_de_passe">
                        Nouveau mot de passe :
                    </label>

                    <input
                        type="password"
                        id="mot_de_passe"
                        name="mot_de_passe"
                        minlength="8"
                        required
                    >

                    <label for="confirmation">
                        Confirmer le nouveau mot de passe :
                    </label>

                    <input
                        type="password"
                        id="confirmation"
                        name="confirmation"
                        minlength="8"
                        required
                    >

                    <button type="submit">
                        Modifier mon mot de passe
                    </button>

                </form>

            <?php elseif (!$message): ?>

                <p>Ce lien est invalide ou a expiré. Veuillez effectuer une nouvelle demande.</p>

            <?php endif; ?>

            <a href="connexion.php">Retour à la connexion</a>

        </section>

    </main>

</body>
</html>
