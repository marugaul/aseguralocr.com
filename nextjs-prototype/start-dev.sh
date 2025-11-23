#!/bin/bash

# Script para iniciar frontend y backend juntos en desarrollo
# AseguraloCR - Next.js Prototype

echo "╔════════════════════════════════════════╗"
echo "║   AseguraloCR - Prototipo Next.js     ║"
echo "║   Iniciando Frontend + Backend         ║"
echo "╚════════════════════════════════════════╝"
echo ""

# Verificar que Node.js está instalado
if ! command -v node &> /dev/null; then
    echo "❌ Error: Node.js no está instalado"
    echo "   Instala Node.js desde: https://nodejs.org/"
    exit 1
fi

echo "✅ Node.js version: $(node --version)"
echo ""

# Verificar que las dependencias están instaladas
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias del frontend..."
    npm install
fi

if [ ! -d "backend/node_modules" ]; then
    echo "📦 Instalando dependencias del backend..."
    cd backend && npm install && cd ..
fi

# Verificar archivos .env
if [ ! -f ".env.local" ]; then
    echo "⚠️  Advertencia: .env.local no existe"
    echo "   Copiando desde .env.example..."
    cp .env.example .env.local
fi

if [ ! -f "backend/.env" ]; then
    echo "⚠️  Advertencia: backend/.env no existe"
    echo "   Copiando desde backend/.env.example..."
    cp backend/.env.example backend/.env
    echo "   ⚠️  IMPORTANTE: Edita backend/.env con tus credenciales de MySQL"
fi

echo ""
echo "🚀 Iniciando servidores..."
echo ""
echo "   Backend API: http://localhost:3001"
echo "   Frontend:    http://localhost:3000"
echo ""
echo "   Presiona Ctrl+C para detener ambos servidores"
echo ""

# Iniciar backend en background
cd backend
npm run dev &
BACKEND_PID=$!

# Volver al directorio principal
cd ..

# Esperar 2 segundos para que el backend inicie
sleep 2

# Iniciar frontend
npm run dev

# Cuando se detiene el frontend, también detener el backend
kill $BACKEND_PID 2>/dev/null
