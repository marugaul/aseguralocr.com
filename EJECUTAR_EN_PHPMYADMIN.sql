-- ============================================
-- EJECUTAR ESTE SQL EN PHPMYADMIN
-- Agrega columna pdf_path para mostrar PDFs en dashboard
-- ============================================

-- Agregar columna pdf_path a submissions
ALTER TABLE submissions
ADD COLUMN pdf_path VARCHAR(500) DEFAULT NULL AFTER payload;

-- Agregar columna pdf_path a cotizaciones
ALTER TABLE cotizaciones
ADD COLUMN pdf_path VARCHAR(500) DEFAULT NULL AFTER payload;

-- Crear índices para búsquedas rápidas
CREATE INDEX idx_submissions_pdf_path ON submissions(pdf_path);
CREATE INDEX idx_cotizaciones_pdf_path ON cotizaciones(pdf_path);

-- Verificar que se crearon correctamente
SHOW COLUMNS FROM submissions LIKE 'pdf_path';
SHOW COLUMNS FROM cotizaciones LIKE 'pdf_path';
