-- Agregar prima_trimestral y prima_semestral a policies (MySQL 5.x compatible)
SET @dbname = DATABASE();

-- Agregar prima_trimestral si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'policies' AND COLUMN_NAME = 'prima_trimestral') = 0,
    'ALTER TABLE policies ADD COLUMN prima_trimestral DECIMAL(15,2) NULL AFTER prima_mensual',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar prima_semestral si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'policies' AND COLUMN_NAME = 'prima_semestral') = 0,
    'ALTER TABLE policies ADD COLUMN prima_semestral DECIMAL(15,2) NULL AFTER prima_trimestral',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
