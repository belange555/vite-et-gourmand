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

// Sécuriser l'affichage
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

// Récupérer les thèmes, régimes et plats
$themes = $pdo->query("
    SELECT id_theme, nom
    FROM theme
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

$regimes = $pdo->query("
    SELECT id_regime, nom
    FROM regime
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

$plats = $pdo->query("
    SELECT id_plat, nom
    FROM plat
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

$messageErreur = '';
$messageSucces = '';

// Valeurs initiales du formulaire
$menu = [
    'titre' => '',
    'description' => '',
    'id_theme' => '',
    'id_regime' => '',
    'nombre_personnes_minimum' => '',
    'prix' => '',
    'stock_disponible' => '',
    'conditions' => '',
    'image' => ''
];

$selection = [
    'entrée' => '',
    'plat' => '',
    'dessert' => ''
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $menu['titre'] = trim($_POST['titre'] ?? '');
    $menu['description'] = trim($_POST['description'] ?? '');
    $menu['id_theme'] = $_POST['id_theme'] ?? '';
    $menu['id_regime'] = $_POST['id_regime'] ?? '';
    $menu['nombre_personnes_minimum'] = $_POST['nombre_personnes_minimum'] ?? '';
    $menu['prix'] = $_POST['prix'] ?? '';
    $menu['stock_disponible'] = $_POST['stock_disponible'] ?? '';
    $menu['conditions'] = trim($_POST['conditions'] ?? '');
    $menu['image'] = trim($_POST['image'] ?? '');

    $selection = [
        'entrée' => $_POST['entree'] ?? '',
        'plat' => $_POST['plat'] ?? '',
        'dessert' => $_POST['dessert'] ?? ''
    ];

    $nombrePersonnes = filter_var(
        $menu['nombre_personnes_minimum'],
        FILTER_VALIDATE_INT
    );

    $prix = filter_var($menu['prix'], FILTER_VALIDATE_FLOAT);

    $stock = filter_var(
        $menu['stock_disponible'],
        FILTER_VALIDATE_INT
    );

    $idTheme = filter_var($menu['id_theme'], FILTER_VALIDATE_INT);
    $idRegime = filter_var($menu['id_regime'], FILTER_VALIDATE_INT);

    // Vérifier les champs obligatoires
    if (
        $menu['titre'] === '' ||
        $menu['description'] === '' ||
        $nombrePersonnes === false ||
        $nombrePersonnes < 1 ||
        $prix === false ||
        $prix < 0 ||
        $stock === false ||
        $stock < 0 ||
        $idTheme === false ||
        $idRegime === false ||
        !$idTheme ||
        !$idRegime
    ) {
        $messageErreur = "Merci de vérifier les informations obligatoires.";
    } else {
        try {
            $pdo->beginTransaction();

            // Ajouter le menu
            $stmtMenu = $pdo->prepare("
                INSERT INTO menu (
                    id_theme,
                    id_regime,
                    titre,
                    description,
                    nombre_personnes_minimum,
                    prix,
                    conditions,
                    stock_disponible,
                    image
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtMenu->execute([
                $idTheme,
                $idRegime,
                $menu['titre'],
                $menu['description'],
                $nombrePersonnes,
                $prix,
                $menu['conditions'],
                $stock,
                $menu['image']
            ]);

            // Récupérer l'identifiant du menu ajouté
            $idMenu = $pdo->lastInsertId();

            // Associer les plats au menu
            $stmtPlat = $pdo->prepare("
                INSERT INTO menu_plat (
                    id_menu,
                    id_plat,
                    type_dans_menu
                )
                VALUES (?, ?, ?)
            ");

            foreach ($selection as $type => $idPlat) {
                if ($idPlat !== '') {
                    $stmtPlat->execute([
                        $idMenu,
                        $idPlat,
                        $type
                    ]);
                }
            }

            $pdo->commit();

            header('Location: gestion-menus.php?success=1');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $messageErreur = "Une erreur est survenue lors de l'ajout du menu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un menu - Vite & Gourmand</title>
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
        <h1>Ajouter un menu</h1>
        <p>Créer un nouveau menu et définir sa composition</p>
         </div>
    </div>
</header>

<main class="container">
    <section class="section formulaire-menu">

        <?php if ($messageErreur): ?>
            <p class="message erreur"><?= e($messageErreur) ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="champ-formulaire">
                <label for="titre">Nom du menu *</label>
                <input type="text" id="titre" name="titre"
                       value="<?= e($menu['titre']) ?>" required>
            </div>

            <div class="champ-formulaire">
                <label for="description">Description *</label>
                <textarea id="description" name="description"
                          rows="4" required><?= e($menu['description']) ?></textarea>
            </div>

            <div class="champ-formulaire">
                <label for="id_theme">Thème *</label>
                <select id="id_theme" name="id_theme" required>
                    <option value="">-- Choisir un thème --</option>

                    <?php foreach ($themes as $theme): ?>
                        <option value="<?= e($theme['id_theme']) ?>"
                            <?= $menu['id_theme'] == $theme['id_theme'] ? 'selected' : '' ?>>
                            <?= e($theme['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="champ-formulaire">
                <label for="id_regime">Régime *</label>
                <select id="id_regime" name="id_regime" required>
                    <option value="">-- Choisir un régime --</option>

                    <?php foreach ($regimes as $regime): ?>
                        <option value="<?= e($regime['id_regime']) ?>"
                            <?= $menu['id_regime'] == $regime['id_regime'] ? 'selected' : '' ?>>
                            <?= e($regime['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="champ-formulaire">
                <label for="nombre_personnes_minimum">
                    Nombre minimum de personnes *
                </label>
                <input type="number" id="nombre_personnes_minimum"
                       name="nombre_personnes_minimum" min="1"
                       value="<?= e($menu['nombre_personnes_minimum']) ?>"
                       required>
            </div>

            <div class="champ-formulaire">
                <label for="prix">Prix par personne (€) *</label>
                <input type="number" id="prix" name="prix"
                       min="0" step="0.01"
                       value="<?= e($menu['prix']) ?>" required>
            </div>

            <div class="champ-formulaire">
                <label for="stock_disponible">Stock disponible *</label>
                <input type="number" id="stock_disponible"
                       name="stock_disponible" min="0"
                       value="<?= e($menu['stock_disponible']) ?>"
                       required>
            </div>

            <div class="champ-formulaire">
                <label for="conditions">Conditions de commande</label>
                <textarea id="conditions" name="conditions"
                          rows="3"><?= e($menu['conditions']) ?></textarea>
            </div>

            <div class="champ-formulaire">
                <label for="image">Chemin de l'image</label>
                <input type="text" id="image" name="image"
                       value="<?= e($menu['image']) ?>"
                       placeholder="images/menu-exemple.jpg">
            </div>

            <h2>Composition du menu</h2>
            <p>Choisis un plat pour chaque catégorie. Les catégories peuvent rester vides.</p>

            <?php
            $categories = [
                'entree' => 'Entrée',
                'plat' => 'Plat',
                'dessert' => 'Dessert'
            ];
            ?>

            <?php foreach ($categories as $cle => $libelle): ?>
                <div class="champ-formulaire">
                    <label for="<?= e($cle) ?>"><?= e($libelle) ?></label>

                    <select id="<?= e($cle) ?>" name="<?= e($cle) ?>">
                        <option value="">-- Aucun plat --</option>

                        <?php foreach ($plats as $plat): ?>
                            <option value="<?= e($plat['id_plat']) ?>"
                                <?= $selection[$cle === 'entree' ? 'entrée' : $cle]
                                    == $plat['id_plat'] ? 'selected' : '' ?>>
                                <?= e($plat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn">Ajouter le menu</button>

        </form>
    </section>

    <p>
        <a href="gestion-menus.php" class="btn">
            Retour à la gestion des menus
        </a>
    </p>
</main>

</body>
</html>