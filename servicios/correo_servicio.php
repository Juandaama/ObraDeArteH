<?php

function enviarNotificacionEstadoCotizacion(array $datos): array
{
    $idCotizacion       = $datos['id_cotizacion'] ?? 0;
    $nombreCliente      = trim($datos['nombre_cliente'] ?? 'Cliente');
    $correoCliente      = trim($datos['correo_cliente'] ?? '');
    $nuevoEstado        = trim($datos['nuevo_estado'] ?? 'Pendiente');
    $estadoAnterior     = trim($datos['estado_anterior'] ?? '');
    $respuestaAdmin     = trim($datos['respuesta_admin'] ?? '');
    $categoria          = trim($datos['categoria'] ?? 'General');
    $nombreProyectoRef  = trim($datos['nombre_proyecto_ref'] ?? '');
    $presupuesto        = trim($datos['presupuesto'] ?? '');
    $detalles           = trim($datos['detalles'] ?? '');
    $fechaCreacion      = trim($datos['fecha_creacion'] ?? date('Y-m-d H:i:s'));

    if (empty($correoCliente) || !filter_var($correoCliente, FILTER_VALIDATE_EMAIL)) {
        return [
            'exito'   => false,
            'mensaje' => 'La cotización no tiene un correo electrónico de cliente válido para notificar.',
            'correo'  => $correoCliente
        ];
    }

    $asunto = "Actualización de Cotización #{$idCotizacion} - Obra de Arte H";

    $cuerpoHtml = generarPlantillaHtmlEstadoCotizacion([
        'id_cotizacion'       => $idCotizacion,
        'nombre_cliente'      => $nombreCliente,
        'correo_cliente'      => $correoCliente,
        'nuevo_estado'        => $nuevoEstado,
        'estado_anterior'     => $estadoAnterior,
        'respuesta_admin'     => $respuestaAdmin,
        'categoria'           => $categoria,
        'nombre_proyecto_ref' => $nombreProyectoRef,
        'presupuesto'         => $presupuesto,
        'detalles'            => $detalles,
        'fecha_creacion'      => $fechaCreacion
    ]);

    $cuerpoTexto = "Hola {$nombreCliente},\n\n"
        . "Te informamos que tu solicitud de cotización #{$idCotizacion} ha sido actualizada.\n\n"
        . "Nuevo Estado: {$nuevoEstado}\n"
        . ($estadoAnterior ? "Estado Anterior: {$estadoAnterior}\n" : "")
        . "\nRespuesta o notas del Administrador:\n"
        . (!empty($respuestaAdmin) ? "{$respuestaAdmin}\n" : "Sin comentarios adicionales por el momento.\n")
        . "\nResumen de la cotización:\n"
        . "- Área: {$categoria}\n"
        . ($nombreProyectoRef ? "- Proyecto Referencia: {$nombreProyectoRef}\n" : "")
        . ($presupuesto ? "- Presupuesto Estimado: {$presupuesto}\n" : "")
        . "\nGracias por confiar en Obra de Arte H.\n";

    return enviarCorreo($correoCliente, $nombreCliente, $asunto, $cuerpoHtml, $cuerpoTexto);
}

