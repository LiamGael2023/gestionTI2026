<?php
/**
 * MailService.php
 * Servicio centralizado de notificaciones por correo electrónico.
 *
 * Utiliza PHPMailer con SMTP (Gmail) y plantillas HTML institucionales
 * con los colores corporativos del Proyecto Especial Chavimochic (PECH).
 *
 * IMPORTANTE: los errores de envío se registran en PHP error_log pero
 * NO interrumpen el flujo principal de la aplicación.
 *
 * Constantes requeridas (definidas en config/config.php):
 *   MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS, MAIL_FROM, MAIL_FROM_NAME
 *
 * GestionTI v1.0 — PECH
 */

// Cargar PHPMailer
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class MailService
{
    // ──────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ──────────────────────────────────────────────────────────────

    /**
     * Crea y configura una instancia PHPMailer lista para enviar.
     */
    private static function buildMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->isHTML(true);

        // Logo embebido como imagen inline (CID)
        $logoPath = __DIR__ . '/../modules/salas/assets/logopech_mail.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logopech_mail', 'logopech_mail.png', 'base64', 'image/png');
        }

        return $mail;
    }

    /**
     * Formatea una fecha ISO (YYYY-MM-DD) a formato legible (DD/MM/YYYY).
     */
    private static function fmtFecha(string $fecha): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d ? $d->format('d/m/Y') : $fecha;
    }

    /**
     * Formatea una hora (HH:MM o HH:MM:SS) dejando solo HH:MM.
     */
    private static function fmtHora(string $hora): string
    {
        return substr($hora, 0, 5);
    }

    // ──────────────────────────────────────────────────────────────
    // PLANTILLA HTML BASE
    // ──────────────────────────────────────────────────────────────

    /**
     * Genera el HTML completo del correo.
     *
     * @param string $titulo    Texto del encabezado coloreado.
     * @param string $color     Color del encabezado en hex (p. ej. '#009540').
     * @param string $cuerpo    HTML del cuerpo del correo (entre header y footer).
     * @param string $badge     Texto del badge de estado (p. ej. 'APROBADA').
     * @param string $badgeColor Color del badge.
     */
    private static function template(
        string $titulo,
        string $color,
        string $cuerpo,
        string $badge = '',
        string $badgeColor = '#6c757d'
    ): string {
        $badgeHtml = $badge
            ? "<p style='text-align:center;margin:18px 0 0'>
                 <span style='display:inline-block;background:{$badgeColor};color:#fff;
                              font-size:13px;font-weight:700;letter-spacing:1px;
                              padding:5px 18px;border-radius:20px;'>{$badge}</span>
               </p>"
            : '';

        $logoSrc = 'cid:logopech_mail';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Segoe UI,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background:#f4f6f8;min-height:100vh;">
    <tr>
      <td align="center" style="padding:30px 15px;">
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background:#fff;
                      border-radius:10px;overflow:hidden;
                      box-shadow:0 4px 20px rgba(0,0,0,.10);">

          <!-- CABECERA -->
          <tr>
            <td style="background:{$color};padding:28px 32px;text-align:center;">
              <img src="{$logoSrc}" alt="Logo PECH"
                   width="160"
                   style="display:block;margin:0 auto 12px;max-width:160px;">
              <h1 style="margin:0;color:#fff;font-size:20px;font-weight:700;
                          letter-spacing:.5px;">{$titulo}</h1>
              <p style="margin:6px 0 0;color:rgba(255,255,255,.85);font-size:13px;">
                Sistema de Gestión de Salas — PECH
              </p>
            </td>
          </tr>

          <!-- CUERPO -->
          <tr>
            <td style="padding:28px 32px 24px;">
              {$cuerpo}
              {$badgeHtml}
            </td>
          </tr>

          <!-- PIE -->
          <tr>
            <td style="background:#f8f9fa;border-top:1px solid #e9ecef;
                        padding:18px 32px;text-align:center;">
              <p style="margin:0;font-size:11px;color:#6c757d;line-height:1.6;">
                Este mensaje fue generado automáticamente por el Sistema GestionTI del
                <strong>Proyecto Especial Chavimochic (PECH)</strong>.<br>
                Por favor, no responda a este correo.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    /**
     * Genera la tabla de detalles de reserva en HTML.
     */
    private static function tablaDetalles(array $d): string
    {
        $equipos = !empty($d['equipos'])
            ? htmlspecialchars($d['equipos'])
            : '<em style="color:#999">Sin equipos AV</em>';

        return <<<HTML
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;margin:20px 0 0;font-size:14px;">
  <tr style="background:#f0f4f8;">
    <td style="padding:9px 14px;font-weight:600;color:#1a2940;
               border-bottom:1px solid #dee2e6;width:38%;">N.° de Reserva</td>
    <td style="padding:9px 14px;color:#333;border-bottom:1px solid #dee2e6;">
      <strong>#{$d['id_reserva']}</strong></td>
  </tr>
  <tr>
    <td style="padding:9px 14px;font-weight:600;color:#1a2940;
               border-bottom:1px solid #dee2e6;">Sede — Sala</td>
    <td style="padding:9px 14px;color:#333;border-bottom:1px solid #dee2e6;">
      {$d['sede']} — {$d['sala']}</td>
  </tr>
  <tr style="background:#f0f4f8;">
    <td style="padding:9px 14px;font-weight:600;color:#1a2940;
               border-bottom:1px solid #dee2e6;">Fecha</td>
    <td style="padding:9px 14px;color:#333;border-bottom:1px solid #dee2e6;">
      {$d['fecha_fmt']}</td>
  </tr>
  <tr>
    <td style="padding:9px 14px;font-weight:600;color:#1a2940;
               border-bottom:1px solid #dee2e6;">Horario</td>
    <td style="padding:9px 14px;color:#333;border-bottom:1px solid #dee2e6;">
      {$d['hora_inicio']} – {$d['hora_fin']}</td>
  </tr>
  <tr style="background:#f0f4f8;">
    <td style="padding:9px 14px;font-weight:600;color:#1a2940;
               border-bottom:1px solid #dee2e6;">Motivo</td>
    <td style="padding:9px 14px;color:#333;border-bottom:1px solid #dee2e6;">
      {$d['motivo']}</td>
  </tr>
  <tr>
    <td style="padding:9px 14px;font-weight:600;color:#1a2940;">Equipos AV</td>
    <td style="padding:9px 14px;color:#333;">{$equipos}</td>
  </tr>
</table>
HTML;
    }

    // ──────────────────────────────────────────────────────────────
    // MÉTODOS PÚBLICOS DE NOTIFICACIÓN
    // ──────────────────────────────────────────────────────────────

    /**
     * Notifica al solicitante que su reserva fue registrada en estado PENDIENTE.
     *
     * @param array $datos {
     *   correo, nombre, id_reserva, sede, sala,
     *   fecha, hora_inicio, hora_fin, motivo, equipos?
     * }
     */
    public static function notificarReservaCreada(array $datos): void
    {
        if (empty($datos['correo'])) return;

        try {
            $d = self::prepararDatos($datos);

            $cuerpo  = "<p style='font-size:15px;color:#333;margin:0 0 6px'>Hola, <strong>"
                     . htmlspecialchars($d['nombre']) . "</strong></p>";
            $cuerpo .= "<p style='font-size:14px;color:#555;margin:0'>Tu solicitud de reserva "
                     . "fue <strong>registrada exitosamente</strong> y se encuentra en revisión.</p>";
            $cuerpo .= self::tablaDetalles($d);
            $cuerpo .= "<p style='font-size:13px;color:#777;margin-top:18px;line-height:1.6;'>"
                     . "Recibirás otra notificación cuando sea aprobada o rechazada.</p>";

            $html = self::template(
                'Solicitud de Reserva Recibida',
                '#3d4c68',
                $cuerpo,
                'PENDIENTE DE REVISIÓN',
                '#f59e0b'
            );

            $mail = self::buildMailer();
            $mail->addAddress($datos['correo'], $datos['nombre']);
            $mail->Subject = "✅ Reserva #{$d['id_reserva']} registrada — Sala {$d['sala']}";
            $mail->Body    = $html;
            $mail->AltBody = "Tu reserva #{$d['id_reserva']} fue registrada y está pendiente de revisión. "
                           . "Sala: {$d['sala']} — {$d['fecha_fmt']} {$d['hora_inicio']}-{$d['hora_fin']}";
            $mail->send();
        } catch (\Throwable $e) {
            error_log("[MailService] notificarReservaCreada: " . $e->getMessage());
        }
    }

    /**
     * Notifica al solicitante que su reserva fue APROBADA.
     */
    public static function notificarReservaAprobada(array $datos): void
    {
        if (empty($datos['correo'])) return;

        try {
            $d = self::prepararDatos($datos);

            $cuerpo  = "<p style='font-size:15px;color:#333;margin:0 0 6px'>Hola, <strong>"
                     . htmlspecialchars($d['nombre']) . "</strong></p>";
            $cuerpo .= "<p style='font-size:14px;color:#555;margin:0'>¡Tu solicitud de reserva "
                     . "fue <strong>aprobada</strong>! Ya puedes utilizar la sala en el horario indicado.</p>";
            $cuerpo .= self::tablaDetalles($d);
            $cuerpo .= "<p style='font-size:13px;color:#777;margin-top:18px;line-height:1.6;'>"
                     . "Recuerda llegar puntual y dejar la sala en buen estado al finalizar.</p>";

            $html = self::template(
                'Reserva Aprobada',
                '#458461',
                $cuerpo,
                'APROBADA',
                '#009540'
            );

            $mail = self::buildMailer();
            $mail->addAddress($datos['correo'], $datos['nombre']);
            $mail->Subject = "🟢 Reserva #{$d['id_reserva']} APROBADA — Sala {$d['sala']}";
            $mail->Body    = $html;
            $mail->AltBody = "Tu reserva #{$d['id_reserva']} fue APROBADA. "
                           . "Sala: {$d['sala']} — {$d['fecha_fmt']} {$d['hora_inicio']}-{$d['hora_fin']}";
            $mail->send();
        } catch (\Throwable $e) {
            error_log("[MailService] notificarReservaAprobada: " . $e->getMessage());
        }
    }

    /**
     * Notifica al solicitante que su reserva fue RECHAZADA.
     *
     * @param string $observacion  Motivo del rechazo (puede ser vacío).
     */
    public static function notificarReservaRechazada(array $datos, string $observacion = ''): void
    {
        if (empty($datos['correo'])) return;

        try {
            $d = self::prepararDatos($datos);

            $obsHtml = !empty(trim($observacion))
                ? "<div style='background:#fff3cd;border-left:4px solid #f59e0b;
                               padding:12px 16px;margin-top:18px;border-radius:4px;'>
                     <strong style='color:#92400e;font-size:13px;'>Motivo del rechazo:</strong><br>
                     <span style='color:#78350f;font-size:14px;'>"
                     . nl2br(htmlspecialchars(trim($observacion)))
                   . "</span></div>"
                : "<p style='font-size:13px;color:#777;margin-top:18px;'>No se indicó motivo de rechazo.</p>";

            $cuerpo  = "<p style='font-size:15px;color:#333;margin:0 0 6px'>Hola, <strong>"
                     . htmlspecialchars($d['nombre']) . "</strong></p>";
            $cuerpo .= "<p style='font-size:14px;color:#555;margin:0'>Lamentamos informarte que "
                     . "tu solicitud de reserva fue <strong>rechazada</strong>.</p>";
            $cuerpo .= self::tablaDetalles($d);
            $cuerpo .= $obsHtml;
            $cuerpo .= "<p style='font-size:13px;color:#777;margin-top:18px;line-height:1.6;'>"
                     . "Puedes crear una nueva solicitud si lo requieres. Ante cualquier duda, "
                     . "comunícate con el área de TI.</p>";

            $html = self::template(
                'Reserva Rechazada',
                '#ec4959',
                $cuerpo,
                'RECHAZADA',
                '#dc3545'
            );

            $mail = self::buildMailer();
            $mail->addAddress($datos['correo'], $datos['nombre']);
            $mail->Subject = "🔴 Reserva #{$d['id_reserva']} RECHAZADA — Sala {$d['sala']}";
            $mail->Body    = $html;
            $mail->AltBody = "Tu reserva #{$d['id_reserva']} fue RECHAZADA. "
                           . "Sala: {$d['sala']} — {$d['fecha_fmt']}. Observación: " . strip_tags($observacion);
            $mail->send();
        } catch (\Throwable $e) {
            error_log("[MailService] notificarReservaRechazada: " . $e->getMessage());
        }
    }

    /**
     * Notifica al solicitante que su reserva fue CANCELADA.
     *
     * @param string $motivo  Razón de la cancelación para mostrar en el correo.
     */
    public static function notificarReservaCancelada(array $datos, string $motivo = ''): void
    {
        if (empty($datos['correo'])) return;

        try {
            $d = self::prepararDatos($datos);

            $motivoHtml = !empty(trim($motivo))
                ? "<div style='background:#f8d7da;border-left:4px solid #dc3545;
                               padding:12px 16px;margin-top:18px;border-radius:4px;'>
                     <strong style='color:#721c24;font-size:13px;'>Motivo:</strong><br>
                     <span style='color:#721c24;font-size:14px;'>"
                     . nl2br(htmlspecialchars(trim($motivo)))
                   . "</span></div>"
                : '';

            $cuerpo  = "<p style='font-size:15px;color:#333;margin:0 0 6px'>Hola, <strong>"
                     . htmlspecialchars($d['nombre']) . "</strong></p>";
            $cuerpo .= "<p style='font-size:14px;color:#555;margin:0'>Tu reserva fue "
                     . "<strong>cancelada</strong>.</p>";
            $cuerpo .= self::tablaDetalles($d);
            $cuerpo .= $motivoHtml;
            $cuerpo .= "<p style='font-size:13px;color:#777;margin-top:18px;line-height:1.6;'>"
                     . "Si crees que esto es un error, comunícate con el área de TI.</p>";

            $html = self::template(
                'Reserva Cancelada',
                '#7a8289',
                $cuerpo,
                'CANCELADA',
                '#6c757d'
            );

            $mail = self::buildMailer();
            $mail->addAddress($datos['correo'], $datos['nombre']);
            $mail->Subject = "⚫ Reserva #{$d['id_reserva']} CANCELADA — Sala {$d['sala']}";
            $mail->Body    = $html;
            $mail->AltBody = "Tu reserva #{$d['id_reserva']} fue CANCELADA. "
                           . "Sala: {$d['sala']} — {$d['fecha_fmt']}";
            $mail->send();
        } catch (\Throwable $e) {
            error_log("[MailService] notificarReservaCancelada: " . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────
    // HELPER INTERNO
    // ──────────────────────────────────────────────────────────────

    /**
     * Normaliza y formatea los datos antes de construir la plantilla.
     */
    private static function prepararDatos(array $datos): array
    {
        return [
            'correo'      => $datos['correo']      ?? '',
            'nombre'      => $datos['nombre']      ?? 'Solicitante',
            'id_reserva'  => $datos['id_reserva']  ?? '—',
            'sede'        => htmlspecialchars($datos['sede']        ?? '—'),
            'sala'        => htmlspecialchars($datos['sala']        ?? '—'),
            'fecha_fmt'   => self::fmtFecha($datos['fecha']         ?? ''),
            'hora_inicio' => self::fmtHora($datos['hora_inicio']    ?? ''),
            'hora_fin'    => self::fmtHora($datos['hora_fin']       ?? ''),
            'motivo'      => htmlspecialchars($datos['motivo']      ?? '—'),
            'equipos'     => $datos['equipos']     ?? '',
        ];
    }
}
