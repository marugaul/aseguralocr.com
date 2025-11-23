# 🚀 MySQL Auto-Executor - Sistema Automático

## 📋 ¿Qué es esto?

Un sistema **permanente** que ejecuta automáticamente cualquier script SQL que yo (Claude) te envíe.

---

## 🎯 Cómo Funciona

```
1. Claude pushea archivo .sql a: mysql-pendientes/
2. Git sync lo trae a tu servidor
3. Cron ejecuta mysql-auto-executor.sh cada minuto
4. Script detecta el .sql y lo ejecuta
5. Archivo se mueve a: mysql-ejecutados/
6. Log se guarda en: mysql-logs/
```

---

## ⚙️ Configuración del Cron

**Comando para cPanel → Cron Jobs:**

```bash
/bin/bash /home/asegural/public_html/mysql-auto-executor.sh
```

**Frecuencia:** Cada minuto (o cada 5 minutos si prefieres)

```
Minuto: *
Hora: *
Día: *
Mes: *
Día de semana: *
```

---

## 📁 Estructura de Carpetas

```
public_html/
├── mysql-auto-executor.sh       ← Script principal (permanente)
├── mysql-pendientes/            ← Aquí van los .sql nuevos
│   └── ejemplo.sql
├── mysql-ejecutados/            ← Archivos ya procesados
│   └── 20241123_183045_ejemplo.sql
└── mysql-logs/                  ← Logs de cada ejecución
    └── 20241123_183045_ejemplo.sql.log
```

---

## 🔄 Uso Futuro

### **Cuando necesite ejecutar SQL:**

1. **Yo (Claude) creo un archivo .sql**
2. **Lo pusheo a:** `mysql-pendientes/nombre.sql`
3. **Git sync lo trae** (máximo 5 minutos)
4. **Cron lo detecta y ejecuta** (en 1 minuto)
5. **Listo** - Verás el log en `mysql-logs/`

### **Tú no haces nada** - Es completamente automático ✨

---

## 📊 Ver Resultados

### **Opción 1: File Manager**
- Ir a: `mysql-logs/`
- Abrir el log más reciente
- Ver si dice "✅ EJECUTADO EXITOSAMENTE"

### **Opción 2: Verificar en phpMyAdmin**
- Ver si las tablas/cambios se aplicaron

---

## 🧪 Probar el Sistema

Ya incluí un archivo de prueba: `mysql-pendientes/crear-tablas-dashboard.sql`

**Para probarlo:**

1. Configura el cron (comando arriba)
2. Espera 1-5 minutos
3. Revisa: `mysql-logs/` → Último archivo
4. Deberías ver las 6 tablas creadas

---

## 🛠️ Ventajas

✅ **Permanente** - El cron siempre está activo
✅ **Automático** - No necesitas hacer nada manual
✅ **Con logs** - Cada ejecución queda registrada
✅ **Seguro** - Solo ejecuta archivos en `pendientes/`
✅ **Organizado** - Archivos procesados se archivan
✅ **Reutilizable** - Sirve para cualquier SQL futuro

---

## 🔒 Seguridad

- ⚠️ Solo ejecuta archivos .sql/.txt de `mysql-pendientes/`
- ⚠️ Las credenciales están en el script (no las compartas)
- ⚠️ Los logs pueden contener datos sensibles

---

## 📝 Ejemplos de Uso Futuro

### **Crear una nueva tabla:**
```sql
-- mysql-pendientes/nueva-tabla.sql
CREATE TABLE nueva_tabla (
    id INT PRIMARY KEY,
    nombre VARCHAR(255)
);
```

### **Modificar tabla existente:**
```sql
-- mysql-pendientes/agregar-columna.sql
ALTER TABLE clients
ADD COLUMN nueva_columna VARCHAR(100);
```

### **Insertar datos:**
```sql
-- mysql-pendientes/datos-iniciales.sql
INSERT INTO oauth_settings (provider, client_id, client_secret, redirect_uri)
VALUES ('google', 'tu-client-id', 'tu-secret', 'https://...');
```

---

## ✅ Checklist de Instalación

- [ ] Cron configurado con el comando
- [ ] Script tiene permisos de ejecución (755)
- [ ] Carpetas creadas (pendientes, ejecutados, logs)
- [ ] Archivo de prueba en pendientes/
- [ ] Esperar 5 minutos
- [ ] Verificar log en mysql-logs/

---

**Una vez configurado, olvídate de ejecutar scripts manualmente. Yo pusheo, el cron ejecuta. Simple.** 🎉
