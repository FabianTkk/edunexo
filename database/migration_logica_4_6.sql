-- migration_logica_4_6.sql
-- Puntos 4, 5 y 6 de "logica del proyecto" de la auditoria. Idempotente: se puede ejecutar varias veces.
--
--  envios_wa.retenido   0 = sale en el envio automatico del viernes; 1 = el admin lo retuvo y no sale
--                       hasta que lo libere o lo envie a mano (punto 4: revision antes de enviar).
--  reportes.updated_at  fecha de la ultima modificacion del reporte. Sirve para detectar reportes nuevos o
--                       editados DESPUES de que el mensaje ya salio, y ofrecer una rectificacion corta
--                       en vez de reenviar el mensaje completo (punto 5).
--
-- Hacer un respaldo antes:  mysqldump -u root edunexo_db > backup_antes_de_logica_4_6.sql
-- (ese archivo queda ignorado por git: backup_*.sql)

USE edunexo_db;

-- 1) envios_wa.retenido
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND COLUMN_NAME = 'retenido');
SET @sql := IF(@existe = 0,
    'ALTER TABLE envios_wa ADD COLUMN retenido TINYINT(1) NOT NULL DEFAULT 0 AFTER estado',
    'SELECT ''envios_wa.retenido ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) reportes.updated_at
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reportes' AND COLUMN_NAME = 'updated_at');
SET @sql := IF(@existe = 0,
    'ALTER TABLE reportes ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT ''reportes.updated_at ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Los reportes que ya existian quedan con la fecha de la migracion; se vuelven a su fecha de creacion
-- para que ninguno aparezca como "editado despues del envio" sin serlo. Asignar updated_at de forma
-- explicita evita que ON UPDATE lo pise. Solo corre si la columna se acaba de crear.
SET @sql := IF(@existe = 0,
    'UPDATE reportes SET updated_at = created_at',
    'SELECT ''sin cambios en reportes.updated_at'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verificacion: deben aparecer las dos columnas.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME = 'envios_wa' AND COLUMN_NAME = 'retenido')
    OR (TABLE_NAME = 'reportes'  AND COLUMN_NAME = 'updated_at'));
