-- Tabla para configuración de recordatorios de pago
CREATE TABLE IF NOT EXISTS payment_reminders_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    send_30_days_before BOOLEAN DEFAULT TRUE,
    send_15_days_before BOOLEAN DEFAULT TRUE,
    send_1_day_before BOOLEAN DEFAULT TRUE,
    email_from VARCHAR(255) DEFAULT 'noreply@aseguralocr.com',
    email_from_name VARCHAR(255) DEFAULT 'AseguraloCR',
    email_subject VARCHAR(255) DEFAULT 'Recordatorio: Vencimiento de su póliza #{numero_poliza}',
    email_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla para trackear emails enviados (evitar duplicados)
CREATE TABLE IF NOT EXISTS payment_reminders_sent (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    reminder_type ENUM('30_days', '15_days', '1_day') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email_to VARCHAR(255) NOT NULL,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reminder (payment_id, reminder_type)
);

-- Insertar configuración por defecto
INSERT INTO payment_reminders_config (id, email_template) VALUES (1, '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Pago</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">AseguraloCR</h1>
                            <p style="color: #dbeafe; margin: 10px 0 0 0; font-size: 14px;">Recordatorio de Pago</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1e293b; margin: 0 0 20px 0; font-size: 20px;">Estimado/a {nombre_cliente},</h2>

                            <p style="color: #475569; line-height: 1.6; margin: 0 0 20px 0;">
                                Le recordamos que tiene un pago pendiente para su póliza de seguro.
                            </p>

                            <!-- Payment Details Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color: #64748b; font-size: 14px; font-weight: 600;">Número de Póliza:</td>
                                                <td style="color: #1e293b; font-size: 14px; font-weight: 700; text-align: right;">{numero_poliza}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-size: 14px; font-weight: 600;">Monto a Pagar:</td>
                                                <td style="color: #1e293b; font-size: 18px; font-weight: 700; text-align: right;">{moneda} {monto}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-size: 14px; font-weight: 600;">Fecha de Vencimiento:</td>
                                                <td style="color: #dc2626; font-size: 14px; font-weight: 700; text-align: right;">{fecha_vencimiento}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-size: 14px; font-weight: 600;">Tipo de Pago:</td>
                                                <td style="color: #1e293b; font-size: 14px; text-align: right;">{tipo_pago}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #475569; line-height: 1.6; margin: 20px 0;">
                                Para mantener su cobertura activa, por favor realice el pago antes de la fecha de vencimiento.
                            </p>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="https://www.aseguralocr.com/client/payments.php" style="display: inline-block; background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;">Ver Mis Pagos</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #64748b; font-size: 13px; line-height: 1.6; margin: 20px 0 0 0;">
                                Si ya realizó el pago, por favor ignore este mensaje. Si tiene alguna duda, contáctenos.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="color: #64748b; font-size: 12px; margin: 0 0 10px 0;">
                                © 2026 AseguraloCR. Todos los derechos reservados.
                            </p>
                            <p style="color: #94a3b8; font-size: 11px; margin: 0;">
                                Este es un correo automático, por favor no responder.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
') ON DUPLICATE KEY UPDATE id=id;
