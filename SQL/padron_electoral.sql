-- Tabla de distritos electorales (para relacionar códigos con nombres)
CREATE TABLE IF NOT EXISTS `padron_distritos` (
    `codelec` VARCHAR(6) NOT NULL PRIMARY KEY,
    `provincia` VARCHAR(20) NOT NULL,
    `canton` VARCHAR(30) NOT NULL,
    `distrito` VARCHAR(40) NOT NULL,
    INDEX `idx_provincia` (`provincia`),
    INDEX `idx_canton` (`canton`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla principal del padrón electoral
CREATE TABLE IF NOT EXISTS `padron_electoral` (
    `cedula` VARCHAR(9) NOT NULL PRIMARY KEY,
    `codelec` VARCHAR(6) NOT NULL,
    `fecha_vencimiento` DATE NULL,
    `junta` VARCHAR(5) NULL,
    `nombre` VARCHAR(30) NOT NULL,
    `primer_apellido` VARCHAR(26) NOT NULL,
    `segundo_apellido` VARCHAR(26) NOT NULL,
    `nombre_completo` VARCHAR(85) GENERATED ALWAYS AS (
        CONCAT(TRIM(nombre), ' ', TRIM(primer_apellido), ' ', TRIM(segundo_apellido))
    ) STORED,
    INDEX `idx_codelec` (`codelec`),
    INDEX `idx_nombre_completo` (`nombre_completo`),
    INDEX `idx_apellidos` (`primer_apellido`, `segundo_apellido`),
    FOREIGN KEY (`codelec`) REFERENCES `padron_distritos`(`codelec`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de control para actualizaciones
CREATE TABLE IF NOT EXISTS `padron_actualizaciones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `registros_importados` INT NOT NULL DEFAULT 0,
    `archivo_origen` VARCHAR(255),
    `usuario` VARCHAR(100),
    `duracion_segundos` INT,
    `estado` ENUM('iniciado', 'completado', 'error') DEFAULT 'iniciado',
    `mensaje` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
