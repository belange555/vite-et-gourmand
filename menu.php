<?php

require_once 'config/database.php';

$idMenu = $_GET['id'] ?? null;

if (!$idMenu) {
    die("Menu introuvable.");
}

$requete = $pdo->prepare("
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
    WHERE MENU.id_menu = ?
");

$requete->execute([$idMenu]);

$menu = $requete->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    die("Ce menu n'existe pas.");
}

$requetePlats = $pdo->prepare("
    SELECT
        PLAT.nom AS plat,
        PLAT.description AS description,
        MENU_PLAT.type_dans_menu,
        GROUP_CONCAT(DISTINCT ALLERGENE.nom SEPARATOR ', ') AS allergenes
    FROM MENU_PLAT
    INNER JOIN PLAT
        ON MENU_PLAT.id_plat = PLAT.id_plat
    LEFT JOIN PLAT_ALLERGENE
        ON PLAT.id_plat = PLAT_ALLERGENE.id_plat
    LEFT JOIN ALLERGENE
        ON PLAT_ALLERGENE.id_allergene = ALLERGENE.id_allergene
    WHERE MENU_PLAT.id_menu = ?
    GROUP BY PLAT.id_plat, PLAT.nom, PLAT.description, MENU_PLAT.type_dans_menu
");

$requetePlats->execute([$idMenu]);

$plats = $requetePlats->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($menu['titre']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

   <header class="site-header">
        <h1>Vite & Gourmand</h1>
    </header>

    <main class="contenu-principal">
        <article class="carte-menu">

            <h2 class="titre-menu"><?= htmlspecialchars($menu['titre']) ?></h2>

            <img
    src="<?= htmlspecialchars($menu['image'] ?? '') ?>"
    alt="<?= htmlspecialchars($menu['titre']) ?>"
    class="image-menu image-menu-detail"
>
    <p>
        <?= htmlspecialchars($menu['description']) ?>
    </p>

    <p>
        <strong>Thème :</strong>
        <?= htmlspecialchars($menu['theme']) ?>
    </p>

    <p>
        <strong>Régime :</strong>
        <?= htmlspecialchars($menu['regime']) ?>
    </p>

    <p>
        <strong>À partir de :</strong>
        <?= $menu['nombre_personnes_minimum'] ?> personnes
    </p>

   <p class="prix-menu">
    <strong>Prix par personne :</strong>
    <span><?= number_format($menu['prix'], 2, ',', ' ') ?> €</span>
</p>

<?php if (!empty($menu['conditions'])) : ?>
    <p>
        <strong>Conditions de commande :</strong>
        <?= htmlspecialchars($menu['conditions']) ?>
    </p>
<?php endif; ?>

<h2 class="titre-composition">Composition du menu</h2>

<?php if (!empty($plats)) : ?>

    <div class="composition-menu">

    <?php
        $typesMenu = [
            'entrée' => 'Entrée',
            'plat' => 'Plat',
            'dessert' => 'Dessert'
        ];
        ?>

        <?php foreach ($typesMenu as $type => $titreType) : ?>

            <?php
            $platsDuType = array_filter($plats, function ($plat) use ($type) {
                return strtolower(trim($plat['type_dans_menu'])) === $type;
            });
            ?>

            <?php if (!empty($platsDuType)) : ?>
                <section class="bloc-composition">

                    <h3><?= htmlspecialchars($titreType) ?></h3>

                    <?php foreach ($platsDuType as $plat) : ?>
                        <div class="detail-plat">

                            <h4><?= htmlspecialchars($plat['plat']) ?></h4>

                            <?php if (!empty($plat['description'])) : ?>
                                <p>
                                    <?= htmlspecialchars($plat['description']) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($plat['allergenes'])) : ?>
                                <p class="allergenes">
                                    <strong>Allergènes :</strong>
                                    <?= htmlspecialchars($plat['allergenes']) ?>
                                </p>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>

                </section>
            <?php endif; ?>

        <?php endforeach; ?>

    </div>

<?php else : ?>

    <p class="composition-indisponible">
        La composition de ce menu sera bientôt disponible.
    </p>

<?php endif; ?>

    <p>
        <a class="bouton" href="nos-menus.php">← Retour aux menus</a>
        <a class="bouton"
   href="commande.php?id=<?= $menu['id_menu'] ?>">
    Passer commande
</a>
    </p>

     </article>

    </main>

</body>

</html>