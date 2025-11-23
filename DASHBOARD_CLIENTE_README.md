# 🏠 Sistema de Dashboard para Clientes - AseguraloCR

## 📋 Descripción General

Sistema completo de gestión de clientes con:
- ✅ **Login con Google OAuth** - Los clientes ingresan con su cuenta de Google
- ✅ **Dashboard de Cliente** - Ver pólizas, cotizaciones y pagos
- ✅ **Panel de Administración** - Registrar pólizas y pagos manualmente
- ✅ **Notificaciones** - Alertas de vencimientos y pagos
- ✅ **Tracking de Pagos** - Control de pagos pendientes y vencidos

---

## 🗄️ Base de Datos

### Paso 1: Ejecutar Migraciones

```bash
# Opción 1: Usando el script PHP
cd /home/asegural/public_html/aseguralocr
php database/run_migration.php

# Opción 2: Manualmente desde phpMyAdmin
# Ejecuta el archivo: database/migrations/002_client_dashboard_system.sql
```

### Tablas Creadas

1. **`clients`** - Clientes con autenticación Google
2. **`policies`** - Pólizas emitidas
3. **`payments`** - Pagos y cuotas
4. **`quotes`** - Cotizaciones
5. **`client_notifications`** - Notificaciones para clientes
6. **`oauth_settings`** - Configuración de Google OAuth

---

## 🔐 Configurar Google OAuth

### Paso 1: Crear Proyecto en Google Cloud Console

1. Ve a https://console.cloud.google.com/
2. Crea un nuevo proyecto llamado "AseguraloCR"
3. Habilita la API de "Google+ API"

### Paso 2: Crear Credenciales OAuth 2.0

1. Ve a **APIs & Services → Credentials**
2. Click en **"Create Credentials" → "OAuth client ID"**
3. Tipo de aplicación: **Web application**
4. Nombre: **AseguraloCR Client Portal**

5. **URIs de redirección autorizadas** - Agrega estas URLs:
   ```
   https://aseguralocr.com/client/oauth-callback.php
   https://staging.aseguralocr.com/client/oauth-callback.php
   http://localhost/client/oauth-callback.php  (para desarrollo)
   ```

6. Guarda y copia el **Client ID** y **Client Secret**

### Paso 3: Configurar en el Sistema

**Opción A: Archivo de configuración (Recomendado)**

```bash
# Copia el archivo de ejemplo
cp app/config/google_oauth.php.example app/config/google_oauth.php

# Edita con tus credenciales
nano app/config/google_oauth.php
```

Actualiza con tus valores:
```php
return [
    'client_id' => 'TU_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'TU_CLIENT_SECRET',
    'redirect_uri' => 'https://aseguralocr.com/client/oauth-callback.php',
];
```

**Opción B: Guardar en Base de Datos**

```sql
INSERT INTO oauth_settings (provider, client_id, client_secret, redirect_uri, enabled)
VALUES (
    'google',
    'TU_CLIENT_ID.apps.googleusercontent.com',
    'TU_CLIENT_SECRET',
    'https://aseguralocr.com/client/oauth-callback.php',
    TRUE
);
```

---

## 📁 Estructura de Archivos

### Portal de Clientes (`/client/`)

```
client/
├── login.php                    # Login con Google
├── oauth-callback.php           # Callback de Google OAuth
├── dashboard.php                # Dashboard principal
├── policies.php                 # Lista de pólizas (por crear)
├── quotes.php                   # Cotizaciones (por crear)
├── payments.php                 # Pagos (por crear)
├── profile.php                  # Perfil del cliente (por crear)
├── logout.php                   # Cerrar sesión
└── includes/
    ├── client_auth.php          # Middleware de autenticación
    └── nav.php                  # Navegación del cliente
```

### Panel de Administración (`/admin/`)