function enviarCorreo(string $destinatario, string $nombreDestinatario, string $asunto, string $cuerpoHtml, string $cuerpoTexto = ''): array
{
    $configRuta = __DIR__ . '/config_correo.php';
    $config = file_exists($configRuta) ? require $configRuta : [];

    $metodo          = $config['metodo'] ?? 'smtp';
    $fromEmail       = trim($config['from_email'] ?? 'obradearteh@gmail.com');
    $fromNombre      = trim($config['from_nombre'] ?? 'Obra de Arte H');
    $guardarCopia    = !empty($config['guardar_copia_log']);
    $smtpConfig      = $config['smtp'] ?? [];

    $enviado = false;
    $detalleEnvio = '';

    if ($metodo === 'smtp' && !empty($smtpConfig['usuario']) && !empty($smtpConfig['password'])) {
        try {
            $smtpResultado = enviarPorSmtpSocket($smtpConfig, $fromEmail, $fromNombre, $destinatario, $nombreDestinatario, $asunto, $cuerpoHtml);
            if ($smtpResultado['exito']) {
                $enviado = true;
                $detalleEnvio = 'Enviado con éxito vía SMTP (' . ($smtpConfig['host'] ?? 'smtp.gmail.com') . ').';
            } else {
                $detalleEnvio = 'Fallo en envío SMTP: ' . $smtpResultado['error'];
            }
        } catch (Throwable $t) {
            $detalleEnvio = 'Excepción SMTP: ' . $t->getMessage();
        }
    }

    if (!$enviado) {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: =?UTF-8?B?' . base64_encode($fromNombre) . '?= <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';

        $mailOk = @mail($destinatario, $asuntoCodificado, $cuerpoHtml, implode("\r\n", $headers));
        if ($mailOk) {
            $enviado = true;
            $detalleEnvio = 'Enviado mediante función nativa mail() de PHP.';
        } else {
            if (empty($detalleEnvio)) {
                $detalleEnvio = 'No se pudo despachar el correo (servidor local sin servidor SMTP activo). Se ha registrado la notificación en el sistema.';
            }
        }
    }

    if ($guardarCopia) {
        registrarEnLogCorreo([
            'fecha'        => date('Y-m-d H:i:s'),
            'destinatario' => $destinatario,
            'nombre'       => $nombreDestinatario,
            'asunto'       => $asunto,
            'enviado'      => $enviado,
            'detalle'      => $detalleEnvio,
            'html'         => $cuerpoHtml
        ]);
    }

    return [
        'exito'   => $enviado,
        'mensaje' => $detalleEnvio,
        'correo'  => $destinatario
    ];
}

function probarConexionCorreo(?string $destinatarioPrueba = null): array
{
    $configRuta = __DIR__ . '/config_correo.php';
    $config = file_exists($configRuta) ? require $configRuta : [];
    $destinatario = !empty($destinatarioPrueba) ? trim($destinatarioPrueba) : ($config['smtp']['usuario'] ?? 'obradearteh@gmail.com');

    $asunto = 'Prueba de Conexión de Correo - Obra de Arte H';
    $cuerpoHtml = "
    <div style='font-family: Arial, sans-serif; padding: 24px; border: 1px solid #ddd; border-radius: 8px; max-width: 580px; margin: 0 auto;'>
        <h2 style='color: #E85A0C; margin-top: 0;'>Obra de Arte H &bull; Envío de Notificaciones</h2>
        <p style='color: #333; font-size: 15px;'>¡Felicitaciones! Este mensaje confirma que el mecanismo de envío de correo SMTP está operando correctamente con Gmail.</p>
        <hr style='border: 0; border-top: 1px solid #eee; margin: 16px 0;'>
        <p style='font-size: 13px; color: #777;'><strong>Fecha y hora del servidor:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p style='font-size: 13px; color: #777;'><strong>Remitente:</strong> " . htmlspecialchars($config['smtp']['usuario'] ?? '') . "</p>
    </div>";

    return enviarCorreo($destinatario, 'Administrador', $asunto, $cuerpoHtml, 'Prueba de correo exitosa');
}

