-- Agrega baja lógica para tutores en instalaciones existentes.
ALTER TABLE tutores
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER telefono;
