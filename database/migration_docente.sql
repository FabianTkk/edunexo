-- Funcionalidades del panel docente: asistencia, avisos y calendario.
-- Es segura para ejecutar sobre una instalación existente.

CREATE TABLE IF NOT EXISTS asistencias (
    id INT NOT NULL AUTO_INCREMENT,
    estudiante_id INT NOT NULL,
    curso_materia_docente_id INT NOT NULL,
    fecha DATE NOT NULL,
    presente TINYINT(1) NOT NULL DEFAULT 1,
    observacion VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_asistencia_estudiante_materia_fecha (estudiante_id, curso_materia_docente_id, fecha),
    KEY idx_asistencia_materia_fecha (curso_materia_docente_id, fecha),
    CONSTRAINT fk_asistencia_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_asistencia_asignacion FOREIGN KEY (curso_materia_docente_id) REFERENCES curso_materia_docente(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS avisos (
    id INT NOT NULL AUTO_INCREMENT,
    curso_materia_docente_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    fecha_aviso DATE NOT NULL,
    aplica_a_todos TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_aviso_asignacion_fecha (curso_materia_docente_id, fecha_aviso),
    CONSTRAINT fk_aviso_asignacion FOREIGN KEY (curso_materia_docente_id) REFERENCES curso_materia_docente(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aviso_estudiante (
    id INT NOT NULL AUTO_INCREMENT,
    aviso_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_aviso_estudiante (aviso_id, estudiante_id),
    CONSTRAINT fk_aviso_estudiante_aviso FOREIGN KEY (aviso_id) REFERENCES avisos(id) ON DELETE CASCADE,
    CONSTRAINT fk_aviso_estudiante_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
