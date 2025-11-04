-- Crear tabla intermedia materia_profesor
-- Esta tabla permite que un profesor enseñe múltiples materias

CREATE TABLE materia_profesor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profesor_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profesor_id) REFERENCES profesores(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_profesor_materia (profesor_id, materia_id)
);