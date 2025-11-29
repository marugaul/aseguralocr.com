-- Agregar client_id a submissions (MySQL 5.x compatible)
SET @dbname = DATABASE();

-- Agregar client_id si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'submissions' AND COLUMN_NAME = 'client_id') = 0,
    'ALTER TABLE submissions ADD COLUMN client_id INT UNSIGNED NULL AFTER email',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índice si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'submissions' AND INDEX_NAME = 'idx_submissions_client_id') = 0,
    'ALTER TABLE submissions ADD INDEX idx_submissions_client_id (client_id)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
