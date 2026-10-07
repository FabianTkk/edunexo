-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para edunexo_db
CREATE DATABASE IF NOT EXISTS `edunexo_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `edunexo_db`;

-- Volcando estructura para tabla edunexo_db.asistencias
CREATE TABLE IF NOT EXISTS `asistencias` (
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

-- Volcando datos para la tabla edunexo_db.asistencias: ~14 rows (aproximadamente)
INSERT INTO `asistencias` (`id`, `estudiante_id`, `curso_materia_docente_id`, `fecha`, `presente`, `justificada`, `observacion`, `created_at`) VALUES
	(1, 4, 10, '2026-09-15', 1, 0, '', '2026-09-15 22:57:54'),
	(2, 5, 10, '2026-09-15', 1, 0, '', '2026-09-15 22:57:54'),
	(15, 4, 10, '2026-09-16', 1, 0, '', '2026-09-16 22:33:40'),
	(16, 5, 10, '2026-09-16', 1, 0, '', '2026-09-16 22:33:40'),
	(27, 4, 10, '2026-09-07', 1, 0, '', '2026-09-19 13:40:03'),
	(28, 5, 10, '2026-09-07', 1, 0, '', '2026-09-19 13:40:03'),
	(29, 4, 10, '2026-09-08', 1, 0, '', '2026-09-19 13:40:27'),
	(30, 5, 10, '2026-09-08', 1, 0, '', '2026-09-19 13:40:27'),
	(31, 4, 10, '2026-09-09', 1, 0, '', '2026-09-19 13:40:32'),
	(32, 5, 10, '2026-09-09', 1, 0, '', '2026-09-19 13:40:32'),
	(33, 4, 10, '2026-09-10', 1, 0, '', '2026-09-19 13:40:39'),
	(34, 5, 10, '2026-09-10', 1, 0, '', '2026-09-19 13:40:39'),
	(35, 4, 10, '2026-09-11', 0, 0, '', '2026-09-19 13:40:49'),
	(36, 5, 10, '2026-09-11', 1, 0, '', '2026-09-19 13:40:49'),
	(37, 4, 11, '2026-09-09', 1, 0, '', '2026-09-19 13:46:17'),
	(38, 5, 11, '2026-09-09', 1, 0, '', '2026-09-19 13:46:17');

-- Volcando estructura para tabla edunexo_db.avisos
CREATE TABLE IF NOT EXISTS `avisos` (
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

-- Volcando datos para la tabla edunexo_db.avisos: ~2 rows (aproximadamente)
INSERT INTO `avisos` (`id`, `curso_materia_docente_id`, `titulo`, `descripcion`, `fecha_aviso`, `aplica_a_todos`, `created_at`) VALUES
	(1, 10, 'Dibujo con punto de fuga (trabajo)', 'Se hizo este trabajo aaa test', '2026-09-14', 1, '2026-09-16 22:35:32'),
	(2, 10, 'Primer Parcial de 20pts', 'Tema Central: "El Arte como Medio de Expresión y Crítica Social"', '2026-09-07', 1, '2026-09-16 22:46:10'),
	(3, 11, 'Aviso E. Fisica', 'se realizaron ejercicios dentro de clases por la lluvia, no hubo deporte al aire libre', '2026-09-09', 1, '2026-09-19 13:47:28');

-- Volcando estructura para tabla edunexo_db.aviso_estudiante
CREATE TABLE IF NOT EXISTS `aviso_estudiante` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aviso_id` int NOT NULL,
  `estudiante_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aviso_estudiante` (`aviso_id`,`estudiante_id`),
  KEY `fk_aviso_est_estudiante` (`estudiante_id`),
  CONSTRAINT `fk_aviso_est_aviso` FOREIGN KEY (`aviso_id`) REFERENCES `avisos` (`id`),
  CONSTRAINT `fk_aviso_est_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.aviso_estudiante: ~0 rows (aproximadamente)

-- Volcando estructura para tabla edunexo_db.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_colegio` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_wa_remitente` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instancia_evolution` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `directorio_evolution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'C:\\laragon\\www\\evolution-api',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.configuracion: ~1 rows (aproximadamente)
INSERT INTO `configuracion` (`id`, `nombre_colegio`, `telefono_wa_remitente`, `instancia_evolution`, `directorio_evolution`, `updated_at`) VALUES
	(1, 'Centro Educativo La Amistad', '595971366877', 'edunexo', 'C:\\laragon\\www\\evolution-api', '2026-09-08 22:32:15');

-- Volcando estructura para tabla edunexo_db.cursos
CREATE TABLE IF NOT EXISTS `cursos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `turno` enum('manana','tarde','noche') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'manana',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cursos_nombre_turno` (`nombre`,`turno`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.cursos: ~3 rows (aproximadamente)
INSERT INTO `cursos` (`id`, `nombre`, `turno`, `activo`, `created_at`) VALUES
	(1, '8vo Grado', 'manana', 1, '2026-08-31 21:22:29'),
	(2, '6to Grado', 'manana', 1, '2026-08-31 21:22:29'),
	(4, '9no Grado', 'manana', 1, '2026-09-15 22:06:49');

-- Volcando estructura para tabla edunexo_db.curso_materia_docente
CREATE TABLE IF NOT EXISTS `curso_materia_docente` (
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

-- Volcando datos para la tabla edunexo_db.curso_materia_docente: ~8 rows (aproximadamente)
INSERT INTO `curso_materia_docente` (`id`, `curso_id`, `materia_id`, `docente_id`, `activo`, `created_at`, `updated_at`) VALUES
	(1, 2, 1, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(2, 2, 2, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(3, 2, 3, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(4, 2, 4, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(5, 2, 5, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(6, 2, 6, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(7, 2, 7, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(8, 2, 8, 1, 1, '2026-08-31 21:53:16', '2026-08-31 21:53:16'),
	(10, 4, 7, 3, 1, '2026-09-15 22:22:14', '2026-09-15 22:22:14'),
	(11, 4, 6, 4, 1, '2026-09-19 13:45:40', '2026-09-19 13:45:40');

-- Volcando estructura para tabla edunexo_db.envios_wa
CREATE TABLE IF NOT EXISTS `envios_wa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporte_id` int DEFAULT NULL,
  `estudiante_id` int NOT NULL,
  `periodo_semana` date NOT NULL,
  `destinatario_telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','enviado','entregado','error') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `retenido` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_hora_envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_envio_alumno_semana_tutor` (`estudiante_id`,`periodo_semana`,`destinatario_telefono`),
  KEY `fk_envio_reporte` (`reporte_id`),
  CONSTRAINT `fk_envio_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_envio_reporte` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.envios_wa: ~2 rows (aproximadamente)
