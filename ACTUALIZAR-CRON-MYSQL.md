# ⚠️ ACTUALIZACIÓN NECESARIA DEL CRON MYSQL

## El archivo SQL está listo en: mysql-pendientes/crear-todas-tablas.txt

### 🔧 CAMBIO NECESARIO EN CRON (Una sola vez):

**Cron actual que NO funcionará:**
```bash
cd /home/asegural/public_html/sql-pendientes && for f in *.txt; do ...
```

**Cron correcto que debe estar configurado:**
```bash
/bin/bash /home/asegural/public_html/aseguralocr/mysql-auto-executor.sh
```

### 📋 Pasos en cPanel:

1. Ir a: **cPanel → Cron Jobs**
2. Buscar el cron de MySQL (el que ejecuta cada minuto)
3. Cambiar el comando a:
   ```
   /bin/bash /home/asegural/public_html/aseguralocr/mysql-auto-executor.sh
   ```
4. Guardar

### ✅ ¿Por qué este cambio?

- Tu Git sync trae los archivos a: `/home/asegural/public_html/aseguralocr/`
- El script `mysql-auto-executor.sh` ya está en esa ruta
- El script busca archivos en: `mysql-pendientes/`
- Todo está alineado correctamente ahora

### 🎯 Después de este cambio:

- **Yo pusheo** archivos .sql o .txt a `mysql-pendientes/`
- **Git sync** los trae a tu servidor (cada 3 min)
- **Cron ejecuta** automáticamente (cada 1 min)
- **Archivos procesados** se mueven a `mysql-ejecutados/`
- **Logs** se guardan en `mysql-logs/`

### 📁 Archivo listo para ejecutar:

Ya está en cola: `mysql-pendientes/crear-todas-tablas.txt`

Este archivo creará las 6 tablas del dashboard:
- clients
- policies
- payments
- quotes
- client_notifications
- oauth_settings

**Una vez actualices el cron, en máximo 4 minutos las tablas estarán creadas.**

---

*Este es el ÚNICO cambio manual que necesitas hacer. Después de esto, todo es automático.* 🚀
