# Guía de Deployment - Prototipo Next.js

## 🎯 Instalación Aislada y Segura

Esta guía te ayudará a instalar el prototipo Next.js **completamente separado** del sistema PHP, fácil de probar y eliminar.

---

## ⚡ Instalación Rápida (3 Pasos)

### 1️⃣ Ejecutar Instalador

```bash
cd /home/asegural/public_html/nextjs-prototype
./install-prototype.sh
```

**Esto instalará automáticamente:**
- ✅ Verificará Node.js y npm
- ✅ Instalará PM2 si no existe
- ✅ Instalará todas las dependencias
- ✅ Configurará variables de entorno (lee config.php automáticamente)
- ✅ Compilará Next.js para producción
- ✅ Iniciará servicios con PM2
- ✅ Guardará configuración para auto-start

**Tiempo estimado:** 5-10 minutos

---

### 2️⃣ Verificar que Funciona

```bash
# Ver estado de servicios
pm2 status

# Deberías ver:
# ┌─────┬────────────────────────┬─────────┬─────────┐
# │ id  │ name                   │ status  │ cpu     │
# ├─────┼────────────────────────┼─────────┼─────────┤
# │ 0   │ aseguralocr-backend    │ online  │ 0%      │
# │ 1   │ aseguralocr-frontend   │ online  │ 0%      │
# └─────┴────────────────────────┴─────────┴─────────┘

# Probar backend
curl http://localhost:3001/api/health
# Respuesta: {"status":"ok","timestamp":"..."}

# Probar frontend
curl http://localhost:3000
# Respuesta: HTML de Next.js
```

**Si ves "online" en ambos → ¡Perfecto! Funcionó** ✅

---

### 3️⃣ Configurar Acceso Público

**Elige UNA opción:**

#### Opción A: Subdominio (Recomendado)
- **URL**: `https://prototype.aseguralocr.com`
- **Ventajas**: Completamente separado, fácil de configurar SSL
- **Pasos**: Ver sección "Configuración Nginx/Apache" abajo

#### Opción B: Ruta en dominio principal
- **URL**: `https://aseguralocr.com/prototype`
- **Ventajas**: No requiere DNS adicional
- **Pasos**: Ver sección "Configuración Nginx/Apache" abajo

---

## 📋 Requisitos Previos

### Software Necesario

| Software | Versión | Check |
|----------|---------|-------|
| **Node.js** | 18.x o superior | `node --version` |
| **npm** | 9.x o superior | `npm --version` |
| **PM2** | Latest | `pm2 --version` |
| **MySQL** | 5.7+ o MariaDB | `mysql --version` |

### Instalar Node.js (si no existe)

#### Método 1: Con nvm (Recomendado para cPanel)
```bash
# Instalar nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Recargar shell
source ~/.bashrc

# Instalar Node.js 18
nvm install 18
nvm use 18
nvm alias default 18

# Verificar
node --version  # v18.x.x
npm --version   # 9.x.x
```

#### Método 2: Desde repositorio (Ubuntu/Debian)
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

#### Método 3: cPanel Node.js Selector
```
1. Ir a cPanel → "Setup Node.js App"
2. Seleccionar Node.js 18.x
3. Set Application Root: /home/usuario/public_html/nextjs-prototype
```

---

## 🚀 Instalación Detallada

### Paso 1: Preparar el Entorno

```bash
# Conectar por SSH
ssh usuario@aseguralocr.com

# Ir al directorio del prototipo
cd /home/asegural/public_html/nextjs-prototype

# Verificar que estás en el lugar correcto
pwd
# Debe mostrar: /home/asegural/public_html/nextjs-prototype

# Ver archivos
ls -la
# Debes ver: install-prototype.sh, package.json, app/, backend/, etc.
```

### Paso 2: Ejecutar Instalador

```bash
# Dar permisos de ejecución (si no los tiene)
chmod +x install-prototype.sh

# Ejecutar instalador
./install-prototype.sh
```

**El instalador hará:**

1. **Verificar requisitos**
   - Node.js, npm, PM2

2. **Instalar dependencias**
   ```
   📦 Instalando dependencias del prototipo...
     → Frontend (Next.js)...
     → Backend (Express)...
   ✅ Dependencias instaladas
   ```

3. **Configurar .env automáticamente**
   - Lee `app/config/config.php` del sistema PHP
   - Crea `.env.local` y `backend/.env`
   - Usa las MISMAS credenciales de MySQL

4. **Compilar Next.js**
   ```
   🔨 Compilando Next.js para producción...
   Creating an optimized production build...
   ✓ Compiled successfully
   ```

