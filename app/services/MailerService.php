<?php
require_once __DIR__ . '/../../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../../vendor/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

class MailerService {
  public static function enviar(array $cfg, array $data, string $pdfPath, string $referencia): bool {
    $m = new PHPMailer(true);
    if ($cfg['smtp']) {
      $m->isSMTP();
      $m->Host = $cfg['host'];
      $m->Port = $cfg['port'];
      $m->SMTPAuth = true;
      $m->SMTPSecure = $cfg['secure'];
      $m->Username = $cfg['user'];
      $m->Password = $cfg['pass'];
    }
    $m->CharSet  = 'UTF-8';

    [$fromEmail,$fromName] = $cfg['from'];
    $m->setFrom($fromEmail, $fromName);
    foreach ($cfg['to'] as $rcpt) $m->addAddress($rcpt);
    foreach (($cfg['bcc'] ?? []) as $rcpt) $m->addBCC($rcpt);

    if (!empty($data['correo']) && filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
      $m->addReplyTo($data['correo'], $data['nombreCompleto'] ?? 'Cliente');
    }

    $m->isHTML(true);
    $m->Subject = "Nueva solicitud Hogar ($referencia)";
    $m->Body = "<p>Se adjunta PDF de la solicitud <b>$referencia</b>.</p>";

    if (is_file($pdfPath)) $m->addAttachment($pdfPath);

    return $m->send();
  }
}
