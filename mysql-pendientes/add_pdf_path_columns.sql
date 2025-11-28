-- Agregar columna pdf_path a submissions
ALTER TABLE submissions
ADD COLUMN IF NOT EXISTS pdf_path VARCHAR(500) DEFAULT NULL AFTER payload;

-- Agregar columna pdf_path a cotizaciones
ALTER TABLE cotizaciones
ADD COLUMN IF NOT EXISTS pdf_path VARCHAR(500) DEFAULT NULL AFTER payload;

-- Crear índice para búsquedas rápidas
CREATE INDEX IF NOT EXISTS idx_submissions_pdf_path ON submissions(pdf_path);
CREATE INDEX IF NOT EXISTS idx_cotizaciones_pdf_path ON cotizaciones(pdf_path);
