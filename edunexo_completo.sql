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

-- Volcando estructura para tabla edunexo_db.cursos
CREATE TABLE IF NOT EXISTS `cursos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `turno` enum('manana','tarde','noche') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'manana',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cursos_nombre_turno` (`nombre`,`turno`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.cursos: ~2 rows (aproximadamente)
INSERT INTO `cursos` (`id`, `nombre`, `turno`, `activo`, `created_at`) VALUES
	(1, '8vo Grado', 'manana', 1, '2026-08-31 21:22:29'),
	(2, '6to Grado', 'manana', 1, '2026-08-31 21:22:29');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.curso_materia_docente: ~0 rows (aproximadamente)

-- Volcando estructura para tabla edunexo_db.envios_wa
CREATE TABLE IF NOT EXISTS `envios_wa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporte_id` int NOT NULL,
  `destinatario_telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','enviado','entregado','error') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `fecha_hora_envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reporte_id` (`reporte_id`),
  CONSTRAINT `envios_wa_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.envios_wa: ~0 rows (aproximadamente)

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.estudiantes: ~2 rows (aproximadamente)
INSERT INTO `estudiantes` (`id`, `ci`, `nombre_completo`, `curso`, `curso_id`, `estado`, `created_at`, `updated_at`) VALUES
	(1, '7123456', 'María López', '8vo Grado', 1, 'activo', '2026-08-24 21:51:38', '2026-08-31 21:23:57'),
	(2, '7123457', 'Carlos López', '6to Grado', 2, 'activo', '2026-08-24 21:51:38', '2026-08-31 21:23:57');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.evaluaciones: ~0 rows (aproximadamente)

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.logs_validacion: ~0 rows (aproximadamente)

-- Volcando estructura para tabla edunexo_db.materias
CREATE TABLE IF NOT EXISTS `materias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_materias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.materias: ~8 rows (aproximadamente)
INSERT INTO `materias` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
	(1, 'Matematica', NULL, 1, '2026-08-31 21:22:29'),
	(2, 'Lengua y Literatura', NULL, 1, '2026-08-31 21:22:29'),
	(3, 'Ciencias Naturales', NULL, 1, '2026-08-31 21:22:29'),
	(4, 'Ciencias Sociales', NULL, 1, '2026-08-31 21:22:29'),
	(5, 'Ingles', NULL, 1, '2026-08-31 21:22:29'),
	(6, 'Educacion Fisica', NULL, 1, '2026-08-31 21:22:29'),
	(7, 'Arte', NULL, 1, '2026-08-31 21:22:29'),
	(8, 'Informatica', NULL, 1, '2026-08-31 21:22:29');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.notas: ~0 rows (aproximadamente)

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
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.reportes: ~0 rows (aproximadamente)

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.tutores: ~1 rows (aproximadamente)
INSERT INTO `tutores` (`id`, `nombre_completo`, `telefono`, `created_at`, `updated_at`) VALUES
	(1, 'Roberto López', '595981234567', '2026-08-24 21:51:38', '2026-08-24 21:51:38');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.tutores_estudiantes: ~2 rows (aproximadamente)
INSERT INTO `tutores_estudiantes` (`id`, `tutor_id`, `estudiante_id`, `created_at`) VALUES
	(1, 1, 1, '2026-08-24 21:51:38'),
	(2, 1, 2, '2026-08-24 21:51:38');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla edunexo_db.usuarios: ~2 rows (aproximadamente)
INSERT INTO `usuarios` (`id`, `nombre`, `username`, `password`, `email`, `rol`, `created_at`, `updated_at`, `activo`) VALUES
	(1, 'Prof. Juan Pérez', 'jperez', '$2y$10$aq/6O1THxAVg3xkzUeJBZeigcf9DI43IDgYHVZdLyc8Z6hNDLyEzy', NULL, 'docente', '2026-08-24 21:51:37', '2026-08-24 23:47:35', 1),
	(2, 'Rodney Fabian Farinha De Leon', 'Fabian03', '$2y$12$IA3uDveJR0f1dhOBJlje3.ckC90OTR5JqDFbOIhibuoSofN6FIacy', 'rodneyfabian2@gmail.com', 'admin', '2026-08-25 21:53:08', '2026-08-25 21:54:13', 1);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
