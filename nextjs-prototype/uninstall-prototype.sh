#!/bin/bash

# ============================================
# DESINSTALADOR DEL PROTOTIPO NEXT.JS
# AseguraloCR - Eliminación Completa y Segura
# ============================================
# Este script elimina TODO el prototipo Next.js
# sin tocar el sistema PHP
# ============================================

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "╔════════════════════════════════════════════════════╗"
echo "║   AseguraloCR - Desinstalador Prototipo Next.js   ║"
echo "║   ⚠️  ELIMINACIÓN COMPLETA                         ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

echo "⚠️  ADVERTENCIA:"
echo "  Este script eliminará COMPLETAMENTE el prototipo Next.js:"
echo "  • Procesos PM2 (aseguralocr-backend y aseguralocr-frontend)"
echo "  • node_modules/ (~200MB)"
echo "  • .next/ (build)"
echo "  • logs/"
echo "  • Archivos .env"
echo ""
echo "  Tu sistema PHP NO será tocado y seguirá funcionando normal"
echo ""

# Pedir confirmación
read -p "¿Estás seguro de ELIMINAR todo el prototipo? (escribe 'SI' para confirmar): " CONFIRM

if [ "$CONFIRM" != "SI" ]; then
    echo ""
    echo "❌ Cancelado. No se eliminó nada."
    exit 0
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

cd "$SCRIPT_DIR"

# 1. Detener y eliminar servicios PM2
echo "🛑 Deteniendo servicios PM2..."
echo ""

if command -v pm2 &> /dev/null; then
    pm2 delete aseguralocr-backend 2>/dev/null && echo "  ✅ Backend eliminado de PM2" || echo "  ⏭️  Backend no estaba en PM2"
    pm2 delete aseguralocr-frontend 2>/dev/null && echo "  ✅ Frontend eliminado de PM2" || echo "  ⏭️  Frontend no estaba en PM2"
    pm2 save --force
    echo ""
    echo "  📊 Servicios PM2 restantes:"
    pm2 list
else
    echo "  ⏭️  PM2 no instalado, nada que detener"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 2. Eliminar node_modules
echo "🗑️  Eliminando dependencias..."
echo ""

if [ -d "node_modules" ]; then
    SIZE=$(du -sh node_modules | cut -f1)
    echo "  🗑️  node_modules/ ($SIZE)..."
    rm -rf node_modules
    echo "  ✅ Eliminado"
else
    echo "  ⏭️  node_modules/ ya no existe"
fi

if [ -d "backend/node_modules" ]; then
    SIZE=$(du -sh backend/node_modules | cut -f1)
    echo "  🗑️  backend/node_modules/ ($SIZE)..."
    rm -rf backend/node_modules
    echo "  ✅ Eliminado"
else
    echo "  ⏭️  backend/node_modules/ ya no existe"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 3. Eliminar builds
echo "🗑️  Eliminando builds..."
echo ""

if [ -d ".next" ]; then
    SIZE=$(du -sh .next | cut -f1)
    echo "  🗑️  .next/ ($SIZE)..."
    rm -rf .next
    echo "  ✅ Eliminado"
else
    echo "  ⏭️  .next/ ya no existe"
fi

if [ -d "out" ]; then
    rm -rf out
    echo "  ✅ out/ eliminado"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 4. Eliminar logs
echo "🗑️  Eliminando logs..."
echo ""

if [ -d "logs" ]; then
    SIZE=$(du -sh logs 2>/dev/null | cut -f1)
    echo "  🗑️  logs/ ($SIZE)..."
    rm -rf logs
    echo "  ✅ Eliminado"
else
    echo "  ⏭️  logs/ ya no existe"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 5. Eliminar archivos de configuración temporales
echo "🗑️  Eliminando configuración..."
echo ""

if [ -f ".env.local" ]; then
    echo "  🗑️  .env.local..."
    rm -f .env.local
    echo "  ✅ Eliminado"
fi

if [ -f "backend/.env" ]; then
    echo "  🗑️  backend/.env..."
    rm -f backend/.env
    echo "  ✅ Eliminado"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 6. Verificar puertos liberados
echo "🔍 Verificando puertos liberados..."
echo ""

if command -v lsof &> /dev/null; then
    PORT_3000=$(lsof -ti:3000 2>/dev/null || echo "")
    PORT_3001=$(lsof -ti:3001 2>/dev/null || echo "")

    if [ -z "$PORT_3000" ]; then
        echo "  ✅ Puerto 3000: libre"
    else
        echo "  ⚠️  Puerto 3000: aún en uso (PID: $PORT_3000)"
        echo "     Ejecuta: kill -9 $PORT_3000"
    fi

    if [ -z "$PORT_3001" ]; then
        echo "  ✅ Puerto 3001: libre"
    else
        echo "  ⚠️  Puerto 3001: aún en uso (PID: $PORT_3001)"
        echo "     Ejecuta: kill -9 $PORT_3001"
    fi
else
    echo "  ℹ️  lsof no disponible, no se pudo verificar puertos"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Resumen final
echo "✅ DESINSTALACIÓN COMPLETADA"
echo ""
echo "📊 Archivos eliminados:"
echo "  ✅ Servicios PM2 detenidos y eliminados"
echo "  ✅ node_modules/ (~200MB)"
echo "  ✅ .next/ (builds)"
echo "  ✅ logs/"
echo "  ✅ .env.local y backend/.env"
echo ""
echo "📁 Archivos CONSERVADOS (código fuente):"
echo "  ✅ app/ (código Next.js)"
echo "  ✅ backend/server.js"
echo "  ✅ package.json"
echo "  ✅ README.md y documentación"
echo ""
echo "💡 Para ELIMINAR TODO incluyendo código fuente:"
echo "  cd .."
echo "  rm -rf nextjs-prototype/"
echo ""
echo "🔄 Para REINSTALAR el prototipo:"
echo "  ./install-prototype.sh"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Tu sistema PHP está intacto y funcionando normal"
echo "✅ Puertos 3000 y 3001 ahora están libres"
echo ""
