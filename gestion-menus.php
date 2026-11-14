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

// Fonction pour sécuriser l'affichage HTML
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

// Récupérer les menus avec leur thème et leur régime
$sqlMenus = "
    SELECT
        m.id_menu,
        m.titre,
        m.description,
        m.nombre_personnes_minimum,
        m.prix,
        m.stock_disponible,
        m.image,
        t.nom AS theme,
        r.nom AS regime
    FROM menu m
    INNER JOIN theme t ON m.id_theme = t.id_theme
    INNER JOIN regime r ON m.id_regime = r.id_regime
    ORDER BY m.id_menu
";

$stmtMenus = $pdo->query($sqlMenus);
$menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des menus - Vite & Gourmand</title>

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
        <h1>Gestion des menus</h1>
        <p>Ajouter, consulter et modifier les menus proposés</p>
    </div>
</header>

<main class="container">

    <section class="section">

        <div class="entete-gestion">
            <h2>Menus disponibles</h2>

            <a href="ajouter-menu.php" class="btn">
                Ajouter un menu
            </a>
        </div>

        <?php if (empty($menus)): ?>

            <p>Aucun menu n'est enregistré.</p>

        <?php else: ?>

            <div class="grille-menus-admin">

                <?php foreach ($menus as $menu): ?>

                    <article class="card menu-admin">

                        <h3><?= e($menu['titre']) ?></h3>

                        <?php if (!empty($menu['image'])): ?>
                            <img
                                src="<?= e($menu['image']) ?>"
                                alt="<?= e($menu['titre']) ?>"
                                class="image-menu-admin"
                            >
                        <?php endif; ?>

                        <p><?= e($menu['description']) ?></p>

                        <p>
                            <strong>Thème :</strong>
                            <?= e($menu['theme']) ?>
                        </p>

                        <p>
                            <strong>Régime :</strong>
                            <?= e($menu['regime']) ?>
                        </p>

                        <p>
                            <strong>Nombre minimum de personnes :</strong>
                            <?= e($menu['nombre_personnes_minimum']) ?>
                        </p>

                        <p>
                            <strong>Prix par personne :</strong>
                            <?= number_format(
                                (float) $menu['prix'],
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </p>

                        <p>
                            <strong>Stock disponible :</strong>
                            <?= e($menu['stock_disponible']) ?>
                        </p>

                        <a
                            href="modifier-menu.php?id=<?= e($menu['id_menu']) ?>"
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