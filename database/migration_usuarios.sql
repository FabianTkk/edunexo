-- ═══════════════════════════════════════════════════════════════════════════
-- EduNexo — Migración de tabla usuarios
-- Ejecutar en phpMyAdmin o MySQL CLI antes de usar el registro
-- ═══════════════════════════════════════════════════════════════════════════

-- Opción A: Crear tabla desde cero (si no existe)
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  UNIQUE DEFAULT NULL,
    username   VARCHAR(50)   NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    rol        ENUM('admin','docente') NOT NULL DEFAULT 'docente',
    activo     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────────────────
-- Opción B: Agregar columnas faltantes a una tabla existente
-- (Ejecutá solo las líneas que correspondan)
-- ───────────────────────────────────────────────────────────────────────────

-- Agregar email si no existe:
-- ALTER TABLE usuarios ADD COLUMN email VARCHAR(150) UNIQUE DEFAULT NULL AFTER nombre;

-- Agregar activo si no existe:
-- ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER rol;

-- Agregar created_at si no existe:
-- ALTER TABLE usuarios ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER activo;

-- ───────────────────────────────────────────────────────────────────────────
-- Crear usuario admin de prueba (contraseña: Admin123)
-- IMPORTANTE: Cambiá la contraseña después del primer acceso
-- ───────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO usuarios (nombre, email, username, password, rol, activo)
VALUES (
    'Administrador',
    'admin@edunexo.local',
    'admin',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin123
    'admin',
    1
);
