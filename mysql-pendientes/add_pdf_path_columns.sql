-- Agregar columna pdf_path a submissions (compatible MySQL 5.x)
-- Si la columna ya existe, el error se ignora

SET @dbname = DATABASE();

-- Agregar pdf_path a submissions si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'submissions' AND COLUMN_NAME = 'pdf_path') = 0,
    'ALTER TABLE submissions ADD COLUMN pdf_path VARCHAR(500) DEFAULT NULL',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar pdf_path a cotizaciones si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'pdf_path') = 0,
    'ALTER TABLE cotizaciones ADD COLUMN pdf_path VARCHAR(500) DEFAULT NULL',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
