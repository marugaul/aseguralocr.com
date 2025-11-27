-- Migration 003: Client Documents Table
-- Tabla para almacenar documentos asociados a clientes

CREATE TABLE IF NOT EXISTS client_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    policy_id INT NULL,

    -- Información del documento
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    tipo_documento ENUM('cedula', 'poliza', 'comprobante_pago', 'contrato', 'factura', 'otro') DEFAULT 'otro',

    -- Archivo
    archivo_nombre VARCHAR(255) NOT NULL,
    archivo_path VARCHAR(500) NOT NULL,
    archivo_size INT UNSIGNED NULL,
    archivo_mime VARCHAR(100) NULL,

    -- Metadata
    uploaded_by INT NULL COMMENT 'admin_id que subió el documento',
    notas TEXT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_client_documents_client (client_id),
    INDEX idx_client_documents_policy (policy_id),
    INDEX idx_client_documents_tipo (tipo_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para notas/historial del cliente
CREATE TABLE IF NOT EXISTS client_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    policy_id INT NULL,

    nota TEXT NOT NULL,
    tipo ENUM('general', 'llamada', 'email', 'visita', 'reclamo', 'renovacion', 'pago') DEFAULT 'general',

    created_by INT NULL COMMENT 'admin_id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE SET NULL,

    INDEX idx_client_notes_client (client_id),
    INDEX idx_client_notes_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
