#!/bin/bash
#
# ============================================
# MYSQL AUTO-EXECUTOR - Sistema Permanente
# ============================================
# Este script se ejecuta via CRON cada minuto
# y ejecuta automáticamente cualquier archivo
# SQL que encuentre en la carpeta pendientes/
#
# USO:
# 1. Sube un archivo .sql a: pendientes/
# 2. El cron lo detecta y ejecuta automáticamente
# 3. El archivo se mueve a: ejecutados/
# 4. Se genera un log en: logs/
# ============================================

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PENDIENTES_DIR="$SCRIPT_DIR/mysql-pendientes"
EJECUTADOS_DIR="$SCRIPT_DIR/mysql-ejecutados"
LOGS_DIR="$SCRIPT_DIR/mysql-logs"

# Crear directorios si no existen
mkdir -p "$PENDIENTES_DIR"
mkdir -p "$EJECUTADOS_DIR"
mkdir -p "$LOGS_DIR"

# Credenciales MySQL
DB_HOST="localhost"
DB_USER="asegural_marugaul"
DB_PASS="Marden7i/"
DB_NAME="asegural_aseguralocr"

# Función para ejecutar SQL
ejecutar_sql() {
    local archivo=$1
    local nombre=$(basename "$archivo")
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local log_file="$LOGS_DIR/${timestamp}_${nombre}.log"

    echo "========================================" | tee "$log_file"
    echo "MySQL Auto-Executor" | tee -a "$log_file"
    echo "========================================" | tee -a "$log_file"
    echo "Archivo: $nombre" | tee -a "$log_file"
    echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')" | tee -a "$log_file"
    echo "========================================" | tee -a "$log_file"
    echo "" | tee -a "$log_file"

    # Ejecutar SQL
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$archivo" 2>&1 | tee -a "$log_file"

    local exit_code=${PIPESTATUS[0]}

    echo "" | tee -a "$log_file"
    echo "========================================" | tee -a "$log_file"

    if [ $exit_code -eq 0 ]; then
        echo "✅ SQL EJECUTADO EXITOSAMENTE" | tee -a "$log_file"

        # Mover a ejecutados con verificación
        local destino="$EJECUTADOS_DIR/${timestamp}_${nombre}"
        mv "$archivo" "$destino"

        if [ $? -eq 0 ]; then
            echo "✅ Archivo movido a: ejecutados/${timestamp}_${nombre}" | tee -a "$log_file"
        else
            echo "❌ ERROR: No se pudo mover el archivo" | tee -a "$log_file"
            echo "⚠️  Origen: $archivo" | tee -a "$log_file"
            echo "⚠️  Destino: $destino" | tee -a "$log_file"
            echo "⚠️  Eliminando archivo para evitar re-ejecución..." | tee -a "$log_file"
            rm -f "$archivo"
            if [ $? -eq 0 ]; then
                echo "✅ Archivo eliminado de pendientes/" | tee -a "$log_file"
            else
                echo "❌ ERROR CRÍTICO: No se pudo eliminar el archivo" | tee -a "$log_file"
            fi
        fi
    else
        echo "❌ ERROR EN LA EJECUCIÓN SQL (código: $exit_code)" | tee -a "$log_file"
        echo "⚠️  Archivo permanece en pendientes/" | tee -a "$log_file"
    fi

    echo "========================================" | tee -a "$log_file"
    echo "Log guardado en: logs/${timestamp}_${nombre}.log" | tee -a "$log_file"

    return $exit_code
}

# Buscar archivos SQL pendientes
archivos_encontrados=0

for archivo in "$PENDIENTES_DIR"/*.sql "$PENDIENTES_DIR"/*.txt; do
    # Verificar si el archivo existe (el glob puede no matchear nada)
    [ -e "$archivo" ] || continue

    archivos_encontrados=$((archivos_encontrados + 1))

    echo "🔍 Detectado: $(basename "$archivo")"
    ejecutar_sql "$archivo"
    echo ""
done

# Si no hay archivos, salir silenciosamente (sin output para evitar emails)
if [ $archivos_encontrados -eq 0 ]; then
    exit 0
fi

echo "✅ Procesados $archivos_encontrados archivo(s)"
