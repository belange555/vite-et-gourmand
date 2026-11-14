
<?php
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

// Seuls les clients peuvent déposer un avis
if (($_SESSION['role'] ?? '') !== 'utilisateur') {
    header('Location: connexion.php');
    exit;
}

require_once 'config/database.php';

// Créer le jeton CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$idUtilisateur = (int) $_SESSION['id_utilisateur'];

$erreurs = [];
$messageSucces = '';

// Traiter l'envoi du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérifier le jeton CSRF
    $tokenRecu = $_POST['csrf_token'] ?? '';

    if (
        !is_string($tokenRecu) ||
        !hash_equals($_SESSION['csrf_token'], $tokenRecu)
    ) {
        $erreurs[] = "Votre session a expiré ou le formulaire est invalide. Veuillez réessayer.";

    } else {

        $idCommande = filter_var(
            $_POST['id_commande'] ?? null,
            FILTER_VALIDATE_INT
        );

        $note = filter_var(
            $_POST['note'] ?? null,
            FILTER_VALIDATE_INT
        );

        $commentaire = $_POST['commentaire'] ?? '';

        if (!is_string($commentaire)) {
            $commentaire = '';
        }

        $commentaire = trim($commentaire);

        // Vérifier la commande sélectionnée
        if (!$idCommande || $idCommande < 1) {
            $erreurs[] = "Veuillez sélectionner une commande valide.";
        }

        // Vérifier la note
        if ($note === false || $note === null || $note < 1 || $note > 5) {
            $erreurs[] = "La note doit être comprise entre 1 et 5.";
        }

        // Vérifier le commentaire
        if ($commentaire === '') {
            $erreurs[] = "Veuillez rédiger un commentaire.";
        }

        if (empty($erreurs)) {

            try {

                $pdo->beginTransaction();

                // Vérifier que la commande appartient au client,
                // qu'elle est terminée et qu'elle n'a pas déjà reçu d'avis.
                $requeteVerification = $pdo->prepare("
                    SELECT c.id_commande
                    FROM commande c
                    WHERE c.id_commande = ?
                    AND c.id_utilisateur = ?
                    AND (
                        SELECT h.id_statut
                        FROM historique_commande h
                        WHERE h.id_commande = c.id_commande
                        ORDER BY h.date_modification DESC, h.id_historique DESC
                        LIMIT 1
                    ) = 6
                    AND NOT EXISTS (
                        SELECT 1
                        FROM avis a
                        WHERE a.id_commande = c.id_commande
                    )
                    FOR UPDATE
                ");

                $requeteVerification->execute([
                    $idCommande,
                    $idUtilisateur
                ]);

                $commande = $requeteVerification->fetch(PDO::FETCH_ASSOC);

                if (!$commande) {

                    $pdo->rollBack();

                    $erreurs[] = "Cette commande n'est pas disponible pour un avis. Elle doit être terminée et ne pas avoir déjà reçu d'avis.";

                } else {

                    // Enregistrer l'avis en attente de validation
                    $requeteAvis = $pdo->prepare("
                        INSERT INTO avis (
                            id_commande,
                            note,
                            commentaire,
                            statut_validation,
                            date_avis
                        )
                        VALUES (?, ?, ?, 'En attente', NOW())
                    ");

                    $requeteAvis->execute([
                        $idCommande,
                        $note,
                        $commentaire
                    ]);

                    $pdo->commit();

                    // Renouveler le jeton après l'envoi
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    $messageSucces = "Votre avis a bien été envoyé. Il sera publié après validation.";
                }

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $erreurs[] = "Une erreur est survenue lors de l'enregistrement de votre avis.";
            }
        }
    }
}

// Récupérer les commandes terminées sans avis
$requeteCommandes = $pdo->prepare("
    SELECT
        c.id_commande,
        c.date_prestation,
        c.prix_total
    FROM commande c
    WHERE c.id_utilisateur = ?
    AND (
        SELECT h.id_statut
        FROM historique_commande h
        WHERE h.id_commande = c.id_commande
        ORDER BY h.date_modification DESC, h.id_historique DESC
        LIMIT 1
    ) = 6
    AND NOT EXISTS (
        SELECT 1
        FROM avis a
        WHERE a.id_commande = c.id_commande
    )
    ORDER BY c.date_prestation DESC
");

$requeteCommandes->execute([$idUtilisateur]);
$commandes = $requeteCommandes->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les avis déjà déposés par le client
$requeteMesAvis = $pdo->prepare("
    SELECT
        a.note,
        a.commentaire,
        a.statut_validation,
        a.date_avis,
        c.id_commande
    FROM avis a
    INNER JOIN commande c
        ON a.id_commande = c.id_commande
    WHERE c.id_utilisateur = ?
    ORDER BY a.date_avis DESC
");

$requeteMesAvis->execute([$idUtilisateur]);
$mesAvis = $requeteMesAvis->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes avis - Vite & Gourmand</title>

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

    <h2>Mes avis clients</h2>

    <p>
        Partagez votre expérience après une commande terminée.
    </p>

    <?php if (!empty($messageSucces)) : ?>
        <div class="message-succes">
            <?= htmlspecialchars($messageSucces, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erreurs)) : ?>
        <div class="erreurs-formulaire">
            <ul>
                <?php foreach ($erreurs as $erreur) : ?>
                    <li>
                        <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="carte-menu">

        <h3>Déposer un avis</h3>

        <?php if (!empty($commandes)) : ?>

            <form action="avis.php" method="POST">

                <!-- Protection CSRF -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="champ-formulaire">

                    <label for="id_commande">
                        Choisir une commande :
                    </label>

                    <select
                        id="id_commande"
                        name="id_commande"
                        required
                    >
                        <option value="">
                            Sélectionner une commande
                        </option>

                        <?php foreach ($commandes as $commande) : ?>

                            <option value="<?= (int) $commande['id_commande'] ?>">
                                Commande n° <?= (int) $commande['id_commande'] ?>
                                - <?= date('d/m/Y', strtotime($commande['date_prestation'])) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="champ-formulaire">

                    <label for="note">
                        Votre note :
                    </label>

                    <select id="note" name="note" required>
                        <option value="">Choisir une note</option>
                        <option value="1">1 - Très décevant</option>
                        <option value="2">2 - Décevant</option>
                        <option value="3">3 - Correct</option>
                        <option value="4">4 - Très bien</option>
                        <option value="5">5 - Excellent</option>
                    </select>

                </div>

                <div class="champ-formulaire">

                    <label for="commentaire">
                        Votre commentaire :
                    </label>

                    <textarea
                        id="commentaire"
                        name="commentaire"
                        rows="5"
                        placeholder="Partagez votre expérience..."
                        required
                    ></textarea>

                </div>

                <div class="champ-formulaire">

                    <button type="submit" class="bouton">
                        Envoyer mon avis
                    </button>

                </div>

            </form>

        <?php else : ?>

            <p>
                Vous n'avez actuellement aucune commande terminée
                disponible pour déposer un avis.
            </p>

        <?php endif; ?>

    </section>

    <section class="carte-menu">

        <h3>Mes avis précédents</h3>

        <?php if (!empty($mesAvis)) : ?>

            <?php foreach ($mesAvis as $avis) : ?>

                <article class="carte-menu">

                    <h4>
                        Commande n° <?= (int) $avis['id_commande'] ?>
                    </h4>

                    <p>
                        <strong>Note :</strong>
                        <?= (int) $avis['note'] ?>/5
                    </p>

                    <p>
                        <strong>Commentaire :</strong>
                        <?= nl2br(htmlspecialchars($avis['commentaire'], ENT_QUOTES, 'UTF-8')) ?>
                    </p>

                    <p>
                        <strong>Statut :</strong>
                        <?= htmlspecialchars($avis['statut_validation'], ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <p>
                        <strong>Date :</strong>
                        <?= date('d/m/Y', strtotime($avis['date_avis'])) ?>
                    </p>

                </article>

            <?php endforeach; ?>

        <?php else : ?>

            <p>Vous n'avez pas encore déposé d'avis.</p>

        <?php endif; ?>

    </section>

    <p class="actions-suivi">

        <a href="espace-utilisateur.php?rubrique=commandes" class="bouton">
            Retour à mes commandes
        </a>

        <a href="espace-utilisateur.php" class="bouton">
            Retour à mon espace utilisateur
        </a>

    </p>

</main>

</body>
</html>
