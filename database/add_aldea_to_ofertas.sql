-- Agregar columna aldea_id a la tabla oferta_academica si no existe
-- Esta columna es necesaria para filtrar ofertas por aldea para coordinadores

-- Verificar si la columna existe y agregarla si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'oferta_academica' 
AND column_name = 'aldea_id';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE oferta_academica ADD COLUMN aldea_id INT NULL AFTER trimestre_id, ADD FOREIGN KEY (aldea_id) REFERENCES aldeas(id)', 
    'SELECT "Column aldea_id already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;