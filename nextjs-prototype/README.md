# AseguraloCR - Prototipo Next.js + React + Node.js

Este es un **prototipo de comparación** entre la versión actual en PHP y una versión moderna con:
- **Frontend**: Next.js 14 + React 18 + TypeScript
- **Backend**: Node.js + Express + MySQL

## 🎯 Propósito

Permitir al cliente final comparar ambas versiones (PHP vs Next.js) visualmente y experimentar las diferencias en rendimiento, navegación y experiencia de usuario.

---

## 📁 Estructura del Proyecto

```
nextjs-prototype/
├── app/                    # Next.js App Router
│   ├── layout.tsx         # Layout principal
│   ├── page.tsx           # Página de inicio (equivalente a index.php)
│   └── globals.css        # Estilos globales + Tailwind
├── backend/               # Express API Server
│   ├── server.js          # Servidor Express
│   ├── package.json       # Dependencias del backend
│   └── .env.example       # Variables de entorno del backend
├── public/                # Archivos estáticos
│   └── imagenes/          # Imágenes (compartidas con versión PHP)
├── package.json           # Dependencias del frontend
├── tsconfig.json          # Configuración TypeScript
├── next.config.js         # Configuración Next.js
└── README.md              # Este archivo
```

---

## 🚀 Instalación y Configuración

### Requisitos Previos

- Node.js 18.x o superior
- npm o yarn
- MySQL (misma base de datos que la versión PHP)

### 1. Instalar Dependencias del Frontend

```bash
cd nextjs-prototype
npm install
```

### 2. Configurar Variables de Entorno del Frontend

```bash
cp .env.example .env.local
```

Editar `.env.local`:
```env
NEXT_PUBLIC_API_URL=http://localhost:3001
```

### 3. Instalar Dependencias del Backend

```bash
cd backend
npm install
```

### 4. Configurar Variables de Entorno del Backend

```bash
cp .env.example .env
```

Editar `backend/.env`:
```env
PORT=3001
NODE_ENV=development

# Usar las mismas credenciales del PHP
DB_HOST=localhost
DB_USER=asegural_user
DB_PASS=tu_password_mysql
DB_NAME=asegural_aseguralocr
DB_PORT=3306

CORS_ORIGIN=http://localhost:3000
```

---

## 🏃‍♂️ Ejecución Local

### Opción 1: Ejecutar Frontend y Backend por Separado

**Terminal 1 - Backend (Express):**
```bash
cd backend
npm run dev
# Servidor corriendo en http://localhost:3001
```

**Terminal 2 - Frontend (Next.js):**
```bash
cd nextjs-prototype
npm run dev
# Aplicación corriendo en http://localhost:3000
```

### Opción 2: Script de Inicio Rápido (TODO)

```bash
./start-dev.sh
```

---

## 📦 Build para Producción

### Frontend (Next.js)

```bash
npm run build
npm run start
```

### Backend (Express)

```bash
cd backend
npm start
```

---

## 🌐 Despliegue en Staging (staging.aseguralocr.com)

### Opción A: PM2 (Recomendado para cPanel)

**1. Instalar PM2 globalmente:**
```bash
npm install -g pm2
```

**2. Iniciar Backend con PM2:**
```bash
cd backend
pm2 start server.js --name aseguralocr-backend
```

**3. Build y Start Frontend con PM2:**
```bash
cd ..
npm run build
pm2 start npm --name aseguralocr-frontend -- start
```

**4. Guardar configuración PM2:**
```bash
pm2 save
pm2 startup
```

**5. Ver logs:**
```bash
pm2 logs
pm2 status
```

### Opción B: Proxy con Nginx

Configurar Nginx para hacer proxy a los puertos 3000 (Next.js) y 3001 (Express):

```nginx
# Frontend Next.js en /nextjs o subdominio
location /nextjs {
    proxy_pass http://localhost:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}

# Backend API
location /api {
    proxy_pass http://localhost:3001;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
}
```

### Opción C: Docker (Avanzado)

```bash
docker-compose up -d
```

---

## 🔍 Comparación PHP vs Next.js

### Página de Inicio

| Aspecto | PHP (index.php) | Next.js (app/page.tsx) |
|---------|----------------|------------------------|
| **Renderizado** | Server-side en cada request | SSR + Client hydration |
| **Navegación** | Recarga completa de página | SPA - Sin recargas |
| **Estado** | Sesiones PHP | React hooks (useState) |
| **Datos** | Consultas MySQL directas | API REST con Express |
| **SEO** | ✅ Excelente | ✅ Excelente (SSR) |
| **Performance** | Bueno | Excelente (prefetching) |
| **Interactividad** | JavaScript vanilla | React components |
| **TypeScript** | ❌ | ✅ Type safety |

