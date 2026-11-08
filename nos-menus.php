<?php
session_start();
require_once 'config/database.php';

$prenomUtilisateur = $_SESSION['prenom'] ?? null;

// Récupérer les thèmes pour le filtre
$requeteThemes = $pdo->query("
    SELECT id_theme, nom
    FROM THEME
    ORDER BY nom
");
$themes = $requeteThemes->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les régimes pour le filtre
$requeteRegimes = $pdo->query("
    SELECT id_regime, nom
    FROM REGIME
    ORDER BY nom
");
$regimes = $requeteRegimes->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les filtres envoyés par le formulaire
$themeSelectionne = $_GET['theme'] ?? '';
$regimeSelectionne = $_GET['regime'] ?? '';
$recherche = trim($_GET['recherche'] ?? '');

// Requête SQL de base
$sql = "
    SELECT
        MENU.id_menu,
        MENU.titre,
        MENU.description,
        MENU.image,
        MENU.nombre_personnes_minimum,
        MENU.prix,
        MENU.conditions,
        THEME.nom AS theme,
        REGIME.nom AS regime
    FROM MENU
    INNER JOIN THEME
        ON MENU.id_theme = THEME.id_theme
    INNER JOIN REGIME
        ON MENU.id_regime = REGIME.id_regime
    WHERE MENU.stock_disponible > 0
";

$params = [];

// Filtre par thème
if ($themeSelectionne !== '') {
    $sql .= " AND MENU.id_theme = :theme";
    $params[':theme'] = $themeSelectionne;
}

// Filtre par régime
if ($regimeSelectionne !== '') {
    $sql .= " AND MENU.id_regime = :regime";
    $params[':regime'] = $regimeSelectionne;
}

// Recherche par titre ou description
if ($recherche !== '') {
    $sql .= " AND (
        MENU.titre LIKE :recherche
        OR MENU.description LIKE :recherche
    )";
    $params[':recherche'] = '%' . $recherche . '%';
}

// Exécuter la requête
$requete = $pdo->prepare($sql);
$requete->execute($params);

$menus = $requete->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos menus - Vite & Gourmand</title>
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

    <section class="section-nos-menus">
        <h2>Nos menus</h2>

        <p>
            Découvrez nos menus savoureux, conçus pour s’adapter
            à tous vos événements.
        </p>
<form method="GET" action="nos-menus.php" class="filtres-menus">

    <select name="theme">
        <option value="">Tous les thèmes</option>
        <?php foreach ($themes as $theme) : ?>
            <option value="<?= (int) $theme['id_theme'] ?>"
                <?= (($_GET['theme'] ?? '') == $theme['id_theme']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($theme['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="regime">
        <option value="">Tous les régimes</option>
        <?php foreach ($regimes as $regime) : ?>
            <option value="<?= (int) $regime['id_regime'] ?>"
                <?= (($_GET['regime'] ?? '') == $regime['id_regime']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($regime['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input
        type="search"
        name="recherche"
        placeholder="Rechercher un menu..."
        value="<?= htmlspecialchars($_GET['recherche'] ?? '') ?>"
    >

    <button type="submit" class="bouton">Rechercher</button>

    <a href="nos-menus.php" class="bouton">Réinitialiser</a>

</form>
        
         <?php if (!empty($menus)) : ?>

    <div class="grille-menus">

        <?php foreach ($menus as $menu) : ?>

            <article class="carte-menu">

                <img
                    src="<?= htmlspecialchars($menu['image'] ?? '') ?>"
                    alt="<?= htmlspecialchars($menu['titre']) ?>"
                    class="image-menu"
                >

                <div class="contenu-carte-menu">

                    <h3><?= htmlspecialchars($menu['titre']) ?></h3>

                    <p class="description-menu">
                        <?= htmlspecialchars($menu['description']) ?>
                    </p>

                    <p class="informations-menu">
                        Thème : <?= htmlspecialchars($menu['theme']) ?><br>
                        Régime : <?= htmlspecialchars($menu['regime']) ?><br>
                        Dès <?= (int) $menu['nombre_personnes_minimum'] ?> personnes<br>
                        <strong>
                            <?= number_format($menu['prix'], 2, ',', ' ') ?>
                            € / personne
                        </strong>
                    </p>

                    <a
                        class="bouton bouton-details"
                        href="menu.php?id=<?= (int) $menu['id_menu'] ?>"
                    >
                        Détails
                    </a>

                </div>
            </article>

        <?php endforeach; ?>

    </div>

<?php else : ?>

    <p class="message-aucun-menu">
        Aucun menu ne correspond à votre recherche.
        Veuillez modifier vos critères ou réinitialiser les filtres.
    </p>

<?php endif; ?>
    </section>

</main>

</body>
</html>