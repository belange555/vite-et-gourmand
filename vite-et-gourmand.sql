-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 08 nov. 2026 à 16:50
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vite-et-gourmand`
--

-- --------------------------------------------------------

--
-- Structure de la table `allergene`
--

CREATE TABLE `allergene` (
  `id_allergene` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `allergene`
--

INSERT INTO `allergene` (`id_allergene`, `nom`) VALUES
(1, 'Gluten'),
(2, 'Lait'),
(3, 'Œufs');

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `note` tinyint(4) NOT NULL,
  `commentaire` text NOT NULL,
  `statut_validation` varchar(20) NOT NULL,
  `date_avis` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id_avis`, `id_commande`, `note`, `commentaire`, `statut_validation`, `date_avis`) VALUES
(3, 10, 5, 'menu exelent, produit frais ! Je recommande Vite & Gourmand!!', 'Validé', '2026-11-07 22:21:13'),
(4, 11, 4, 'no comment! super et proffestionnel!', 'Validé', '2026-11-08 13:55:49');

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id_commande` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_commande` datetime NOT NULL,
  `date_prestation` date NOT NULL,
  `heure_prestation` time NOT NULL,
  `lieu_livraison` varchar(150) NOT NULL,
  `adresse_livraison` varchar(255) NOT NULL,
  `prix_livraison` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prix_total` decimal(10,2) NOT NULL,
  `materiel_prete` tinyint(1) NOT NULL DEFAULT 0,
  `date_retour_materiel` datetime DEFAULT NULL,
  `motif_annulation` text DEFAULT NULL,
  `mode_contact` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id_commande`, `id_utilisateur`, `date_commande`, `date_prestation`, `heure_prestation`, `lieu_livraison`, `adresse_livraison`, `prix_livraison`, `reduction`, `prix_total`, `materiel_prete`, `date_retour_materiel`, `motif_annulation`, `mode_contact`, `instructions`) VALUES
(10, 5, '2026-11-07 20:51:56', '2026-11-08', '20:00:00', 'Domicile', '6 chemin vert, 33000 Bordeaux', 5.00, 0.00, 97.00, 0, NULL, NULL, NULL, 'RAS'),
(11, 5, '2026-11-07 23:33:01', '2026-11-21', '19:00:00', 'Retrait sur place', 'Retrait sur place', 0.00, 0.00, 100.00, 0, NULL, NULL, NULL, NULL),
(12, 5, '2026-11-08 13:42:30', '2026-11-08', '20:00:00', 'Domicile', '6 chemin vert, 33000 Bordeaux', 5.00, 0.00, 173.00, 0, NULL, NULL, NULL, 'RAS');

-- --------------------------------------------------------

--
-- Structure de la table `historique_commande`
--

