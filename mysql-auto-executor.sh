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

# PATH completo para cron (importante!)
export PATH="/usr/local/bin:/usr/bin:/bin:$PATH"

# Log principal en /home/asegural/ (como otros crons)
MAIN_LOG="/home/asegural/mysql-auto-executor.log"

# Función para escribir al log principal
log_main() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$MAIN_LOG"
}

log_main "========== INICIO EJECUCIÓN =========="

# Detectar ubicación de mysql
MYSQL_BIN=$(which mysql 2>/dev/null)
if [ -z "$MYSQL_BIN" ]; then
    # Rutas comunes en cPanel/hosting
    for path in /usr/bin/mysql /usr/local/bin/mysql /usr/local/mysql/bin/mysql; do
        if [ -x "$path" ]; then
            MYSQL_BIN="$path"
            break
        fi
    done
fi

if [ -z "$MYSQL_BIN" ]; then
    log_main "ERROR: No se encontró el comando mysql"
    echo "ERROR: No se encontró el comando mysql"
    exit 1
fi

log_main "MySQL encontrado en: $MYSQL_BIN"

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
log_main "SCRIPT_DIR: $SCRIPT_DIR"
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

    log_main "Iniciando ejecución de: $nombre"
    log_main "Log específico: $log_file"

    echo "========================================" | tee "$log_file"
    echo "MySQL Auto-Executor" | tee -a "$log_file"
    echo "========================================" | tee -a "$log_file"
    echo "Archivo: $nombre" | tee -a "$log_file"
    echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')" | tee -a "$log_file"
    echo "========================================" | tee -a "$log_file"
    echo "" | tee -a "$log_file"

    # Ejecutar SQL usando la ruta detectada
    echo "Usando MySQL: $MYSQL_BIN" | tee -a "$log_file"
    "$MYSQL_BIN" -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$archivo" 2>&1 | tee -a "$log_file"

    local exit_code=${PIPESTATUS[0]}

    echo "" | tee -a "$log_file"
    echo "========================================" | tee -a "$log_file"

    if [ $exit_code -eq 0 ]; then
        echo "✅ SQL EJECUTADO EXITOSAMENTE" | tee -a "$log_file"
        log_main "SQL ejecutado exitosamente: $nombre"

        # Mover a ejecutados con verificación
        local destino="$EJECUTADOS_DIR/${timestamp}_${nombre}"
        mv "$archivo" "$destino"

        if [ $? -eq 0 ]; then
            echo "✅ Archivo movido a: ejecutados/${timestamp}_${nombre}" | tee -a "$log_file"
            log_main "Archivo movido a ejecutados: $nombre"
        else
            echo "❌ ERROR: No se pudo mover el archivo" | tee -a "$log_file"
            echo "⚠️  Origen: $archivo" | tee -a "$log_file"
            echo "⚠️  Destino: $destino" | tee -a "$log_file"
            log_main "ERROR: No se pudo mover $nombre"
            echo "⚠️  Eliminando archivo para evitar re-ejecución..." | tee -a "$log_file"
            rm -f "$archivo"
            if [ $? -eq 0 ]; then
                echo "✅ Archivo eliminado de pendientes/" | tee -a "$log_file"
                log_main "Archivo eliminado de pendientes: $nombre"
            else
                echo "❌ ERROR CRÍTICO: No se pudo eliminar el archivo" | tee -a "$log_file"
                log_main "ERROR CRÍTICO: No se pudo eliminar $nombre"
            fi
        fi
    else
        echo "❌ ERROR EN LA EJECUCIÓN SQL (código: $exit_code)" | tee -a "$log_file"
        echo "⚠️  Archivo permanece en pendientes/" | tee -a "$log_file"
        log_main "ERROR SQL (código $exit_code): $nombre permanece en pendientes"
    fi

    echo "========================================" | tee -a "$log_file"
    echo "Log guardado en: logs/${timestamp}_${nombre}.log" | tee -a "$log_file"

    return $exit_code
}

# Buscar archivos SQL pendientes
archivos_encontrados=0

log_main "Buscando archivos en: $PENDIENTES_DIR"
log_main "Archivos encontrados: $(ls -la "$PENDIENTES_DIR" 2>&1)"

for archivo in "$PENDIENTES_DIR"/*.sql "$PENDIENTES_DIR"/*.txt; do
    # Verificar si el archivo existe (el glob puede no matchear nada)
    [ -e "$archivo" ] || continue

    archivos_encontrados=$((archivos_encontrados + 1))
    log_main "Procesando archivo #$archivos_encontrados: $(basename "$archivo")"

    echo "🔍 Detectado: $(basename "$archivo")"
    ejecutar_sql "$archivo"
    resultado=$?
    log_main "Resultado ejecución $(basename "$archivo"): código $resultado"
    echo ""
done

# Si no hay archivos, loguear y salir
if [ $archivos_encontrados -eq 0 ]; then
    log_main "No hay archivos pendientes. Saliendo."
    log_main "========== FIN EJECUCIÓN =========="
    exit 0
fi

log_main "Total procesados: $archivos_encontrados archivo(s)"
log_main "========== FIN EJECUCIÓN =========="
echo "✅ Procesados $archivos_encontrados archivo(s)"
