-- Agrupa los envios de WhatsApp por alumno + semana + telefono del tutor, en lugar de por
-- reporte individual. Antes, si dos docentes (por ejemplo el de Matematica y el de Ingles)
-- cargaban su reporte semanal del mismo estudiante, el tutor recibia DOS mensajes casi iguales
-- (la misma asistencia, distinta calificacion). Ahora se manda UN SOLO mensaje que incluye el
-- aporte de todos los docentes de esa semana.
--
-- Es seguro ejecutarla mas de una vez.
-- Ejecutar sobre la base edunexo_db.

-- ───────────────────────────────────────────────────────────────────────────
-- PARTE 1: reportes — evitar que el MISMO docente cargue dos reportes del
-- mismo estudiante en la misma semana (no impide que dos docentes DISTINTOS
-- reporten al mismo estudiante: eso es justamente lo que ahora se agrupa).
-- ───────────────────────────────────────────────────────────────────────────

-- Antes de agregar la restriccion, hay que saber si ya existen duplicados.
-- Si esta consulta devuelve filas, resolvelas a mano (editando o borrando el
-- reporte sobrante) y volve a correr este script: la restriccion no se agrega
-- hasta que no haya duplicados.
SELECT estudiante_id, usuario_id,
       DATE_SUB(periodo_semana, INTERVAL WEEKDAY(periodo_semana) DAY) AS semana,
       COUNT(*) AS cantidad, GROUP_CONCAT(id ORDER BY id) AS ids_reportes
FROM reportes
GROUP BY estudiante_id, usuario_id, DATE_SUB(periodo_semana, INTERVAL WEEKDAY(periodo_semana) DAY)
HAVING COUNT(*) > 1;

SET @hay_duplicados_reportes = (
    SELECT COUNT(*) FROM (
        SELECT 1
        FROM reportes
        GROUP BY estudiante_id, usuario_id, DATE_SUB(periodo_semana, INTERVAL WEEKDAY(periodo_semana) DAY)
        HAVING COUNT(*) > 1
    ) dup
);
SET @existe_uq_reportes = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reportes' AND INDEX_NAME = 'uq_reporte_docente_semana'
);
SET @sql = IF(
    @hay_duplicados_reportes > 0,
    'SELECT "AVISO: hay reportes duplicados (mismo docente, mismo estudiante, misma semana). Resolvelos y volve a correr este script; por ahora NO se agrego la restriccion." AS info',
    IF(@existe_uq_reportes = 0,
       'ALTER TABLE reportes ADD UNIQUE KEY uq_reporte_docente_semana (estudiante_id, usuario_id, periodo_semana)',
       'SELECT "La restriccion uq_reporte_docente_semana ya existe" AS info')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ───────────────────────────────────────────────────────────────────────────
-- PARTE 2: envios_wa — agregar las columnas de agrupacion (estudiante_id,
-- periodo_semana) y completarlas para las filas que ya existen, tomando el
-- estudiante y la semana (normalizada al lunes) del reporte que las origino.
-- ───────────────────────────────────────────────────────────────────────────

SET @existe_col_estudiante = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND COLUMN_NAME = 'estudiante_id'
);
SET @sql = IF(@existe_col_estudiante = 0,
    'ALTER TABLE envios_wa ADD COLUMN estudiante_id INT NULL AFTER reporte_id',
    'SELECT "La columna estudiante_id ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_col_semana = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND COLUMN_NAME = 'periodo_semana'
);
SET @sql = IF(@existe_col_semana = 0,
    'ALTER TABLE envios_wa ADD COLUMN periodo_semana DATE NULL AFTER estudiante_id',
    'SELECT "La columna periodo_semana ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill: solo toca las filas que todavia no tienen estudiante_id (o sea, es
-- seguro repetir esta parte tantas veces como se quiera).
UPDATE envios_wa ew
JOIN reportes r ON r.id = ew.reporte_id
SET ew.estudiante_id = r.estudiante_id,
    ew.periodo_semana = DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY)
WHERE ew.estudiante_id IS NULL;

-- Si quedara alguna fila sin poder completarse (el reporte que la origino ya
-- no existe), no hay forma de saber a que alumno/semana pertenecia: se borra,
-- ya que es un envio huerfano que nunca se podria volver a generar.
DELETE FROM envios_wa WHERE estudiante_id IS NULL;

-- PARTE 2.5: envios_wa -- fusionar duplicados. Antes de este cambio, cada
-- reporte generaba su propio envio: si dos docentes ya le habian reportado al
-- mismo estudiante la misma semana, quedaron dos (o mas) filas en envios_wa
-- con el mismo estudiante+semana+telefono. Ahora debe quedar una sola por
-- combinacion, asi que se fusionan: se conserva la de estado mas avanzado
-- (entregado > enviado > error > pendiente) y, entre iguales, la mas nueva.
-- Es seguro repetir esta parte: si ya no hay duplicados, no borra nada.

