# 📅 Instalación vía Cron Job (cPanel)

## 🎯 Instrucciones Paso a Paso

### 1️⃣ Ir a Cron Jobs en cPanel

```
cPanel → Herramientas Avanzadas → Cron Jobs
```

---

### 2️⃣ Configurar el Cron

**Frecuencia:** Selecciona "Una vez" o configura manualmente:

```
Minuto:  *
Hora:    *
Día:     *
Mes:     *
Día de la semana: *
```

**Comando:** Copia y pega EXACTAMENTE esto (ajusta la ruta si es diferente):

```bash
/bin/bash /home/asegural/public_html/nextjs-prototype/install-via-cron.sh
```

---

### 3️⃣ Verificar la Ruta

**⚠️ IMPORTANTE:** Ajusta la ruta según tu configuración:

- Si tu usuario es `asegural`: `/home/asegural/public_html/...`
- Si es diferente: `/home/TU_USUARIO/public_html/...`

**Para verificar tu ruta correcta:**

En cPanel → File Manager → Navega a `nextjs-prototype` → Mira la barra de dirección.

---

### 4️⃣ Guardar y Ejecutar

1. Click en **"Add New Cron Job"**
2. Espera 1-2 minutos (el cron se ejecutará automáticamente)
3. Elimina el cron después (ya no es necesario)

---

## 📧 Verificar Ejecución

### Opción A: Email de cPanel

cPanel enviará un email con el resultado de la ejecución del cron a tu email registrado.

**Busca un email con asunto:** "Cron ..." que contenga el resultado de la instalación.

---

### Opción B: Ver el Log en File Manager

1. Ir a cPanel → File Manager
2. Navegar a: `public_html/nextjs-prototype/`
3. Buscar archivo: **`install-log.txt`**
4. Click derecho → View o Edit
5. Ver el resultado completo de la instalación

**Si ves:**
```
✅ INSTALACIÓN COMPLETADA EXITOSAMENTE
```
→ ¡Funcionó! 🎉

---

### Opción C: Verificar PM2 (Si tienes Terminal en cPanel)

Algunos cPanel tienen "Terminal":

```bash
pm2 status
```

Deberías ver:
```
aseguralocr-backend    │ online
aseguralocr-frontend   │ online
```

---

## 🔒 Protección Contra Re-instalación

El script **solo se ejecuta UNA vez**. Después crea un archivo `.installed` que previene ejecuciones duplicadas.

Si el cron se ejecuta de nuevo, simplemente dirá:
```
⚠️ INSTALACIÓN YA COMPLETADA ANTERIORMENTE
```

---

## 🗑️ Eliminar el Cron Después

**⚠️ IMPORTANTE:** Después de la instalación, elimina el cron job:

1. Ir a cPanel → Cron Jobs
2. Buscar el cron con el comando `install-via-cron.sh`
3. Click en **"Delete"**

**¿Por qué?** Ya no es necesario y evita ejecuciones innecesarias.

---

## 🎯 Alternativa: Ejecutar Manualmente (Sin esperar el cron)

Si tienes acceso a "Terminal" en cPanel:

```bash
cd /home/asegural/public_html/nextjs-prototype
./install-via-cron.sh
```

Esto ejecuta la instalación inmediatamente sin esperar el cron.

---

## 📊 Comandos para Verificar (Terminal cPanel)

```bash
# Ver estado de servicios
pm2 status

# Ver logs
pm2 logs

# Ver log de instalación
cat /home/asegural/public_html/nextjs-prototype/install-log.txt

# Probar backend
curl http://localhost:3001/api/health

# Probar frontend
curl http://localhost:3000
```

---

## 🔄 Para Reinstalar

Si necesitas reinstalar:

1. **Eliminar archivo lock:**
   - cPanel → File Manager
   - Navegar a `nextjs-prototype/`
   - Eliminar: `.installed`

2. **Ejecutar cron nuevamente** o ejecutar manualmente:
   ```bash
   ./install-via-cron.sh
   ```

---

## ❌ Si Algo Sale Mal

### "Command not found: pm2"

**Problema:** PM2 no se instaló correctamente.

**Solución en Terminal cPanel:**
```bash
npm install -g pm2
export PATH=$PATH:~/.npm-global/bin
echo 'export PATH=$PATH:~/.npm-global/bin' >> ~/.bashrc
```

Luego reinstalar:
```bash
cd /home/asegural/public_html/nextjs-prototype
rm .installed
./install-via-cron.sh
```

---

### "Node.js not found"

**Problema:** Node.js no instalado.

**Solución en cPanel:**
1. Ir a "Setup Node.js App"
2. Instalar Node.js 18.x
3. Crear app apuntando a `nextjs-prototype/`

O instalar con nvm (si tienes Terminal):
```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 18
```

---

### Ver Errores Detallados

```bash
# Ver todo el log
cat /home/asegural/public_html/nextjs-prototype/install-log.txt

# Ver solo errores
grep -i error /home/asegural/public_html/nextjs-prototype/install-log.txt
```

---

## 📱 Configuración Recomendada en cPanel

### Si tienes "Setup Node.js App":

1. Crear aplicación Node.js:
   - Application Root: `nextjs-prototype`
   - Application URL: `prototype.aseguralocr.com` (si usas subdominio)
   - Node.js Version: 18.x

2. **NO** usar "Run npm install" (lo hace el script)
3. **NO** configurar startup file (usamos PM2)

---

## ✅ Resumen Rápido

1. **Crear cron en cPanel:**
   ```bash
   /bin/bash /home/asegural/public_html/nextjs-prototype/install-via-cron.sh
   ```

2. **Esperar 1-2 minutos**

3. **Ver resultado:**
   - Email de cPanel
   - O File Manager → `install-log.txt`

4. **Eliminar el cron** (ya no necesario)

5. **Verificar:**
   - Terminal: `pm2 status`
   - O ver log: `install-log.txt`

---

## 🎉 Si Todo Funcionó

Verás en `install-log.txt`:
```
✅ INSTALACIÓN COMPLETADA EXITOSAMENTE

📊 Estado de servicios PM2:
aseguralocr-backend    │ online
aseguralocr-frontend   │ online

🌐 Acceso local:
   Backend:  http://localhost:3001/api/health
   Frontend: http://localhost:3000
```

**Siguiente paso:** Configurar acceso público (ver `DEPLOYMENT.md`)

---

## 📞 Ayuda

**Archivos importantes:**
- `install-log.txt` → Log completo de instalación
- `DEPLOYMENT.md` → Guía completa
- `INICIO-RAPIDO.md` → Guía rápida

**Si necesitas ayuda:** Comparte el contenido de `install-log.txt`
