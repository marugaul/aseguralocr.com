# AseguraloCR - Deployment Guide

Configuración de deployment automático con cPanel para ambientes de staging y producción.

## 📋 Estructura de Directorios

```
/home/asegural/
├── aseguralocr_repo/              # Repositorio Git para producción (rama main)
├── staging_repo/                  # Repositorio Git para staging (rama staging)
├── deploy-staging.sh              # Script de deployment para staging
├── deploy-production.sh           # Script de deployment para producción
├── deploy_staging.log             # Log de deployments de staging
├── deploy_production.log          # Log de deployments de producción
└── public_html/
    ├── aseguralocr/               # Producción → aseguralocr.com
    └── aseguralocrstaging/        # Staging → staging.aseguralocr.com
```

## 🚀 Workflow de Deployment

### Desarrollo
1. Claude hace cambios en el repositorio Git
2. Los cambios se pushean a la rama correspondiente

### Staging (testing)
- **Rama**: `staging`
- **URL**: staging.aseguralocr.com
- **Directorio**: `/home/asegural/public_html/aseguralocrstaging`
- **Cron**: Cada 5 minutos

### Producción
- **Rama**: `main`
- **URL**: aseguralocr.com
- **Directorio**: `/home/asegural/public_html/aseguralocr`
- **Cron**: Cada 10 minutos

## 📝 Configuración Inicial

### Paso 1: Subir scripts al servidor

Sube estos archivos a `/home/asegural/`:
- `deploy-staging.sh`
- `deploy-production.sh`

### Paso 2: Dar permisos de ejecución

```bash
chmod +x /home/asegural/deploy-staging.sh
chmod +x /home/asegural/deploy-production.sh
```

### Paso 3: Crear rama staging en GitHub

Si aún no existe la rama `staging`, créala:

```bash
# En el repositorio local
git checkout -b staging
git push -u origin staging
```

### Paso 4: Configurar token de GitHub (si el repo es privado)

Si el repositorio es privado, necesitas un token de acceso personal:

1. Ve a GitHub → Settings → Developer settings → Personal access tokens
2. Genera un nuevo token con permisos de `repo`
3. Modifica las URLs en los scripts:

```bash
# En lugar de:
GITHUB_REPO="https://github.com/marugaul/aseguralocr.com.git"

# Usa:
GITHUB_REPO="https://TU_TOKEN@github.com/marugaul/aseguralocr.com.git"
```

### Paso 5: Crear directorios de destino

Asegúrate de que existan los directorios de destino:

```bash
mkdir -p /home/asegural/public_html/aseguralocr
mkdir -p /home/asegural/public_html/aseguralocrstaging
```

### Paso 6: Configurar archivos de configuración en el servidor

Los archivos de configuración NO se sincronizan automáticamente (están excluidos).
Debes crearlos manualmente en cada ambiente:

**Para Staging:**
```bash
# Crear archivo de configuración de base de datos
/home/asegural/public_html/aseguralocrstaging/app/config/config.php
/home/asegural/public_html/aseguralocrstaging/includes/db.php
```

**Para Producción:**
```bash
# Crear archivo de configuración de base de datos
/home/asegural/public_html/aseguralocr/app/config/config.php
/home/asegural/public_html/aseguralocr/includes/db.php
```

### Paso 7: Crear directorios con permisos de escritura

```bash
# Staging
mkdir -p /home/asegural/public_html/aseguralocrstaging/logs
mkdir -p /home/asegural/public_html/aseguralocrstaging/storage/pdfs
mkdir -p /home/asegural/public_html/aseguralocrstaging/storage/uploads
mkdir -p /home/asegural/public_html/aseguralocrstaging/storage/temp
chmod -R 777 /home/asegural/public_html/aseguralocrstaging/logs
chmod -R 777 /home/asegural/public_html/aseguralocrstaging/storage

# Producción
mkdir -p /home/asegural/public_html/aseguralocr/logs
mkdir -p /home/asegural/public_html/aseguralocr/storage/pdfs
mkdir -p /home/asegural/public_html/aseguralocr/storage/uploads
mkdir -p /home/asegural/public_html/aseguralocr/storage/temp
chmod -R 777 /home/asegural/public_html/aseguralocr/logs
chmod -R 777 /home/asegural/public_html/aseguralocr/storage
```

## ⏰ Configuración de Cron Jobs

Accede a cPanel → Cron Jobs y añade:

### Staging (cada 5 minutos)
```
*/5 * * * * /bin/bash /home/asegural/deploy-staging.sh
```

### Producción (cada 10 minutos)
```
*/10 * * * * /bin/bash /home/asegural/deploy-production.sh
```

## 🔍 Monitoreo de Deployments

### Ver logs en tiempo real

**Staging:**
```bash
tail -f /home/asegural/deploy_staging.log
```

**Producción:**
```bash
tail -f /home/asegural/deploy_production.log
```

### Ver últimos deployments

```bash
tail -50 /home/asegural/deploy_staging.log
tail -50 /home/asegural/deploy_production.log
```

## 📂 Archivos Excluidos del Deployment

Los siguientes archivos/directorios NO se sincronizan (se mantienen en el servidor):

- `.git/` - Directorio Git
- `vendor/` y `composer/vendor/` - Dependencias (si usas Composer)
- `logs/` - Logs del sistema
- `storage/pdfs/`, `storage/uploads/`, `storage/temp/` - Archivos generados
- `app/config/config.php` - Configuración de base de datos
- `includes/db.php` - Configuración de conexión
- `.env` - Variables de entorno
- `*.log`, `php_error.log` - Archivos de log
- `sessions/` - Sesiones PHP
- `deploy-*.sh` - Scripts de deployment
- `DEPLOYMENT.md` - Esta documentación

## 🧪 Prueba Manual de Deployment

Para probar los scripts manualmente:

```bash
# Test staging
/bin/bash /home/asegural/deploy-staging.sh

# Test producción
/bin/bash /home/asegural/deploy-production.sh
```

## 🔄 Proceso de Desarrollo Típico

1. **Claude hace cambios** en el repositorio local
2. **Push a rama staging**:
   ```bash
   git checkout staging
   git merge claude/feature-branch
   git push origin staging
   ```
3. **Esperar 5 minutos** (o ejecutar manualmente) para que se despliegue a staging
4. **Probar en staging.aseguralocr.com**
5. **Si todo está OK, merge a main**:
   ```bash
   git checkout main
   git merge staging
   git push origin main
   ```
6. **Esperar 10 minutos** (o ejecutar manualmente) para producción

## 🛠️ Troubleshooting

### El deployment no funciona

1. Verifica que los scripts tienen permisos de ejecución:
   ```bash
   ls -la /home/asegural/deploy-*.sh
   ```

2. Verifica que Git está instalado:
   ```bash
   which git
   ```

3. Revisa los logs para ver errores:
   ```bash
   tail -100 /home/asegural/deploy_staging.log
   ```

### Los archivos no se actualizan

1. Verifica que el cron está ejecutándose:
   ```bash
   grep CRON /var/log/syslog | tail
   ```

2. Ejecuta el script manualmente para ver errores en tiempo real:
   ```bash
   bash -x /home/asegural/deploy-staging.sh
   ```

### Conflictos de Git

Los scripts usan `git reset --hard` que sobrescribe cualquier cambio local.
Si necesitas hacer cambios en el servidor, házlos en el repositorio Git, no directamente.

## 📞 Soporte

Para problemas o preguntas, revisa:
1. Los logs de deployment
2. Los logs de PHP en el servidor
3. Los logs de error de Apache/Nginx

---

**Última actualización**: 2025-11-22