CREATE TABLE `historique_commande` (
  `id_historique` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `id_statut` int(11) NOT NULL,
  `date_modification` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_commande`
--

INSERT INTO `historique_commande` (`id_historique`, `id_commande`, `id_statut`, `date_modification`) VALUES
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

-- --------------------------------------------------------

--
-- Structure de la table `horaire`
--

CREATE TABLE `horaire` (
  `id_horaire` int(11) NOT NULL,
  `jour` varchar(20) NOT NULL,
  `heure_ouverture` time DEFAULT NULL,
  `heure_fermeture` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `horaire`
--

INSERT INTO `horaire` (`id_horaire`, `jour`, `heure_ouverture`, `heure_fermeture`) VALUES
(1, '1', '12:00:00', '23:00:00'),
(2, '2', '12:00:00', '23:00:00'),
(3, '3', NULL, NULL),
(4, '4', NULL, NULL),
(5, '5', NULL, NULL),
(6, '6', NULL, NULL),
(7, '7', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ligne_commande`
--

CREATE TABLE `ligne_commande` (
  `id_ligne_commande` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `nombre_personnes` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `sous_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ligne_commande`
--

INSERT INTO `ligne_commande` (`id_ligne_commande`, `id_commande`, `id_menu`, `nombre_personnes`, `prix_unitaire`, `sous_total`) VALUES
(10, 10, 4, 4, 23.00, 92.00),
(11, 11, 1, 4, 25.00, 100.00),
(12, 12, 3, 6, 28.00, 168.00);

-- --------------------------------------------------------

--
-- Structure de la table `menu`
--

CREATE TABLE `menu` (
  `id_menu` int(11) NOT NULL,
  `id_theme` int(11) NOT NULL,
  `id_regime` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `nombre_personnes_minimum` int(11) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `conditions` text DEFAULT NULL,
  `stock_disponible` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menu`
--

INSERT INTO `menu` (`id_menu`, `id_theme`, `id_regime`, `titre`, `description`, `nombre_personnes_minimum`, `prix`, `conditions`, `stock_disponible`, `image`) VALUES
(1, 1, 1, 'Menu Méditerranéen', 'Une formule aux saveurs méditerranéennes composée de plusieurs plats.', 4, 25.00, 'Commande minimum de 4 personnes.', 20, 'images/menu-mediterraneen.jpg'),
(3, 2, 1, 'Menu Italien', 'Les incontournables de la cuisine italienne à votre table.', 6, 28.00, 'Commande minimum de 6 personnes.', 20, 'images/menu-italien.jpg'),
(4, 3, 2, 'Menu Végétarien', 'Des légumes de saison et des saveurs gourmandes.', 4, 23.00, 'Commande minimum de 4 personnes.', 20, 'images/menu-vegetarien.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `menu_plat`
--

CREATE TABLE `menu_plat` (
  `id_menu` int(11) NOT NULL,
  `id_plat` int(11) NOT NULL,
  `type_dans_menu` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menu_plat`
--

INSERT INTO `menu_plat` (`id_menu`, `id_plat`, `type_dans_menu`) VALUES
(1, 1, 'entrée'),
(1, 2, 'plat'),
(1, 3, 'dessert'),
(3, 4, 'entrée'),
(3, 5, 'plat'),
(3, 6, 'dessert'),
(4, 3, 'dessert'),
(4, 7, 'entrée'),
(4, 8, 'plat');

-- --------------------------------------------------------

--
-- Structure de la table `message_contact`
--

CREATE TABLE `message_contact` (
  `id_message` int(11) NOT NULL,
  `objet` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `message_contact`
--

INSERT INTO `message_contact` (`id_message`, `objet`, `email`, `message`, `date_envoi`) VALUES
(1, 'Question sur un menu', 'marie.test@example.com', 'Bonjour, je voudrais avoir plus de renseignements sur vos prestations pour 20 personnes.\r\nJe reste à votre disposition', '2026-11-08 09:59:01');

-- --------------------------------------------------------

--
-- Structure de la table `plat`
--

CREATE TABLE `plat` (
  `id_plat` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `plat`
--

INSERT INTO `plat` (`id_plat`, `nom`, `description`) VALUES
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

-- --------------------------------------------------------

--
-- Structure de la table `plat_allergene`
--

CREATE TABLE `plat_allergene` (
  `id_plat` int(11) NOT NULL,
  `id_allergene` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `plat_allergene`
--

INSERT INTO `plat_allergene` (`id_plat`, `id_allergene`) VALUES
(3, 1),
(3, 3);

-- --------------------------------------------------------

--
-- Structure de la table `regime`
--

CREATE TABLE `regime` (
  `id_regime` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `regime`
--

INSERT INTO `regime` (`id_regime`, `nom`) VALUES
(1, 'Classique'),
(2, 'Végétarien'),
(3, 'Sans gluten');

-- --------------------------------------------------------

--
-- Structure de la table `statut_commande`
--

CREATE TABLE `statut_commande` (
  `id_statut` int(11) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `statut_commande`
--

INSERT INTO `statut_commande` (`id_statut`, `libelle`) VALUES
(1, 'Acceptée'),
(2, 'En préparation'),
(3, 'En cours de livraison'),
(4, 'Livrée'),
(5, 'En attente du retour de matériel'),
(6, 'Terminée'),
(7, 'En attente de validation'),
(8, 'Refusée');

-- --------------------------------------------------------

--
-- Structure de la table `theme`
--

CREATE TABLE `theme` (
  `id_theme` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `theme`
--

INSERT INTO `theme` (`id_theme`, `nom`) VALUES
(1, 'Méditerranéen'),
(2, 'Italien'),
(3, 'Végétarien');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `gsm` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `token_reinitialisation` varchar(255) DEFAULT NULL,
  `expiration_reinitialisation` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `prenom`, `email`, `gsm`, `adresse`, `mot_de_passe`, `role`, `actif`, `token_reinitialisation`, `expiration_reinitialisation`) VALUES
(1, 'Dupont', 'Helène', 'marie.test@example.com', '0612345678', '10 rue des fleurs, 30000 Bordeaux', '$2y$10$nSoqBHs7jjm7E7nuUo9yM.vjIrUjX/mRl0S3hbAISCzEmJYnM6aiy', 'utilisateur', 1, NULL, NULL),
(2, 'Dupont', 'Arthur', 'Dupont.test@example.com', '0600000000', '6 chemin des fleurs, 44000 Nantes', '$2y$10$Sgle23MVr5hHkRlV.l2srukDWrGrD3IlWp1ywgOacAWtFOxd8ZV6a', 'employe', 1, NULL, NULL),
(3, 'Admin', 'paul', 'Admin.test@example.com', '0600000001', '6 chemin vert, 44000 Nantes', '$2y$10$ORGf5nJtTAYbcjQzz6AEq.w5hEiri76R32n5nwqfg.PTa67nfIUlO', 'administrateur', 1, NULL, NULL),
(4, 'Martin', 'Sophie', 'sophie.martin@test.fr', '0600000001', NULL, '$2y$10$gTOT/tLkwErG1IRkk2RrMunveqo0DkMNz7I6PXedt4BYxNQldX8vO', 'employe', 1, NULL, NULL),
(5, 'Bernard', 'Camille', 'camille.bernard@test.fr', '0612345678', '6 chemin vert, 33000 Bordeaux', '$2y$10$4xOplKuM040bj4V0oq62l.FMqD2jUrHHjeIb61bDQ0N.ho9PGiEkm', 'utilisateur', 1, NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `allergene`
--
ALTER TABLE `allergene`
  ADD PRIMARY KEY (`id_allergene`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD KEY `fk_avis_commande` (`id_commande`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `fk_commande_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `historique_commande`
--
ALTER TABLE `historique_commande`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `fk_historique_commande` (`id_commande`),
  ADD KEY `fk_historique_statut` (`id_statut`);

--
-- Index pour la table `horaire`
--
ALTER TABLE `horaire`
  ADD PRIMARY KEY (`id_horaire`);

--
-- Index pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD PRIMARY KEY (`id_ligne_commande`),
  ADD KEY `fk_ligne_commande_commande` (`id_commande`),
  ADD KEY `fk_ligne_commande_menu` (`id_menu`);

--
-- Index pour la table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`),
  ADD KEY `fk_menu_theme` (`id_theme`),
  ADD KEY `fk_menu_regime` (`id_regime`);

--
-- Index pour la table `menu_plat`
--
ALTER TABLE `menu_plat`
  ADD PRIMARY KEY (`id_menu`,`id_plat`),
  ADD KEY `fk_menu_plat_plat` (`id_plat`);

--
-- Index pour la table `message_contact`
--
ALTER TABLE `message_contact`
  ADD PRIMARY KEY (`id_message`);

--
-- Index pour la table `plat`
--
ALTER TABLE `plat`
  ADD PRIMARY KEY (`id_plat`);

--
-- Index pour la table `plat_allergene`
--
ALTER TABLE `plat_allergene`
  ADD PRIMARY KEY (`id_plat`,`id_allergene`),
  ADD KEY `fk_plat_allergene_allergene` (`id_allergene`);

--
-- Index pour la table `regime`
--
ALTER TABLE `regime`
  ADD PRIMARY KEY (`id_regime`);

--
-- Index pour la table `statut_commande`
--
ALTER TABLE `statut_commande`
  ADD PRIMARY KEY (`id_statut`);

--
-- Index pour la table `theme`
--
ALTER TABLE `theme`
  ADD PRIMARY KEY (`id_theme`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `allergene`
--
ALTER TABLE `allergene`
  MODIFY `id_allergene` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `historique_commande`
--
ALTER TABLE `historique_commande`
  MODIFY `id_historique` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pour la table `horaire`
--
ALTER TABLE `horaire`
  MODIFY `id_horaire` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  MODIFY `id_ligne_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `message_contact`
--
ALTER TABLE `message_contact`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `plat`
--
ALTER TABLE `plat`
  MODIFY `id_plat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `regime`
--
ALTER TABLE `regime`
  MODIFY `id_regime` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `statut_commande`
--
ALTER TABLE `statut_commande`
  MODIFY `id_statut` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `theme`
--
ALTER TABLE `theme`
  MODIFY `id_theme` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_commande` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`);

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `fk_commande_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Contraintes pour la table `historique_commande`
--
ALTER TABLE `historique_commande`
  ADD CONSTRAINT `fk_historique_commande` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`),
  ADD CONSTRAINT `fk_historique_statut` FOREIGN KEY (`id_statut`) REFERENCES `statut_commande` (`id_statut`);

--
-- Contraintes pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD CONSTRAINT `fk_ligne_commande_commande` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`),
  ADD CONSTRAINT `fk_ligne_commande_menu` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`);

--
-- Contraintes pour la table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_regime` FOREIGN KEY (`id_regime`) REFERENCES `regime` (`id_regime`),
  ADD CONSTRAINT `fk_menu_theme` FOREIGN KEY (`id_theme`) REFERENCES `theme` (`id_theme`);

--
-- Contraintes pour la table `menu_plat`
--
ALTER TABLE `menu_plat`
  ADD CONSTRAINT `fk_menu_plat_menu` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`),
  ADD CONSTRAINT `fk_menu_plat_plat` FOREIGN KEY (`id_plat`) REFERENCES `plat` (`id_plat`);

--
-- Contraintes pour la table `plat_allergene`
--
ALTER TABLE `plat_allergene`
  ADD CONSTRAINT `fk_plat_allergene_allergene` FOREIGN KEY (`id_allergene`) REFERENCES `allergene` (`id_allergene`),
  ADD CONSTRAINT `fk_plat_allergene_plat` FOREIGN KEY (`id_plat`) REFERENCES `plat` (`id_plat`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
