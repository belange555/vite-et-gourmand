<?php
session_start();
require_once 'config/database.php';

$prenomUtilisateur = $_SESSION['prenom'] ?? null;

$requete = $pdo->query("
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
");

$menus = $requete->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les horaires d'ouverture
$requeteHoraires = $pdo->query("
    SELECT jour, heure_ouverture, heure_fermeture
    FROM horaire
    ORDER BY jour
");

$horaires = $requeteHoraires->fetchAll(PDO::FETCH_ASSOC);

$nomsJours = [
    1 => 'Lundi',
    2 => 'Mardi',
    3 => 'Mercredi',
    4 => 'Jeudi',
    5 => 'Vendredi',
    6 => 'Samedi',
    7 => 'Dimanche'
];

// Récupérer les avis clients validés
$requeteAvis = $pdo->query("
    SELECT
        utilisateur.prenom,
        avis.note,
        avis.commentaire,
        avis.date_avis
    FROM avis
    INNER JOIN commande
        ON avis.id_commande = commande.id_commande
    INNER JOIN utilisateur
        ON commande.id_utilisateur = utilisateur.id_utilisateur
    WHERE avis.statut_validation = 'Validé'
    ORDER BY avis.date_avis DESC
");

$avisClients = $requeteAvis->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="google-site-verification" content="SgXJpX9Qx-sVOaARZ5_YxnUnEf2iHgNrUbvt_Ty6i4Y" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
</head>

<body>

    <header class="site-header">
        <div class="entete-accueil">
           <h1>
    <img src="images/logo-vite-et-gourmand.png"
         alt="Vite & Gourmand - Traiteur">
</h1>

<button class="menu-hamburger" id="menuHamburger"
        aria-label="Ouvrir le menu"
        aria-expanded="false"
        aria-controls="navigationPrincipale">
    ☰
</button>

            <nav class="navigation-principale" id="navigationPrincipale">
                <a href="index.php">Accueil</a>
                <a href="nos-menus.php">Nos menus</a>
                <a href="contact.php">Contact</a>

                <?php if ($prenomUtilisateur !== null) : ?>

    <?php if (($_SESSION['role'] ?? '') === 'utilisateur') : ?>
        <a href="espace-utilisateur.php">Mon espace</a>

    <?php elseif (($_SESSION['role'] ?? '') === 'employe') : ?>
        <a href="espace-employe.php">Espace employé</a>

    <?php elseif (($_SESSION['role'] ?? '') === 'administrateur') : ?>
        <a href="espace-administrateur.php">Espace administrateur</a>
    <?php endif; ?>

    <a href="deconnexion.php">Se déconnecter</a>

<?php else : ?>

    <a class="bouton" href="connexion.php">Connexion</a>

<?php endif; ?>

            </nav>
        </div>
    </header>

    <main class="contenu-principal">

        <section class="section-accueil">
            <div class="texte-accueil">
                <h2>Une cuisine gourmande pour vos événements</h2>

                <p>
                    Depuis plus de 25 ans, notre expérience et notre savoir-faire donnent vie à des menus savoureux, préparés avec soin pour accompagner vos moments en famille, entre amis ou entre collègues.
                </p>

               <a class="bouton" href="nos-menus.php">
                    Découvrir nos menus
              </a>
            </div>

            <div class="image-accueil">
                <img
                    src="images/image-accueil.jpg"
                    alt="Buffet gourmand préparé pour un événement"
                >
            </div>
        </section>

        <section id="nos-menus" class="section-nos-menus">
            <h2>Nos menus</h2>

            <div class="grille-menus">

                <?php foreach ($menus as $menu) : ?>

                    <article class="carte-menu">

                        <img
                            src="<?= htmlspecialchars($menu['image']) ?>"
                            alt="<?= htmlspecialchars($menu['titre']) ?>"
                            class="image-menu"
                        >

                        <div class="contenu-carte-menu">
                            <h3><?= htmlspecialchars($menu['titre']) ?></h3>

                            <p class="description-menu">
                                <?= htmlspecialchars($menu['description']) ?>
                            </p>

                            <p class="informations-menu">
                                Dès <?= $menu['nombre_personnes_minimum'] ?> personnes
                                <br>
                                <strong>
                                    <?= number_format($menu['prix'], 2, ',', ' ') ?>
                                    € / personne
                                </strong>
                            </p>

                            <a
                                class="bouton bouton-details"
                                href="menu.php?id=<?= $menu['id_menu'] ?>"
                            >
                                Détails
                            </a>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>
        </section>

    </main>

    <section class="section-avis">
    <h2>Avis de nos clients</h2>

    <?php if (!empty($avisClients)) : ?>

        <div class="grille-avis">

            <?php foreach ($avisClients as $avis) : ?>

                <article class="carte-avis">

                    <h3><?= htmlspecialchars($avis['prenom']) ?></h3>

                    <p class="note-avis">
                        <?= str_repeat('★', (int) $avis['note']) ?>
                    </p>

                    <p>
                        <?= htmlspecialchars($avis['commentaire']) ?>
                    </p>

                    <small>
                        <?= date('d/m/Y', strtotime($avis['date_avis'])) ?>
                    </small>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else : ?>

        <p>Aucun avis client pour le moment.</p>

    <?php endif; ?>
</section>
    
<footer class="pied-de-page">
    <div class="container">
        <h2>Nos horaires d'ouverture</h2>

        <ul class="liste-horaires">
            <?php foreach ($horaires as $horaire) : ?>
                <li>
                    <strong>
                        <?= htmlspecialchars($nomsJours[(int) $horaire['jour']]) ?>
                    </strong>

                    <?php if (
                        !empty($horaire['heure_ouverture']) &&
                        !empty($horaire['heure_fermeture'])
                    ) : ?>
                        :
                        <?= substr($horaire['heure_ouverture'], 0, 5) ?>
                        -
                        <?= substr($horaire['heure_fermeture'], 0, 5) ?>
                    <?php else : ?>
                        : Fermé
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="informations-footer">
            <p>
                <span aria-hidden="true">📍</span>
                12 rue des gourmets, 33000 Bordeaux
            </p>

            <p>
                Entreprise fictive – Projet pédagogique de fin d'études.
            </p>
        </div>

        <div class="liens-legaux">
            <a href="mentions-legales.php">Mentions légales</a>
            <a href="cgv.php">Conditions générales de vente</a>
        </div>
    </div>
</footer>
<script>
    const boutonMenu = document.querySelector('#menuHamburger');
    const navigation = document.querySelector('#navigationPrincipale');

    boutonMenu.addEventListener('click', function () {

        navigation.classList.toggle('ouvert');

        const menuOuvert = navigation.classList.contains('ouvert');

        boutonMenu.setAttribute('aria-expanded', menuOuvert);

        boutonMenu.setAttribute(
            'aria-label',
            menuOuvert ? 'Fermer le menu' : 'Ouvrir le menu'
        );
    });
</script>
</body>
</html>