<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

require_once 'config/database.php';

// Récupérer et vérifier l'identifiant du menu
$idMenu = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idMenu || $idMenu < 1) {
    die("Menu introuvable.");
}

// Récupérer les informations du menu
$requete = $pdo->prepare("
    SELECT
        id_menu,
        titre,
        description,
        nombre_personnes_minimum,
        prix,
        conditions
    FROM menu
    WHERE id_menu = ?
");

$requete->execute([$idMenu]);
$menu = $requete->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    die("Ce menu n'existe pas.");
}

// Préparer les erreurs et les valeurs du formulaire
$erreurs = [];

$nombrePersonnes = (int)$menu['nombre_personnes_minimum'];
$datePrestation = '';
$heurePrestation = '';
$lieuLivraison = '';
$adresseLivraison = '';
$instructions = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupérer les données envoyées par le formulaire
    $nombrePersonnes = filter_var(
        $_POST['nombre_personnes'] ?? null,
        FILTER_VALIDATE_INT
    );

    $datePrestation = trim($_POST['date_prestation'] ?? '');
    $heurePrestation = trim($_POST['heure_prestation'] ?? '');
    $lieuLivraison = $_POST['lieu_livraison'] ?? '';
    $adresseLivraison = trim($_POST['adresse_livraison'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    // Vérifier le nombre de personnes
    if (
        $nombrePersonnes === false ||
        $nombrePersonnes < (int)$menu['nombre_personnes_minimum']
    ) {
        $erreurs[] = "Le nombre de personnes est inférieur au minimum requis ou invalide.";
    }

    // Vérifier le format de la date
    $dateValide = DateTime::createFromFormat('!Y-m-d', $datePrestation);

    if (
        !$dateValide ||
        $dateValide->format('Y-m-d') !== $datePrestation
    ) {
        $erreurs[] = "Veuillez renseigner une date valide.";
    }

    // Vérifier le format de l'heure
    $heureValide = DateTime::createFromFormat('!H:i', $heurePrestation);

    if (
        !$heureValide ||
        $heureValide->format('H:i') !== $heurePrestation
    ) {
        $erreurs[] = "Veuillez renseigner une heure valide.";
    }

    // Vérifier le mode de réception
    if (!in_array(
        $lieuLivraison,
        ['Domicile', 'Retrait sur place'],
        true
    )) {
        $erreurs[] = "Veuillez choisir un mode de réception valide.";
    }

    // Vérifier l'adresse pour une livraison à domicile
    if (
        $lieuLivraison === 'Domicile' &&
        empty($adresseLivraison)
    ) {
        $erreurs[] = "Veuillez renseigner votre adresse de livraison.";
    }

    // Pour un retrait, l'adresse obligatoire en SQL reçoit cette indication
    if ($lieuLivraison === 'Retrait sur place') {
        $adresseLivraison = 'Retrait sur place';
    }

    // Enregistrer uniquement si aucune erreur n'est détectée
    if (empty($erreurs)) {

        // Calculer les prix côté serveur
        $prixUnitaire = (float)$menu['prix'];
        $sousTotal = $prixUnitaire * $nombrePersonnes;

        // Livraison fixe de 5 € à domicile
        $prixLivraison = ($lieuLivraison === 'Domicile') ? 5.00 : 0.00;

        $reduction = 0.00;
        $prixTotal = $sousTotal + $prixLivraison;

        // Les instructions sont facultatives
        $instructionsEnregistrees = ($instructions !== '')
            ? $instructions
            : null;

        try {

            // Démarrer une transaction SQL
            $pdo->beginTransaction();

            // 1. Enregistrer la commande
            $requeteCommande = $pdo->prepare("
                INSERT INTO commande (
                    id_utilisateur,
                    date_commande,
                    date_prestation,
                    heure_prestation,
                    lieu_livraison,
                    adresse_livraison,
                    prix_livraison,
                    reduction,
                    prix_total,
                    instructions
                )
                VALUES (
                    ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $requeteCommande->execute([
                $_SESSION['id_utilisateur'],
                $datePrestation,
                $heurePrestation,
                $lieuLivraison,
                $adresseLivraison,
                $prixLivraison,
                $reduction,
                $prixTotal,
                $instructionsEnregistrees
            ]);

            // Récupérer l'identifiant de la commande créée
            $idCommande = $pdo->lastInsertId();

            // 2. Enregistrer la ligne de commande
            $requeteLigne = $pdo->prepare("
                INSERT INTO ligne_commande (
                    id_commande,
                    id_menu,
                    nombre_personnes,
                    prix_unitaire,
                    sous_total
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $requeteLigne->execute([
                $idCommande,
                $idMenu,
                $nombrePersonnes,
                $prixUnitaire,
                $sousTotal
            ]);

            // 3. Créer le premier historique de commande
            // Le statut 7 correspond à « En attente de validation »
            $requeteHistorique = $pdo->prepare("
                INSERT INTO historique_commande (
                    id_commande,
                    id_statut,
                    date_modification
                )
                VALUES (?, 7, NOW())
            ");

            $requeteHistorique->execute([
                $idCommande
            ]);

            // Valider les trois enregistrements
            $pdo->commit();

            // Rediriger vers les commandes du client
            header('Location: espace-utilisateur.php?rubrique=commandes');
            exit;

        } catch (PDOException $e) {

            // Annuler les opérations en cas d'erreur SQL
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erreurs[] = "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passer commande - Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
</head>
<body>

    <header class="site-header">
       <img
    src="images/logo-vite-et-gourmand.png"
    alt="Vite & Gourmand - Traiteur"
    class="logo-site"
> 
    </header>

    <main class="contenu-principal">

    <h2>Passer une commande</h2>

    <article class="carte-menu">

        <h3><?= htmlspecialchars($menu['titre']) ?></h3>

        <p>
            <?= htmlspecialchars($menu['description']) ?>
        </p>

        <p>
            <strong>Nombre minimum de personnes :</strong>
            <?= (int)$menu['nombre_personnes_minimum'] ?>
        </p>

        <p>
            <strong>Prix par personne :</strong>
            <?= number_format((float)$menu['prix'], 2, ',', ' ') ?> €
        </p>

        <?php if (!empty($menu['conditions'])) : ?>
            <p>
                <strong>Conditions :</strong>
                <?= htmlspecialchars($menu['conditions']) ?>
            </p>
        <?php endif; ?>

    </article>

    <form action="commande.php?id=<?= (int)$menu['id_menu'] ?>" method="POST">
        <?php if (!empty($erreurs)) : ?>
    <div class="erreurs-formulaire">
        <ul>
            <?php foreach ($erreurs as $erreur) : ?>
                <li><?= htmlspecialchars($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

    <div class="champ-formulaire">
        <label for="nombre_personnes">Nombre de personnes :</label>

        <input
            type="number"
            id="nombre_personnes"
            name="nombre_personnes"
            min="<?= (int)$menu['nombre_personnes_minimum'] ?>"
            value="<?= (int)$menu['nombre_personnes_minimum'] ?>"
            required
        >
    </div>

    <div class="champ-formulaire">
    <label for="date_prestation">Date de prestation :</label>
    <input
        type="date"
        id="date_prestation"
        name="date_prestation"
        required
    >
</div>

<div class="champ-formulaire">
    <label for="heure_prestation">Heure de prestation :</label>
    <input
        type="time"
        id="heure_prestation"
        name="heure_prestation"
        required
    >
</div>

<div class="champ-formulaire">
    <label for="lieu_livraison">Mode de réception :</label>
    <select id="lieu_livraison" name="lieu_livraison" required>
        <option value="">Choisir une option</option>
        <option value="Domicile">Livraison à domicile</option>
        <option value="Retrait sur place">Retrait sur place</option>
    </select>
</div>

<div class="champ-formulaire">
    <label for="adresse_livraison">
        Adresse de livraison :
    </label>

    <input
        type="text"
        id="adresse_livraison"
        name="adresse_livraison"
        placeholder="Numéro, rue, code postal et ville"
    >
</div>

<div class="champ-formulaire">
    <label for="instructions">
        Instructions particulières (facultatif) :
    </label>

    <textarea
        id="instructions"
        name="instructions"
        rows="4"
        placeholder="Précisions concernant votre commande..."
    ></textarea>
</div>

    <div class="champ-formulaire">
    <p>
        <strong>Prix total :</strong>
        <span id="prix-total">
            <?= number_format(
                (float)$menu['prix'] * (int)$menu['nombre_personnes_minimum'],
                2,
                ',',
                ' '
            ) ?> €
        </span>
    </p>
</div>

<div class="champ-formulaire">
    <button type="submit" class="bouton">
        Valider ma commande
    </button>
</div>

</form>

</main>

<script>
    // Récupérer les éléments du formulaire
    const champPersonnes = document.getElementById('nombre_personnes');
const affichagePrix = document.getElementById('prix-total');
const modeReception = document.getElementById('lieu_livraison');

const prixParPersonne = <?= json_encode((float)$menu['prix']) ?>;

function calculerPrix() {

    const nombrePersonnes = parseInt(champPersonnes.value) || 0;

    const sousTotal = nombrePersonnes * prixParPersonne;

    const fraisLivraison = modeReception.value === 'Domicile' ? 5 : 0;

    const prixTotal = sousTotal + fraisLivraison;

    affichagePrix.textContent = prixTotal.toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' €';
}

champPersonnes.addEventListener('input', calculerPrix);
modeReception.addEventListener('change', calculerPrix);

calculerPrix();
</script>

<script>
const champAdresse = document.getElementById('adresse_livraison').parentElement;
const adresse = document.getElementById('adresse_livraison');

function gererAdresse() {
    const livraison = modeReception.value === 'Domicile';

    champAdresse.style.display = livraison ? 'block' : 'none';
    adresse.required = livraison;

    if (!livraison) {
        adresse.value = '';
    }
}

modeReception.addEventListener('change', gererAdresse);

gererAdresse();
</script>

</body>
</html>