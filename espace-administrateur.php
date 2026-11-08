
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

// Générer un jeton CSRF s'il n'existe pas
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Échapper les données affichées dans le HTML
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

$message = '';
$typeMessage = '';

// Traitement des actions de l'administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérifier le jeton CSRF
    $tokenRecu = $_POST['csrf_token'] ?? '';

    if (
        !is_string($tokenRecu) ||
        !hash_equals($_SESSION['csrf_token'], $tokenRecu)
    ) {
        $message = "Votre session a expiré ou le formulaire est invalide. Veuillez réessayer.";
        $typeMessage = 'erreur';

    } else {

        $idCommande = filter_input(INPUT_POST, 'id_commande', FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? '';
        $motif = trim($_POST['motif_annulation'] ?? '');

        if (!$idCommande) {
            $message = "Commande invalide.";
            $typeMessage = 'erreur';

        } elseif (!in_array($action, ['accepter', 'refuser'], true)) {
            $message = "Action invalide.";
            $typeMessage = 'erreur';

        } elseif ($action === 'refuser' && $motif === '') {
            $message = "Merci de saisir le motif du refus.";
            $typeMessage = 'erreur';

        } else {

            try {
                $pdo->beginTransaction();

                // Vérifier que la commande est toujours en attente de validation
                $sqlStatut = "
                    SELECT h.id_statut
                    FROM historique_commande h
                    WHERE h.id_commande = :id_commande
                    ORDER BY h.date_modification DESC, h.id_historique DESC
                    LIMIT 1
                    FOR UPDATE
                ";

                $stmtStatut = $pdo->prepare($sqlStatut);
                $stmtStatut->execute([
                    'id_commande' => $idCommande
                ]);

                $statutActuel = $stmtStatut->fetchColumn();

                if ((int) $statutActuel !== 7) {
                    throw new Exception(
                        "Cette commande n'est plus en attente de validation."
                    );
                }

                if ($action === 'accepter') {

                    // Statut 1 : Acceptée
                    $nouveauStatut = 1;

                    $sqlHistorique = "
                        INSERT INTO historique_commande
                        (id_commande, id_statut, date_modification)
                        VALUES (:id_commande, :id_statut, NOW())
                    ";

                    $stmtHistorique = $pdo->prepare($sqlHistorique);
                    $stmtHistorique->execute([
                        'id_commande' => $idCommande,
                        'id_statut' => $nouveauStatut
                    ]);

                    $message = "La commande a été acceptée.";
                    $typeMessage = 'succes';

                } else {

                    // Statut 8 : Refusée

                    // Enregistrer le motif du refus
                    $sqlMotif = "
                        UPDATE commande
                        SET motif_annulation = :motif
                        WHERE id_commande = :id_commande
                    ";

                    $stmtMotif = $pdo->prepare($sqlMotif);
                    $stmtMotif->execute([
                        'motif' => $motif,
                        'id_commande' => $idCommande
                    ]);

                    // Ajouter le statut 8 dans l'historique
                    $sqlHistorique = "
                        INSERT INTO historique_commande
                        (id_commande, id_statut, date_modification)
                        VALUES (:id_commande, 8, NOW())
                    ";

                    $stmtHistorique = $pdo->prepare($sqlHistorique);
                    $stmtHistorique->execute([
                        'id_commande' => $idCommande
                    ]);

                    $message = "La commande a été refusée.";
                    $typeMessage = 'succes';
                }

                $pdo->commit();

            } catch (Throwable $exception) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $message = "Une erreur est survenue lors du traitement de la commande.";
                $typeMessage = 'erreur';
            }
        }
    }
}

// Récupérer les commandes actuellement en attente de validation
$sqlCommandes = "
    SELECT
        c.id_commande,
        c.date_commande,
        c.date_prestation,
        c.heure_prestation,
        c.lieu_livraison,
        c.adresse_livraison,
        c.prix_livraison,
        c.prix_total,
        c.instructions,
        u.nom,
        u.prenom,
        u.email,
        u.gsm,
        m.titre AS menu,
        lc.nombre_personnes,
        lc.prix_unitaire,
        lc.sous_total
    FROM commande c
    INNER JOIN utilisateur u
        ON c.id_utilisateur = u.id_utilisateur
    INNER JOIN historique_commande h
        ON h.id_commande = c.id_commande
    INNER JOIN ligne_commande lc
        ON lc.id_commande = c.id_commande
    INNER JOIN menu m
        ON lc.id_menu = m.id_menu
    WHERE h.id_statut = 7
      AND h.id_historique = (
          SELECT h2.id_historique
          FROM historique_commande h2
          WHERE h2.id_commande = c.id_commande
          ORDER BY h2.date_modification DESC, h2.id_historique DESC
          LIMIT 1
      )
    ORDER BY c.date_prestation ASC, c.heure_prestation ASC
";

$stmtCommandes = $pdo->query($sqlCommandes);
$commandes = $stmtCommandes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Espace administrateur - Vite & Gourmand</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body class="page-administrateur">

