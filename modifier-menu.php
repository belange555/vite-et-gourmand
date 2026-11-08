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

// Récupérer l'identifiant du menu
$idMenu = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idMenu) {
    header('Location: gestion-menus.php');
    exit;
}

// Récupérer les informations du menu
$stmtMenu = $pdo->prepare("
    SELECT *
    FROM menu
    WHERE id_menu = ?
");
$stmtMenu->execute([$idMenu]);
$menu = $stmtMenu->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    header('Location: gestion-menus.php');
    exit;
}

// Récupérer thèmes et régimes
$themes = $pdo->query("SELECT id_theme, nom FROM theme ORDER BY nom")
              ->fetchAll(PDO::FETCH_ASSOC);

$regimes = $pdo->query("SELECT id_regime, nom FROM regime ORDER BY nom")
                ->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les plats disponibles
$plats = $pdo->query("
    SELECT id_plat, nom
    FROM plat
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les plats déjà associés au menu
$stmtComposition = $pdo->prepare("
    SELECT id_plat, type_dans_menu
    FROM menu_plat
    WHERE id_menu = ?
");
$stmtComposition->execute([$idMenu]);
$compositionActuelle = $stmtComposition->fetchAll(PDO::FETCH_ASSOC);

// Préparer les plats sélectionnés par type
$selection = ['entree' => '', 'plat' => '', 'dessert' => ''];

foreach ($compositionActuelle as $ligne) {

    $type = strtolower(trim($ligne['type_dans_menu']));

    // Uniformiser la catégorie "entrée"
    if ($type === 'entrée') {
        $type = 'entree';
    }

    if (array_key_exists($type, $selection)) {
        $selection[$type] = (int) $ligne['id_plat'];
    }
}

$messageErreur = '';
$messageSucces = '';

// Traitement de la suppression du menu
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'supprimer'
) {
    try {
        $pdo->beginTransaction();

        // Supprimer les associations entre le menu et ses plats
        $stmtDeleteComposition = $pdo->prepare("
            DELETE FROM menu_plat
            WHERE id_menu = ?
        ");
        $stmtDeleteComposition->execute([$idMenu]);

        // Supprimer le menu
        $stmtDeleteMenu = $pdo->prepare("
            DELETE FROM menu
            WHERE id_menu = ?
        ");
        $stmtDeleteMenu->execute([$idMenu]);

        $pdo->commit();

        // Retour à la gestion des menus après suppression
        header('Location: gestion-menus.php?deleted=1');
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $messageErreur = "Ce menu ne peut pas être supprimé. Il est peut-être lié à une commande.";
    }
}

// Traitement du formulaire
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') !== 'supprimer'
) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $nombrePersonnes = filter_input(
        INPUT_POST,
        'nombre_personnes_minimum',
        FILTER_VALIDATE_INT
    );
    $prix = filter_input(INPUT_POST, 'prix', FILTER_VALIDATE_FLOAT);
    $conditions = trim($_POST['conditions'] ?? '');
    $stock = filter_input(INPUT_POST, 'stock_disponible', FILTER_VALIDATE_INT);
    $image = trim($_POST['image'] ?? '');
    $idTheme = filter_input(INPUT_POST, 'id_theme', FILTER_VALIDATE_INT);
    $idRegime = filter_input(INPUT_POST, 'id_regime', FILTER_VALIDATE_INT);

    $selection = [
        'entree' => $_POST['entree'] ?? '',
        'plat' => $_POST['plat'] ?? '',
        'dessert' => $_POST['dessert'] ?? ''
    ];

    if (
        $titre === '' ||
        $description === '' ||
        !$nombrePersonnes ||
        $nombrePersonnes < 1 ||
        $prix === false ||
        $prix < 0 ||
        $stock === false ||
        $stock < 0 ||
        !$idTheme ||
        !$idRegime
    ) {
        $messageErreur = "Merci de vérifier les informations obligatoires.";
    } else {
        try {
            $pdo->beginTransaction();

            // Modifier les informations du menu
            $stmtUpdate = $pdo->prepare("
                UPDATE menu
                SET
                    titre = ?,
                    description = ?,
                    nombre_personnes_minimum = ?,
                    prix = ?,
                    conditions = ?,
                    stock_disponible = ?,
                    image = ?,
                    id_theme = ?,
                    id_regime = ?
                WHERE id_menu = ?
            ");

            $stmtUpdate->execute([
                $titre,
                $description,
                $nombrePersonnes,
                $prix,
                $conditions,
                $stock,
                $image,
                $idTheme,
                $idRegime,
                $idMenu
            ]);

            // Supprimer l'ancienne composition
            $stmtDelete = $pdo->prepare("
                DELETE FROM menu_plat
                WHERE id_menu = ?
            ");
            $stmtDelete->execute([$idMenu]);

            // Ajouter la nouvelle composition
            $stmtInsert = $pdo->prepare("
                INSERT INTO menu_plat (id_menu, id_plat, type_dans_menu)
                VALUES (?, ?, ?)
            ");

            foreach ($selection as $type => $idPlat) {
    if ($idPlat !== '') {
        // Enregistrer "entrée" dans la base, même si le formulaire utilise "entree"
        $typeDansMenu = ($type === 'entree') ? 'entrée' : $type;

        $stmtInsert->execute([
            $idMenu,
            $idPlat,
            $typeDansMenu
        ]);
    }
}

            $pdo->commit();

            // Recharger la page avec le message de succès
            header('Location: modifier-menu.php?id=' . $idMenu . '&success=1');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $messageErreur = "Une erreur est survenue lors de la modification.";
        }
    }

    // Réafficher les valeurs saisies après erreur
    $menu['titre'] = $titre;
    $menu['description'] = $description;
    $menu['nombre_personnes_minimum'] = $nombrePersonnes;
    $menu['prix'] = $prix;
    $menu['conditions'] = $conditions;
    $menu['stock_disponible'] = $stock;
    $menu['image'] = $image;
    $menu['id_theme'] = $idTheme;
    $menu['id_regime'] = $idRegime;
}

if (isset($_GET['success'])) {
    $messageSucces = "Le menu a été modifié avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un menu - Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="page-gestion-menus">

<header>
    <div class="container">
        <h1>Modifier un menu</h1>
        <p>Mettre à jour les informations et la composition du menu</p>
    </div>
</header>

<main class="container">
    <section class="section formulaire-menu">

        <?php if ($messageSucces): ?>
            <p class="message succes"><?= e($messageSucces) ?></p>
        <?php endif; ?>

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
                    <?php foreach ($regimes as $regime): ?>
                        <option value="<?= e($regime['id_regime']) ?>"
                            <?= $menu['id_regime'] == $regime['id_regime'] ? 'selected' : '' ?>>
                            <?= e($regime['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="champ-formulaire">
                <label for="nombre_personnes_minimum">Nombre minimum de personnes *</label>
                <input type="number" id="nombre_personnes_minimum"
                       name="nombre_personnes_minimum" min="1"
                       value="<?= e($menu['nombre_personnes_minimum']) ?>" required>
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
                       value="<?= e($menu['stock_disponible']) ?>" required>
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
            <option
    value="<?= e($plat['id_plat']) ?>"
    <?= ($selection[$cle] ?? '') == $plat['id_plat'] ? 'selected' : '' ?>
>
    <?= e($plat['nom']) ?>
</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn">Enregistrer les modifications</button>

        </form>

        <form method="POST"
      onsubmit="return confirm('Attention : veux-tu vraiment supprimer ce menu ? Cette action est définitive.');">

    <input type="hidden" name="action" value="supprimer">

    <button type="submit" class="btn btn-danger">
        Supprimer ce menu
    </button>

</form>

    </section>

    <p>
        <a href="gestion-menus.php" class="btn">Retour à la gestion des menus</a>
    </p>
</main>

</body>
</html>
```