```
admin/
├── clients.php                  # ✅ Gestión de clientes
├── add-policy.php               # ✅ Registrar emisión de póliza
├── client-detail.php            # Detalles de cliente (por crear)
├── add-payment.php              # Registrar pago (por crear)
└── actions/
    ├── save-policy.php          # ✅ Guardar póliza
    ├── save-client.php          # Guardar cliente (por crear)
    └── save-payment.php         # Guardar pago (por crear)
```

### Servicios (`/app/services/`)

```
app/services/
├── GoogleAuth.php               # ✅ Servicio de autenticación Google
└── Security.php                 # ✅ Servicios de seguridad
```

---

## 🚀 Uso del Sistema

### Para Clientes

1. **Acceder al Portal**
   - URL: https://aseguralocr.com/client/login.php
   - Click en "Continuar con Google"
   - Autorizar el acceso

2. **Dashboard del Cliente**
   - Ver pólizas activas
   - Ver cotizaciones
   - Ver pagos pendientes
   - Notificaciones de vencimientos

### Para Administradores

#### 1. Gestionar Clientes

```
URL: /admin/clients.php
```

- Ver lista completa de clientes
- Crear nuevos clientes manualmente
- Ver resumen de pólizas y pagos por cliente

#### 2. Registrar Emisión de Póliza

```
URL: /admin/add-policy.php?client_id=123
```

**Datos requeridos:**
- ✅ Cliente
- ✅ Número de póliza (de la aseguradora)
- ✅ Tipo de seguro (hogar, auto, vida, salud, otros)
- ✅ Fechas (emisión, inicio, fin de vigencia)
- ✅ Prima anual
- ✅ Moneda (colones o dólares)

**Datos opcionales:**
- Prima mensual
- Monto asegurado
- Coberturas incluidas
- Archivo PDF de la póliza
- Notas administrativas

**Funcionalidad automática:**
- ✅ Genera plan de pagos automáticamente
- ✅ Crea notificación para el cliente
- ✅ Calcula prima mensual si solo ingresaste anual

#### 3. Registrar Pagos Manualmente

```
URL: /admin/add-payment.php?policy_id=456
```

Para registrar cuando un cliente paga:
- Seleccionar póliza
- Monto del pago
- Fecha de pago
- Método de pago
- Subir comprobante (opcional)

---

## 📊 Funcionalidades Automáticas

### Triggers de Base de Datos

1. **Actualizar estado de pólizas**
   - Cambia a "vencida" si la fecha de fin pasó
   - Cambia a "por_vencer" si faltan <= 30 días

2. **Actualizar estado de pagos**
   - Cambia a "vencido" si la fecha de vencimiento pasó

### Vista de Dashboard

La vista `client_dashboard_summary` proporciona:
- Total de pólizas
- Pólizas vigentes
- Pólizas por vencer
- Total de cotizaciones
- Pagos pendientes
- Monto pendiente total
- Próxima renovación

---

## 🔔 Sistema de Notificaciones

### Tipos de Notificaciones

1. **`poliza_emitida`** - Cuando se registra una póliza nueva
2. **`pago_pendiente`** - Recordatorio de pago próximo a vencer
3. **`poliza_por_vencer`** - Póliza próxima a vencer (30 días)
4. **`pago_recibido`** - Confirmación de pago registrado
5. **`cotizacion_lista`** - Cotización disponible para ver
6. **`general`** - Notificaciones generales

### Crear Notificación Manualmente

```php
$stmt = $pdo->prepare("
    INSERT INTO client_notifications (client_id, tipo, titulo, mensaje, policy_id)
    VALUES (:client_id, :tipo, :titulo, :mensaje, :policy_id)
");
$stmt->execute([
    ':client_id' => 123,
    ':tipo' => 'pago_pendiente',
    ':titulo' => 'Pago Próximo a Vencer',
    ':mensaje' => 'Tu cuota de ₡25,000 vence el 30/11/2024',
    ':policy_id' => 456
]);
```

---

## 🔗 Vincular Solicitudes Existentes con Clientes

Si ya tienes solicitudes en la tabla `submissions`, vincúlalas:

