#!/bin/bash

# ============================================
# INSTALADOR VIA CRON - UNA SOLA VEZ
# AseguraloCR - Next.js Prototype
# ============================================
# Este script se ejecuta una vez y luego se
# auto-desactiva creando un archivo .installed
# ============================================

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
LOG_FILE="$SCRIPT_DIR/install-log.txt"
LOCK_FILE="$SCRIPT_DIR/.installed"

# Redirigir todo el output al log
exec > >(tee -a "$LOG_FILE") 2>&1

echo "════════════════════════════════════════════════════════"
echo "Instalación Next.js Prototype vía CRON"
echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo "════════════════════════════════════════════════════════"
echo ""

# Verificar si ya se instaló
if [ -f "$LOCK_FILE" ]; then
    echo "⚠️  INSTALACIÓN YA COMPLETADA ANTERIORMENTE"
    echo ""
    echo "Este script ya se ejecutó exitosamente el:"
    cat "$LOCK_FILE"
    echo ""
    echo "Si quieres reinstalar:"
    echo "  1. Elimina el archivo: $LOCK_FILE"
    echo "  2. Ejecuta el cron nuevamente"
    echo ""
    echo "O ejecuta directamente:"
    echo "  cd $SCRIPT_DIR"
    echo "  ./install-prototype.sh"
    echo ""
    exit 0
fi

echo "🚀 Iniciando instalación..."
echo ""

cd "$SCRIPT_DIR"

# Verificar que install-prototype.sh existe
if [ ! -f "install-prototype.sh" ]; then
    echo "❌ ERROR: install-prototype.sh no encontrado"
    echo "   Directorio actual: $(pwd)"
    echo "   Archivos disponibles:"
    ls -la
    exit 1
fi

# Dar permisos de ejecución
chmod +x install-prototype.sh
chmod +x uninstall-prototype.sh
chmod +x start-dev.sh

echo "✅ Permisos de ejecución configurados"
echo ""
echo "────────────────────────────────────────────────────────"
echo ""

# Ejecutar instalador principal
echo "📦 Ejecutando instalador principal..."
echo ""

./install-prototype.sh

INSTALL_EXIT_CODE=$?

echo ""
echo "────────────────────────────────────────────────────────"
echo ""

if [ $INSTALL_EXIT_CODE -eq 0 ]; then
    echo "✅ INSTALACIÓN COMPLETADA EXITOSAMENTE"
    echo ""
    echo "📊 Estado de servicios PM2:"
    pm2 status
    echo ""
    echo "🌐 Acceso local:"
    echo "   Backend:  http://localhost:3001/api/health"
    echo "   Frontend: http://localhost:3000"
    echo ""
    echo "📝 Próximos pasos:"
    echo "   1. Verificar que servicios están 'online'"
    echo "   2. Configurar acceso público (ver DEPLOYMENT.md)"
    echo "   3. Acceder a https://prototype.aseguralocr.com"
    echo ""
    echo "📄 Logs guardados en: $LOG_FILE"
    echo ""

    # Crear archivo lock para evitar reinstalación
    date '+%Y-%m-%d %H:%M:%S' > "$LOCK_FILE"
    echo ""
    echo "🔒 Script marcado como ejecutado (no se volverá a instalar)"
    echo ""

else
    echo "❌ ERROR EN LA INSTALACIÓN"
    echo ""
    echo "Código de salida: $INSTALL_EXIT_CODE"
    echo ""
    echo "🔍 Revisa el log completo:"
    echo "   $LOG_FILE"
    echo ""
    echo "🔧 Para reintentar manualmente:"
    echo "   cd $SCRIPT_DIR"
    echo "   ./install-prototype.sh"
    echo ""
    exit $INSTALL_EXIT_CODE
fi

echo "════════════════════════════════════════════════════════"
echo "Instalación finalizada: $(date '+%Y-%m-%d %H:%M:%S')"
echo "════════════════════════════════════════════════════════"
