<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer l'identifiant de la commande
$idCommande = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idCommande) {
    die("Commande invalide.");
}

// Récupérer la commande appartenant à l'utilisateur connecté
$sqlCommande = "
    SELECT id_commande
    FROM commande
    WHERE id_commande = :id_commande
    AND id_utilisateur = :id_utilisateur
";

$stmtCommande = $pdo->prepare($sqlCommande);
$stmtCommande->execute([
    'id_commande' => $idCommande,
    'id_utilisateur' => $_SESSION['id_utilisateur']
]);

$commande = $stmtCommande->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    die("Commande introuvable.");
}

// Préparer un jeton de sécurité pour le formulaire d'annulation
if (empty($_SESSION['csrf_annulation'])) {
    $_SESSION['csrf_annulation'] = bin2hex(random_bytes(32));
}

$messageErreurSuppression = '';

// Traiter la demande d'annulation
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['annuler_commande'])) {

    // Vérifier le jeton de sécurité
    if (
        !isset($_POST['csrf_token'])
        || !hash_equals($_SESSION['csrf_annulation'], $_POST['csrf_token'])
    ) {
        $messageErreurSuppression = "La demande n'est pas valide. Veuillez réessayer.";
    } else {

        try {
            $pdo->beginTransaction();

            // Vérifier le dernier statut de la commande
            $requeteStatut = $pdo->prepare("
                SELECT id_statut
                FROM historique_commande
                WHERE id_commande = ?
                ORDER BY date_modification DESC, id_historique DESC
                LIMIT 1
                FOR UPDATE
            ");

            $requeteStatut->execute([$idCommande]);
            $statutActuel = $requeteStatut->fetchColumn();

            if ((int)$statutActuel !== 7) {

                $pdo->rollBack();
                $messageErreurSuppression =
                    "Cette commande ne peut plus être annulée car elle n'est plus en attente de validation.";

            } else {

                // Vérifier qu'aucun avis n'est associé à la commande
                $requeteAvis = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM avis
                    WHERE id_commande = ?
                ");

                $requeteAvis->execute([$idCommande]);

                if ((int)$requeteAvis->fetchColumn() > 0) {

                    $pdo->rollBack();
                    $messageErreurSuppression =
                        "Cette commande ne peut pas être supprimée car un avis lui est associé.";

                } else {

                    // Supprimer d'abord les données liées à la commande
                    $requeteLignes = $pdo->prepare("
                        DELETE FROM ligne_commande
                        WHERE id_commande = ?
                    ");
                    $requeteLignes->execute([$idCommande]);

                    $requeteHistorique = $pdo->prepare("
                        DELETE FROM historique_commande
                        WHERE id_commande = ?
                    ");
                    $requeteHistorique->execute([$idCommande]);

                    // Supprimer ensuite la commande
                    $requeteSuppression = $pdo->prepare("
                        DELETE FROM commande
                        WHERE id_commande = ?
                        AND id_utilisateur = ?
                    ");
                    $requeteSuppression->execute([
                        $idCommande,
                        $_SESSION['id_utilisateur']
                    ]);

                    $pdo->commit();

                    // Rediriger vers la liste des commandes
                    header('Location: espace-utilisateur.php?rubrique=commandes&annulation=ok');
                    exit;
                }
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $messageErreurSuppression =
                "Une erreur est survenue lors de l'annulation de votre commande.";
        }
    }
}

$sqlHistorique = "
    SELECT
        statut_commande.libelle,
         historique_commande.id_statut,
        historique_commande.date_modification
    FROM historique_commande
    INNER JOIN statut_commande
        ON historique_commande.id_statut = statut_commande.id_statut
    WHERE historique_commande.id_commande = :id_commande
    ORDER BY historique_commande.date_modification ASC
";

$stmtHistorique = $pdo->prepare($sqlHistorique);
$stmtHistorique->execute([
    'id_commande' => $idCommande
]);


$historique = $stmtHistorique->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de commande - Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header class="site-header">
    <h1>Vite & Gourmand</h1>
</header>

<main class="contenu-utilisateur">

    <div class="layout-utilisateur">


        <section class="contenu-section">

            <h2>Suivi de la commande n° <?= htmlspecialchars($commande['id_commande']) ?></h2>

            <p class="statut-commande-actuel">
                Statut actuel :
                <strong>
                    <?php
                    if (!empty($historique)) {
                        echo htmlspecialchars(end($historique)['libelle']);
                    } else {
                        echo "Historique non disponible";
                    }
                    ?>
                </strong>
            </p>

            <h3>Historique de votre commande</h3>

            <?php if (!empty($historique)) : ?>

                <div class="historique-commande">
                    <?php foreach ($historique as $etape) : ?>
                        <div class="etape-suivi">
                            <span class="statut-suivi">
                                <?= htmlspecialchars($etape['libelle']) ?>
                            </span>

                            <span class="date-suivi">
                                <?= date(
                                    'd/m/Y à H:i',
                                    strtotime($etape['date_modification'])
                                ) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else : ?>
                <p>L'historique de cette commande n'est pas encore disponible.</p>
            <?php endif; ?>

            <?php if (
    !empty($historique)
    && (int)end($historique)['id_statut'] === 7
) : ?>

    <form method="POST" action="suivi-commande.php?id=<?= (int)$idCommande ?>"
          onsubmit="return confirm('Voulez-vous vraiment annuler cette commande ?');">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_annulation']) ?>"
        >

        <button
    type="submit"
    name="annuler_commande"
    class="lien-annulation"
>
    Annuler ma commande
</button>
    </form>

<?php endif; ?>

            <div class="actions-suivi">
                <a href="espace-utilisateur.php?section=commandes" class="bouton">
                    Retour à mes commandes
                </a>

                <a href="index.php" class="bouton">
                    Retour à l'accueil
                </a>
            </div>

        </section>
    </div>
</main>

</body>
</html>