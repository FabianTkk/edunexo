-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: edunexo_db
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `asistencias`
--

DROP TABLE IF EXISTS `asistencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asistencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `curso_materia_docente_id` int NOT NULL,
  `fecha` date NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT '0',
  `justificada` tinyint(1) NOT NULL DEFAULT '0',
  `observacion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencia` (`estudiante_id`,`curso_materia_docente_id`,`fecha`),
  KEY `fk_asist_cmd` (`curso_materia_docente_id`),
  CONSTRAINT `fk_asist_cmd` FOREIGN KEY (`curso_materia_docente_id`) REFERENCES `curso_materia_docente` (`id`),
  CONSTRAINT `fk_asist_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencias`
--

LOCK TABLES `asistencias` WRITE;
/*!40000 ALTER TABLE `asistencias` DISABLE KEYS */;
INSERT INTO `asistencias` VALUES (1,4,10,'2026-09-15',1,0,'','2026-09-15 22:57:54'),(2,5,10,'2026-09-15',1,0,'','2026-09-15 22:57:54'),(15,4,10,'2026-09-16',1,0,'','2026-09-16 22:33:40'),(16,5,10,'2026-09-16',1,0,'','2026-09-16 22:33:40'),(27,4,10,'2026-09-07',1,0,'','2026-09-19 13:40:03'),(28,5,10,'2026-09-07',1,0,'','2026-09-19 13:40:03'),(29,4,10,'2026-09-08',1,0,'','2026-09-19 13:40:27'),(30,5,10,'2026-09-08',1,0,'','2026-09-19 13:40:27'),(31,4,10,'2026-09-09',1,0,'','2026-09-19 13:40:32'),(32,5,10,'2026-09-09',1,0,'','2026-09-19 13:40:32'),(33,4,10,'2026-09-10',1,0,'','2026-09-19 13:40:39'),(34,5,10,'2026-09-10',1,0,'','2026-09-19 13:40:39'),(35,4,10,'2026-09-11',0,0,'','2026-09-19 13:40:49'),(36,5,10,'2026-09-11',1,0,'','2026-09-19 13:40:49'),(37,4,11,'2026-09-09',1,0,'','2026-09-19 13:46:17'),(38,5,11,'2026-09-09',1,0,'','2026-09-19 13:46:17');
/*!40000 ALTER TABLE `asistencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aviso_estudiante`
--

DROP TABLE IF EXISTS `aviso_estudiante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aviso_estudiante` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aviso_id` int NOT NULL,
  `estudiante_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aviso_estudiante` (`aviso_id`,`estudiante_id`),
  KEY `fk_aviso_est_estudiante` (`estudiante_id`),
  CONSTRAINT `fk_aviso_est_aviso` FOREIGN KEY (`aviso_id`) REFERENCES `avisos` (`id`),
  CONSTRAINT `fk_aviso_est_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aviso_estudiante`
--

