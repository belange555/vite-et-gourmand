# Vite & Gourmand

**Site web de traiteur – Projet ECF**

## 1. Présentation du projet

Vite & Gourmand est un site web réalisé dans le cadre d'un projet d'évaluation en développement web.

Il représente une entreprise fictive de traiteur située à Bordeaux. Le site permet aux visiteurs de découvrir les menus, de créer un compte, de passer des commandes et de suivre leur évolution.

Ce projet m'a permis de mettre en pratique les compétences acquises en conception, développement, gestion de base de données et déploiement d'un site web.

## 2. Accès au site

**Site en ligne :**  
https://vite-et-gourmand-site-bd5823d90991.herokuapp.com/

**Dépôt GitHub :**  
https://github.com/belange555/vite-et-gourmand

## 3. Technologies utilisées

- HTML5 : structure des pages.
- CSS3 : mise en forme et adaptation aux différents écrans.
- JavaScript : interactions côté navigateur.
- PHP : traitement des données et fonctionnement du site.
- MariaDB / MySQL : gestion de la base de données.
- phpMyAdmin : administration de la base de données en local.
- XAMPP : environnement de développement local.
- Visual Studio Code : éditeur de code.
- Git et GitHub : gestion des versions et stockage du code source.
- Heroku : hébergement et déploiement du site.
- JawsDB : base de données distante utilisée en production.

## 4. Fonctionnalités du site

### Espace visiteur
- Consultation de la page d'accueil.
- Découverte des menus et de leurs détails.
- Recherche et filtrage des menus.
- Accès au formulaire de contact.
- Création d'un compte et connexion.

### Espace utilisateur
- Consultation du tableau de bord.
- Consultation des commandes.
- Suivi de l'état des commandes.
- Gestion des informations personnelles.
- Dépôt d'avis sur les commandes.
- Réinitialisation du mot de passe.

### Espace employé
- Gestion des commandes.
- Modification du statut des commandes.
- Gestion des menus et des plats.
- Gestion des avis clients.

### Espace administrateur
- Gestion des menus et des plats.
- Gestion des employés.
- Consultation des statistiques.

## 5. Installation en local

### Prérequis

- XAMPP avec PHP et MariaDB.
- Visual Studio Code.
- Un navigateur web.

### Étapes d'installation

1. Télécharger ou cloner le dépôt GitHub.
2. Placer le dossier du projet dans le répertoire `htdocs` de XAMPP.
3. Démarrer Apache et MySQL depuis le panneau de contrôle XAMPP.
4. Créer une base de données nommée `vite-et-gourmand` dans phpMyAdmin.
5. Importer le fichier SQL de création et de remplissage de la base de données.
6. Vérifier la configuration de connexion dans `config/database.php`.
7. Ouvrir le site dans le navigateur :

```text
http://localhost/Vite-et-Gourmand/
```

## 6. Configuration de la base de données

Le projet utilise deux configurations :

- **En local :** connexion à la base de données MariaDB de XAMPP.
- **En production :** connexion à la base de données distante JawsDB grâce à la variable d'environnement `JAWSDB_URL`.

Les identifiants de connexion ne doivent pas être publiés dans le dépôt GitHub.

## 7. Déploiement

Le site est hébergé sur Heroku.

Le code source est stocké sur GitHub. Le déploiement automatique est activé sur la branche `main` : chaque mise à jour envoyée sur cette branche déclenche un nouveau déploiement sur Heroku.

## 8. Sécurité

Le projet intègre plusieurs mesures de sécurité :

- Utilisation de requêtes préparées avec PDO.
- Protection de certaines actions avec des jetons CSRF.
- Gestion des accès selon les rôles des utilisateurs.
- Protection de l'affichage des données avec `htmlspecialchars()`.

## 9. Auteur et cadre du projet

Projet réalisé dans le cadre d'un ECF de formation en développement web.

**Vite & Gourmand – Projet pédagogique.**