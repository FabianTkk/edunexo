-- Migracion: permite diferenciar faltas justificadas de ausencias no justificadas.
-- Es seguro ejecutarla mas de una vez: solo agrega la columna si no existe.
-- Ejecutar sobre la base edunexo_db.

SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'asistencias'
      AND COLUMN_NAME = 'justificada'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE asistencias ADD COLUMN justificada TINYINT(1) NOT NULL DEFAULT 0 AFTER presente',
    'SELECT ''La columna justificada ya existe'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
