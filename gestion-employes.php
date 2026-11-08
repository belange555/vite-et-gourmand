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

// Sécuriser l'affichage HTML
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

$messageErreur = '';
$messageSucces = '';

// Activer ou désactiver un compte employé
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['changer_statut'])
) {
    $idEmploye = filter_input(
        INPUT_POST,
        'id_utilisateur',
        FILTER_VALIDATE_INT
    );

    $nouvelEtat = filter_input(
        INPUT_POST,
        'nouvel_etat',
        FILTER_VALIDATE_INT
    );

    if (!$idEmploye || !in_array($nouvelEtat, [0, 1], true)) {
        $messageErreur = "Demande de modification invalide.";
    } else {
        $requete = $pdo->prepare("
            UPDATE utilisateur
            SET actif = ?
            WHERE id_utilisateur = ?
            AND role = 'employe'
        ");

        $requete->execute([$nouvelEtat, $idEmploye]);

        $messageSucces = $nouvelEtat === 1
            ? "Le compte employé a été activé."
            : "Le compte employé a été désactivé.";
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['changer_statut'])
) {

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $gsm = trim($_POST['gsm'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (
        $nom === '' ||
        $prenom === '' ||
        $email === '' ||
        $motDePasse === '' ||
        $confirmation === ''
    ) {
        $messageErreur = "Merci de remplir tous les champs obligatoires.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messageErreur = "Veuillez saisir une adresse e-mail valide.";

    } elseif (strlen($motDePasse) < 10) {
        $messageErreur = "Le mot de passe doit contenir au moins 10 caractères.";

    } elseif (
        !preg_match('/[A-Z]/', $motDePasse) ||
        !preg_match('/[a-z]/', $motDePasse) ||
        !preg_match('/[0-9]/', $motDePasse) ||
        !preg_match('/[^a-zA-Z0-9]/', $motDePasse)
    ) {
        $messageErreur = "Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.";

    } elseif ($motDePasse !== $confirmation) {
        $messageErreur = "Les deux mots de passe ne correspondent pas.";
    }

    // Vérifier si l'adresse e-mail existe déjà
    if ($messageErreur === '') {

        $requete = $pdo->prepare("
            SELECT id_utilisateur
            FROM utilisateur
            WHERE email = ?
        ");

        $requete->execute([$email]);

        if ($requete->fetch()) {
            $messageErreur = "Cette adresse e-mail est déjà utilisée.";
        }
    }

    // Créer le compte employé
    if ($messageErreur === '') {

        $motDePasseHash = password_hash(
            $motDePasse,
            PASSWORD_DEFAULT
        );

        $requete = $pdo->prepare("
            INSERT INTO utilisateur
                (nom, prenom, gsm, email, mot_de_passe, role, actif)
            VALUES
                (?, ?, ?, ?, ?, 'employe', 1)
        ");

        $requete->execute([
            $nom,
            $prenom,
            $gsm,
            $email,
            $motDePasseHash
        ]);

        $messageSucces = "Le compte employé a été créé avec succès.";
    }
}

// Récupérer les comptes employés
$sqlEmployes = "
    SELECT
        id_utilisateur,
        nom,
        prenom,
        email,
        gsm,
        actif
    FROM utilisateur
    WHERE role = 'employe'
    ORDER BY nom, prenom
";

$stmtEmployes = $pdo->query($sqlEmployes);
$employes = $stmtEmployes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des employés - Vite & Gourmand</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body class="page-gestion-employes">

<header>
    <div class="container">
        <h1>Gestion des employés</h1>
        <p>Consulter et gérer les comptes employés</p>
    </div>
</header>

<main class="container">

    <section class="section">

     <h2>Créer un compte employé</h2>

        <h2>Liste des employés</h2>

<?php if ($messageErreur !== ''): ?>
    <p class="message erreur" role="alert">
        <?= e($messageErreur) ?>
    </p>
<?php endif; ?>

<?php if ($messageSucces !== ''): ?>
    <p class="message succes">
        <?= e($messageSucces) ?>
    </p>
<?php endif; ?>

<form method="POST" action="gestion-employes.php" class="formulaire">

    <div class="champ-formulaire">
        <label for="nom">Nom *</label>
        <input type="text" id="nom" name="nom" required>
    </div>

    <div class="champ-formulaire">
        <label for="prenom">Prénom *</label>
        <input type="text" id="prenom" name="prenom" required>
    </div>

    <div class="champ-formulaire">
        <label for="gsm">Téléphone</label>
        <input type="tel" id="gsm" name="gsm">
    </div>

    <div class="champ-formulaire">
        <label for="email">Adresse e-mail *</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div class="champ-formulaire">
        <label for="mot_de_passe">Mot de passe temporaire *</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe"
               autocomplete="new-password" required>
    </div>

    <div class="champ-formulaire">
        <label for="confirmation">Confirmer le mot de passe *</label>
        <input type="password" id="confirmation" name="confirmation"
               autocomplete="new-password" required>
    </div>

    <button type="submit" class="btn">
        Créer le compte employé
    </button>

</form>

        <?php if (empty($employes)): ?>

            <p>Aucun compte employé n'est enregistré.</p>

        <?php else: ?>

            <?php foreach ($employes as $employe): ?>

                <article class="card">

                    <h3>
                        <?= e($employe['prenom']) ?>
                        <?= e($employe['nom']) ?>
                    </h3>

                    <p>
                        <strong>Email :</strong>
                        <?= e($employe['email']) ?>
                    </p>

                    <p>
                        <strong>Téléphone :</strong>
                        <?= e($employe['gsm'] ?? 'Non renseigné') ?>
                    </p>

                    <p>
                        <strong>Statut du compte :</strong>
                        <?= (int) $employe['actif'] === 1
                            ? 'Actif'
                            : 'Désactivé' ?>
                    </p>

                </article>
<form method="POST" action="gestion-employes.php">

    <input
        type="hidden"
        name="id_utilisateur"
        value="<?= e($employe['id_utilisateur']) ?>"
    >

    <input
        type="hidden"
        name="nouvel_etat"
        value="<?= (int) $employe['actif'] === 1 ? 0 : 1 ?>"
    >

    <button type="submit" name="changer_statut" class="btn">
        <?= (int) $employe['actif'] === 1
            ? 'Désactiver le compte'
            : 'Activer le compte' ?>
    </button>

</form>
            <?php endforeach; ?>

        <?php endif; ?>

    </section>

    <a href="espace-administrateur.php" class="btn-retour-admin">
    Retour à l'espace administrateur
</a>

</main>

</body>
</html>