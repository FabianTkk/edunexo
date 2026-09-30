-- migration_notas_restrict.sql
-- Urgente 3 de la auditoria: fk_notas_evaluacion estaba en ON DELETE CASCADE, asi que borrar una
-- evaluacion borraba en silencio todas sus notas. Se cambia a RESTRICT: la base ya no deja borrar una
-- evaluacion que tenga notas, y DocenteEvaluacionesController::delete() muestra el mensaje claro.
--
-- Idempotente: se puede ejecutar varias veces. Solo toca la clave si hoy esta en CASCADE.
-- Hacer mysqldump antes de ejecutar:  mysqldump -u root edunexo_db > backup_antes_de_notas_restrict.sql

USE edunexo_db;

SET @regla := (
    SELECT DELETE_RULE
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notas'
      AND CONSTRAINT_NAME = 'fk_notas_evaluacion'
);

-- Paso 1: quitar la clave vieja (solo si esta en CASCADE).
SET @sql := IF(@regla = 'CASCADE',
    'ALTER TABLE notas DROP FOREIGN KEY fk_notas_evaluacion',
    'SELECT ''fk_notas_evaluacion ya no esta en CASCADE (o no existe): no se cambia nada'' AS aviso');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 2: crearla de nuevo con RESTRICT (solo si el paso 1 la quito).
SET @sql := IF(@regla = 'CASCADE',
    'ALTER TABLE notas ADD CONSTRAINT fk_notas_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id) ON DELETE RESTRICT',
    'SELECT ''sin cambios'' AS aviso');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificacion: DELETE_RULE debe decir RESTRICT.
SELECT CONSTRAINT_NAME, DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'notas' AND CONSTRAINT_NAME = 'fk_notas_evaluacion';
