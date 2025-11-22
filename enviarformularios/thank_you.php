<?php
declare(strict_types=1);
// thank_you.php - Página de agradecimiento después de enviar formulario

$ref_submission = $_GET['ref'] ?? '';
$ref_cotizacion = $_GET['cot'] ?? '';
$email_enviado = isset($_GET['email']) && $_GET['email'] === '1';
$pdf_url = $_GET['pdf'] ?? '';

function escapeHtml(?string $str): string {
    if (!$str) return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Recibida - AseguraloCR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 48px 36px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        h1 {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            color: #111827;
            margin-bottom: 16px;
        }
        
        .subtitle {
            font-size: 16px;
            line-height: 1.6;
            text-align: center;
            color: #6b7280;
            margin-bottom: 32px;
        }
        
        .info-box {
            background: #f9fafb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border-left: 4px solid #3b82f6;
        }
        
        .info-item {
            margin-bottom: 20px;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        
        .status-message {
            text-align: center;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 15px;
            margin-bottom: 28px;
        }
        
        .status-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .status-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }
        
        .footer {
            margin-top: 32px;
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer p {
            font-size: 13px;
            color: #9ca3af;
            line-height: 1.6;
        }
        
        .logo {
            max-width: 180px;
            margin: 0 auto 24px;
            display: block;
        }
        
        @media (max-width: 640px) {
            .container {
                padding: 32px 24px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .success-icon {
                width: 64px;
                height: 64px;
                font-size: 36px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        
        <h1>¡Solicitud Recibida Exitosamente!</h1>
        
        <p class="subtitle">
            Gracias por confiar en AseguraloCR. Su solicitud de seguro de hogar ha sido procesada correctamente. 
            Nuestro equipo revisará la información y se pondrá en contacto con usted a la brevedad posible.
        </p>
        
        <?php if ($ref_submission || $ref_cotizacion): ?>
        <div class="info-box">
            <?php if ($ref_submission): ?>
            <div class="info-item">
                <div class="info-label">Referencia de Envío</div>
                <div class="info-value"><?= escapeHtml($ref_submission) ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($ref_cotizacion): ?>
            <div class="info-item">
                <div class="info-label">Código de Cotización</div>
                <div class="info-value"><?= escapeHtml($ref_cotizacion) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="status-message <?= $email_enviado ? 'status-success' : 'status-warning' ?>">
            <?php if ($email_enviado): ?>
                ✓ Se ha enviado un correo electrónico de confirmación a su bandeja de entrada
            <?php else: ?>
                ⚠ No se pudo enviar el correo de confirmación en este momento, pero su solicitud fue registrada correctamente
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <?php if ($pdf_url): ?>
            <a href="<?= escapeHtml($pdf_url) ?>" target="_blank" class="btn btn-primary">
                📄 Descargar PDF de la Solicitud
            </a>
            <?php endif; ?>
            
            <a href="/" class="btn btn-secondary">
                ← Volver al Inicio
            </a>
        </div>
        
        <div class="footer">
            <p>
                <strong>AseguraloCR</strong><br>
                Protegiendo lo que más valoras<br>
                info@aseguralocr.com | www.aseguralocr.com
            </p>
        </div>
    </div>
</body>
</html>