/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for Linux (aarch64)
--
-- Host: localhost    Database: ott
-- ------------------------------------------------------
-- Server version	10.11.18-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `ott`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `ott` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `ott`;

--
-- Table structure for table `inquiry`
--

DROP TABLE IF EXISTS `inquiry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry` (
  `inquiry_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `status` enum('ÎåÄÍ∏∞','Ï≤òÎ¶¨ÏôÑÎ£å') NOT NULL DEFAULT 'ÎåÄÍ∏∞',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`inquiry_id`),
  KEY `fk_inquiry_member` (`member_id`),
  CONSTRAINT `fk_inquiry_member` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiry`
--

LOCK TABLES `inquiry` WRITE;
/*!40000 ALTER TABLE `inquiry` DISABLE KEYS */;
INSERT INTO `inquiry` VALUES
(1,1,'Í≤∞Ï†úÍ∞Ä ÎêòÏßÄ ÏïäÏäµÎãàÎã§.','Ïπ¥Îìú Í≤∞Ï†úÎ•º ÌñàÎäîÎç∞ Ïù¥Ïö©Í∂åÏù¥ Îì±Î°ùÎêòÏßÄ ÏïäÏïòÏäµÎãàÎã§.','ÎåÄÍ∏∞','2026-07-17 16:28:30');
/*!40000 ALTER TABLE `inquiry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(20) DEFAULT 'USER',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `email` varbinary(512) DEFAULT NULL,
  `phone` varbinary(512) DEFAULT NULL,
  `password` varbinary(512) DEFAULT NULL,
  `name` varbinary(512) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'ADMIN','2026-07-07 11:19:34','ÄÉ;\⁄µ≠\˜/´≥ß\ÙÃ±',';B\ı< ±å1\∆*}ç\ﬁ','˙\0Jlö\ÿ¸´.ûQ~A3\À','ü≤\n\»8î˝!è∞(áK[\Ù'),
(2,'USER','2026-07-07 11:19:34','C\ﬂ\Ù˙\Ï,2ß\Î\0~5¿\Êøa','®0\ı\ﬂ˚≥\ıê\’L^:Ø≤\Í','{gåL\"Ú§èãF~ÿØkp','iHQ2?^\˜˙\‰\…˛\Ô\ÙS\∆\›'),
(3,'USER','2026-07-07 11:19:34','\ˆ\÷\—z\ \ıMjC™\ÒF+ü','%\ÓÑt˘\Z\…&\◊\‡†\‚±B','{gåL\"Ú§èãF~ÿØkp','\"\Ÿ\ı¡Q\¨\Ë\r\ﬂ^ã&\ÛG'),
(4,'USER','2026-07-07 11:19:34','!ß`¿d®B±\ÃÓ∞ï≤6∏','˛Çx˛|X\Ó	;I2=x\Â','{gåL\"Ú§èãF~ÿØkp','\—µ[ b-∂(\Û\ŸHY\ÿNí'),
(5,'USER','2026-07-07 11:19:34','¿\‘ŸæøHCDu:L\røª','y…è÷çZE˚4®íÄ˙j\Á\ﬁ','{gåL\"Ú§èãF~ÿØkp','√õ8\≈ïπÄ%™JbJ'),
(6,'USER','2026-07-07 11:19:34','É¶»ü\Ú\Õms∂CaØ\€G¿','\Œ\‰ã4S\–Wi\“\œ\Ù','{gåL\"Ú§èãF~ÿØkp','\›˚-\—!UŸü#\»+HÃÆ$l');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `video` (
  `video_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`video_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video`
--

LOCK TABLES `video` WRITE;
/*!40000 ALTER TABLE `video` DISABLE KEYS */;
INSERT INTO `video` VALUES
(1,'Ïò§ÏßïÏñ¥ Í≤åÏûÑ ÏãúÏ¶å3','ÏµúÌõÑÏùò Í≤åÏûÑÏù¥ ÏãúÏûëÎê©ÎãàÎã§.','/uploads/thumbnails/squid3.jpg','ÎìúÎùºÎßà','2026-07-17 16:28:17');
/*!40000 ALTER TABLE `video` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-28 10:39:37
