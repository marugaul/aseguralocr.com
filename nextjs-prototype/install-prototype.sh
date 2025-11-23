#!/bin/bash

# ============================================
# INSTALADOR DEL PROTOTIPO NEXT.JS
# AseguraloCR - Instalación Aislada
# ============================================
# Este script instala el prototipo sin tocar
# el sistema PHP existente
# ============================================

set -e  # Exit on error

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "╔════════════════════════════════════════════════════╗"
echo "║   AseguraloCR - Instalador Prototipo Next.js      ║"
echo "║   Instalación 100% aislada del sistema PHP        ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# Verificaciones previas
echo "📋 Verificando requisitos..."
echo ""

# 1. Node.js
if ! command -v node &> /dev/null; then
    echo "❌ ERROR: Node.js no está instalado"
    echo ""
    echo "Instala Node.js 18+ desde:"
    echo "  https://nodejs.org/"
    echo ""
    echo "O con nvm (recomendado para cPanel):"
    echo "  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash"
    echo "  source ~/.bashrc"
    echo "  nvm install 18"
    exit 1
fi

NODE_VERSION=$(node --version)
echo "  ✅ Node.js: $NODE_VERSION"

# 2. npm
if ! command -v npm &> /dev/null; then
    echo "❌ ERROR: npm no está instalado"
    exit 1
fi

NPM_VERSION=$(npm --version)
echo "  ✅ npm: $NPM_VERSION"

# 3. PM2
if ! command -v pm2 &> /dev/null; then
    echo "  ⚠️  PM2 no está instalado. Instalando..."
    npm install -g pm2
    echo "  ✅ PM2 instalado"
else
    PM2_VERSION=$(pm2 --version)
    echo "  ✅ PM2: $PM2_VERSION"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Directorio del prototipo
cd "$SCRIPT_DIR"

echo "📦 Instalando dependencias del prototipo..."
echo ""

# Frontend
echo "  → Frontend (Next.js)..."
npm install --production=false

# Backend
echo "  → Backend (Express)..."
cd backend
npm install --production=false
cd ..

echo ""
echo "✅ Dependencias instaladas"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Configurar variables de entorno
echo "⚙️  Configurando variables de entorno..."
echo ""

# Frontend .env.local
if [ ! -f ".env.local" ]; then
    cat > .env.local << 'EOF'
# Next.js Frontend
NEXT_PUBLIC_API_URL=http://localhost:3001
EOF
    echo "  ✅ Creado: .env.local"
else
    echo "  ⏭️  Ya existe: .env.local (no sobrescrito)"
fi

# Backend .env
if [ ! -f "backend/.env" ]; then
    # Intentar leer config de PHP
    CONFIG_FILE="$PROJECT_ROOT/app/config/config.php"

    if [ -f "$CONFIG_FILE" ]; then
        echo "  🔍 Leyendo configuración de MySQL desde config.php..."

        # Extraer DB config (básico - puede necesitar ajustes)
        DB_HOST=$(grep -oP "(?<='host' => ')[^']*" "$CONFIG_FILE" || echo "localhost")
        DB_NAME=$(grep -oP "(?<='dbname' => ')[^']*" "$CONFIG_FILE" || echo "asegural_aseguralocr")
        DB_USER=$(grep -oP "(?<='user' => ')[^']*" "$CONFIG_FILE" || echo "asegural_user")
        DB_PASS=$(grep -oP "(?<='pass' => ')[^']*" "$CONFIG_FILE" || echo "")

        cat > backend/.env << EOF
# Backend Express API
PORT=3001
NODE_ENV=production

# MySQL Database (mismo que PHP)
DB_HOST=$DB_HOST
DB_USER=$DB_USER
DB_PASS=$DB_PASS
DB_NAME=$DB_NAME
DB_PORT=3306

# CORS
CORS_ORIGIN=http://localhost:3000
EOF
        echo "  ✅ Creado: backend/.env (con credenciales de config.php)"
    else
        # Crear plantilla
        cp backend/.env.example backend/.env
        echo "  ⚠️  Creado: backend/.env (EDITA LAS CREDENCIALES MANUALMENTE)"
    fi
else
    echo "  ⏭️  Ya existe: backend/.env (no sobrescrito)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Build del frontend
echo "🔨 Compilando Next.js para producción..."
echo ""
npm run build

echo ""
echo "✅ Build completado"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Configurar PM2
echo "🚀 Configurando PM2..."
echo ""

# Actualizar ecosystem.config.js con rutas correctas
CURRENT_DIR=$(pwd)
cat > ecosystem.config.js << EOF
module.exports = {
  apps: [
    // Backend API (Express)
    {
      name: 'aseguralocr-backend',
      script: './backend/server.js',
      cwd: '$CURRENT_DIR',
      instances: 1,
      exec_mode: 'fork',
      env: {
        NODE_ENV: 'production',
        PORT: 3001
      },
      error_file: '$CURRENT_DIR/logs/backend-error.log',
      out_file: '$CURRENT_DIR/logs/backend-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      merge_logs: true,
      max_memory_restart: '500M',
      autorestart: true,
      watch: false
    },

    // Frontend Next.js
    {
      name: 'aseguralocr-frontend',
      script: 'node_modules/next/dist/bin/next',
      args: 'start -p 3000',
      cwd: '$CURRENT_DIR',
      instances: 1,
      exec_mode: 'fork',
      env: {
        NODE_ENV: 'production',
        PORT: 3000
      },
      error_file: '$CURRENT_DIR/logs/frontend-error.log',
      out_file: '$CURRENT_DIR/logs/frontend-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      merge_logs: true,
      max_memory_restart: '1G',
      autorestart: true,
      watch: false
    }
  ]
};
EOF

# Crear directorio de logs
mkdir -p logs

echo "  ✅ Configuración PM2 actualizada"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Iniciar con PM2
echo "🎬 Iniciando servicios con PM2..."
echo ""

# Detener si ya existen
pm2 delete aseguralocr-backend 2>/dev/null || true
pm2 delete aseguralocr-frontend 2>/dev/null || true

# Iniciar
pm2 start ecosystem.config.js

# Guardar configuración
pm2 save

echo ""
echo "✅ Servicios iniciados"
echo ""

# Mostrar estado
pm2 list

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ INSTALACIÓN COMPLETADA"
echo ""
echo "📊 Estado de los servicios:"
echo ""
pm2 status aseguralocr-backend
pm2 status aseguralocr-frontend
echo ""
echo "🌐 Acceso:"
echo "  • Frontend Next.js: http://localhost:3000"
echo "  • Backend API:      http://localhost:3001/api/health"
echo "  • PHP (intacto):    http://aseguralocr.com"
echo ""
echo "📝 Logs:"
echo "  pm2 logs aseguralocr-backend"
echo "  pm2 logs aseguralocr-frontend"
echo "  pm2 logs  (ambos)"
echo ""
echo "🔧 Comandos útiles:"
echo "  pm2 status          # Ver estado"
echo "  pm2 restart all     # Reiniciar todo"
echo "  pm2 stop all        # Detener todo"
echo "  pm2 delete all      # Eliminar todo"
echo ""
echo "🗑️  Para DESINSTALAR completamente:"
echo "  ./uninstall-prototype.sh"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "⚠️  IMPORTANTE:"
echo "  El prototipo Next.js está en los puertos 3000 y 3001"
echo "  Tu sistema PHP NO ha sido tocado y funciona normal"
echo "  Ambos sistemas funcionan independientemente"
echo ""
echo "  Para acceso público, configura Nginx/Apache"
echo "  Ver: DEPLOYMENT.md"
echo ""
