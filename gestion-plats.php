<?php
session_start();

require_once 'config/database.php';

// Vérifier que l'utilisateur est connecté et administrateur
if (
    !isset($_SESSION['id_utilisateur']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'administrateur'
) {
    header('Location: connexion.php');
    exit;
}

// Sécuriser l'affichage HTML
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

$message = '';
$erreur = '';

// Valeurs du formulaire
$idPlat = '';
$nom = '';
$description = '';

// Déterminer si on souhaite modifier un plat
if (isset($_GET['modifier']) && ctype_digit($_GET['modifier'])) {
    $idPlat = (int) $_GET['modifier'];

    $stmtPlat = $pdo->prepare("
        SELECT id_plat, nom, description
        FROM plat
        WHERE id_plat = ?
    ");
    $stmtPlat->execute([$idPlat]);
    $platAModifier = $stmtPlat->fetch(PDO::FETCH_ASSOC);

    if ($platAModifier) {
        $nom = $platAModifier['nom'];
        $description = $platAModifier['description'];
    } else {
        $erreur = "Le plat demandé n'existe pas.";
        $idPlat = '';
    }
}

// Traiter l'envoi du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPlat = $_POST['id_plat'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($nom === '' || $description === '') {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        if ($idPlat !== '') {
            // Modifier un plat existant
            $stmtUpdate = $pdo->prepare("
                UPDATE plat
                SET nom = ?, description = ?
                WHERE id_plat = ?
            ");
            $stmtUpdate->execute([$nom, $description, $idPlat]);

            $message = "Le plat a bien été modifié.";
        } else {
            // Ajouter un nouveau plat
            $stmtInsert = $pdo->prepare("
                INSERT INTO plat (nom, description)
                VALUES (?, ?)
            ");
            $stmtInsert->execute([$nom, $description]);

            $message = "Le plat a bien été ajouté.";
        }

        // Réinitialiser le formulaire
        $idPlat = '';
        $nom = '';
        $description = '';
    }
}

// Récupérer les plats enregistrés
$stmtPlats = $pdo->query("
    SELECT id_plat, nom, description
    FROM plat
    ORDER BY nom
");

$plats = $stmtPlats->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des plats - Vite & Gourmand</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
</head>

<body class="page-gestion-menus">

<header class="entete-administrateur">
    <div class="container">

        <a href="index.php" aria-label="Retour à l'accueil">
            <img
                src="images/logo-vite-et-gourmand.png"
                alt="Vite & Gourmand - Traiteur"
                class="logo-site"
            >
        </a>
     <div class="informations-administrateur">
        <h1>Gestion des plats</h1>
        <p>Ajouter, consulter et modifier les plats proposés</p>
    </div>
</header>

<main class="container">

    <section class="section">

        <h2><?= $idPlat !== '' ? 'Modifier un plat' : 'Ajouter un plat' ?></h2>

        <?php if ($message !== ''): ?>
            <p class="message-succes"><?= e($message) ?></p>
        <?php endif; ?>

        <?php if ($erreur !== ''): ?>
            <p class="message-erreur"><?= e($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" action="gestion-plats.php" class="formulaire">

            <input
                type="hidden"
                name="id_plat"
                value="<?= e($idPlat) ?>"
            >

            <div class="form-group">
                <label for="nom">Nom du plat</label>
                <input
                    type="text"
                    id="nom"
                    name="nom"
                    maxlength="150"
                    value="<?= e($nom) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    required
                ><?= e($description) ?></textarea>
            </div>

            <button type="submit" class="btn">
                <?= $idPlat !== '' ? 'Enregistrer les modifications' : 'Ajouter le plat' ?>
            </button>

            <?php if ($idPlat !== ''): ?>
                <a href="gestion-plats.php" class="btn">
                    Annuler
                </a>
            <?php endif; ?>

        </form>

    </section>

    <section class="section">

        <div class="entete-gestion">
            <h2>Plats disponibles</h2>
        </div>

        <?php if (empty($plats)): ?>

            <p>Aucun plat n'est enregistré.</p>

        <?php else: ?>

            <div class="grille-menus-admin">

                <?php foreach ($plats as $plat): ?>

                    <article class="card menu-admin">

                        <h3><?= e($plat['nom']) ?></h3>

                        <p><?= e($plat['description']) ?></p>

                        <a
                            href="gestion-plats.php?modifier=<?= e($plat['id_plat']) ?>"
                            class="btn"
                        >
                            Modifier
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

    <a href="espace-administrateur.php" class="btn-retour-admin">
    Retour à l'espace administrateur
</a>

</main>

</body>
</html>