5. **Iniciar servicios**
   ```
   🚀 Iniciando servicios con PM2...
   [PM2] Starting backend...
   [PM2] Starting frontend...
   ✅ Servicios iniciados
   ```

### Paso 3: Verificar Instalación

```bash
# Ver estado PM2
pm2 status

# Ver logs en tiempo real
pm2 logs

# Ver logs específicos
pm2 logs aseguralocr-backend
pm2 logs aseguralocr-frontend

# Probar endpoints
curl http://localhost:3001/api/health
curl http://localhost:3001/api/stats
curl -I http://localhost:3000  # Frontend
```

**Indicadores de éxito:**
- ✅ PM2 muestra "online" en ambos servicios
- ✅ Backend responde en puerto 3001
- ✅ Frontend responde en puerto 3000
- ✅ No hay errores en los logs

---

## 🌐 Configuración de Acceso Público

### Opción A: Subdominio (Recomendado)

**URL final:** `https://prototype.aseguralocr.com`

#### 1. Crear DNS Record

En tu proveedor DNS (Cloudflare, cPanel, etc.):

```
Tipo: A
Nombre: prototype
Valor: [IP de tu servidor]
TTL: 3600
```

Esperar propagación (5-30 minutos):
```bash
nslookup prototype.aseguralocr.com
```

#### 2. Configurar Nginx

```bash
# Copiar configuración
sudo cp nginx-config-example.conf /etc/nginx/sites-available/prototype.aseguralocr.com

# Editar si es necesario
sudo nano /etc/nginx/sites-available/prototype.aseguralocr.com

# Crear symlink
sudo ln -s /etc/nginx/sites-available/prototype.aseguralocr.com /etc/nginx/sites-enabled/

# Test
sudo nginx -t

# Reload
sudo systemctl reload nginx
```

#### 3. Configurar SSL (Let's Encrypt)

```bash
# Instalar certbot (si no existe)
sudo apt-get install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d prototype.aseguralocr.com

# Seguir prompts:
#   Email: info@aseguralocr.com
#   Terms: Agree
#   Redirect HTTP to HTTPS: Yes

# Renovación automática ya está configurada
# Verificar:
sudo certbot renew --dry-run
```

#### 4. Configurar Apache (Alternativa)

```bash
# Copiar configuración
sudo cp apache-config-example.conf /etc/apache2/sites-available/prototype.aseguralocr.com.conf

# Habilitar módulos necesarios
sudo a2enmod proxy proxy_http proxy_wstunnel ssl

# Habilitar sitio
sudo a2ensite prototype.aseguralocr.com

# Test
sudo apachectl configtest

# Reload
sudo systemctl reload apache2

# SSL con certbot
sudo certbot --apache -d prototype.aseguralocr.com
```

#### 5. Para cPanel

```
1. Ir a cPanel → "Dominios" → "Crear un Nuevo Dominio"
   - Dominio: prototype.aseguralocr.com
   - Document Root: /home/usuario/public_html/nextjs-prototype

2. SSL/TLS → AutoSSL → Run

3. Configurar proxy reverso:
   - Crear .htaccess (ver apache-config-example.conf)
```

#### 6. Verificar Funcionamiento

```bash
# Health check
curl https://prototype.aseguralocr.com/api/health

# Frontend
curl -I https://prototype.aseguralocr.com

# Abrir navegador
https://prototype.aseguralocr.com
```

---

### Opción B: Ruta en Dominio Principal

**URL final:** `https://aseguralocr.com/prototype`

#### Para Nginx

Agregar en `/etc/nginx/sites-available/aseguralocr.com`:

```nginx
# Dentro del bloque server { ... }

location /prototype {
    proxy_pass http://localhost:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}

location /api {
    proxy_pass http://localhost:3001;
    proxy_http_version 1.1;
}
```

```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### Para Apache

Agregar en VirtualHost de `aseguralocr.com`:

```apache
<IfModule mod_proxy.c>
    ProxyPass /prototype http://localhost:3000
    ProxyPassReverse /prototype http://localhost:3000

    ProxyPass /api http://localhost:3001/api
    ProxyPassReverse /api http://localhost:3001/api
</IfModule>
```

```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

---

## 🔧 Comandos Útiles PM2

### Ver Estado
```bash
pm2 status                    # Estado general
pm2 info aseguralocr-backend  # Info detallada backend
pm2 info aseguralocr-frontend # Info detallada frontend
```

### Logs
```bash
pm2 logs                      # Todos los logs en tiempo real
pm2 logs aseguralocr-backend  # Solo backend
pm2 logs aseguralocr-frontend # Solo frontend
pm2 logs --lines 100          # Últimas 100 líneas
```