```sql
-- Ejemplo: vincular submission con cliente por email
UPDATE submissions s
INNER JOIN clients c ON s.email = c.email
SET s.client_id = c.id
WHERE s.client_id IS NULL;
```

---

## 🎨 Personalización

### Colores del Dashboard

Edita en `/client/dashboard.php`:
```css
.gradient-bg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Logo de la Empresa

Actualiza en `/client/includes/nav.php`:
```html
<div class="w-10 h-10 gradient-bg rounded-lg flex items-center justify-center">
    <!-- Reemplaza con tu logo -->
    <i class="fas fa-shield-alt text-white text-lg"></i>
</div>
```

---

## 📧 Configurar Emails de Notificación

Para enviar emails automáticos cuando:
- Se emite una póliza
- Se acerca un pago
- Se vence una póliza

Crea un cron job:

```bash
# /home/asegural/cron_notifications.php
*/30 * * * * php /home/asegural/public_html/aseguralocr/cron/send-notifications.php
```

---

## 🐛 Solución de Problemas

### Error: "Google OAuth no configurado"

✅ **Solución:** Verifica que `google_oauth.php` existe y tiene las credenciales correctas

### Error: "Token de seguridad inválido"

✅ **Solución:** El state de OAuth expiró. Intenta nuevamente desde el login

### Clientes no pueden ver pólizas

✅ **Solución:** Verifica que `policy.client_id` coincide con el ID del cliente

### Archivos PDF no se suben

✅ **Solución:**
```bash
# Crear directorio y dar permisos
mkdir -p /home/asegural/public_html/aseguralocr/storage/policies
chmod 755 /home/asegural/public_html/aseguralocr/storage/policies
```

---

## 🔒 Seguridad

### Medidas Implementadas

✅ CSRF Protection en todos los formularios
✅ Rate limiting en login
✅ Sesiones seguras (HttpOnly, Secure, SameSite)
✅ Credenciales fuera del código (config file)
✅ Validación de state en OAuth
✅ Prepared statements (PDO)
✅ Archivos sensibles bloqueados por .htaccess

### Recomendaciones

1. **Forzar HTTPS:**
   ```apache
   # En .htaccess
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

2. **Backup de Base de Datos:**
   ```bash
   # Cron diario a las 2 AM
   0 2 * * * mysqldump asegural_aseguralocr > /home/asegural/backups/db_$(date +\%Y\%m\%d).sql
   ```

3. **Logs de Acceso:**
   ```php
   // En client_auth.php
   error_log("Client login: {$_SESSION['client_email']} from {$_SERVER['REMOTE_ADDR']}");
   ```

---

## 📱 Páginas Pendientes por Completar

Las siguientes páginas están referenciadas pero necesitan ser creadas:

1. `/client/policies.php` - Lista completa de pólizas
2. `/client/quotes.php` - Lista de cotizaciones
3. `/client/payments.php` - Historial de pagos
4. `/client/profile.php` - Editar perfil del cliente
5. `/client/policy-detail.php` - Detalle de una póliza
6. `/client/quote-detail.php` - Detalle de cotización
7. `/admin/client-detail.php` - Vista detallada del cliente
8. `/admin/add-payment.php` - Registrar pago manual
9. `/admin/actions/save-client.php` - Backend para guardar cliente

---

## 🎯 Próximos Pasos Sugeridos

1. **Implementar páginas faltantes**
2. **Configurar envío de emails automáticos**
3. **Agregar reportes en PDF** para pólizas
4. **Crear dashboard de estadísticas** para admin
5. **Agregar renovación automática** de pólizas
6. **Implementar pasarela de pagos** (SINPE, tarjetas)

---

## 📞 Soporte

Para cualquier duda o problema:
- Email: info@aseguralocr.com
- Dashboard Admin: https://aseguralocr.com/admin/login.php

---

**Creado con ❤️ por Claude AI**
**Fecha: Noviembre 2024**
