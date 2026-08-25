# Envio de Correos con PHPMailer

Esta guia explica como usar PHPMailer para enviar correos electronicos desde CHAVIsystems, usando Gmail SMTP.

---

## 1. Ubicacion y Carga

PHPMailer se encuentra en `utils/PHPMailer/`:

```
utils/
  PHPMailer/
    src/
      Exception.php
      PHPMailer.php
      SMTP.php
  mailer.php          -- Funcion helper para envio de correos
```

### Carga manual

```php
require_once 'utils/PHPMailer/src/Exception.php';
require_once 'utils/PHPMailer/src/PHPMailer.php';
require_once 'utils/PHPMailer/src/SMTP.php';
```

### Usando la funcion helper existente

El proyecto ya incluye `utils/mailer.php` con `sendResetPasswordMail()` para recuperacion de contrasena.

---

## 2. Configuracion SMTP (Gmail)

Las credenciales SMTP estan en `utils/mailer.php`:

```php
define('GMAIL_USER',         'appsmoviles@chavimochic.gob.pe');
define('GMAIL_APP_PASSWORD', '...');  // Clave de aplicacion de Gmail
define('GMAIL_FROM_EMAIL',   'appsmoviles@chavimochic.gob.pe');
define('GMAIL_FROM_NAME',    'Sistema Integral de Gestion CHAVISYSTEM');
```

La configuracion SMTP usa:
- Host: `smtp.gmail.com`
- Puerto: `587`
- Encriptacion: `STARTTLS`

---

## 3. Envio de Correo Simple

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../utils/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../utils/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../utils/PHPMailer/src/SMTP.php';

function enviarCorreo($destinatario, $asunto, $cuerpoHTML)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'appsmoviles@chavimochic.gob.pe';
        $mail->Password   = '...';  // Clave de aplicacion
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('appsmoviles@chavimochic.gob.pe', 'CHAVISYSTEM');
        $mail->addAddress($destinatario);
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body    = $cuerpoHTML;
        $mail->AltBody = strip_tags($cuerpoHTML);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo: " . $mail->ErrorInfo);
        return false;
    }
}
```

---

## 4. Plantilla de Correo HTML

```php
$html = '
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "Segoe UI", sans-serif; background: #f4f7f6; margin: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: #fff; padding: 20px; text-align: center; border-bottom: 3px solid #0a4f9e; }
        .content { padding: 30px; color: #333; line-height: 1.6; }
        .btn { display: inline-block; padding: 15px 25px; background: #0a4f9e; color: #fff !important; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .footer { background: #f8fafc; padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://app.chavimochic.gob.pe/Webservice/contador/LogoChavimochicFINAL.png" width="180" alt="CHAVIMOCHIC">
        </div>
        <div class="content">
            <h2 style="color: #0a4f9e;">Titulo del Correo</h2>
            <p>Hola, <strong>' . htmlspecialchars($nombre) . '</strong>.</p>
            <p>Contenido del mensaje aqui.</p>
        </div>
        <div class="footer">
            (c) ' . date('Y') . ' Proyecto Especial CHAVIMOCHIC<br>Area de Informatica
        </div>
    </div>
</body>
</html>';
```

---

## 5. Casos de Uso Comunes

### Recuperacion de Contrasena

Ya implementado en `utils/mailer.php`:

```php
require_once __DIR__ . '/../../utils/mailer.php';

$enlace = BASE_URL . '/recuperar?token=' . $token;
$enviado = sendResetPasswordMail($email, $nombre, $enlace);
```

### Notificacion de Nueva Solicitud

```php
$asunto = 'Nueva solicitud de ' . $tipo . ' - ' . $codigo;
$html = '<p>Se ha registrado una nueva solicitud:</p>
         <p><strong>Codigo:</strong> ' . $codigo . '</p>
         <p><strong>Solicitante:</strong> ' . $nombre . '</p>';

enviarCorreo('jefe@chavimochic.gob.pe', $asunto, $html);
```

### Alerta de Vencimiento

```php
$asunto = 'Alerta: Documentos por vencer';
$html = '<p>Los siguientes documentos estan proximos a vencer:</p><ul>';
foreach ($documentos as $doc) {
    $html .= '<li>' . $doc['nombre'] . ' - Vence: ' . $doc['fecha_vencimiento'] . '</li>';
}
$html .= '</ul>';

enviarCorreo($email_responsable, $asunto, $html);
```

---

## 6. Consideraciones

- **Clave de aplicacion:** Gmail requiere una clave de aplicacion, no la contrasena normal. Se genera en `https://myaccount.google.com/apppasswords`.
- **Limite de envios:** Gmail tiene limite de ~500 correos/dia para cuentas normales, ~2000 para Google Workspace.
- **Timeout:** Si el envio falla, PHPMailer lanza excepcion. Siempre usar try/catch.
- **Adjuntos:** Usar `$mail->addAttachment($rutaArchivo, $nombreOpcional)` para adjuntar PDFs, imagenes, etc.
- **No exponer credenciales:** Las credenciales SMTP nunca deben ir en el codigo del modulo. Usar las constantes definidas en `utils/mailer.php` o variables de entorno.