function enviarPorSmtpSocket(array $smtp, string $fromEmail, string $fromNombre, string $destinatario, string $nombreDestinatario, string $asunto, string $html): array
{
    $host      = trim($smtp['host'] ?? 'smtp.gmail.com');
    $puerto    = (int)($smtp['puerto'] ?? 587);
    $seguridad = strtolower(trim($smtp['seguridad'] ?? 'tls'));
    $usuario   = trim($smtp['usuario'] ?? '');
    $password  = trim($smtp['password'] ?? '');
    $timeout   = (int)($smtp['timeout'] ?? 12);

    // Si el usuario copia una Contraseña de Aplicación de Google con espacios (ej. "abcd efgh ijkl mnop"), se limpian automáticamente
    $passwordLimpia = str_replace(' ', '', $password);

    // En Gmail el remitente debe ser el usuario autenticado para evitar rechazos
    if (empty($fromEmail) || (stripos($host, 'gmail.com') !== false && !empty($usuario))) {
        $fromEmail = $usuario;
    }

    $esSsl   = ($seguridad === 'ssl' || $puerto === 465);
    $prefijo = $esSsl ? 'ssl://' : '';

    $contexto = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ]);

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        "{$prefijo}{$host}:{$puerto}",
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $contexto
    );

    if (!$socket) {
        return ['exito' => false, 'error' => "No se pudo conectar a {$host}:{$puerto} ({$errstr})"];
    }

    stream_set_timeout($socket, $timeout);

    $leerRespuesta = function () use ($socket) {
        $respuesta = '';
        while (!feof($socket) && ($linea = fgets($socket, 515))) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
            if (preg_match('/^\d{3}\r?\n$/', $linea)) {
                break;
            }
        }
        return $respuesta;
    };

    $enviarComando = function ($cmd) use ($socket, $leerRespuesta) {
        fputs($socket, $cmd . "\r\n");
        return $leerRespuesta();
    };

    $resp = $leerRespuesta();
    if (substr($resp, 0, 3) !== '220') {
        fclose($socket);
        return ['exito' => false, 'error' => "Banner inicial inválido: " . trim($resp)];
    }

    // Dominio limpio para el comando EHLO (sin puertos)
    $clientDomain = 'localhost';
    if (!empty($_SERVER['SERVER_NAME'])) {
        $dominioLimpio = preg_replace('/:[0-9]+$/', '', trim($_SERVER['SERVER_NAME']));
        if (!empty($dominioLimpio)) {
            $clientDomain = $dominioLimpio;
        }
    }

    $resp = $enviarComando("EHLO {$clientDomain}");

    // Inicio de STARTTLS para puerto 587
    if (!$esSsl && ($seguridad === 'tls' || $puerto === 587)) {
        $resp = $enviarComando("STARTTLS");
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['exito' => false, 'error' => "STARTTLS rechazado: " . trim($resp)];
        }

        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') && defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        $cryptoOk = false;
        for ($intento = 0; $intento < 50; $intento++) {
            $cryptoOk = stream_socket_enable_crypto($socket, true, $cryptoMethod);
            if ($cryptoOk === true) {
                break;
            } elseif ($cryptoOk === false) {
                fclose($socket);
                return ['exito' => false, 'error' => "Fallo al iniciar encriptación TLS con {$host}"];
            }
            usleep(20000); // 20ms
        }

        if ($cryptoOk !== true) {
            fclose($socket);
            return ['exito' => false, 'error' => "Tiempo de espera agotado al negociar TLS con {$host}"];
        }

        $resp = $enviarComando("EHLO {$clientDomain}");
    }

    // Autenticación de credenciales
    if (!empty($usuario)) {
        $resp = $enviarComando("AUTH LOGIN");
        if (substr($resp, 0, 3) !== '334') {
            fclose($socket);
            return ['exito' => false, 'error' => "AUTH LOGIN no soportado o rechazado: " . trim($resp)];
        }

        $resp = $enviarComando(base64_encode($usuario));
        if (substr($resp, 0, 3) !== '334') {
            fclose($socket);
            return ['exito' => false, 'error' => "Usuario SMTP rechazado: " . trim($resp)];
        }

        $resp = $enviarComando(base64_encode($passwordLimpia));
        if (substr($resp, 0, 3) !== '235') {
            fclose($socket);
            $errorAuth = "Autenticación SMTP fallida: " . trim($resp);
            if (strpos($resp, '535') !== false || stripos($resp, 'BadCredentials') !== false) {
                $errorAuth .= "\n[AYUDA] Google/Gmail rechazó la contraseña. Google no acepta la contraseña habitual de tu cuenta para SMTP. Debes generar una 'Contraseña de Aplicación' de 16 caracteres en https://myaccount.google.com/apppasswords teniendo la 'Verificación en 2 pasos' activada.";
            }
            return ['exito' => false, 'error' => $errorAuth];
        }
    }

    // Remitente del mensaje
    $resp = $enviarComando("MAIL FROM: <{$fromEmail}>");
    if (substr($resp, 0, 3) !== '250') {
        fclose($socket);
        return ['exito' => false, 'error' => "MAIL FROM rechazado: " . trim($resp)];
    }

    // Destinatario del mensaje
    $resp = $enviarComando("RCPT TO: <{$destinatario}>");
    if (substr($resp, 0, 3) !== '250') {
        fclose($socket);
        return ['exito' => false, 'error' => "RCPT TO rechazado ({$destinatario}): " . trim($resp)];
    }

    // Inicio de transferencia de datos
    $resp = $enviarComando("DATA");
    if (substr($resp, 0, 3) !== '354') {
        fclose($socket);
        return ['exito' => false, 'error' => "DATA rechazado: " . trim($resp)];
    }

    // Normalización de saltos de línea y transparencia de puntos (RFC 5321 §4.5.2)
    $cuerpoNormalizado = str_replace(["\r\n", "\r"], "\n", $html);
    $cuerpoNormalizado = preg_replace('/^\./m', '..', $cuerpoNormalizado);
    $cuerpoNormalizado = str_replace("\n", "\r\n", $cuerpoNormalizado);

    $cabeceras = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: =?UTF-8?B?' . base64_encode($fromNombre) . '?= <' . $fromEmail . '>',
        'To: =?UTF-8?B?' . base64_encode($nombreDestinatario) . '?= <' . $destinatario . '>',
        'Reply-To: <' . $fromEmail . '>',
        'Subject: =?UTF-8?B?' . base64_encode($asunto) . '?=',
        'Date: ' . date('r'),
        'X-Mailer: ObraDeArteH Mailer 1.0'
    ];

    $contenidoMensaje = implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpoNormalizado . "\r\n.";
    $resp = $enviarComando($contenidoMensaje);

    $enviarComando("QUIT");
    fclose($socket);

    if (substr($resp, 0, 3) === '250') {
        return ['exito' => true];
    } else {
        return ['exito' => false, 'error' => "Envío de contenido rechazado: " . trim($resp)];
    }
}