### Arquitectura

**PHP (Actual):**
```
Cliente → Apache/Nginx → index.php → MySQL
                           ↓
                      HTML completo
```

**Next.js (Prototipo):**
```
Cliente → Next.js Server → React (SSR)
           ↓
        API calls
           ↓
     Express Backend → MySQL
```

---

## 🧪 Endpoints de la API

El backend Express expone los siguientes endpoints:

### GET `/api/health`
Health check del servidor
```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00.000Z",
  "service": "AseguraloCR Backend API"
}
```

### GET `/api/stats`
Estadísticas para la página de inicio
```json
{
  "success": true,
  "data": {
    "homes": 50000,
    "satisfaction": 98,
    "coverage24_7": true
  }
}
```

### GET `/api/insurance-types`
Tipos de seguros disponibles
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Seguro de Hogar",
      "slug": "hogar",
      "available": true,
      ...
    }
  ]
}
```

### POST `/api/contact`
Envío de formulario de contacto
```json
{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "telefono": "8888-8888",
  "mensaje": "Quiero cotizar..."
}
```

---

## 🎨 Diferencias Visuales

La versión Next.js mantiene **exactamente el mismo diseño visual** que la versión PHP, pero con:

### Mejoras de UX:
- ⚡ Navegación instantánea sin recargas
- 🔄 Actualizaciones de datos en tiempo real
- 📱 Mejor manejo del menú móvil con React state
- ✨ Transiciones más suaves entre páginas
- 🚀 Prefetching automático de enlaces

### Mejoras Técnicas:
- 📦 Componentes reutilizables
- 🎯 Type safety con TypeScript
- 🔒 Validación de datos en frontend y backend
- 📊 API REST bien estructurada
- 🧪 Fácil de testear (Jest, React Testing Library)

---

## 🔐 Variables de Entorno

### Frontend (`.env.local`)
```env
NEXT_PUBLIC_API_URL=http://localhost:3001  # URL del backend Express
```

### Backend (`backend/.env`)
```env
PORT=3001                          # Puerto del servidor Express
NODE_ENV=development               # development | production
DB_HOST=localhost                  # Host de MySQL
DB_USER=asegural_user             # Usuario de MySQL
DB_PASS=password                   # Password de MySQL
DB_NAME=asegural_aseguralocr      # Nombre de la BD
CORS_ORIGIN=http://localhost:3000  # Origen permitido para CORS
```

---

## 🐛 Troubleshooting

### Error: "Cannot connect to database"
- Verifica las credenciales en `backend/.env`
- Asegúrate de que MySQL está corriendo
- Verifica que la base de datos existe

### Error: "API fetch failed"
- Asegúrate de que el backend está corriendo en el puerto 3001
- Verifica `NEXT_PUBLIC_API_URL` en `.env.local`
- Revisa los logs del backend: `cd backend && npm run dev`

### Error: "Port already in use"
```bash
# Encuentra el proceso usando el puerto
lsof -i :3000
lsof -i :3001

# Mata el proceso
kill -9 <PID>
```

### Frontend se ve sin estilos
- Asegúrate de haber ejecutado `npm install`
- Verifica que Tailwind CSS está configurado
- Revisa `app/globals.css`

---

## 📚 Próximos Pasos

### Para Implementación Completa:

1. **Autenticación**
   - Implementar NextAuth.js para Google OAuth
   - Crear middleware de autenticación

2. **Dashboard de Clientes**
   - Portal de clientes con sus pólizas
   - Visualización de pagos y vencimientos

3. **Formularios Dinámicos**
   - Recrear `hogar-comprensivo.php` en Next.js
   - Validación con React Hook Form + Zod

4. **Integración con INS**
   - Crear servicios para integración con APIs del INS
   - Manejo de webhooks

5. **Testing**
   - Unit tests con Jest
   - Integration tests con Playwright
   - E2E tests

6. **CI/CD**
   - GitHub Actions para deploy automático
   - Tests automáticos en cada push

---

## 📖 Documentación Adicional

- [Next.js Documentation](https://nextjs.org/docs)
- [React Documentation](https://react.dev)
- [Express.js Guide](https://expressjs.com/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)

---

## 👥 Soporte

Para preguntas o issues con el prototipo:
- 📧 Email: info@aseguralocr.com
- 📂 Ver archivo: `ANALISIS_MIGRACION_LARAVEL_VS_NEXTJS.md`

---

## 📝 Notas

- Este es un **prototipo para comparación**, no un sistema completo
- La versión PHP actual sigue siendo la versión de producción
- Este prototipo demuestra las capacidades de Next.js/React/Node.js
- Revisar el análisis completo antes de decidir migración
