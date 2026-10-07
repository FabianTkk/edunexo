-- migration_revision_envios.sql
-- Logica 4 y 5 de la auditoria:
--  * Revision previa: los mensajes que llevan texto libre de un docente (observaciones de notas e
--    incidentes disciplinarios) quedan retenidos hasta que el admin vea el mensaje y lo apruebe.
--    configuracion.revision_previa lo enciende o apaga (por defecto encendido).
--    envios_wa guarda la huella (sha1) del texto aprobado: si el contenido cambia despues, hay que aprobarlo de nuevo.
--  * Reporte editado despues del envio: ya no se reenvia solo. envios_wa.cambios_pendientes marca que
--    hubo cambios y el admin decide si manda una correccion corta (actualizado_en guarda cuando se mando).
--
-- Idempotente: se puede ejecutar varias veces.
-- Hacer mysqldump antes:  mysqldump -u root edunexo_db > backup_antes_de_revision_envios.sql

USE edunexo_db;

-- configuracion.revision_previa
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuracion' AND COLUMN_NAME = 'revision_previa') = 0,
    'ALTER TABLE configuracion ADD COLUMN revision_previa TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT ''configuracion.revision_previa ya existe'' AS aviso');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- envios_wa: aprobacion y cambios posteriores al envio (todo junto, en una sola sentencia)
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND COLUMN_NAME = 'cambios_pendientes') = 0,
    'ALTER TABLE envios_wa
        ADD COLUMN aprobado_hash CHAR(40) NULL,
        ADD COLUMN aprobado_en DATETIME NULL,
        ADD COLUMN aprobado_por INT NULL,
        ADD COLUMN cambios_pendientes TINYINT(1) NOT NULL DEFAULT 0,
        ADD COLUMN actualizado_en DATETIME NULL,
        ADD CONSTRAINT fk_envio_aprobador FOREIGN KEY (aprobado_por) REFERENCES usuarios (id) ON DELETE SET NULL',
    'SELECT ''envios_wa ya tiene las columnas nuevas'' AS aviso');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificacion: deben aparecer 5 columnas en envios_wa y 1 en configuracion.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME = 'envios_wa' AND COLUMN_NAME IN ('aprobado_hash','aprobado_en','aprobado_por','cambios_pendientes','actualizado_en'))
    OR (TABLE_NAME = 'configuracion' AND COLUMN_NAME = 'revision_previa'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;