### Controlar Servicios
```bash
pm2 restart all               # Reiniciar todo
pm2 restart aseguralocr-backend
pm2 stop all                  # Detener
pm2 start all                 # Iniciar
pm2 delete all                # Eliminar (no borra archivos)
```

### Monitoreo
```bash
pm2 monit                     # Monitor interactivo
pm2 plus                      # PM2 Plus (dashboard web)
```

### Persistencia
```bash
pm2 save                      # Guardar configuración actual
pm2 startup                   # Configurar auto-start en boot
pm2 unstartup                 # Remover auto-start
```

---

## 🗑️ Desinstalación Completa

### Método 1: Script Automático (Recomendado)

```bash
cd /home/asegural/public_html/nextjs-prototype
./uninstall-prototype.sh
```

**Esto eliminará:**
- ✅ Servicios PM2
- ✅ node_modules/ (~200MB)
- ✅ .next/ (builds)
- ✅ logs/
- ✅ .env files
- ✅ Liberará puertos 3000 y 3001

**NO eliminará:**
- ✅ Código fuente (por si quieres reinstalar)
- ✅ Documentación

### Método 2: Manual

```bash
# 1. Detener y eliminar servicios PM2
pm2 delete aseguralocr-backend
pm2 delete aseguralocr-frontend
pm2 save

# 2. Eliminar dependencias y builds
cd /home/asegural/public_html/nextjs-prototype
rm -rf node_modules
rm -rf backend/node_modules
rm -rf .next
rm -rf logs

# 3. Eliminar configuración
rm -f .env.local
rm -f backend/.env
```

### Método 3: Eliminación Total (incluye código)

```bash
cd /home/asegural/public_html
rm -rf nextjs-prototype/
```

### Limpiar Configuración Web Server

#### Nginx
```bash
# Eliminar subdominio
sudo rm /etc/nginx/sites-enabled/prototype.aseguralocr.com
sudo rm /etc/nginx/sites-available/prototype.aseguralocr.com
sudo systemctl reload nginx

# Revocar SSL
sudo certbot delete --cert-name prototype.aseguralocr.com
```

#### Apache
```bash
# Eliminar subdominio
sudo a2dissite prototype.aseguralocr.com
sudo rm /etc/apache2/sites-available/prototype.aseguralocr.com.conf
sudo systemctl reload apache2

# Revocar SSL
sudo certbot delete --cert-name prototype.aseguralocr.com
```

---

## 🔍 Troubleshooting

### Problema: "Node.js no encontrado"

**Solución:**
```bash
# Verificar instalación
which node
which npm

# Si usa nvm
nvm use 18

# Agregar a PATH
echo 'export PATH=$PATH:/usr/local/bin' >> ~/.bashrc
source ~/.bashrc
```

### Problema: "PM2 no encontrado"

**Solución:**
```bash
npm install -g pm2

# Si da error de permisos
sudo npm install -g pm2

# Para cPanel sin sudo
npm install -g pm2 --prefix=$HOME/.npm-global
echo 'export PATH=$PATH:$HOME/.npm-global/bin' >> ~/.bashrc
source ~/.bashrc
```

### Problema: "Cannot connect to database"

**Verificar:**
```bash
# 1. Credenciales en backend/.env
cat backend/.env

# 2. MySQL corriendo
mysql -h localhost -u usuario -p

# 3. Permisos de usuario
SHOW GRANTS FOR 'usuario'@'localhost';
```

**Solución:**
```bash
# Editar backend/.env con credenciales correctas
nano backend/.env

# Reiniciar backend
pm2 restart aseguralocr-backend
pm2 logs aseguralocr-backend
```

### Problema: "Port already in use"

**Verificar qué usa el puerto:**
```bash
lsof -i :3000
lsof -i :3001

# Matar proceso
kill -9 <PID>

# O cambiar puerto en ecosystem.config.js
```

### Problema: "502 Bad Gateway" en navegador

**Posibles causas:**

1. **PM2 no corriendo**
   ```bash
   pm2 status
   pm2 restart all
   ```

2. **Puerto incorrecto en Nginx/Apache**
   ```bash
   # Verificar que proxy apunta a puertos correctos
   sudo nginx -t
   # o
   sudo apachectl configtest
   ```

3. **Firewall bloqueando**
   ```bash
   # Ver puertos abiertos
   sudo netstat -tlnp | grep -E '3000|3001'

   # Abrir puertos (si es necesario)
   sudo ufw allow 3000
   sudo ufw allow 3001
   ```

