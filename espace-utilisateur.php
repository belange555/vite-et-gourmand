
<?php

session_start();

// --------------------------------------------------
// SÉCURITÉ : CONTRÔLE D'ACCÈS
// --------------------------------------------------

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'utilisateur') {
    header('Location: connexion.php');
    exit;
}

// Générer un jeton CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$prenomUtilisateur = $_SESSION['prenom'] ?? '';
$rubrique = $_GET['rubrique'] ?? 'tableau-de-bord';

$commandes = [];
$erreurModification = '';
$messageSucces = '';

require_once 'config/database.php';

$idUtilisateur = (int) $_SESSION['id_utilisateur'];

// --------------------------------------------------
// RÉCUPÉRER LES INFORMATIONS DU CLIENT
// --------------------------------------------------

$requete = $pdo->prepare("
    SELECT nom, prenom, email, gsm, adresse
    FROM utilisateur
    WHERE id_utilisateur = ?
");

$requete->execute([$idUtilisateur]);

$utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

// --------------------------------------------------
// MODIFIER LES INFORMATIONS PERSONNELLES
// --------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['modifier_informations'])
) {

    // Vérification CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {

        $erreurModification = "La vérification de sécurité a échoué. Veuillez réessayer.";

    } else {

        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gsm = trim($_POST['gsm'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');

        // Vérifier les informations obligatoires
        if (
            $nom === '' ||
            $prenom === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            $erreurModification = "Veuillez renseigner un nom, un prénom et une adresse e-mail valide.";

        } else {

            // Vérifier si l'adresse e-mail appartient à un autre compte
            $verificationEmail = $pdo->prepare("
                SELECT id_utilisateur
                FROM utilisateur
                WHERE email = ?
                AND id_utilisateur != ?
            ");

            $verificationEmail->execute([
                $email,
                $idUtilisateur
            ]);

            if ($verificationEmail->fetch()) {

                $erreurModification = "Cette adresse e-mail est déjà utilisée par un autre compte.";

            } else {

                // Enregistrer les modifications
                $requeteModification = $pdo->prepare("
                    UPDATE utilisateur
                    SET nom = ?, prenom = ?, email = ?, gsm = ?, adresse = ?
                    WHERE id_utilisateur = ?
                ");

                $requeteModification->execute([
                    $nom,
                    $prenom,
                    $email,
                    $gsm,
                    $adresse,
                    $idUtilisateur
                ]);

                // Actualiser le prénom dans la session
                $_SESSION['prenom'] = $prenom;

                // Redirection après modification
                header('Location: espace-utilisateur.php?rubrique=informations&modification=ok');
                exit;
            }
        }
    }
}

// --------------------------------------------------
// RÉCUPÉRER LES COMMANDES DU CLIENT
// --------------------------------------------------

$requeteTableauBord = $pdo->prepare("
    SELECT
        c.id_commande,
        c.date_commande,
        c.date_prestation,
        c.heure_prestation,
        c.lieu_livraison,
        c.adresse_livraison,
        c.prix_total,
        s.libelle AS statut

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

    LEFT JOIN statut_commande s
        ON s.id_statut = h.id_statut

    WHERE c.id_utilisateur = ?

    ORDER BY c.date_commande DESC, c.id_commande DESC
");

$requeteTableauBord->execute([$idUtilisateur]);

$toutesLesCommandes = $requeteTableauBord->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// CALCUL DES STATISTIQUES
// --------------------------------------------------

$nombreCommandesPassees = count($toutesLesCommandes);
$nombreCommandesEnCours = 0;
$nombreCommandesLivrees = 0;

foreach ($toutesLesCommandes as $commande) {

    if (in_array($commande['statut'], [
        'Acceptée',
        'En préparation',
        'En cours de livraison'
    ], true)) {

        $nombreCommandesEnCours++;
    }

    if (in_array($commande['statut'], [
        'Livrée',
        'Terminée'
    ], true)) {

        $nombreCommandesLivrees++;
    }
}

// --------------------------------------------------
// LES DEUX DERNIÈRES COMMANDES
// --------------------------------------------------

$dernieresCommandes = [];

if ($rubrique === 'tableau-de-bord') {

    $requeteDernieres = $pdo->prepare("
        SELECT
            c.id_commande,
            c.date_prestation,
            c.prix_total,
            s.libelle AS statut

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

        LEFT JOIN statut_commande s
            ON s.id_statut = h.id_statut

        WHERE c.id_utilisateur = ?

        ORDER BY c.date_commande DESC, c.id_commande DESC

        LIMIT 2
    ");

    $requeteDernieres->execute([$idUtilisateur]);

    $dernieresCommandes = $requeteDernieres->fetchAll(PDO::FETCH_ASSOC);
}

// --------------------------------------------------
// RÉCUPÉRER LES COMMANDES POUR LA RUBRIQUE COMMANDES
// --------------------------------------------------

if ($rubrique === 'commandes') {

    $requeteCommandes = $pdo->prepare("
        SELECT
            c.id_commande,
            c.date_commande,
            c.date_prestation,
            c.heure_prestation,
            c.lieu_livraison,
            c.adresse_livraison,
            c.prix_total,
            s.libelle AS statut

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

        LEFT JOIN statut_commande s
            ON s.id_statut = h.id_statut

        WHERE c.id_utilisateur = ?

        ORDER BY c.date_commande DESC
    ");

    $requeteCommandes->execute([$idUtilisateur]);

    $commandes = $requeteCommandes->fetchAll(PDO::FETCH_ASSOC);
}

// Actualiser le prénom affiché
$prenomUtilisateur = $_SESSION['prenom'] ?? '';

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mon espace utilisateur - Vite &amp; Gourmand</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<header class="site-header">

    <div class="entete-utilisateur">

        <h1>Vite &amp; Gourmand</h1>

        <div class="profil-utilisateur">
            Bienvenue dans votre espace,
            <?= htmlspecialchars($prenomUtilisateur) ?> !
        </div>

    </div>

</header>

<main class="contenu-principal">

    <h2>Espace utilisateur</h2>

    <p>
        Bienvenue dans votre espace personnel,
        <?= htmlspecialchars($prenomUtilisateur) ?> !
    </p>

    <div class="espace-utilisateur">

        <!-- NAVIGATION -->
        <nav class="menu-utilisateur">

            <a
                href="espace-utilisateur.php"
                class="lien-utilisateur <?= $rubrique === 'tableau-de-bord' ? 'actif' : '' ?>"
            >
                Mon tableau de bord
            </a>

            <a
                href="espace-utilisateur.php?rubrique=commandes"
                class="lien-utilisateur <?= $rubrique === 'commandes' ? 'actif' : '' ?>"
            >
                Mes commandes
            </a>

            <a href="contact.php" class="lien-utilisateur">
                Contact
            </a>

            <a
                href="espace-utilisateur.php?rubrique=informations"
                class="lien-utilisateur <?= $rubrique === 'informations' ? 'actif' : '' ?>"
            >
                Mes informations
            </a>

            <a href="avis.php" class="lien-utilisateur">
                Mes avis
            </a>

            <a href="deconnexion.php" class="lien-utilisateur">
                Se déconnecter
            </a>

        </nav>

        <section class="contenu-utilisateur">

            <!-- TABLEAU DE BORD -->
            <?php if ($rubrique === 'tableau-de-bord') : ?>

                <h3>Mon tableau de bord</h3>

                <p>
                    Retrouvez ici les informations de votre espace personnel.
                </p>

                <div class="statistiques-utilisateur">

                    <div class="carte-statistique">
                        <h4>Commandes passées</h4>
                        <p><?= $nombreCommandesPassees ?></p>
                    </div>

                    <div class="carte-statistique">
                        <h4>Commandes en cours</h4>
                        <p><?= $nombreCommandesEnCours ?></p>
                    </div>

                    <div class="carte-statistique">
                        <h4>Commandes livrées</h4>
                        <p><?= $nombreCommandesLivrees ?></p>
                    </div>

                </div>

                <h4>Mes dernières commandes</h4>

                <?php if (!empty($dernieresCommandes)) : ?>

                    <div class="tableau-commandes">

                        <table>

                            <thead>
                                <tr>
                                    <th>N° commande</th>
                                    <th>Date de prestation</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Suivi</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($dernieresCommandes as $commande) : ?>

                                    <tr>

                                        <td>
                                            #<?= (int) $commande['id_commande'] ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($commande['date_prestation']) ?>
                                        </td>

                                        <td>
                                            <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($commande['statut'] ?? 'Non défini') ?>
                                        </td>

                                        <td>

                                            <?php if (in_array($commande['statut'], [
                                                'Acceptée',
                                                'En préparation',
                                                'En cours de livraison',
                                                'Livrée',
                                                'En attente du retour de matériel',
                                                'Terminée'
                                            ], true)) : ?>

                                                <a
                                                    href="suivi-commande.php?id=<?= (int) $commande['id_commande'] ?>"
                                                    class="bouton-suivi"
                                                >
                                                    Voir le suivi
                                                </a>

                                            <?php else : ?>

                                                —

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else : ?>

                    <p>Vous n'avez pas encore passé de commande.</p>

                <?php endif; ?>

            <!-- MES INFORMATIONS -->
            <?php elseif ($rubrique === 'informations') : ?>

                <h3>Mes informations</h3>

                <?php if (
                    isset($_GET['modification']) &&
                    $_GET['modification'] === 'ok'
                ) : ?>

                    <p class="message-succes">
                        Vos informations ont été modifiées avec succès.
                    </p>

                <?php endif; ?>

                <?php if ($erreurModification !== '') : ?>

                    <p class="message-erreur">
                        <?= htmlspecialchars($erreurModification) ?>
                    </p>

                <?php endif; ?>

                <?php if ($utilisateur) : ?>

                    <form
                        method="POST"
                        action="espace-utilisateur.php?rubrique=informations"
                    >

                        <input
                            type="hidden"
                            name="modifier_informations"
                            value="1"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                        >

                        <div class="champ-formulaire">

                            <label for="nom">Nom</label>

                            <input
                                type="text"
                                id="nom"
                                name="nom"
                                value="<?= htmlspecialchars($utilisateur['nom']) ?>"
                                required
                            >

                        </div>

                        <div class="champ-formulaire">

                            <label for="prenom">Prénom</label>

                            <input
                                type="text"
                                id="prenom"
                                name="prenom"
                                value="<?= htmlspecialchars($utilisateur['prenom']) ?>"
                                required
                            >

                        </div>

                        <div class="champ-formulaire">

                            <label for="email">Adresse e-mail</label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($utilisateur['email']) ?>"
                                required
                            >

                        </div>

                        <div class="champ-formulaire">

                            <label for="gsm">Téléphone</label>

                            <input
                                type="tel"
                                id="gsm"
                                name="gsm"
                                value="<?= htmlspecialchars($utilisateur['gsm'] ?? '') ?>"
                            >

                        </div>

                        <div class="champ-formulaire">

                            <label for="adresse">Adresse postale</label>

                            <input
                                type="text"
                                id="adresse"
                                name="adresse"
                                value="<?= htmlspecialchars($utilisateur['adresse'] ?? '') ?>"
                            >

                        </div>

                        <button type="submit" class="bouton">
                            Enregistrer les modifications
                        </button>

                    </form>

                <?php else : ?>

                    <p>Impossible de récupérer les informations du compte.</p>

                <?php endif; ?>

            <!-- MES COMMANDES -->
            <?php elseif ($rubrique === 'commandes') : ?>

                <h3>Mes commandes</h3>

                <?php if (!empty($commandes)) : ?>

                    <?php foreach ($commandes as $commande) : ?>

                        <div class="carte-menu carte-commande">

                            <h4>
                                Commande n° <?= (int) $commande['id_commande'] ?>
                            </h4>

                            <p>
                                <strong>Date de prestation :</strong>
                                <?= date('d/m/Y', strtotime($commande['date_prestation'])) ?>
                            </p>

                            <p>
                                <strong>Heure :</strong>
                                <?= htmlspecialchars($commande['heure_prestation']) ?>
                            </p>

                            <p>
                                <strong>Adresse :</strong>
                                <?= htmlspecialchars($commande['adresse_livraison'] ?? '') ?>
                            </p>

                            <p>
                                <strong>Montant :</strong>
                                <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €
                            </p>

                            <p>
                                <strong>Statut :</strong>
                                <?= htmlspecialchars($commande['statut'] ?? 'En attente de statut') ?>
                            </p>

                            <p>

                                <a
                                    href="suivi-commande.php?id=<?= (int) $commande['id_commande'] ?>"
                                    class="bouton-suivi"
                                >
                                    Voir le suivi
                                </a>

                            </p>

                        </div>

                    <?php endforeach; ?>

                <?php else : ?>

                    <p>Vous n'avez pas encore de commande.</p>

                <?php endif; ?>

            <?php else : ?>

                <h3>Rubrique introuvable</h3>

                <p>La rubrique demandée n'existe pas.</p>

            <?php endif; ?>

        </section>

    </div>

</main>

</body>
</html>
