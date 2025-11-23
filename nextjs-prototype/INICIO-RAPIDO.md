# 🚀 Inicio Rápido - Prototipo Next.js

## ⚡ 3 Pasos para Instalar

### 1️⃣ Conectar al Servidor
```bash
ssh tu-usuario@aseguralocr.com
```

### 2️⃣ Ir al Directorio del Prototipo
```bash
cd /home/asegural/public_html/nextjs-prototype
```

### 3️⃣ Ejecutar Instalador
```bash
./install-prototype.sh
```

**¡Eso es todo!** 🎉

El script automáticamente:
- ✅ Verifica Node.js, npm, PM2
- ✅ Instala dependencias
- ✅ Lee credenciales de MySQL de config.php
- ✅ Compila Next.js
- ✅ Inicia servicios con PM2

**Tiempo: 5-10 minutos**

---

## 🌐 Acceder al Prototipo

Después de la instalación:

### Acceso Local (en el servidor)
```bash
# Backend API
curl http://localhost:3001/api/health

# Frontend
curl http://localhost:3000
```

### Acceso Público

**Opción 1: Subdominio (Recomendado)**
- URL: `https://prototype.aseguralocr.com`
- Configurar DNS + Nginx/Apache
- Ver: `DEPLOYMENT.md` (sección "Configuración de Acceso Público")

**Opción 2: Ruta en dominio principal**
- URL: `https://aseguralocr.com/prototype`
- Configurar Nginx/Apache proxy
- Ver: `DEPLOYMENT.md`

---

## 📊 Verificar que Funciona

```bash
# Ver estado de servicios
pm2 status

# Deberías ver:
# aseguralocr-backend    │ online
# aseguralocr-frontend   │ online

# Ver logs
pm2 logs
```

Si ambos están "online" → **¡Funcionó!** ✅

---

## 🔧 Comandos Útiles

```bash
# Ver estado
pm2 status

# Ver logs en tiempo real
pm2 logs

# Reiniciar servicios
pm2 restart all

# Detener servicios
pm2 stop all

# Iniciar servicios
pm2 start all
```

---

## 🗑️ Desinstalar

Si quieres eliminar el prototipo:

```bash
./uninstall-prototype.sh
```

Esto eliminará:
- ✅ Servicios PM2
- ✅ node_modules (~200MB)
- ✅ Builds
- ✅ Logs
- ✅ Archivos .env

**NO eliminará:**
- ✅ Código fuente (por si quieres reinstalar)
- ✅ Tu sistema PHP (queda intacto)

Para reinstalar: `./install-prototype.sh`

---

## 📚 Más Información

- **DEPLOYMENT.md** - Guía completa de instalación y configuración
- **README.md** - Información general del proyecto
- **COMPARACION.md** - Comparación PHP vs Next.js

---

## ❓ Problemas Comunes

### "Node.js no encontrado"
```bash
# Instalar con nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 18
```

### "PM2 no encontrado"
```bash
npm install -g pm2
```

### "Cannot connect to database"
```bash
# Verificar backend/.env
cat backend/.env

# Editar si es necesario
nano backend/.env

# Reiniciar
pm2 restart aseguralocr-backend
```

### Servicios no inician
```bash
# Ver logs detallados
pm2 logs

# Reinstalar
pm2 delete all
./install-prototype.sh
```

---

## 🎯 ¿Qué Sigue?

1. **Probar localmente**: `curl http://localhost:3000`
2. **Configurar acceso público**: Ver `DEPLOYMENT.md`
3. **Comparar con PHP**: Navegar ambas versiones
4. **Decidir**: ¿Seguir con Next.js, Laravel, o PHP actual?

---

## ✅ Checklist de Instalación

- [ ] SSH al servidor conectado
- [ ] En directorio `nextjs-prototype/`
- [ ] Ejecutado `./install-prototype.sh`
- [ ] PM2 muestra servicios "online"
- [ ] `curl http://localhost:3001/api/health` responde
- [ ] `curl http://localhost:3000` responde
- [ ] Configurado proxy Nginx/Apache (opcional)
- [ ] Acceso público funcionando (opcional)

---

**¿Listo para instalar?**

```bash
cd /home/asegural/public_html/nextjs-prototype
./install-prototype.sh
```

🚀 **¡Adelante!**
