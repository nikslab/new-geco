-- MySQL dump 10.13  Distrib 8.0.30, for Linux (x86_64)
--
-- Host: localhost    Database: new_geco
-- ------------------------------------------------------
-- Server version	8.0.30-0ubuntu0.20.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bots`
--

DROP TABLE IF EXISTS `bots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots` (
  `id` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `experiment_id` bigint DEFAULT NULL,
  `generation` bigint DEFAULT NULL,
  `score` double(8,4) DEFAULT NULL,
  `fitness` double(8,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_experiment` (`experiment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `experiments`
--

DROP TABLE IF EXISTS `experiments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experiments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `description` varchar(1000) DEFAULT NULL,
  `experiment_type` varchar(100) DEFAULT NULL,
  `experiment_options` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geneaology`
--

DROP TABLE IF EXISTS `geneaology`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geneaology` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `bot_id` varchar(45) DEFAULT NULL,
  `parent_id` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `genes`
--

DROP TABLE IF EXISTS `genes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `bot_id` varchar(45) DEFAULT NULL,
  `gene` varchar(45) DEFAULT NULL,
  `allele` varchar(5) DEFAULT NULL,
  `mutated` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gene` (`gene`),
  KEY `idx_allele` (`allele`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=986086 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ipd_games`
--

DROP TABLE IF EXISTS `ipd_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_games` (
  `id` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `bot1_id` varchar(45) DEFAULT NULL,
  `bot2_id` varchar(45) DEFAULT NULL,
  `experiment_id` bigint DEFAULT NULL,
  `generation` bigint DEFAULT NULL,
  `history1` varchar(1000) DEFAULT NULL,
  `moves` int DEFAULT NULL,
  `score1` double(8,4) DEFAULT NULL,
  `score2` double(8,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot1` (`bot1_id`),
  KEY `idx_bot2` (`bot2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pd_moves`
--

DROP TABLE IF EXISTS `pd_moves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pd_moves` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ipd_game_id` varchar(45) DEFAULT NULL,
  `bot_id` varchar(45) DEFAULT NULL,
  `gene` varchar(45) DEFAULT NULL,
  `allele` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_gene` (`gene`),
  KEY `idx_game` (`ipd_game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11382129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-08-30 19:39:17
