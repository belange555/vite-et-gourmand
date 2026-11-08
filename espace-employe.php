
<?php

session_start();

// Générer le jeton CSRF si nécessaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sécurité : accès réservé aux employés
if (
    !isset($_SESSION['id_utilisateur']) ||
    ($_SESSION['role'] ?? '') !== 'employe'
) {
    header('Location: connexion.php');
    exit;
}

$prenomEmploye = $_SESSION['prenom'] ?? 'Employé';

require_once 'config/database.php';

$erreurs = [];
$messageSucces = '';

// --------------------------------------------------
// TRAITEMENT DES FORMULAIRES
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // Vérification CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $erreurs[] = "La vérification de sécurité a échoué. Veuillez réessayer.";

    } elseif ($action === 'changer_statut') {

        // Récupération et validation des identifiants
        $idCommande = filter_var(
            $_POST['id_commande'] ?? null,
            FILTER_VALIDATE_INT
        );

        $idStatut = filter_var(
            $_POST['id_statut'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            !$idCommande ||
            $idCommande < 1 ||
            !$idStatut ||
            $idStatut < 2 ||
            $idStatut > 6
        ) {
            $erreurs[] = "Les informations de la commande sont invalides.";

        } else {

            try {

                $pdo->beginTransaction();

                // Récupérer le dernier statut de la commande
                $verification = $pdo->prepare("
                    SELECT h.id_statut
                    FROM commande c
                    LEFT JOIN historique_commande h
                        ON h.id_historique = (
                            SELECT h2.id_historique
                            FROM historique_commande h2
                            WHERE h2.id_commande = c.id_commande
                            ORDER BY h2.date_modification DESC,
                                     h2.id_historique DESC
                            LIMIT 1
                        )
                    WHERE c.id_commande = ?
                    FOR UPDATE
                ");

                $verification->execute([$idCommande]);

                $statutActuel = $verification->fetchColumn();

                if ($statutActuel === false) {
                    throw new Exception("Commande introuvable.");
                }

                $statutActuel = (int) $statutActuel;

                // Transitions autorisées
                $transitionsAutorisees = [
                    1 => 2,
                    2 => 3,
                    3 => 4,
                    4 => 6
                ];

                // Vérifier la transition
                if (
                    !isset($transitionsAutorisees[$statutActuel]) ||
                    $idStatut !== $transitionsAutorisees[$statutActuel]
                ) {
                    throw new Exception(
                        "Le changement de statut demandé n'est pas autorisé."
                    );
                }

                // Enregistrer le nouveau statut
                $requete = $pdo->prepare("
                    INSERT INTO historique_commande
                    (id_commande, id_statut, date_modification)
                    VALUES (?, ?, NOW())
                ");

                $requete->execute([$idCommande, $idStatut]);

                $pdo->commit();

                $messageSucces = "Le statut de la commande a été modifié.";

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $erreurs[] = $e->getMessage();
            }
        }

    } elseif ($action === 'valider_avis') {

        // Récupérer les informations de l'avis
        $idAvis = filter_var(
            $_POST['id_avis'] ?? null,
            FILTER_VALIDATE_INT
        );

        $decision = $_POST['decision'] ?? '';

        if (
            !$idAvis ||
            $idAvis < 1 ||
            !in_array($decision, ['Validé', 'Refusé'], true)
        ) {
            $erreurs[] = "Les informations de l'avis sont invalides.";

        } else {

            $requete = $pdo->prepare("
                UPDATE avis
                SET statut_validation = ?
                WHERE id_avis = ?
                AND statut_validation = 'En attente'
            ");

            $requete->execute([$decision, $idAvis]);

            if ($requete->rowCount() > 0) {
                $messageSucces = "La décision concernant l'avis a été enregistrée.";
            } else {
                $erreurs[] = "Cet avis n'existe pas ou a déjà été traité.";
            }
        }

    } else {

        $erreurs[] = "Action non reconnue.";
    }
}

// --------------------------------------------------
// RECHERCHE ET FILTRAGE DES COMMANDES
// --------------------------------------------------

$recherche = trim($_GET['recherche'] ?? '');

$filtreStatut = filter_var(
    $_GET['statut'] ?? '',
    FILTER_VALIDATE_INT
);

// Requête pour récupérer les commandes et leur dernier statut
$sqlCommandes = "
    SELECT
        c.id_commande,
        c.date_commande,
        c.date_prestation,
        c.heure_prestation,
        c.lieu_livraison,
        c.adresse_livraison,
        c.prix_total,
        c.instructions,
        u.nom,
        u.prenom,
        u.email,
        sc.id_statut,
        sc.libelle AS statut,

        (
            SELECT GROUP_CONCAT(
                CONCAT(m.titre, ' - ', lc.nombre_personnes, ' personnes')
                SEPARATOR ', '
            )
            FROM ligne_commande lc
            INNER JOIN menu m ON m.id_menu = lc.id_menu
            WHERE lc.id_commande = c.id_commande
        ) AS details_menu

    FROM commande c

    INNER JOIN utilisateur u
        ON u.id_utilisateur = c.id_utilisateur

    LEFT JOIN historique_commande h
        ON h.id_historique = (
            SELECT h2.id_historique
            FROM historique_commande h2
            WHERE h2.id_commande = c.id_commande
            ORDER BY h2.date_modification DESC,
                     h2.id_historique DESC
            LIMIT 1
        )

    LEFT JOIN statut_commande sc
        ON sc.id_statut = h.id_statut

    WHERE 1 = 1
";

// Ajouter le filtre de recherche
if ($recherche !== '') {

    $sqlCommandes .= "
        AND (
            CAST(c.id_commande AS CHAR) LIKE :recherche
            OR u.nom LIKE :recherche
            OR u.prenom LIKE :recherche
        )
    ";
}

// Ajouter le filtre de statut
if ($filtreStatut !== false && $filtreStatut > 0) {

    $sqlCommandes .= "
        AND sc.id_statut = :statut
    ";
}

$sqlCommandes .= " ORDER BY c.date_prestation ASC";

$requeteCommandes = $pdo->prepare($sqlCommandes);

if ($recherche !== '') {
    $requeteCommandes->bindValue(
        ':recherche',
        '%' . $recherche . '%',
        PDO::PARAM_STR
    );
}

if ($filtreStatut !== false && $filtreStatut > 0) {
    $requeteCommandes->bindValue(
        ':statut',
        $filtreStatut,
        PDO::PARAM_INT
    );
}

$requeteCommandes->execute();

$commandes = $requeteCommandes->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// TABLEAU DE BORD : COMMANDES RÉCENTES
// --------------------------------------------------

$commandesRecentes = [];
$commandesATraiter = 0;

$dateAujourdhui = date('Y-m-d');

foreach ($commandes as $commande) {

    $statutActuel = (int) ($commande['id_statut'] ?? 0);

    if (in_array($statutActuel, [1, 2, 3, 4], true)) {

        $commandesATraiter++;

        if ($commande['date_prestation'] >= $dateAujourdhui) {
            $commandesRecentes[] = $commande;
        }
    }
}

$commandesRecentes = array_slice($commandesRecentes, 0, 3);

// --------------------------------------------------
// RÉCUPÉRER LES STATUTS
// --------------------------------------------------

$requeteStatuts = $pdo->query("
    SELECT id_statut, libelle
    FROM statut_commande
    WHERE id_statut IN (2, 3, 4, 6)
    ORDER BY id_statut
");

$statuts = $requeteStatuts->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// RÉCUPÉRER LES AVIS EN ATTENTE
// --------------------------------------------------

$requeteAvis = $pdo->query("
    SELECT
        a.id_avis,
        a.note,
        a.commentaire,
        a.date_avis,
        c.id_commande,
        u.nom,
        u.prenom

    FROM avis a

    INNER JOIN commande c
        ON c.id_commande = a.id_commande

    INNER JOIN utilisateur u
        ON u.id_utilisateur = c.id_utilisateur

    WHERE a.statut_validation = 'En attente'

    ORDER BY a.date_avis ASC
");

$avisEnAttente = $requeteAvis->fetchAll(PDO::FETCH_ASSOC);

$nombreAvisEnAttente = count($avisEnAttente);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Espace employé - Vite &amp; Gourmand</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header class="site-header">

    <h1>Vite &amp; Gourmand</h1>

    <div class="bienvenue-header">
        <span aria-hidden="true">♙</span>
        Bonjour <?= htmlspecialchars($prenomEmploye) ?>
    </div>

</header>

<main class="contenu-principal">

    <h2>
        Espace employé
        <span class="bienvenue-employe">
            — Bienvenue dans votre espace de gestion
        </span>
    </h2>

    <!-- Tableau de bord -->
    <div class="tableau-bord-employe">

        <div class="carte-statistique-employe">
            <h3>Prochaines commandes à traiter</h3>
            <p><?= $commandesATraiter ?></p>
        </div>

        <div class="carte-statistique-employe">
            <h3>Avis en attente</h3>
            <p><?= $nombreAvisEnAttente ?></p>
        </div>

    </div>

    <!-- Messages -->
    <?php if ($messageSucces !== '') : ?>

        <div class="message-succes">
            <?= htmlspecialchars($messageSucces) ?>
        </div>

    <?php endif; ?>

    <?php if (!empty($erreurs)) : ?>

        <div class="erreurs-formulaire">
            <ul>
                <?php foreach ($erreurs as $erreur) : ?>
                    <li><?= htmlspecialchars($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

    <?php endif; ?>

    <!-- Commandes récentes -->
    <section class="carte-menu">

        <h3>Commandes récentes</h3>

        <?php if (!empty($commandesRecentes)) : ?>

            <?php foreach ($commandesRecentes as $commande) : ?>

                <article class="carte-menu">

                    <h4>
                        Commande n° <?= (int) $commande['id_commande'] ?>
                    </h4>

                    <p>
                        <strong>Client :</strong>
                        <?= htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']) ?>
                    </p>

                    <p>
                        <strong>Date de prestation :</strong>
                        <?= date('d/m/Y', strtotime($commande['date_prestation'])) ?>
                    </p>

                    <p>
                        <strong>Statut :</strong>
                        <?= htmlspecialchars($commande['statut'] ?? 'Non défini') ?>
                    </p>

                </article>

            <?php endforeach; ?>

        <?php else : ?>

            <p>Aucune commande récente.</p>

        <?php endif; ?>

    </section>

    <!-- Gestion des commandes -->
    <section class="carte-menu">

        <h3>Gestion des commandes</h3>

        <!-- Recherche et filtres -->
        <form method="GET" class="filtres-commandes-employe">

            <div class="champ-filtre">

                <label for="recherche">Rechercher une commande</label>

                <input
                    type="text"
                    id="recherche"
                    name="recherche"
                    placeholder="N° de commande ou nom du client"
                    value="<?= htmlspecialchars($recherche) ?>"
                >

            </div>

            <div class="champ-filtre">

                <label for="filtre-statut">Filtrer par statut</label>

                <select id="filtre-statut" name="statut">

                    <option value="">Tous les statuts</option>

                    <?php foreach ($statuts as $statut) : ?>

                        <option
                            value="<?= (int) $statut['id_statut'] ?>"
                            <?= ((string) ($_GET['statut'] ?? '') === (string) $statut['id_statut']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($statut['libelle']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <button type="submit">Rechercher</button>

            <a href="espace-employe.php" class="bouton-reinitialiser">
                Réinitialiser
            </a>

        </form>

        <!-- Liste des commandes -->
        <?php if (!empty($commandes)) : ?>

            <?php foreach ($commandes as $commande) : ?>

                <article class="carte-menu">

                    <h4>
                        Commande n° <?= (int) $commande['id_commande'] ?>
                    </h4>

                    <p>
                        <strong>Client :</strong>
                        <?= htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']) ?>
                    </p>

                    <p>
                        <strong>Email :</strong>
                        <?= htmlspecialchars($commande['email']) ?>
                    </p>

                    <p>
                        <strong>Date de prestation :</strong>
                        <?= date('d/m/Y', strtotime($commande['date_prestation'])) ?>
                    </p>

                    <p>
                        <strong>Heure :</strong>
                        <?= htmlspecialchars($commande['heure_prestation']) ?>
                    </p>

                    <p>
                        <strong>Réception :</strong>
                        <?= htmlspecialchars($commande['lieu_livraison']) ?>
                    </p>

                    <?php if (!empty($commande['adresse_livraison'])) : ?>

                        <p>
                            <strong>Adresse :</strong>
                            <?= htmlspecialchars($commande['adresse_livraison']) ?>
                        </p>

                    <?php endif; ?>

                    <p>
                        <strong>Menu :</strong>
                        <?= htmlspecialchars($commande['details_menu'] ?? 'Non renseigné') ?>
                    </p>

                    <p>
                        <strong>Instructions :</strong>
                        <?= nl2br(htmlspecialchars($commande['instructions'] ?? 'Aucune')) ?>
                    </p>

                    <p>
                        <strong>Montant :</strong>
                        <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €
                    </p>

                    <p>
                        <strong>Statut actuel :</strong>
                        <?= htmlspecialchars($commande['statut'] ?? 'Non défini') ?>
                    </p>

                    <?php
                    $statutActuel = (int) ($commande['id_statut'] ?? 0);
                    ?>

                    <?php if (in_array($statutActuel, [1, 2, 3, 4], true)) : ?>

                        <!-- FORMULAIRE DE CHANGEMENT DE STATUT -->
                        <form action="espace-employe.php" method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="changer_statut"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                            >

                            <input
                                type="hidden"
                                name="id_commande"
                                value="<?= (int) $commande['id_commande'] ?>"
                            >

                            <div class="champ-formulaire">

                                <label for="statut-<?= (int) $commande['id_commande'] ?>">
                                    Modifier le statut :
                                </label>

                                <select
                                    id="statut-<?= (int) $commande['id_commande'] ?>"
                                    name="id_statut"
                                    required
                                >

                                    <option value="">Choisir un statut</option>

                                    <?php foreach ($statuts as $statut) : ?>

                                        <option value="<?= (int) $statut['id_statut'] ?>">
                                            <?= htmlspecialchars($statut['libelle']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <button type="submit" class="bouton">
                                Enregistrer le statut
                            </button>

                        </form>
                        <!-- FIN DU FORMULAIRE DE CHANGEMENT DE STATUT -->

                    <?php else : ?>

                        <p>
                            <em>
                                Cette commande n'est pas modifiable par l'employé.
                            </em>
                        </p>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php else : ?>

            <p>Aucune commande enregistrée pour le moment.</p>

        <?php endif; ?>

    </section>

    <!-- Gestion des avis -->
    <section class="carte-menu">

        <h3>Avis clients à valider</h3>

        <?php if (!empty($avisEnAttente)) : ?>

            <?php foreach ($avisEnAttente as $avis) : ?>

                <article class="carte-menu">

                    <h4>
                        Avis sur la commande n° <?= (int) $avis['id_commande'] ?>
                    </h4>

                    <p>
                        <strong>Client :</strong>
                        <?= htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']) ?>
                    </p>

                    <p>
                        <strong>Note :</strong>
                        <?= (int) $avis['note'] ?>/5
                    </p>

                    <p>
                        <strong>Commentaire :</strong>
                        <?= nl2br(htmlspecialchars($avis['commentaire'])) ?>
                    </p>

                    <p>
                        <strong>Date :</strong>
                        <?= date('d/m/Y', strtotime($avis['date_avis'])) ?>
                    </p>

                    <!-- FORMULAIRE DE VALIDATION DES AVIS -->
                    <form action="espace-employe.php" method="POST">

                        <input
                            type="hidden"
                            name="action"
                            value="valider_avis"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                        >

                        <input
                            type="hidden"
                            name="id_avis"
                            value="<?= (int) $avis['id_avis'] ?>"
                        >

                        <button
                            type="submit"
                            name="decision"
                            value="Validé"
                            class="bouton"
                        >
                            Valider l'avis
                        </button>

                        <button
                            type="submit"
                            name="decision"
                            value="Refusé"
                            class="bouton"
                        >
                            Refuser l'avis
                        </button>

                    </form>
                    <!-- FIN DU FORMULAIRE DE VALIDATION DES AVIS -->

                </article>

            <?php endforeach; ?>

        <?php else : ?>

            <p>Aucun avis en attente de validation.</p>

        <?php endif; ?>

    </section>

    <a href="deconnexion.php" class="bouton-deconnexion">
        Se déconnecter
    </a>

</main>

</body>
</html>