LOCK TABLES `aviso_estudiante` WRITE;
/*!40000 ALTER TABLE `aviso_estudiante` DISABLE KEYS */;
/*!40000 ALTER TABLE `aviso_estudiante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avisos`
--

DROP TABLE IF EXISTS `avisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avisos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_materia_docente_id` int NOT NULL,
  `titulo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_aviso` date NOT NULL,
  `aplica_a_todos` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_aviso_cmd` (`curso_materia_docente_id`),
  CONSTRAINT `fk_aviso_cmd` FOREIGN KEY (`curso_materia_docente_id`) REFERENCES `curso_materia_docente` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avisos`
--

LOCK TABLES `avisos` WRITE;
/*!40000 ALTER TABLE `avisos` DISABLE KEYS */;
INSERT INTO `avisos` VALUES (1,10,'Dibujo con punto de fuga (trabajo)','Se hizo este trabajo aaa test','2026-09-14',1,'2026-09-16 22:35:32'),(2,10,'Primer Parcial de 20pts','Tema Central: \"El Arte como Medio de Expresión y Crítica Social\"','2026-09-07',1,'2026-09-16 22:46:10'),(3,11,'Aviso E. Fisica','se realizaron ejercicios dentro de clases por la lluvia, no hubo deporte al aire libre','2026-09-09',1,'2026-09-19 13:47:28');
/*!40000 ALTER TABLE `avisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_colegio` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_wa_remitente` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instancia_evolution` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `directorio_evolution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'C:\\laragon\\www\\evolution-api',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion`
--

LOCK TABLES `configuracion` WRITE;
/*!40000 ALTER TABLE `configuracion` DISABLE KEYS */;
INSERT INTO `configuracion` VALUES (1,'Centro Educativo La Amistad','595971366877','edunexo','C:\\laragon\\www\\evolution-api','2026-09-08 22:32:15');
/*!40000 ALTER TABLE `configuracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_materia_docente`
--

DROP TABLE IF EXISTS `curso_materia_docente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_materia_docente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_id` int NOT NULL,
  `materia_id` int NOT NULL,
  `docente_id` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cmd_curso_materia` (`curso_id`,`materia_id`),
  KEY `fk_cmd_materia` (`materia_id`),
  KEY `fk_cmd_docente` (`docente_id`),
  CONSTRAINT `fk_cmd_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmd_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cmd_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_materia_docente`
--

LOCK TABLES `curso_materia_docente` WRITE;
/*!40000 ALTER TABLE `curso_materia_docente` DISABLE KEYS */;
INSERT INTO `curso_materia_docente` VALUES (1,2,1,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(2,2,2,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(3,2,3,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(4,2,4,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(5,2,5,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(6,2,6,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(7,2,7,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(8,2,8,1,1,'2026-08-31 21:53:16','2026-08-31 21:53:16'),(10,4,7,3,1,'2026-09-15 22:22:14','2026-09-15 22:22:14'),(11,4,6,4,1,'2026-09-19 13:45:40','2026-09-19 13:45:40');
/*!40000 ALTER TABLE `curso_materia_docente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cursos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `turno` enum('manana','tarde','noche') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'manana',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cursos_nombre_turno` (`nombre`,`turno`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cursos`
--

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
INSERT INTO `cursos` VALUES (1,'8vo Grado','manana',1,'2026-08-31 21:22:29'),(2,'6to Grado','manana',1,'2026-08-31 21:22:29'),(4,'9no Grado','manana',1,'2026-09-15 22:06:49');
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `envios_wa`
--

DROP TABLE IF EXISTS `envios_wa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `envios_wa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporte_id` int DEFAULT NULL,
  `estudiante_id` int NOT NULL,
  `periodo_semana` date NOT NULL,
  `destinatario_telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','enviado','entregado','error') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `fecha_hora_envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_envio_alumno_semana_tutor` (`estudiante_id`,`periodo_semana`,`destinatario_telefono`),
  KEY `fk_envio_reporte` (`reporte_id`),
  CONSTRAINT `fk_envio_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_envio_reporte` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `envios_wa`
--

LOCK TABLES `envios_wa` WRITE;
/*!40000 ALTER TABLE `envios_wa` DISABLE KEYS */;
INSERT INTO `envios_wa` VALUES (4,4,4,'2026-09-14','595985700559','enviado','2026-09-16 23:00:56'),(7,7,4,'2026-09-07','595985700559','enviado','2026-09-23 22:00:24');
/*!40000 ALTER TABLE `envios_wa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ci` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_id` int DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ci` (`ci`),
  KEY `fk_estudiantes_curso` (`curso_id`),
  CONSTRAINT `fk_estudiantes_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiantes`
--

LOCK TABLES `estudiantes` WRITE;
/*!40000 ALTER TABLE `estudiantes` DISABLE KEYS */;
INSERT INTO `estudiantes` VALUES (1,'7123456','María López','8vo Grado',1,'activo','2026-08-24 21:51:38','2026-08-31 21:23:57'),(2,'7123457','Carlos López','6to Grado',2,'activo','2026-08-24 21:51:38','2026-08-31 21:23:57'),(3,'6784217','Ramon Alvarez','6to Grado',2,'activo','2026-09-03 22:40:29','2026-09-03 22:40:29'),(4,'7097278','Alejandra Isabel Silva Gonzalez','9no Grado',4,'activo','2026-09-15 22:44:34','2026-09-15 22:44:34'),(5,'7456321','Miguel Sander Bareiro Ortiz','9no Grado',4,'activo','2026-09-15 22:45:42','2026-09-15 22:45:42');
/*!40000 ALTER TABLE `estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluaciones`
--

DROP TABLE IF EXISTS `evaluaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_materia_docente_id` int NOT NULL,
  `tipo_evaluacion_id` int NOT NULL,
  `titulo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `puntaje_maximo` decimal(5,2) NOT NULL DEFAULT '10.00',
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_eval_cmd` (`curso_materia_docente_id`),
  KEY `fk_eval_tipo` (`tipo_evaluacion_id`),
  CONSTRAINT `fk_eval_cmd` FOREIGN KEY (`curso_materia_docente_id`) REFERENCES `curso_materia_docente` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eval_tipo` FOREIGN KEY (`tipo_evaluacion_id`) REFERENCES `tipo_evaluacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluaciones`
--

LOCK TABLES `evaluaciones` WRITE;
/*!40000 ALTER TABLE `evaluaciones` DISABLE KEYS */;
INSERT INTO `evaluaciones` VALUES (2,7,4,'Dibujo con punto de fuga (que se yo)','2026-09-07',10.00,'','2026-08-31 22:12:18'),(4,10,4,'Dibujo con punto de fuga (que se yo)','2026-09-14',10.00,'','2026-09-16 22:32:46'),(5,10,2,'El Arte como Medio de Expresión y Crítica Social','2026-09-07',20.00,'Este tema permite integrar la teoría, la historia del arte y la práctica creativa, conectando a los estudiantes con su entorno. \r\nParte 1: Selección Múltiple y Verdadero/Falso (20%):\r\n\r\nConceptos clave sobre técnicas artísticas, movimientos muralistas y elementos visuales.\r\n\r\nParte 2: Análisis de Imagen (30%):\r\n\r\nMostrar una obra de arte urbano o un mural famoso y pedir al estudiante que identifique el mensaje principal, los colores predominantes y la emoción que transmite.\r\n\r\nParte 3: Producción Práctica / Boceto (50%):\r\n\r\nConsigna: \"Diseña el boceto de un mural escolar o comunitario que promueva un valor social (por ejemplo: el cuidado del medio ambiente, la inclusión, la paz o la expresión juvenil). Utiliza la teoría del color aprendida en clase para resaltar el mensaje central.\"','2026-09-16 22:41:22');
/*!40000 ALTER TABLE `evaluaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_validacion`
--

DROP TABLE IF EXISTS `logs_validacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_validacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `telefono_intentado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resultado` tinyint(1) NOT NULL,
  `fecha_intento` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  CONSTRAINT `logs_validacion_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_validacion`
--

LOCK TABLES `logs_validacion` WRITE;
/*!40000 ALTER TABLE `logs_validacion` DISABLE KEYS */;
INSERT INTO `logs_validacion` VALUES (1,4,'595981376043',1,'2026-09-15 23:41:29'),(2,4,'595985700559',1,'2026-09-15 23:44:18'),(3,4,'595985700559',1,'2026-09-15 23:44:19'),(4,4,'595985700559',1,'2026-09-16 22:35:58'),(5,4,'595985700559',1,'2026-09-16 22:54:18'),(6,4,'595985700559',1,'2026-09-16 22:54:57'),(7,4,'595985700559',1,'2026-09-16 22:55:29'),(8,4,'595985700559',1,'2026-09-16 22:56:31'),(9,4,'595985700559',1,'2026-09-16 23:00:54'),(10,4,'595985700559',1,'2026-09-19 13:47:57'),(11,4,'595985700559',1,'2026-09-19 13:48:12'),(12,4,'595985700559',1,'2026-09-23 22:00:19');
/*!40000 ALTER TABLE `logs_validacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materias`
--

DROP TABLE IF EXISTS `materias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_materias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materias`
--

LOCK TABLES `materias` WRITE;
/*!40000 ALTER TABLE `materias` DISABLE KEYS */;
INSERT INTO `materias` VALUES (1,'Matematica',NULL,1,'2026-08-31 21:22:29'),(2,'Literatura',NULL,1,'2026-08-31 21:22:29'),(3,'Ciencias',NULL,1,'2026-08-31 21:22:29'),(4,'Ciencias Sociales',NULL,0,'2026-08-31 21:22:29'),(5,'Ingles',NULL,1,'2026-08-31 21:22:29'),(6,'Educacion Fisica',NULL,1,'2026-08-31 21:22:29'),(7,'Artes',NULL,1,'2026-08-31 21:22:29'),(8,'Informatica',NULL,1,'2026-08-31 21:22:29'),(9,'Trabajo y Tecnologia',NULL,1,'2026-09-15 22:12:33'),(10,'Orientacion',NULL,1,'2026-09-15 22:12:43'),(11,'Musica',NULL,1,'2026-09-15 22:12:51'),(12,'Proyecto',NULL,1,'2026-09-15 22:12:58'),(13,'Historia',NULL,1,'2026-09-15 22:13:06'),(14,'Salud',NULL,1,'2026-09-15 22:13:13'),(15,'Etica',NULL,1,'2026-09-15 22:13:46');
/*!40000 ALTER TABLE `materias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notas`
--

DROP TABLE IF EXISTS `notas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `evaluacion_id` int NOT NULL,
  `estudiante_id` int NOT NULL,
  `puntaje_obtenido` decimal(5,2) DEFAULT NULL,
  `observacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notas_eval_estudiante` (`evaluacion_id`,`estudiante_id`),
  KEY `fk_notas_estudiante` (`estudiante_id`),
  CONSTRAINT `fk_notas_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notas_evaluacion` FOREIGN KEY (`evaluacion_id`) REFERENCES `evaluaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notas`
--

LOCK TABLES `notas` WRITE;
/*!40000 ALTER TABLE `notas` DISABLE KEYS */;
INSERT INTO `notas` VALUES (1,2,2,8.50,'','2026-08-31 22:13:07','2026-08-31 22:13:07'),(2,4,4,9.00,'pq tuvo q prestar materiales idk','2026-09-16 22:33:27','2026-09-16 22:33:27'),(3,4,5,8.50,'falto pulcritud ni idea','2026-09-16 22:33:27','2026-09-16 22:33:27'),(4,5,4,20.00,'Excelente Trabajo!!!!','2026-09-16 22:44:13','2026-09-16 22:44:13'),(5,5,5,20.00,'','2026-09-16 22:44:13','2026-09-16 22:44:13');
/*!40000 ALTER TABLE `notas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reportes`
--

DROP TABLE IF EXISTS `reportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `periodo_semana` date NOT NULL,
  `calificacion_general` enum('Logrado','En Proceso','Aun no logrado','No evaluado') COLLATE utf8mb4_unicode_ci DEFAULT 'Logrado',
  `dias_ausente` int DEFAULT '0',
  `tareas_incompletas` int DEFAULT '0',
  `comportamiento` enum('Excelente','Bueno','Regular','Requiere Atencion') COLLATE utf8mb4_unicode_ci DEFAULT 'Bueno',
  `incidentes_disciplinarios` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reporte_docente_semana` (`estudiante_id`,`usuario_id`,`periodo_semana`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reportes`
--

LOCK TABLES `reportes` WRITE;
/*!40000 ALTER TABLE `reportes` DISABLE KEYS */;
INSERT INTO `reportes` VALUES (4,4,3,'2026-09-14','Logrado',0,1,'Excelente','Hola esto es un test 19:34','2026-09-16 22:34:26'),(6,4,3,'2026-09-07','Logrado',1,0,'Bueno','','2026-09-19 13:43:14'),(7,4,4,'2026-09-07','Logrado',1,0,'Bueno','','2026-09-19 13:46:45');
/*!40000 ALTER TABLE `reportes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_cambio_reportes`
--

DROP TABLE IF EXISTS `solicitudes_cambio_reportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_cambio_reportes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporte_id` int NOT NULL,
  `docente_id` int NOT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','atendida','rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `admin_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atendida_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_solicitud_estado` (`estado`,`created_at`),
  KEY `idx_solicitud_reporte` (`reporte_id`),
  KEY `fk_solicitud_docente` (`docente_id`),
  KEY `fk_solicitud_admin` (`admin_id`),
  CONSTRAINT `fk_solicitud_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_solicitud_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_solicitud_reporte` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_cambio_reportes`
--

LOCK TABLES `solicitudes_cambio_reportes` WRITE;
/*!40000 ALTER TABLE `solicitudes_cambio_reportes` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_cambio_reportes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_evaluacion`
--

DROP TABLE IF EXISTS `tipo_evaluacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_evaluacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `peso_porcentaje` decimal(5,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipo_evaluacion_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_evaluacion`
--

LOCK TABLES `tipo_evaluacion` WRITE;
/*!40000 ALTER TABLE `tipo_evaluacion` DISABLE KEYS */;
INSERT INTO `tipo_evaluacion` VALUES (1,'Trabajo Practico',20.00,1),(2,'Examen Parcial',35.00,1),(3,'Examen Final',35.00,1),(4,'Tarea',10.00,1);
/*!40000 ALTER TABLE `tipo_evaluacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutores`
--

DROP TABLE IF EXISTS `tutores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tutores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutores`
--

LOCK TABLES `tutores` WRITE;
/*!40000 ALTER TABLE `tutores` DISABLE KEYS */;
INSERT INTO `tutores` VALUES (1,'Roberto López','595981234567',1,'2026-08-24 21:51:38','2026-08-24 21:51:38'),(2,'Gilberto Mora','595964675832',1,'2026-09-03 22:39:57','2026-09-03 22:39:57'),(3,'Juan Angel Silva Amarilla','595985700559',1,'2026-09-15 22:43:59','2026-09-15 23:43:17');
/*!40000 ALTER TABLE `tutores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutores_estudiantes`
--

DROP TABLE IF EXISTS `tutores_estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tutores_estudiantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tutor_id` int NOT NULL,
  `estudiante_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tutor_id` (`tutor_id`,`estudiante_id`),
  KEY `estudiante_id` (`estudiante_id`),
  CONSTRAINT `tutores_estudiantes_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tutores_estudiantes_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutores_estudiantes`
--

LOCK TABLES `tutores_estudiantes` WRITE;
/*!40000 ALTER TABLE `tutores_estudiantes` DISABLE KEYS */;
INSERT INTO `tutores_estudiantes` VALUES (1,1,1,'2026-08-24 21:51:38'),(2,1,2,'2026-08-24 21:51:38'),(3,2,3,'2026-09-03 22:40:29'),(4,3,4,'2026-09-15 22:44:34'),(5,2,5,'2026-09-15 22:45:42');
/*!40000 ALTER TABLE `tutores_estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('admin','docente') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Prof. Juan Pérez','jperez','$2y$10$aq/6O1THxAVg3xkzUeJBZeigcf9DI43IDgYHVZdLyc8Z6hNDLyEzy',NULL,'docente','2026-08-24 21:51:37','2026-08-24 23:47:35',1),(2,'Rodney Fabian Farinha De Leon','Fabian03','$2y$10$HF92BF3ScKMAzTil/O1wXOzaV/5cTdexTV3AcYDMK4gMRMGpikt1e','rodneyfabian2@gmail.com','admin','2026-08-25 21:53:08','2026-08-31 21:44:23',1),(3,'Javier Britez','javierbritez','$2y$10$EKSfVoJaRU73snuNtJGsYectPVkaf9GssdCMEFLC9fyWf3Tj.X2h.','javibritez@gmail.com','docente','2026-09-15 22:21:00','2026-09-15 22:24:17',1),(4,'Ariel Escobar','arielescobar','$2y$10$atmx5g9mA3SYb8/4h4.IWuxE8BuAWO7NNajX.fiAD50KorhWjSm6S','arielescobar@gmail.com','docente','2026-09-19 13:45:24','2026-09-19 13:45:24',1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-30 19:06:07
