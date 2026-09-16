-- Ejecutar una sola vez sobre la base de datos actual de EduNexo.
-- Permite diferenciar faltas justificadas de ausencias no justificadas.
ALTER TABLE asistencias
    ADD COLUMN justificada TINYINT(1) NOT NULL DEFAULT 0 AFTER presente;
