-- Crear tabla graduaciones para historial completo de graduaciones
CREATE TABLE graduaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estudiante_id INT NOT NULL,
    tipo_graduacion ENUM('TSU', 'Licenciado') NOT NULL,
    fecha_graduacion DATE NOT NULL,
    pnf_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (pnf_id) REFERENCES pnfs(id)
);

-- Índices para optimizar consultas
CREATE INDEX idx_graduaciones_estudiante ON graduaciones(estudiante_id);
CREATE INDEX idx_graduaciones_tipo ON graduaciones(tipo_graduacion);
CREATE INDEX idx_graduaciones_fecha ON graduaciones(fecha_graduacion);