function registrarEnLogCorreo(array $datos): void
{
    $dirLogs = dirname(__DIR__) . '/logs';
    if (!is_dir($dirLogs)) {
        @mkdir($dirLogs, 0777, true);
    }

    $lineaLog = sprintf(
        "[%s] Destinatario: %s (%s) | Asunto: %s | Estado: %s | Detalle: %s\n",
        $datos['fecha'],
        $datos['destinatario'],
        $datos['nombre'],
        $datos['asunto'],
        $datos['enviado'] ? 'ENVIADO' : 'REGISTRADO_LOCAL',
        $datos['detalle']
    );

    @file_put_contents($dirLogs . '/correos_enviados.log', $lineaLog, FILE_APPEND);

    if (!empty($datos['html'])) {
        @file_put_contents($dirLogs . '/ultimo_correo.html', $datos['html']);
    }
}

function generarPlantillaHtmlEstadoCotizacion(array $datos): string
{
    $idCotizacion       = (int)$datos['id_cotizacion'];
    $nombreCliente      = htmlspecialchars($datos['nombre_cliente'], ENT_QUOTES, 'UTF-8');
    $nuevoEstado        = htmlspecialchars($datos['nuevo_estado'], ENT_QUOTES, 'UTF-8');
    $estadoAnterior     = htmlspecialchars($datos['estado_anterior'], ENT_QUOTES, 'UTF-8');
    $respuestaAdmin     = nl2br(htmlspecialchars($datos['respuesta_admin'], ENT_QUOTES, 'UTF-8'));
    $categoria          = htmlspecialchars($datos['categoria'], ENT_QUOTES, 'UTF-8');
    $nombreProyectoRef  = htmlspecialchars($datos['nombre_proyecto_ref'] ?: 'General', ENT_QUOTES, 'UTF-8');
    $presupuesto        = htmlspecialchars($datos['presupuesto'] ?: 'No especificado', ENT_QUOTES, 'UTF-8');
    $detalles           = nl2br(htmlspecialchars($datos['detalles'], ENT_QUOTES, 'UTF-8'));
    $fechaCreacion      = date('d/m/Y', strtotime($datos['fecha_creacion']));

    $colorFondoBadge = '#e1f0fa';
    $colorBordeBadge = '#90caf9';
    $colorTextoBadge = '#125586';
    $iconoEstado = '&#128269;';

    if ($nuevoEstado === 'Pendiente') {
        $colorFondoBadge = '#fff8e1';
        $colorBordeBadge = '#ffe082';
        $colorTextoBadge = '#b37400';
        $iconoEstado = '&#9203;';
    } elseif ($nuevoEstado === 'Aprobada') {
        $colorFondoBadge = '#e8f5e9';
        $colorBordeBadge = '#a5d6a7';
        $colorTextoBadge = '#1e701e';
        $iconoEstado = '&#10004;';
    } elseif ($nuevoEstado === 'Rechazada') {
        $colorFondoBadge = '#fbe9e7';
        $colorBordeBadge = '#ffab91';
        $colorTextoBadge = '#C94A08';
        $iconoEstado = '&#10008;';
    }

    $bloqueRespuesta = '';
    if (!empty($datos['respuesta_admin'])) {
        $bloqueRespuesta = "
        <div style='background-color: #FAF9F6; border-left: 4px solid #E85A0C; padding: 18px 20px; margin: 24px 0; border-radius: 4px;'>
            <h3 style='margin: 0 0 8px 0; font-size: 15px; color: #252525; font-family: Arial, sans-serif; text-transform: uppercase; letter-spacing: 0.5px;'>
                Respuesta del Administrador
            </h3>
            <div style='font-size: 14px; color: #333333; line-height: 1.6; font-family: Arial, sans-serif;'>
                {$respuestaAdmin}
            </div>
        </div>";
    } else {
        $bloqueRespuesta = "
        <div style='background-color: #F8F9FA; border-left: 4px solid #999999; padding: 14px 18px; margin: 24px 0; border-radius: 4px; font-size: 13px; color: #666666; font-style: italic;'>
            El administrador actualizó el estado de tu cotización a <strong>{$nuevoEstado}</strong> sin notas adicionales.
        </div>";
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Cotización #{$idCotizacion} - Obra de Arte H</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F2F2F0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #252525; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #F2F2F0; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 620px; background-color: #FFFFFF; border: 1px solid #D9D9D9; border-radius: 6px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
                    
                    <tr>
                        <td style="background-color: #252525; padding: 26px 32px; border-bottom: 4px solid #E85A0C; text-align: center;">
                            <h1 style="margin: 0; color: #FFFFFF; font-size: 24px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;">
                                OBRA DE ARTE H
                            </h1>
                            <p style="margin: 6px 0 0 0; color: #E85A0C; font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 600;">
                                Arquitectura &bull; Diseño &bull; Remodelaciones
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px 32px 24px 32px;">
                            <h2 style="margin: 0 0 12px 0; color: #252525; font-size: 20px; font-weight: 700;">
                                ¡Hola, {$nombreCliente}!
                            </h2>
                            <p style="margin: 0 0 20px 0; font-size: 15px; color: #444444; line-height: 1.6;">
                                Te notificamos que el estado de tu solicitud de cotización <strong>#{$idCotizacion}</strong> ha sido actualizado por nuestro equipo de administración.
                            </p>

                            <div style="background-color: {$colorFondoBadge}; border: 1px solid {$colorBordeBadge}; border-radius: 6px; padding: 18px 20px; margin: 20px 0; text-align: center;">
                                <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #555555; display: block; margin-bottom: 6px;">
                                    Nuevo Estado de tu Cotización:
                                </span>
                                <span style="font-size: 22px; font-weight: 700; color: {$colorTextoBadge}; display: inline-block;">
                                    {$iconoEstado} {$nuevoEstado}
                                </span>
                            </div>

                            {$bloqueRespuesta}

                            <div style="border: 1px solid #E5E5E5; border-radius: 6px; padding: 20px; margin-top: 24px; background-color: #FAFAFA;">
                                <h3 style="margin: 0 0 14px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #252525; border-bottom: 1px solid #E0E0E0; padding-bottom: 8px;">
                                    Resumen de tu Solicitud
                                </h3>
                                <table width="100%" cellspacing="0" cellpadding="6" border="0" style="font-size: 13px; color: #444444;">
                                    <tr>
                                        <td width="38%" style="font-weight: 600; color: #252525;">N° de Cotización:</td>
                                        <td width="62%">#{$idCotizacion}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #252525;">Área o Categoría:</td>
                                        <td>{$categoria}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #252525;">Proyecto de Referencia:</td>
                                        <td>{$nombreProyectoRef}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #252525;">Presupuesto Estimado:</td>
                                        <td>{$presupuesto}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #252525;">Fecha de Solicitud:</td>
                                        <td>{$fechaCreacion}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #252525; vertical-align: top;">Requerimientos:</td>
                                        <td style="vertical-align: top; line-height: 1.5; color: #555555;">{$detalles}</td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin: 28px 0 10px 0; font-size: 14px; color: #555555; line-height: 1.6;">
                                Si tienes alguna inquietud sobre esta respuesta o deseas coordinar una visita técnica a tu inmueble, no dudes en responder a este correo o contactarnos directamente.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #FAFAFA; border-top: 1px solid #D9D9D9; padding: 22px 32px; text-align: center;">
                            <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #252525;">
                                Obra de Arte H &bull; Creando espacios con propósito
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #777777;">
                                Este es un correo automático generado por el sistema de cotizaciones de Obra de Arte H.
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
