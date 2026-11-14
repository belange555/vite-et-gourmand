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

// Échapper les données affichées dans le HTML
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

$message = '';
$typeMessage = '';

// Jours de la semaine
$jours = [
    1 => 'Lundi',
    2 => 'Mardi',
    3 => 'Mercredi',
    4 => 'Jeudi',
    5 => 'Vendredi',
    6 => 'Samedi',
    7 => 'Dimanche'
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        foreach ($jours as $numero => $jour) {
            $ouvert = isset($_POST['ouvert'][$numero]) ? 1 : 0;

            $heureOuverture = trim($_POST['heure_ouverture'][$numero] ?? '');
            $heureFermeture = trim($_POST['heure_fermeture'][$numero] ?? '');

            if ($ouvert === 1) {
                if ($heureOuverture === '' || $heureFermeture === '') {
                    throw new Exception(
                        "Merci de renseigner les horaires pour le jour : " . $jour
                    );
                }

                if ($heureOuverture >= $heureFermeture) {
                    throw new Exception(
                        "L'heure de fermeture doit être après l'heure d'ouverture pour : " . $jour
                    );
                }
            } else {
                $heureOuverture = null;
                $heureFermeture = null;
            }

            // Vérifier si un horaire existe déjà pour ce jour
            $sqlExiste = "
                SELECT id_horaire
                FROM horaire
                WHERE jour = :jour
            ";

            $stmtExiste = $pdo->prepare($sqlExiste);
            $stmtExiste->execute(['jour' => $numero]);
            $idHoraire = $stmtExiste->fetchColumn();

            if ($idHoraire) {
                // Modifier l'horaire existant
                $sql = "
                    UPDATE horaire
                    SET heure_ouverture = :heure_ouverture,
                        heure_fermeture = :heure_fermeture
                    WHERE id_horaire = :id_horaire
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'heure_ouverture' => $heureOuverture,
                    'heure_fermeture' => $heureFermeture,
                    'id_horaire' => $idHoraire
                ]);
            } else {
               // Ajouter un nouvel horaire
$sql = "
    INSERT INTO horaire
    (jour, heure_ouverture, heure_fermeture)
    VALUES
    (:jour, :heure_ouverture, :heure_fermeture)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'jour' => $numero,
    'heure_ouverture' => $heureOuverture,
    'heure_fermeture' => $heureFermeture,
]);
            }
        }

        $pdo->commit();

        $message = "Les horaires ont été enregistrés.";
        $typeMessage = 'succes';

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $e->getMessage();
        $typeMessage = 'erreur';
    }
}

// Récupérer les horaires enregistrés
$sqlHoraires = "
    SELECT jour, heure_ouverture, heure_fermeture
    FROM horaire
    ORDER BY jour
";

$stmtHoraires = $pdo->query($sqlHoraires);
$horairesEnregistres = $stmtHoraires->fetchAll(PDO::FETCH_ASSOC);

// Organiser les horaires par jour
$horaires = [];

foreach ($horairesEnregistres as $horaire) {
    $horaires[(int) $horaire['jour']] = $horaire;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des horaires - Vite & Gourmand</title>

<link rel="stylesheet" href="css/style.css">

</head>

<body class="page-gestion-horaires">

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
            <h1>Gestion des horaires</h1>
            <p>Configurer les horaires d'ouverture de Vite & Gourmand</p>
        </div>
    </div>
</header>

<main class="container">

    <section class="section">

        <h2>Horaires d'ouverture</h2>

        <?php if ($message !== ''): ?>
            <div class="message <?= e($typeMessage) ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-gestion-horaires">

            <?php foreach ($jours as $numero => $jour): ?>

                <?php
                $horaire = $horaires[$numero] ?? [];

                $ouvert = !empty($horaire['heure_ouverture'])
                  && !empty($horaire['heure_fermeture'])
                  ? 1
                  : 0;

                $heureOuverture = $horaire['heure_ouverture'] ?? '';
                $heureFermeture = $horaire['heure_fermeture'] ?? '';
                ?>

                <div class="ligne-horaire">

                    <h3><?= e($jour) ?></h3>

                    <label>
                        <input
                            type="checkbox"
                            name="ouvert[<?= $numero ?>]"
                            value="1"
                            <?= $ouvert === 1 ? 'checked' : '' ?>
                        >
                        Ouvert
                    </label>

                    <div class="champs-horaires">

                        <div>
                            <label for="ouverture-<?= $numero ?>">
                                Heure d'ouverture
                            </label>

                            <input
                                type="time"
                                id="ouverture-<?= $numero ?>"
                                name="heure_ouverture[<?= $numero ?>]"
                                value="<?= e($heureOuverture) ?>"
                            >
                        </div>

                        <div>
                            <label for="fermeture-<?= $numero ?>">
                                Heure de fermeture
                            </label>

                            <input
                                type="time"
                                id="fermeture-<?= $numero ?>"
                                name="heure_fermeture[<?= $numero ?>]"
                                value="<?= e($heureFermeture) ?>"
                            >
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

            <button type="submit" class="btn">
                Enregistrer les horaires
            </button>

        </form>

    </section>
    
<a href="espace-administrateur.php" class="btn-retour-admin">
    Retour à l'espace administrateur
</a>

</main>

</body>
</html>