<?php
session_start();

$prenomUtilisateur = $_SESSION['prenom'] ?? null;

$erreurs = [];
$messageEnvoye = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $objet = trim($_POST['objet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Vérifier l'objet
    if ($objet === '') {
        $erreurs[] = "Veuillez choisir un objet de demande.";
    }

    // Vérifier l'adresse e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Veuillez saisir une adresse e-mail valide.";
    }

    // Vérifier le message
    if ($message === '') {
        $erreurs[] = "Veuillez saisir votre message.";
    }

   // Si aucune erreur, enregistrer le message
if (empty($erreurs)) {

    require_once 'config/database.php';

    $requete = $pdo->prepare("
        INSERT INTO message_contact (objet, email, message)
        VALUES (?, ?, ?)
    ");

    $requete->execute([$objet, $email, $message]);

    $messageEnvoye = true;
}
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact - Vite & Gourmand</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header class="site-header">
    <div class="entete-accueil">

        <h1>Vite & Gourmand</h1>

        <nav class="navigation-principale">
            <a href="index.php">Accueil</a>
            <a href="nos-menus.php">Nos menus</a>
            <a href="contact.php">Contact</a>

            <?php if ($prenomUtilisateur !== null) : ?>
                <a href="deconnexion.php">Se déconnecter</a>
            <?php else : ?>
                <a class="bouton" href="connexion.php">Connexion</a>
            <?php endif; ?>
        </nav>

    </div>
</header>

<main class="contenu-principal">

    <section class="section-contact">

        <h2>Contactez-nous</h2>

        <p>
            Une question sur nos menus ou nos prestations ?
            Écrivez-nous.
        </p>

        <?php if ($messageEnvoye) : ?>

            <div class="message-succes">
                Votre message a bien été pris en compte.
            </div>

        <?php else : ?>

            <?php if (!empty($erreurs)) : ?>
                <div class="message-erreur">
                    <ul>
                        <?php foreach ($erreurs as $erreur) : ?>
                            <li><?= htmlspecialchars($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="contact.php" class="formulaire-contact">

                <div class="champ-formulaire">
                    <label for="objet">Objet de votre demande</label>

                    <select id="objet" name="objet" required>
                        <option value="">Choisissez un objet</option>
                        <option value="Question sur un menu">Question sur un menu</option>
                        <option value="Demande de prestation">Demande de prestation</option>
                        <option value="Réclamation">Réclamation</option>
                        <option value="Autre demande">Autre demande</option>
                    </select>
                </div>

                <div class="champ-formulaire">
                    <label for="email">Adresse e-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Votre@mail.fr"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="champ-formulaire">
                    <label for="message">Votre message</label>

                    <textarea
                        id="message"
                        name="message"
                        placeholder="Décrivez votre demande..."
                        rows="6"
                        required
                    ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="bouton">
                    Envoyer mon message
                </button>

            </form>

            <p class="information-rgpd">
    Les informations transmises sont utilisées uniquement
    pour répondre à votre demande. Pour en savoir plus,
    consultez nos <a href="mentions-legales.php">mentions légales
    et notre politique de confidentialité</a>.
</p>

        <?php endif; ?>

    </section>

</main>

</body>
</html>