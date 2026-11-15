-- =====================================================
-- VITE & GOURMAND
-- Script de création et de remplissage de la base
-- Projet ECF - Développement web
-- SGBD : MariaDB / MySQL
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des tables si elles existent déjà
DROP TABLE IF EXISTS plat_allergene;
DROP TABLE IF EXISTS menu_plat;
DROP TABLE IF EXISTS historique_commande;
DROP TABLE IF EXISTS ligne_commande;
DROP TABLE IF EXISTS avis;
DROP TABLE IF EXISTS message_contact;
DROP TABLE IF EXISTS commande;
DROP TABLE IF EXISTS plat;
DROP TABLE IF EXISTS menu;
DROP TABLE IF EXISTS allergene;
DROP TABLE IF EXISTS regime;
DROP TABLE IF EXISTS theme;
DROP TABLE IF EXISTS statut_commande;
DROP TABLE IF EXISTS horaire;
DROP TABLE IF EXISTS utilisateur;

-- =====================================================
-- 1. TABLE UTILISATEUR
-- =====================================================

CREATE TABLE utilisateur (
    id_utilisateur INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    gsm VARCHAR(20) DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    token_reinitialisation VARCHAR(255) DEFAULT NULL,
    expiration_reinitialisation DATETIME DEFAULT NULL,
    PRIMARY KEY (id_utilisateur),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. TABLE THEME
-- =====================================================

CREATE TABLE theme (
    id_theme INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_theme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. TABLE REGIME
-- =====================================================

CREATE TABLE regime (
    id_regime INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_regime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. TABLE ALLERGENE
-- =====================================================

CREATE TABLE allergene (
    id_allergene INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_allergene)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. TABLE STATUT_COMMANDE
-- =====================================================

CREATE TABLE statut_commande (
    id_statut INT NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. TABLE HORAIRE
-- =====================================================

CREATE TABLE horaire (
    id_horaire INT NOT NULL AUTO_INCREMENT,
    jour VARCHAR(20) NOT NULL,
    heure_ouverture TIME DEFAULT NULL,
    heure_fermeture TIME DEFAULT NULL,
    PRIMARY KEY (id_horaire)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- 7. TABLE MENU
-- =====================================================

CREATE TABLE menu (
    id_menu INT NOT NULL AUTO_INCREMENT,
    id_theme INT NOT NULL,
    id_regime INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    nombre_personnes_minimum INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    conditions TEXT DEFAULT NULL,
    stock_disponible INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id_menu),
    KEY fk_menu_theme (id_theme),
    KEY fk_menu_regime (id_regime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. TABLE PLAT
-- =====================================================

CREATE TABLE plat (
    id_plat INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    PRIMARY KEY (id_plat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. TABLE COMMANDE
-- =====================================================

CREATE TABLE commande (
    id_commande INT NOT NULL AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    date_commande DATETIME NOT NULL,
    date_prestation DATE NOT NULL,
    heure_prestation TIME NOT NULL,
    lieu_livraison VARCHAR(150) NOT NULL,
    adresse_livraison VARCHAR(255) NOT NULL,
    prix_livraison DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reduction DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    prix_total DECIMAL(10,2) NOT NULL,
    materiel_prete TINYINT(1) NOT NULL DEFAULT 0,
    date_retour_materiel DATETIME DEFAULT NULL,
    motif_annulation TEXT DEFAULT NULL,
    mode_contact VARCHAR(50) DEFAULT NULL,
    instructions TEXT DEFAULT NULL,
    PRIMARY KEY (id_commande),
    KEY fk_commande_utilisateur (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 10. TABLE LIGNE_COMMANDE
-- =====================================================

CREATE TABLE ligne_commande (
    id_ligne_commande INT NOT NULL AUTO_INCREMENT,
    id_commande INT NOT NULL,
    id_menu INT NOT NULL,
    nombre_personnes INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    sous_total DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_ligne_commande),
    KEY fk_ligne_commande_commande (id_commande),
    KEY fk_ligne_commande_menu (id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 11. TABLE HISTORIQUE_COMMANDE
-- =====================================================

CREATE TABLE historique_commande (
    id_historique INT NOT NULL AUTO_INCREMENT,
    id_commande INT NOT NULL,
    id_statut INT NOT NULL,
    date_modification DATETIME NOT NULL,
    PRIMARY KEY (id_historique),
    KEY fk_historique_commande (id_commande),
    KEY fk_historique_statut (id_statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 12. TABLE AVIS
-- =====================================================

CREATE TABLE avis (
    id_avis INT NOT NULL AUTO_INCREMENT,
    id_commande INT NOT NULL,
    note TINYINT NOT NULL,
    commentaire TEXT NOT NULL,
    statut_validation VARCHAR(20) NOT NULL,
    date_avis DATETIME NOT NULL,
    PRIMARY KEY (id_avis),
    KEY fk_avis_commande (id_commande)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 13. TABLE MESSAGE_CONTACT
-- =====================================================

CREATE TABLE message_contact (
    id_message INT NOT NULL AUTO_INCREMENT,
    objet VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 14. TABLE MENU_PLAT
-- =====================================================

CREATE TABLE menu_plat (
    id_menu INT NOT NULL,
    id_plat INT NOT NULL,
    type_dans_menu VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_menu, id_plat),
    KEY fk_menu_plat_plat (id_plat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 15. TABLE PLAT_ALLERGENE
-- =====================================================

CREATE TABLE plat_allergene (
    id_plat INT NOT NULL,
    id_allergene INT NOT NULL,
    PRIMARY KEY (id_plat, id_allergene),
    KEY fk_plat_allergene_allergene (id_allergene)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 16. DONNEES DE TEST
-- =====================================================

INSERT INTO utilisateur VALUES
(1, 'Dupont', 'Helène', 'marie.test@example.com', '0612345678', '10 rue des fleurs, 30000 Bordeaux', '$2y$10$nSoqBHs7jjm7E7nuUo9yM.vjIrUjX/mRl0S3hbAISCzEmJYnM6aiy', 'utilisateur', 1, NULL, NULL),
(2, 'Dupont', 'Arthur', 'Dupont.test@example.com', '0600000000', '6 chemin des fleurs, 44000 Nantes', '$2y$10$Sgle23MVr5hHkRlV.l2srukDWrGrD3IlWp1ywgOacAWtFOxd8ZV6a', 'employe', 1, NULL, NULL),
(3, 'Admin', 'paul', 'Admin.test@example.com', '0600000001', '6 chemin vert, 44000 Nantes', '$2y$10$ORGf5nJtTAYbcqJzz6AEq.w5hEiri76R32n5nwqfg.PTa67nfIUlO', 'administrateur', 1, NULL, NULL),
(4, 'Martin', 'Sophie', 'sophie.martin@test.fr', '0600000001', NULL, '$2y$10$gTOT/tLkwErG1IRkk2RrMunveqo0DkMNz7I6PXedt4BYxNQldX8vO', 'employe', 1, NULL, NULL),
(5, 'Bernard', 'Camille', 'camille.bernard@test.fr', '0612345678', '6 chemin vert, 33000 Bordeaux', '$2y$10$4xOplKuM040bj4V0oq62l.FMqD2jUrHHjeIb61bDQ0N.ho9PGiEkm', 'utilisateur', 1, NULL, NULL);

INSERT INTO theme VALUES
(1, 'Méditerranéen'),
(2, 'Italien'),
(3, 'Végétarien');

INSERT INTO regime VALUES
(1, 'Classique'),
(2, 'Végétarien'),
(3, 'Sans gluten');

INSERT INTO allergene VALUES
(1, 'Gluten'),
(2, 'Lait'),
(3, 'Œufs');

INSERT INTO statut_commande VALUES
(1, 'Acceptée'),
(2, 'En préparation'),
(3, 'En cours de livraison'),
(4, 'Livrée'),
(5, 'En attente du retour de matériel'),
(6, 'Terminée'),
(7, 'En attente de validation'),
(8, 'Refusée');

INSERT INTO horaire VALUES
(1, '1', '12:00:00', '23:00:00'),
(2, '2', '12:00:00', '23:00:00'),
(3, '3', NULL, NULL),
(4, '4', NULL, NULL),
(5, '5', NULL, NULL),
(6, '6', NULL, NULL),
(7, '7', NULL, NULL);

INSERT INTO menu VALUES
(1, 1, 1, 'Menu Méditerranéen', 'Une formule aux saveurs méditerranéennes composée de plusieurs plats.', 4, 25.00, 'Commande minimum de 4 personnes.', 20, 'images/menu-mediterraneen.jpg'),
(3, 2, 1, 'Menu Italien', 'Les incontournables de la cuisine italienne à votre table.', 6, 28.00, 'Commande minimum de 6 personnes.', 20, 'images/menu-italien.jpg'),
(4, 3, 2, 'Menu Végétarien', 'Des légumes de saison et des saveurs gourmandes.', 4, 23.00, 'Commande minimum de 4 personnes.', 20, 'images/menu-vegetarien.jpg');

INSERT INTO plat VALUES
(1, 'Salade grecque', 'Salade composée de tomates, concombre, feta et olives.'),
(2, 'Poulet rôti aux légumes', 'Poulet rôti accompagné de légumes de saison.'),
(3, 'Tarte aux pommes', 'Tarte traditionnelle aux pommes.'),
(4, 'Bruschetta tomate et basilic', 'Pain grillé garni de tomates fraîches, basilic et huile d’olive.'),
(5, 'Lasagnes à la bolognaise', 'Lasagnes traditionnelles à la sauce bolognaise.'),
(6, 'Tiramisu', 'Dessert italien au mascarpone, café et cacao.'),
(7, 'Salade de légumes de saison', 'Salade composée de légumes frais de saison.'),
(8, 'Risotto aux légumes', 'Risotto crémeux accompagné de légumes de saison.'),
(10, 'Osso buco', 'Osso buco de veau à la milanaise (mijoté au vin blanc, bouillon, tomates et garniture aromatique), saupoudré au moment de servir de gremolata'),
(11, 'Mille-feuille', 'Mille-feuille traditionnel avec pâte feuilletée, crème pâtissière, et glaçage marbré au fondant et chocolat');

INSERT INTO commande VALUES
(10, 5, '2026-11-07 20:51:56', '2026-11-08', '20:00:00', 'Domicile', '6 chemin vert, 33000 Bordeaux', 5.00, 0.00, 97.00, 0, NULL, NULL, NULL, 'RAS'),
(11, 5, '2026-11-07 23:33:01', '2026-11-21', '19:00:00', 'Retrait sur place', 'Retrait sur place', 0.00, 0.00, 100.00, 0, NULL, NULL, NULL, NULL),
(12, 5, '2026-11-08 13:42:30', '2026-11-08', '20:00:00', 'Domicile', '6 chemin vert, 33000 Bordeaux', 5.00, 0.00, 173.00, 0, NULL, NULL, NULL, 'RAS');

INSERT INTO ligne_commande VALUES
(10, 10, 4, 4, 23.00, 92.00),
(11, 11, 1, 4, 25.00, 100.00),
(12, 12, 3, 6, 28.00, 168.00);

INSERT INTO historique_commande VALUES
(28, 10, 7, '2026-11-07 20:51:56'),
(29, 10, 1, '2026-11-07 21:24:47'),
(30, 10, 2, '2026-11-07 21:28:13'),
(31, 10, 3, '2026-11-07 21:35:40'),
(32, 10, 4, '2026-11-07 21:38:10'),
(33, 10, 6, '2026-11-07 22:17:38'),
(34, 11, 7, '2026-11-07 23:33:01'),
(35, 11, 1, '2026-11-08 12:44:59'),
(36, 11, 2, '2026-11-08 13:16:50'),
(37, 12, 7, '2026-11-08 13:42:30'),
(38, 12, 1, '2026-11-08 13:43:03'),
(39, 11, 3, '2026-11-08 13:46:14'),
(40, 11, 4, '2026-11-08 13:54:33'),
(41, 11, 6, '2026-11-08 13:54:43');

INSERT INTO avis VALUES
(3, 10, 5, 'menu exelent, produit frais ! Je recommande Vite & Gourmand!!', 'Validé', '2026-11-07 22:21:13'),
(4, 11, 4, 'no comment! super et proffestionnel!', 'Validé', '2026-11-08 13:55:49');

INSERT INTO message_contact VALUES
(1, 'Question sur un menu', 'marie.test@example.com', 'Bonjour, je voudrais avoir plus de renseignements sur vos prestations pour 20 personnes. Je reste à votre disposition', '2026-11-08 09:59:01');

INSERT INTO menu_plat VALUES
(1, 1, 'entrée'),
(1, 2, 'plat'),
(1, 3, 'dessert'),
(3, 4, 'entrée'),
(3, 5, 'plat'),
(3, 6, 'dessert'),
(4, 3, 'dessert'),
(4, 7, 'entrée'),
(4, 8, 'plat');

INSERT INTO plat_allergene VALUES
(3, 1),
(3, 3);

-- =====================================================
-- 17. RELATIONS ET CLES ETRANGERES
-- =====================================================

ALTER TABLE menu
    ADD CONSTRAINT fk_menu_theme
    FOREIGN KEY (id_theme) REFERENCES theme (id_theme),
    ADD CONSTRAINT fk_menu_regime
    FOREIGN KEY (id_regime) REFERENCES regime (id_regime);

ALTER TABLE commande
    ADD CONSTRAINT fk_commande_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur);

ALTER TABLE ligne_commande
    ADD CONSTRAINT fk_ligne_commande_commande
    FOREIGN KEY (id_commande) REFERENCES commande (id_commande),
    ADD CONSTRAINT fk_ligne_commande_menu
    FOREIGN KEY (id_menu) REFERENCES menu (id_menu);

ALTER TABLE historique_commande
    ADD CONSTRAINT fk_historique_commande
    FOREIGN KEY (id_commande) REFERENCES commande (id_commande),
    ADD CONSTRAINT fk_historique_statut
    FOREIGN KEY (id_statut) REFERENCES statut_commande (id_statut);

ALTER TABLE avis
    ADD CONSTRAINT fk_avis_commande
    FOREIGN KEY (id_commande) REFERENCES commande (id_commande);

ALTER TABLE menu_plat
    ADD CONSTRAINT fk_menu_plat_menu
    FOREIGN KEY (id_menu) REFERENCES menu (id_menu),
    ADD CONSTRAINT fk_menu_plat_plat
    FOREIGN KEY (id_plat) REFERENCES plat (id_plat);

ALTER TABLE plat_allergene
    ADD CONSTRAINT fk_plat_allergene_allergene
    FOREIGN KEY (id_allergene) REFERENCES allergene (id_allergene),
    ADD CONSTRAINT fk_plat_allergene_plat
    FOREIGN KEY (id_plat) REFERENCES plat (id_plat);

-- =====================================================
-- FIN DU SCRIPT
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;