<header>
    <div class="container">
        <h1>Espace administrateur</h1>
        <p>Gestion et validation des commandes</p>
    </div>
</header>

<main class="container">

    <div class="menu-admin-conteneur">

        <button
            type="button"
            class="bouton-menu-admin"
            aria-expanded="false"
            aria-controls="navigation-admin"
        >
            ☰ Menu de gestion
        </button>

        <nav class="navigation-admin" id="navigation-admin">
            <a href="gestion-menus.php">Gérer les menus</a>
            <a href="gestion-plats.php">Gérer les plats</a>
            <a href="gestion-employes.php">Gérer les employés</a>
            <a href="gestion-horaires.php">Gérer les horaires</a>
            <a href="statistiques.php">Statistiques</a>
        </nav>

    </div>

    <section class="section">

        <h2>Commandes en attente de validation</h2>

        <?php if ($message !== ''): ?>
            <div class="message <?= e($typeMessage) ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($commandes)): ?>

            <p>Aucune commande n'est actuellement en attente de validation.</p>

        <?php else: ?>

            <?php foreach ($commandes as $commande): ?>

                <article class="card commande-admin">

                    <h3>
                        Commande n°<?= e($commande['id_commande']) ?>
                    </h3>

                    <p>
                        <strong>Client :</strong>
                        <?= e($commande['prenom']) ?>
                        <?= e($commande['nom']) ?>
                    </p>

                    <p>
                        <strong>Email :</strong>
                        <?= e($commande['email']) ?>
                    </p>

                    <p>
                        <strong>Téléphone :</strong>
                        <?= e($commande['gsm']) ?>
                    </p>

                    <p>
                        <strong>Date de prestation :</strong>
                        <?= e($commande['date_prestation']) ?>
                    </p>

                    <p>
                        <strong>Heure :</strong>
                        <?= e($commande['heure_prestation']) ?>
                    </p>

                    <p>
                        <strong>Lieu de livraison :</strong>
                        <?= e($commande['lieu_livraison']) ?>
                    </p>

                    <?php if (!empty($commande['adresse_livraison'])): ?>
                        <p>
                            <strong>Adresse :</strong>
                            <?= e($commande['adresse_livraison']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="details-menus-commande">

                        <h4>Menus commandés</h4>

                        <p>
                            <strong>Menu :</strong>
                            <?= e($commande['menu']) ?>
                        </p>

                        <p>
                            <strong>Nombre de personnes :</strong>
                            <?= e($commande['nombre_personnes']) ?>
                        </p>

                        <p>
                            <strong>Prix par personne :</strong>
                            <?= number_format((float) $commande['prix_unitaire'], 2, ',', ' ') ?>
                            €
                        </p>

                        <p>
                            <strong>Sous-total :</strong>
                            <?= number_format((float) $commande['sous_total'], 2, ',', ' ') ?>
                            €
                        </p>

                    </div>

                    <p>
                        <strong>Frais de livraison :</strong>
                        <?= number_format((float) $commande['prix_livraison'], 2, ',', ' ') ?>
                        €
                    </p>

                    <p>
                        <strong>Prix total :</strong>
                        <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?>
                        €
                    </p>

                    <?php if (!empty($commande['instructions'])): ?>
                        <p>
                            <strong>Instructions du client :</strong><br>
                            <?= nl2br(e($commande['instructions'])) ?>
                        </p>
                    <?php endif; ?>

                    <hr>

                    <!-- Formulaire d'acceptation -->
                    <form method="POST" class="form-action-admin">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >

                        <input
                            type="hidden"
                            name="id_commande"
                            value="<?= e($commande['id_commande']) ?>"
                        >

                        <button
                            type="submit"
                            name="action"
                            value="accepter"
                            class="btn"
                        >
                            Accepter la commande
                        </button>

                    </form>

                    <!-- Formulaire de refus avec motif obligatoire -->
                    <form method="POST" class="form-action-admin">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >

                        <input
                            type="hidden"
                            name="id_commande"
                            value="<?= e($commande['id_commande']) ?>"
                        >

                        <label for="motif-<?= e($commande['id_commande']) ?>">
                            Motif du refus
                        </label>

                        <textarea
                            id="motif-<?= e($commande['id_commande']) ?>"
                            name="motif_annulation"
                            rows="3"
                            placeholder="Ex. : indisponibilité du matériel à cette date"
                        ></textarea>

                        <button
                            type="submit"
                            name="action"
                            value="refuser"
                            class="btn btn-danger"
                        >
                            Refuser la commande
                        </button>

                    </form>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

    <p>
        <a href="index.php">Retour à l'accueil</a>
    </p>

</main>

<script>
    const boutonMenu = document.querySelector('.bouton-menu-admin');
    const navigationAdmin = document.querySelector('.navigation-admin');

    boutonMenu.addEventListener('click', function () {
        navigationAdmin.classList.toggle('ouvert');

        const menuOuvert = navigationAdmin.classList.contains('ouvert');

        boutonMenu.setAttribute('aria-expanded', menuOuvert);
    });
</script>

</body>
</html>
