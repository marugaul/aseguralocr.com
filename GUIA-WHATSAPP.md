# 📱 Guía: Agregar Recordatorios por WhatsApp

## Opciones para enviar WhatsApp:

### **Opción 1: Twilio (Recomendada - Más confiable)**

1. **Crear cuenta en Twilio**
   - https://www.twilio.com/
   - Obtener Account SID y Auth Token
   - Configurar WhatsApp Business Sender

2. **Instalar SDK de Twilio**
   ```bash
   composer require twilio/sdk
   ```

3. **Código de ejemplo:**
   ```php
   require_once 'vendor/autoload.php';
   use Twilio\Rest\Client;

   $sid = 'TU_ACCOUNT_SID';
   $token = 'TU_AUTH_TOKEN';
   $client = new Client($sid, $token);

   $message = $client->messages->create(
       'whatsapp:+50612345678', // Número destino
       [
           'from' => 'whatsapp:+14155238886', // Tu número Twilio
           'body' => 'Recordatorio: Tu póliza vence en 30 días'
       ]
   );
   ```

4. **Costo:** ~$0.005 por mensaje

---

### **Opción 2: WhatsApp Business API (Gratis pero complejo)**

1. **Requisitos:**
   - Tener WhatsApp Business Account
   - Verificar número de teléfono
   - Aprobar plantillas de mensajes

2. **Configuración:**
   - https://business.facebook.com/
   - Crear app en Facebook Developers
   - Obtener token de acceso

3. **Código ejemplo:**
   ```php
   $token = 'TU_WHATSAPP_TOKEN';
   $phone = '50612345678';
   $templateName = 'recordatorio_pago';

   $ch = curl_init('https://graph.facebook.com/v18.0/TU_PHONE_ID/messages');
   curl_setopt($ch, CURLOPT_HTTPHEADER, [
       'Authorization: Bearer ' . $token,
       'Content-Type: application/json'
   ]);
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
       'messaging_product' => 'whatsapp',
       'to' => $phone,
       'type' => 'template',
       'template' => [
           'name' => $templateName,
           'language' => ['code' => 'es'],
           'components' => [
               [
                   'type' => 'body',
                   'parameters' => [
                       ['type' => 'text', 'text' => 'POL-2026-001'],
                       ['type' => 'text', 'text' => '₡150,000.00'],
                       ['type' => 'text', 'text' => '15/02/2026']
                   ]
               ]
           ]
       ]
   ]));
   $response = curl_exec($ch);
   curl_close($ch);
   ```

4. **Costo:** Gratis (hasta cierto límite)

---

### **Opción 3: Ultramsg (Más simple, sin aprobación)**

1. **Crear cuenta:** https://ultramsg.com/
2. **Obtener API Token**
3. **Código:**
   ```php
   $token = 'TU_ULTRAMSG_TOKEN';
   $instance = 'TU_INSTANCE_ID';
   $phone = '50612345678';
   $message = urlencode('Recordatorio: Tu póliza vence pronto');

   $url = "https://api.ultramsg.com/{$instance}/messages/chat";
   $data = [
       'token' => $token,
       'to' => $phone,
       'body' => $message
   ];

   $ch = curl_init($url);
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   $response = curl_exec($ch);
   curl_close($ch);
   ```

4. **Costo:** ~$10/mes plan básico

---

## 🔧 Integración en AseguraloCR:

### Paso 1: Agregar campo de teléfono en clientes

```sql
ALTER TABLE clients ADD COLUMN telefono_whatsapp VARCHAR(20) AFTER telefono;
ALTER TABLE clients ADD COLUMN preferencia_notificacion ENUM('email', 'whatsapp', 'ambos') DEFAULT 'email';
```

### Paso 2: Actualizar formulario de clientes

Agregar campo para WhatsApp y preferencia de notificación.

### Paso 3: Modificar `cron-send-reminders.php`

Agregar lógica para enviar por WhatsApp además de email:

```php
// Después de enviar email, verificar si también enviar WhatsApp
if ($client['preferencia_notificacion'] === 'whatsapp' ||
    $client['preferencia_notificacion'] === 'ambos') {

    if (!empty($client['telefono_whatsapp'])) {
        sendWhatsAppReminder($client['telefono_whatsapp'], $reminderData);
    }
}
```

### Paso 4: Crear función de envío

```php
function sendWhatsAppReminder($phone, $data) {
    // Usar Twilio, WhatsApp API o Ultramsg
    // según la opción que elijas
}
```

---

## 📋 Recomendación:

**Para empezar:** Usa **Twilio** (opción 1)
- Es la más confiable
- Configuración simple
- Buenos límites de envío
- Documentación excelente

**Una vez funcione:** Migra a **WhatsApp Business API** (opción 2)
- Gratis (hasta 1000 mensajes/día)
- Oficial de WhatsApp
- Más profesional

---

## 🚀 ¿Quieres que implemente WhatsApp ahora?

Dime qué opción prefieres (Twilio, WhatsApp API o Ultramsg) y te lo configuro completo.