### Problema: "Application error" en Next.js

**Ver logs detallados:**
```bash
pm2 logs aseguralocr-frontend --lines 200

# Revisar build
cd /home/asegural/public_html/nextjs-prototype
npm run build
```

### Problema: Frontend carga pero API no responde

**Verificar:**
```bash
# 1. Backend corriendo
pm2 status aseguralocr-backend

# 2. API responde localmente
curl http://localhost:3001/api/health

# 3. Variable de entorno del frontend
cat .env.local
# Debe tener: NEXT_PUBLIC_API_URL=http://localhost:3001

# 4. CORS configurado
# Ver backend/server.js → CORS_ORIGIN
```

---

## 📊 Verificación de Funcionamiento

### Checklist Completo

```bash
# ✅ 1. Node.js instalado
node --version  # >= v18

# ✅ 2. PM2 instalado
pm2 --version

# ✅ 3. Dependencias instaladas
ls -la node_modules  # Debe existir
ls -la backend/node_modules  # Debe existir

# ✅ 4. Build compilado
ls -la .next  # Debe existir

# ✅ 5. Variables de entorno configuradas
cat .env.local  # NEXT_PUBLIC_API_URL
cat backend/.env  # DB credentials

# ✅ 6. PM2 corriendo
pm2 status  # Ambos "online"

# ✅ 7. Backend responde
curl http://localhost:3001/api/health
# {"status":"ok",...}

# ✅ 8. Frontend responde
curl -I http://localhost:3000
# HTTP/1.1 200 OK

# ✅ 9. Proxy web server funcionando
curl -I https://prototype.aseguralocr.com
# HTTP/1.1 200 OK

# ✅ 10. SSL activo
curl -I https://prototype.aseguralocr.com | grep -i strict
# strict-transport-security: ...
```

### Test de Carga Simple

```bash
# Instalar apache bench
sudo apt-get install apache2-utils

# Test backend
ab -n 100 -c 10 http://localhost:3001/api/health

# Test frontend
ab -n 100 -c 10 http://localhost:3000/
```

---

## 🔒 Seguridad

### Recomendaciones

1. **Firewall**: Solo abrir puertos necesarios
   ```bash
   # Cerrar 3000 y 3001 al público
   sudo ufw deny 3000
   sudo ufw deny 3001

   # Solo permitir acceso local (proxy interno)
   # Nginx/Apache hace proxy desde 80/443
   ```

2. **Rate Limiting**: Agregar en Nginx
   ```nginx
   limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

   location /api {
       limit_req zone=api burst=20;
       proxy_pass http://localhost:3001;
   }
   ```

3. **Headers de Seguridad**: Ya incluidos en configs
   - X-Frame-Options
   - X-Content-Type-Options
   - X-XSS-Protection

4. **Monitoreo**: Configurar alertas PM2
   ```bash
   pm2 install pm2-logrotate
   ```

---

## 📈 Próximos Pasos

Después de instalar:

1. **Comparar con versión PHP**
   - PHP: https://aseguralocr.com/
   - Next.js: https://prototype.aseguralocr.com/
   - Comparar velocidad, navegación, UX

2. **Revisar documentación**
   - `README.md` - Información general
   - `COMPARACION.md` - Comparación detallada

3. **Decidir siguiente paso**
   - Continuar con Next.js
   - Migrar a Laravel
   - Mantener PHP actual

---

## 📞 Soporte

**Logs importantes:**
```bash
# PM2 logs
pm2 logs
~/.pm2/logs/

# Nginx logs
/var/log/nginx/prototype.aseguralocr.com.access.log
/var/log/nginx/prototype.aseguralocr.com.error.log

# Apache logs
/var/log/apache2/prototype.aseguralocr.com-error.log
```

**Comandos de diagnóstico:**
```bash
# Estado general del sistema
pm2 status
pm2 monit
netstat -tlnp | grep -E '3000|3001'
systemctl status nginx  # o apache2
```

---

## ✅ Resumen Rápido

```bash
# INSTALAR
cd /home/asegural/public_html/nextjs-prototype
./install-prototype.sh
pm2 status  # Verificar "online"

# CONFIGURAR ACCESO PÚBLICO
# Ver sección "Configuración Nginx/Apache"

# VERIFICAR
curl http://localhost:3001/api/health
curl http://localhost:3000
https://prototype.aseguralocr.com  # En navegador

# DESINSTALAR
./uninstall-prototype.sh
```

¡Listo! Tu prototipo Next.js está **completamente aislado** del sistema PHP y puede ser eliminado fácilmente. 🎉
