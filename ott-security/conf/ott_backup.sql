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
(1,'ADMIN','2026-07-07 11:19:34','€ƒ;\Úµ­\÷/«³§\ôÌ±',';B\õ<Ê±Œ1\Æ*}\Þ','ú\0Jlš\Øü«.žQ~A3\Ë','Ÿ²\n\È8”ý!°(‡K[\ô'),
(2,'USER','2026-07-07 11:19:34','C\ß\ôú\ì,2§\ë\0~5À\æ¿a','¨0\õ\ßû³\õ\ÕL^:¯²\ê','{gŒL\"ò¤‹F~Ø¯kp','iHQ2?^\÷ú\ä\Éþ\ï\ôS\Æ\Ý'),
(3,'USER','2026-07-07 11:19:34','\ö\Ö\Ñz\Ê\õMjCª\ñF+Ÿ','%\î„tù\Z\É&\×\à \â±B','{gŒL\"ò¤‹F~Ø¯kp','\"\Ù\õÁQ\ð¬\è\r\ß^‹&\óG'),
(4,'USER','2026-07-07 11:19:34','!§`Àd¨B±\Ìî°•²6¸','þ‚xþ|X\î	;I2=x\å','{gŒL\"ò¤‹F~Ø¯kp','\Ñµ[ b-¶(\ó\ÙHY\ØN’'),
(5,'USER','2026-07-07 11:19:34','À\ÔÙ¾¿HCDu:L\r¿»','yÉÖZEû4¨’€új\ç\Þ','{gŒL\"ò¤‹F~Ø¯kp','Ã›8\Å•¹€%ªJbJ'),
(6,'USER','2026-07-07 11:19:34','ƒ¦ÈŸ\ò\Íms¶Ca¯\ÛGÀ','\Î\ä‹4S\ÐWi\Ò\Ï\ô','{gŒL\"ò¤‹F~Ø¯kp','\Ýû-\Ñ!UÙŸ#\È+HÌ®$l');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-15  9:23:49
