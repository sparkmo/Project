/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for Linux (aarch64)
--
-- Host: localhost    Database: intranet_db
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
-- Current Database: `intranet_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `intranet_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `intranet_db`;

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee` (
  `employee_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `login_id` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(30) NOT NULL,
  `department` varchar(30) NOT NULL,
  `role` enum('Admin','Manager','User') NOT NULL DEFAULT 'User',
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `login_id` (`login_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
INSERT INTO `employee` VALUES
(1,'admin01','1234','관리자','보안팀','Admin','a@hi.xyz'),
(2,'manager01','1234','김매니저','운영팀','Manager','b@hi.xyz'),
(3,'user01','1234','이일반','개발팀','User','c@hi.xyz'),
(4,'user02','1234','박일반','마케팅팀','User','d@hi.xyz'),
(5,'user03','1234','최일반','고객지원팀','User','e@hi.xyz');
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
UNLOCK TABLES;
--
-- Table structure for table `notice`
--

DROP TABLE IF EXISTS `notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice` (
  `notice_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`notice_id`),
  KEY `fk_notice_employee` (`employee_id`),
  CONSTRAINT `fk_notice_employee` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notice`
--

LOCK TABLES `notice` WRITE;
/*!40000 ALTER TABLE `notice` DISABLE KEYS */;
INSERT INTO `notice` VALUES
(1,1,'시스템 점검','7월 20일 새벽 2시부터 시스템 점검이 진행됩니다.','2026-07-17 16:27:25');
/*!40000 ALTER TABLE `notice` ENABLE KEYS */;
LOCK TABLES `notice` WRITE;
/*!40000 ALTER TABLE `notice` DISABLE KEYS */;
INSERT INTO `notice` VALUES
(1,1,'시스템 점검','7월 20일 새벽 2시부터 시스템 점검이 진행됩니다.','2026-07-17 16:27:25'),
(2,2,'신규 인원 안내','이번 주부터 개발팀/마케팅팀/고객지원팀에 신규 인원이 합류합니다. 사내 계정 발급 완료했으니 참고 바랍니다.','2026-07-18 09:12:00'),
(3,1,'보안 정책 업데이트','사내 메일 비밀번호는 인트라넷 로그인 비밀번호와 별도로 관리되니 유의해주세요.','2026-07-19 10:40:15'),
(4,3,'개발팀 배포 일정','다음 배포는 7월 25일 오후 3시로 예정되어 있습니다.','2026-07-20 14:05:30'),
(5,4,'마케팅 캠페인 공지','8월 프로모션 관련 자료는 공유 드라이브에 업로드했습니다.','2026-07-21 11:20:00'),
(6,5,'고객 문의 응대 가이드','신규 문의 채널 관련 응대 가이드를 첨부합니다. 확인 부탁드립니다.','2026-07-22 08:55:45');
/*!40000 ALTER TABLE `notice` ENABLE KEYS */;
UNLOCK TABLES;
