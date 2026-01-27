# 🔔 Configurar Recordatorios de Pago

## 📋 Pasos para activar:

### 1️⃣ Crear las tablas en la base de datos

Ejecuta el archivo SQL:
```bash
mysql -u asegural_marugaul -p asegural_aseguralocr < SQL/payment_reminders.sql
```

O copia el contenido de `SQL/payment_reminders.sql` y ejecútalo en phpMyAdmin.

---

### 2️⃣ Acceder a la configuración

1. Ve al admin: **https://www.aseguralocr.com/admin/dashboard.php**
2. Click en **🔔 Recordatorios** en el menú
3. Verás la página de configuración

---

### 3️⃣ Configurar recordatorios

**Frecuencia de envío:**
- ☑ 30 días antes del vencimiento
- ☑ 15 días antes del vencimiento
- ☑ 1 día antes del vencimiento (si no pagó)

**Email:**
- Email remitente: `noreply@aseguralocr.com`
- Nombre remitente: `AseguraloCR`
- Asunto: `Recordatorio: Vencimiento de su póliza #{numero_poliza}`

**Plantilla:**
- La plantilla HTML ya viene precargada
- Puedes personalizarla con variables:
  - `{numero_poliza}` - Número de póliza
  - `{nombre_cliente}` - Nombre del cliente
  - `{monto}` - Monto a pagar
  - `{moneda}` - ₡ o $
  - `{fecha_vencimiento}` - Fecha de vencimiento
  - `{tipo_pago}` - Cuota mensual, trimestral, etc.

---

### 4️⃣ Probar el email

1. Click en **📧 Enviar Email de Prueba**
2. Se enviará un email de prueba a tu dirección registrada
3. Verifica que se vea bien

---

### 5️⃣ Configurar el CRON (envío automático)

Agrega este cron para que se ejecute **diariamente a las 8:00 AM**:

```bash
0 8 * * * php /home/asegural/public_html/aseguralocr/cron-send-reminders.php >> /home/asegural/reminders.log 2>&1
```

**En cPanel:**
1. Ve a **Cron Jobs**
2. Agrega nuevo cron
3. **Hora:** `8` **Minuto:** `0` (8:00 AM)
4. **Comando:**
   ```
   php /home/asegural/public_html/aseguralocr/cron-send-reminders.php >> /home/asegural/reminders.log 2>&1
   ```

---

### 6️⃣ Verificar que funciona

**Opción 1: Ejecutar manualmente**
```bash
php /home/asegural/public_html/aseguralocr/cron-send-reminders.php
```

**Opción 2: Ver el log**
```bash
cat /home/asegural/reminders.log
```

Deberías ver algo como:
```
=== Payment Reminders - 2026-01-27 08:00:00 ===

Checking 30-day reminders...
  ✓ Sent to Juan Pérez (juan@example.com)
  ✓ Sent to María González (maria@example.com)

Checking 15-day reminders...
  ✓ Sent to Carlos López (carlos@example.com)

=== Summary ===
Sent: 3
Failed: 0
Done.
```

---

## 🎯 Funcionalidades:

✅ **Recordatorios automáticos** - Se envían sin intervención manual
✅ **Sin duplicados** - Solo se envía una vez por tipo de recordatorio
✅ **Trackeo** - Todos los envíos quedan registrados en la BD
✅ **Solo pagos pendientes** - No envía a pagos ya pagados
✅ **Personalizable** - Plantilla HTML editable
✅ **Email de prueba** - Verifica antes de activar

---

## 📊 Ver historial de recordatorios

**Consulta SQL:**
```sql
SELECT
    p.fecha_vencimiento,
    pol.numero_poliza,
    c.nombre_completo,
    rs.reminder_type,
    rs.sent_at,
    rs.status
FROM payment_reminders_sent rs
JOIN payments p ON rs.payment_id = p.id
JOIN policies pol ON p.policy_id = pol.id
JOIN clients c ON p.client_id = c.id
ORDER BY rs.sent_at DESC
LIMIT 50;
```

---

## ⚠️ Importante:

1. **Configurar servidor de email** - Asegúrate de que PHP `mail()` funcione correctamente en tu servidor
2. **SPF/DKIM** - Configura registros DNS para evitar spam
3. **Probar primero** - Usa el botón de email de prueba antes de activar
4. **Monitorear** - Revisa el log periódicamente

---

## 🚀 Listo!

Una vez configurado, el sistema enviará recordatorios automáticamente cada día a las 8 AM.

**Próximo paso:** Agregar envío por WhatsApp (ver `GUIA-WHATSAPP.md`)