INSERT INTO `envios_wa` (`id`, `reporte_id`, `estudiante_id`, `periodo_semana`, `destinatario_telefono`, `estado`, `retenido`, `fecha_hora_envio`) VALUES
	(4, 4, 4, '2026-09-14', '595985700559', 'enviado', 0, '2026-09-16 23:00:56'),
	(7, 7, 4, '2026-09-07', '595985700559', 'enviado', 0, '2026-09-23 22:00:24');

-- Volcando estructura para tabla edunexo_db.estudiantes
CREATE TABLE IF NOT EXISTS `estudiantes` (
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

-- Volcando datos para la tabla edunexo_db.estudiantes: ~5 rows (aproximadamente)
INSERT INTO `estudiantes` (`id`, `ci`, `nombre_completo`, `curso`, `curso_id`, `estado`, `created_at`, `updated_at`) VALUES
	(1, '7123456', 'María López', '8vo Grado', 1, 'activo', '2026-08-24 21:51:38', '2026-08-31 21:23:57'),
	(2, '7123457', 'Carlos López', '6to Grado', 2, 'activo', '2026-08-24 21:51:38', '2026-08-31 21:23:57'),
	(3, '6784217', 'Ramon Alvarez', '6to Grado', 2, 'activo', '2026-09-03 22:40:29', '2026-09-03 22:40:29'),
	(4, '7097278', 'Alejandra Isabel Silva Gonzalez', '9no Grado', 4, 'activo', '2026-09-15 22:44:34', '2026-09-15 22:44:34'),
	(5, '7456321', 'Miguel Sander Bareiro Ortiz', '9no Grado', 4, 'activo', '2026-09-15 22:45:42', '2026-09-15 22:45:42');

-- Volcando estructura para tabla edunexo_db.evaluaciones
CREATE TABLE IF NOT EXISTS `evaluaciones` (
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

-- Volcando datos para la tabla edunexo_db.evaluaciones: ~3 rows (aproximadamente)
INSERT INTO `evaluaciones` (`id`, `curso_materia_docente_id`, `tipo_evaluacion_id`, `titulo`, `fecha`, `puntaje_maximo`, `descripcion`, `created_at`) VALUES
	(2, 7, 4, 'Dibujo con punto de fuga (que se yo)', '2026-09-07', 10.00, '', '2026-08-31 22:12:18'),
	(4, 10, 4, 'Dibujo con punto de fuga (que se yo)', '2026-09-14', 10.00, '', '2026-09-16 22:32:46'),
	(5, 10, 2, 'El Arte como Medio de Expresión y Crítica Social', '2026-09-07', 20.00, 'Este tema permite integrar la teoría, la historia del arte y la práctica creativa, conectando a los estudiantes con su entorno. \r\nParte 1: Selección Múltiple y Verdadero/Falso (20%):\r\n\r\nConceptos clave sobre técnicas artísticas, movimientos muralistas y elementos visuales.\r\n\r\nParte 2: Análisis de Imagen (30%):\r\n\r\nMostrar una obra de arte urbano o un mural famoso y pedir al estudiante que identifique el mensaje principal, los colores predominantes y la emoción que transmite.\r\n\r\nParte 3: Producción Práctica / Boceto (50%):\r\n\r\nConsigna: "Diseña el boceto de un mural escolar o comunitario que promueva un valor social (por ejemplo: el cuidado del medio ambiente, la inclusión, la paz o la expresión juvenil). Utiliza la teoría del color aprendida en clase para resaltar el mensaje central."', '2026-09-16 22:41:22');

-- Volcando estructura para tabla edunexo_db.logs_validacion
CREATE TABLE IF NOT EXISTS `logs_validacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `telefono_intentado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resultado` tinyint(1) NOT NULL,
  `fecha_intento` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  CONSTRAINT `logs_validacion_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.logs_validacion: ~9 rows (aproximadamente)
INSERT INTO `logs_validacion` (`id`, `estudiante_id`, `telefono_intentado`, `resultado`, `fecha_intento`) VALUES
	(1, 4, '595981376043', 1, '2026-09-15 23:41:29'),
	(2, 4, '595985700559', 1, '2026-09-15 23:44:18'),
	(3, 4, '595985700559', 1, '2026-09-15 23:44:19'),
	(4, 4, '595985700559', 1, '2026-09-16 22:35:58'),
	(5, 4, '595985700559', 1, '2026-09-16 22:54:18'),
	(6, 4, '595985700559', 1, '2026-09-16 22:54:57'),
	(7, 4, '595985700559', 1, '2026-09-16 22:55:29'),
	(8, 4, '595985700559', 1, '2026-09-16 22:56:31'),
	(9, 4, '595985700559', 1, '2026-09-16 23:00:54'),
	(10, 4, '595985700559', 1, '2026-09-19 13:47:57'),
	(11, 4, '595985700559', 1, '2026-09-19 13:48:12'),
	(12, 4, '595985700559', 1, '2026-09-23 22:00:19');

-- Volcando estructura para tabla edunexo_db.materias
CREATE TABLE IF NOT EXISTS `materias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_materias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.materias: ~15 rows (aproximadamente)
INSERT INTO `materias` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
	(1, 'Matematica', NULL, 1, '2026-08-31 21:22:29'),
	(2, 'Literatura', NULL, 1, '2026-08-31 21:22:29'),
	(3, 'Ciencias', NULL, 1, '2026-08-31 21:22:29'),
	(4, 'Ciencias Sociales', NULL, 0, '2026-08-31 21:22:29'),
	(5, 'Ingles', NULL, 1, '2026-08-31 21:22:29'),
	(6, 'Educacion Fisica', NULL, 1, '2026-08-31 21:22:29'),
	(7, 'Artes', NULL, 1, '2026-08-31 21:22:29'),
	(8, 'Informatica', NULL, 1, '2026-08-31 21:22:29'),
	(9, 'Trabajo y Tecnologia', NULL, 1, '2026-09-15 22:12:33'),
	(10, 'Orientacion', NULL, 1, '2026-09-15 22:12:43'),
	(11, 'Musica', NULL, 1, '2026-09-15 22:12:51'),
	(12, 'Proyecto', NULL, 1, '2026-09-15 22:12:58'),
	(13, 'Historia', NULL, 1, '2026-09-15 22:13:06'),
	(14, 'Salud', NULL, 1, '2026-09-15 22:13:13'),
	(15, 'Etica', NULL, 1, '2026-09-15 22:13:46');

-- Volcando estructura para tabla edunexo_db.notas
CREATE TABLE IF NOT EXISTS `notas` (
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

-- Volcando datos para la tabla edunexo_db.notas: ~5 rows (aproximadamente)
INSERT INTO `notas` (`id`, `evaluacion_id`, `estudiante_id`, `puntaje_obtenido`, `observacion`, `created_at`, `updated_at`) VALUES
	(1, 2, 2, 8.50, '', '2026-08-31 22:13:07', '2026-08-31 22:13:07'),
	(2, 4, 4, 9.00, 'pq tuvo q prestar materiales idk', '2026-09-16 22:33:27', '2026-09-16 22:33:27'),
	(3, 4, 5, 8.50, 'falto pulcritud ni idea', '2026-09-16 22:33:27', '2026-09-16 22:33:27'),
	(4, 5, 4, 20.00, 'Excelente Trabajo!!!!', '2026-09-16 22:44:13', '2026-09-16 22:44:13'),
	(5, 5, 5, 20.00, '', '2026-09-16 22:44:13', '2026-09-16 22:44:13');

-- Volcando estructura para tabla edunexo_db.reportes
CREATE TABLE IF NOT EXISTS `reportes` (
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reporte_docente_semana` (`estudiante_id`,`usuario_id`,`periodo_semana`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.reportes: ~3 rows (aproximadamente)
INSERT INTO `reportes` (`id`, `estudiante_id`, `usuario_id`, `periodo_semana`, `calificacion_general`, `dias_ausente`, `tareas_incompletas`, `comportamiento`, `incidentes_disciplinarios`, `created_at`, `updated_at`) VALUES
	(4, 4, 3, '2026-09-14', 'Logrado', 0, 1, 'Excelente', 'Hola esto es un test 19:34', '2026-09-16 22:34:26', '2026-09-16 22:34:26'),
	(6, 4, 3, '2026-09-07', 'Logrado', 1, 0, 'Bueno', '', '2026-09-19 13:43:14', '2026-09-19 13:43:14'),
	(7, 4, 4, '2026-09-07', 'Logrado', 1, 0, 'Bueno', '', '2026-09-19 13:46:45', '2026-09-19 13:46:45');

-- Volcando estructura para tabla edunexo_db.solicitudes_cambio_reportes
CREATE TABLE IF NOT EXISTS `solicitudes_cambio_reportes` (
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

-- Volcando datos para la tabla edunexo_db.solicitudes_cambio_reportes: ~0 rows (aproximadamente)

-- Volcando estructura para tabla edunexo_db.tipo_evaluacion
CREATE TABLE IF NOT EXISTS `tipo_evaluacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `peso_porcentaje` decimal(5,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipo_evaluacion_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.tipo_evaluacion: ~4 rows (aproximadamente)
INSERT INTO `tipo_evaluacion` (`id`, `nombre`, `peso_porcentaje`, `activo`) VALUES
	(1, 'Trabajo Practico', 20.00, 1),
	(2, 'Examen Parcial', 35.00, 1),
	(3, 'Examen Final', 35.00, 1),
	(4, 'Tarea', 10.00, 1);

-- Volcando estructura para tabla edunexo_db.tutores
CREATE TABLE IF NOT EXISTS `tutores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.tutores: ~3 rows (aproximadamente)
INSERT INTO `tutores` (`id`, `nombre_completo`, `telefono`, `activo`, `created_at`, `updated_at`) VALUES
	(1, 'Roberto López', '595981234567', 1, '2026-08-24 21:51:38', '2026-08-24 21:51:38'),
	(2, 'Gilberto Mora', '595964675832', 1, '2026-09-03 22:39:57', '2026-09-03 22:39:57'),
	(3, 'Juan Angel Silva Amarilla', '595985700559', 1, '2026-09-15 22:43:59', '2026-09-15 23:43:17');

-- Volcando estructura para tabla edunexo_db.tutores_estudiantes
CREATE TABLE IF NOT EXISTS `tutores_estudiantes` (
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

-- Volcando datos para la tabla edunexo_db.tutores_estudiantes: ~5 rows (aproximadamente)
INSERT INTO `tutores_estudiantes` (`id`, `tutor_id`, `estudiante_id`, `created_at`) VALUES
	(1, 1, 1, '2026-08-24 21:51:38'),
	(2, 1, 2, '2026-08-24 21:51:38'),
	(3, 2, 3, '2026-09-03 22:40:29'),
	(4, 3, 4, '2026-09-15 22:44:34'),
	(5, 2, 5, '2026-09-15 22:45:42');

-- Volcando estructura para tabla edunexo_db.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
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

-- Volcando datos para la tabla edunexo_db.usuarios: ~4 rows (aproximadamente)
INSERT INTO `usuarios` (`id`, `nombre`, `username`, `password`, `email`, `rol`, `created_at`, `updated_at`, `activo`) VALUES
	(1, 'Prof. Juan Pérez', 'jperez', '$2y$10$aq/6O1THxAVg3xkzUeJBZeigcf9DI43IDgYHVZdLyc8Z6hNDLyEzy', NULL, 'docente', '2026-08-24 21:51:37', '2026-09-30 22:14:03', 1),
	(2, 'Rodney Fabian Farinha De Leon', 'Fabian03', '$2y$10$HF92BF3ScKMAzTil/O1wXOzaV/5cTdexTV3AcYDMK4gMRMGpikt1e', 'rodneyfabian2@gmail.com', 'admin', '2026-08-25 21:53:08', '2026-08-31 21:44:23', 1),
	(3, 'Javier Britez', 'javierbritez', '$2y$10$lTyJUVz1Jfg1zRGw4pAd4O8N.YpHohGttOCwYt9RxiTitWHclY81G', 'javibritez@gmail.com', 'docente', '2026-09-15 22:21:00', '2026-10-07 21:57:52', 1),
	(4, 'Ariel Escobar', 'arielescobar', '$2y$10$atmx5g9mA3SYb8/4h4.IWuxE8BuAWO7NNajX.fiAD50KorhWjSm6S', 'arielescobar@gmail.com', 'docente', '2026-09-19 13:45:24', '2026-09-19 13:45:24', 1);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
