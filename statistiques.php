<?php
session_start();

require_once 'config/database.php';

// Vérifier que l'utilisateur connecté est administrateur
if (
    !isset($_SESSION['id_utilisateur']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'administrateur'
) {
    header('Location: connexion.php');
    exit;
}

// Fonction pour sécuriser l'affichage HTML
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

// Récupérer le dernier statut de chaque commande
$sqlDernierStatut = "
    SELECT h.id_commande, h.id_statut
    FROM historique_commande h
    WHERE h.id_historique = (
        SELECT h2.id_historique
        FROM historique_commande h2
        WHERE h2.id_commande = h.id_commande
        ORDER BY h2.date_modification DESC, h2.id_historique DESC
        LIMIT 1
    )
";

// Nombre total de commandes
$sqlTotalCommandes = "
    SELECT COUNT(*)
    FROM commande
";

$totalCommandes = (int) $pdo->query($sqlTotalCommandes)->fetchColumn();

// Nombre de commandes terminées
$sqlCommandesTerminees = "
    SELECT COUNT(*)
    FROM ($sqlDernierStatut) AS derniers_statuts
    WHERE id_statut = 6
";

$commandesTerminees = (int) $pdo->query($sqlCommandesTerminees)->fetchColumn();

// Chiffre d'affaires des commandes terminées
$sqlChiffreAffaires = "
    SELECT COALESCE(SUM(c.prix_total), 0)
    FROM commande c
    INNER JOIN ($sqlDernierStatut) AS derniers_statuts
        ON c.id_commande = derniers_statuts.id_commande
    WHERE derniers_statuts.id_statut = 6
";

$chiffreAffaires = (float) $pdo->query($sqlChiffreAffaires)->fetchColumn();

// Nombre de commandes par statut
$sqlStatuts = "
    SELECT
        s.libelle AS libelle_statut,
        COUNT(*) AS nombre
    FROM ($sqlDernierStatut) AS derniers_statuts
    INNER JOIN statut_commande s
        ON s.id_statut = derniers_statuts.id_statut
    GROUP BY s.id_statut, s.libelle
    ORDER BY s.id_statut
";

$statutsCommandes = $pdo->query($sqlStatuts)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Vite & Gourmand</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
</head>

<body class="page-statistiques">

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
            <h1>Statistiques</h1>
            <p>Tableau de bord de l'activité</p>
        </div>

    </div>
</header>

<main class="container">

   <section class="section">
    <h2>Vue d'ensemble</h2>

    <div class="cartes-statistiques">

        <article class="card">
            <h3 class="titre-statistique">Commandes totales</h3>
            <p class="valeur-statistique">
                <?= e($totalCommandes) ?>
            </p>
        </article>

        <article class="card">
            <h3 class="titre-statistique">Chiffre d'affaires</h3>
            <p class="valeur-statistique">
                <?= number_format($chiffreAffaires, 2, ',', ' ') ?> €
            </p>
        </article>

        <article class="card">
            <h3 class="titre-statistique">Commandes terminées</h3>
            <p class="valeur-statistique">
                <?= e($commandesTerminees) ?>
            </p>
        </article>

    </div>
</section>

<section class="section">
    <h2>Répartition des commandes par statut</h2>

    <div class="graphique-statistiques">
        <canvas id="graphiqueCommandes"></canvas>
    </div>
</section>

    <a href="espace-administrateur.php" class="btn-retour-admin">
    Retour à l'espace administrateur
</a>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const statuts = <?= json_encode(
        array_column($statutsCommandes, 'libelle_statut'),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    const nombres = <?= json_encode(
        array_column($statutsCommandes, 'nombre'),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    const graphique = document.getElementById('graphiqueCommandes');

    new Chart(graphique, {
        type: 'bar',

        data: {
            labels: statuts,
            datasets: [{
                label: 'Nombre de commandes',
                data: nombres,
                backgroundColor: '#2F5D50',
                borderRadius: 6
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },

            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>
</body>
</html>