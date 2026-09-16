-- Ejecutar una sola vez. Registra solicitudes de modificación de reportes vencidos.
CREATE TABLE IF NOT EXISTS solicitudes_cambio_reportes (
    id INT NOT NULL AUTO_INCREMENT,
    reporte_id INT NOT NULL,
    docente_id INT NOT NULL,
    motivo VARCHAR(500) NOT NULL,
    estado ENUM('pendiente','atendida','rechazada') NOT NULL DEFAULT 'pendiente',
    admin_id INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atendida_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_solicitud_estado (estado, created_at),
    KEY idx_solicitud_reporte (reporte_id),
    CONSTRAINT fk_solicitud_reporte FOREIGN KEY (reporte_id) REFERENCES reportes(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_docente FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_admin FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