-- Muestra los grupos duplicados que se van a fusionar, solo a modo informativo.
SELECT estudiante_id, periodo_semana, destinatario_telefono, COUNT(*) AS cantidad,
       GROUP_CONCAT(CONCAT(id,':',estado) ORDER BY id) AS envios
FROM envios_wa
GROUP BY estudiante_id, periodo_semana, destinatario_telefono
HAVING COUNT(*) > 1;

DELETE ew1 FROM envios_wa ew1
JOIN envios_wa ew2
  ON ew1.estudiante_id = ew2.estudiante_id
 AND ew1.periodo_semana = ew2.periodo_semana
 AND ew1.destinatario_telefono = ew2.destinatario_telefono
 AND ew1.id <> ew2.id
WHERE FIELD(ew2.estado, 'entregado', 'enviado', 'error', 'pendiente')
      < FIELD(ew1.estado, 'entregado', 'enviado', 'error', 'pendiente')
   OR (FIELD(ew2.estado, 'entregado', 'enviado', 'error', 'pendiente')
       = FIELD(ew1.estado, 'entregado', 'enviado', 'error', 'pendiente')
       AND ew2.id > ew1.id);

-- ───────────────────────────────────────────────────────────────────────────
-- PARTE 3: envios_wa — reporte_id deja de ser obligatorio y de borrar en
-- cascada. De ahora en mas UN envio puede reunir varios reportes (de varios
-- docentes); reporte_id solo queda como referencia del ultimo que lo toco. Si
-- ese reporte puntual se borra, el envio no debe desaparecer (los demas
-- reportes de esa semana siguen ahi).
-- ───────────────────────────────────────────────────────────────────────────

SET @fk_reporte = (
    SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND COLUMN_NAME = 'reporte_id'
          AND REFERENCED_TABLE_NAME = 'reportes' LIMIT 1
);
SET @sql = IF(@fk_reporte IS NOT NULL,
    CONCAT('ALTER TABLE envios_wa DROP FOREIGN KEY `', @fk_reporte, '`'),
    'SELECT "No hay una FK de reporte_id que quitar (ya se habia quitado antes)" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @reporte_id_es_nullable = (
    SELECT IS_NULLABLE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND COLUMN_NAME = 'reporte_id'
);
SET @sql = IF(@reporte_id_es_nullable = 'NO',
    'ALTER TABLE envios_wa MODIFY reporte_id INT NULL',
    'SELECT "reporte_id ya acepta NULL" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_fk_set_null = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND CONSTRAINT_NAME = 'fk_envio_reporte'
);
SET @sql = IF(@existe_fk_set_null = 0,
    'ALTER TABLE envios_wa ADD CONSTRAINT fk_envio_reporte FOREIGN KEY (reporte_id) REFERENCES reportes(id) ON DELETE SET NULL',
    'SELECT "La FK fk_envio_reporte ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ───────────────────────────────────────────────────────────────────────────
-- PARTE 4: envios_wa — estudiante_id y periodo_semana pasan a ser
-- obligatorios, con su propia FK, y un solo envio por alumno+semana+telefono
-- (la clave que evita mandar dos mensajes por la misma semana).
-- ───────────────────────────────────────────────────────────────────────────

SET @sql = IF(@existe_col_estudiante = 0 OR (SELECT COUNT(*) FROM envios_wa WHERE estudiante_id IS NULL) = 0,
    'ALTER TABLE envios_wa MODIFY estudiante_id INT NOT NULL, MODIFY periodo_semana DATE NOT NULL',
    'SELECT "Quedan filas sin estudiante_id: revisar antes de continuar" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_fk_estudiante = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND CONSTRAINT_NAME = 'fk_envio_estudiante'
);
SET @sql = IF(@existe_fk_estudiante = 0,
    'ALTER TABLE envios_wa ADD CONSTRAINT fk_envio_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE',
    'SELECT "La FK fk_envio_estudiante ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_uq_envio = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'envios_wa' AND INDEX_NAME = 'uq_envio_alumno_semana_tutor'
);
SET @sql = IF(@existe_uq_envio = 0,
    'ALTER TABLE envios_wa ADD UNIQUE KEY uq_envio_alumno_semana_tutor (estudiante_id, periodo_semana, destinatario_telefono)',
    'SELECT "La restriccion uq_envio_alumno_semana_tutor ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
