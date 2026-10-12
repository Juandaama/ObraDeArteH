<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rutaConfig = __DIR__ . '/config_correo.php';
$config = file_exists($rutaConfig) ? require $rutaConfig : [];

require_once __DIR__ . '/correo_servicio.php';

$resultado = null;
$mensajeGuardado = null;

// Procesar actualización de contraseña o prueba
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar_clave') {
        $nuevaClave = trim($_POST['nueva_clave'] ?? '');
        if (!empty($nuevaClave)) {
            $config['smtp']['password'] = $nuevaClave;
            $contenidoPhp = "<?php\nreturn [\n"
                . "    // Método de envío: 'smtp' o 'mail'\n"
                . "    'metodo' => '" . addslashes($config['metodo'] ?? 'smtp') . "',\n\n"
                . "    // Correo y nombre del remitente\n"
                . "    'from_email'  => '" . addslashes($config['from_email'] ?? 'obradearteh@gmail.com') . "',\n"
                . "    'from_nombre' => '" . addslashes($config['from_nombre'] ?? 'Obra de Arte H') . "',\n\n"
                . "    // Conexión SMTP con Google / Gmail\n"
                . "    'smtp' => [\n"
                . "        'host'      => '" . addslashes($config['smtp']['host'] ?? 'smtp.gmail.com') . "',\n"
                . "        'puerto'    => " . (int)($config['smtp']['puerto'] ?? 587) . ",\n"
                . "        'seguridad' => '" . addslashes($config['smtp']['seguridad'] ?? 'tls') . "',\n"
                . "        'usuario'   => '" . addslashes($config['smtp']['usuario'] ?? 'obradearteh@gmail.com') . "',\n"
                . "        'password'  => '" . addslashes($nuevaClave) . "',\n"
                . "        'timeout'   => " . (int)($config['smtp']['timeout'] ?? 12) . ",\n"
                . "    ],\n\n"
                . "    // Guardar copia de historial en /logs/correos_enviados.log\n"
                . "    'guardar_copia_log' => true,\n"
                . "];\n";

            file_put_contents($rutaConfig, $contenidoPhp);
            $mensajeGuardado = 'Contraseña actualizada correctamente en config_correo.php.';
            $config = require $rutaConfig;
        }
    }

    if ($accion === 'probar_envio') {
        $destinatario = trim($_POST['destinatario'] ?? '');
        if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            $destinatario = $config['smtp']['usuario'] ?? 'obradearteh@gmail.com';
        }
        $resultado = probarConexionCorreo($destinatario);
    }
}

$claveActual = $config['smtp']['password'] ?? '';
$longitudClave = strlen(str_replace(' ', '', $claveActual));
$esProbableAppPassword = ($longitudClave === 16 && ctype_lower(str_replace(' ', '', $claveActual)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Envío de Correos - Obra de Arte H</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/estilos.css">
    <link rel="stylesheet" href="../assets/estilos_adicionales.css">
    <style>
        .diag-tarjeta {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .diag-tarjeta h2 {
            margin-top: 0;
            font-size: 1.15rem;
            color: #252525;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 12px;
        }
        .diag-fila {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
            font-size: 0.92rem;
        }
        .diag-fila:last-child {
            border-bottom: none;
        }
        .diag-etiq {
            font-weight: 600;
            color: #555;
        }
        .diag-val {
            font-family: monospace;
            color: #222;
        }
        .diag-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .diag-badge--ok { background: #e8f5e9; color: #2e7d32; }
        .diag-badge--warn { background: #fff8e1; color: #b45309; }
        .diag-instrucciones {
            background: #fffdfa;
            border-left: 4px solid #E85A0C;
            padding: 18px 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .diag-instrucciones ol {
            margin: 10px 0 0 20px;
            padding: 0;
            line-height: 1.7;
            font-size: 0.92rem;
            color: #444;
        }
        .diag-instrucciones li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="contenido-admin" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
        <div style="display: flex; gap: 12px; margin-bottom: 20px;">
            <a href="../admin/cotizaciones/administracion_Cotizaciones.php" class="enlace-volver">&larr; Volver a Cotizaciones</a>
            <a href="../admin/panel.php" class="enlace-volver">Panel Admin</a>
        </div>

        <div class="contenido-admin__encabezado">
            <h1 class="contenido-admin__titulo">Diagnóstico del Mecanismo de Correo</h1>
            <p class="contenido-admin__descripcion">Verificación de conectividad SMTP con Google/Gmail y pruebas de envío</p>
        </div>

        <?php if ($mensajeGuardado): ?>
            <div class="alerta alerta--exito">
                <p><?= htmlspecialchars($mensajeGuardado) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($resultado !== null): ?>
            <?php if ($resultado['exito']): ?>
                <div class="alerta alerta--exito" style="padding: 18px;">
                    <p><strong>&#10004; ¡Envío Exitoso!</strong> El correo de prueba se despachó correctamente a <code><?= htmlspecialchars($resultado['correo']) ?></code> vía SMTP.</p>
                    <p style="margin-top: 8px; font-size: 0.85rem; color: #1e701e;"><?= htmlspecialchars($resultado['mensaje']) ?></p>
                </div>
            <?php else: ?>
                <div class="alerta alerta--advertencia" style="padding: 18px;">
                    <p><strong>&#9888; El envío falló:</strong></p>
                    <pre style="background: rgba(0,0,0,0.04); padding: 12px; border-radius: 4px; font-size: 0.85rem; white-space: pre-wrap; word-break: break-all; margin: 10px 0;"><?= htmlspecialchars($resultado['mensaje']) ?></pre>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="diag-tarjeta">
            <h2>Configuración SMTP Actual</h2>
            <div class="diag-fila">
                <span class="diag-etiq">Servidor SMTP (Host):</span>
                <span class="diag-val"><?= htmlspecialchars($config['smtp']['host'] ?? 'smtp.gmail.com') ?>:<?= (int)($config['smtp']['puerto'] ?? 587) ?> (<?= htmlspecialchars(strtoupper($config['smtp']['seguridad'] ?? 'tls')) ?>)</span>
            </div>
            <div class="diag-fila">
                <span class="diag-etiq">Cuenta de Correo (Usuario):</span>
                <span class="diag-val"><?= htmlspecialchars($config['smtp']['usuario'] ?? '') ?></span>
            </div>
            <div class="diag-fila">
                <span class="diag-etiq">Remitente Configurado:</span>
                <span class="diag-val"><?= htmlspecialchars($config['from_email'] ?? '') ?></span>
            </div>
            <div class="diag-fila">
                <span class="diag-etiq">Tipo de Contraseña Detectada:</span>
                <span class="diag-val">
                    <?php if ($esProbableAppPassword): ?>
                        <span class="diag-badge diag-badge--ok">&#10004; Contraseña de Aplicación de 16 letras</span>
                    <?php else: ?>
                        <span class="diag-badge diag-badge--warn">&#9888; Contraseña estándar (Google requiere Contraseña de Aplicación)</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="diag-tarjeta">
            <h2>Realizar Prueba de Envío</h2>
            <p style="color: #666; font-size: 0.9rem; margin-top: 0;">Ingresa un correo electrónico de destino para enviar un mensaje de comprobación inmediato.</p>
            <form method="POST" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="accion" value="probar_envio">
                <input 
                    type="email" 
                    name="destinatario" 
                    class="buscador-caja__input" 
                    style="flex: 1; min-width: 260px;" 
                    placeholder="correo@ejemplo.com"
                    value="<?= htmlspecialchars($_POST['destinatario'] ?? ($config['smtp']['usuario'] ?? 'obradearteh@gmail.com')) ?>"
                    required
                >
                <button type="submit" class="boton boton--primario">
                    Enviar Correo de Prueba
                </button>
            </form>
        </div>

        <div class="diag-tarjeta">
            <h2>Actualizar Contraseña de Envío</h2>
            <p style="color: #666; font-size: 0.9rem; margin-top: 0;">Si generaste una nueva Contraseña de Aplicación en Google, puedes guardarla aquí directamente:</p>
            <form method="POST" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="accion" value="guardar_clave">
                <input 
                    type="text" 
                    name="nueva_clave" 
                    class="buscador-caja__input" 
                    style="flex: 1; min-width: 260px;" 
                    placeholder="Ejemplo: xxxx xxxx xxxx xxxx"
                    required
                >
                <button type="submit" class="boton boton--secundario">
                    Guardar Nueva Contraseña
                </button>
            </form>

            <div class="diag-instrucciones">
                <strong style="color: #252525; font-size: 0.98rem;">¿Por qué Google rechaza la contraseña habitual (Error 535 BadCredentials)?</strong>
                <p style="margin: 8px 0 0 0; font-size: 0.9rem; color: #555;">
                    Por políticas de seguridad mundiales, Google no permite utilizar la contraseña personal/habitual de una cuenta para conexiones SMTP de aplicaciones. Es obligatorio generar una <strong>Contraseña de Aplicación de 16 caracteres</strong> siguiendo estos pasos:
                </p>
                <ol>
                    <li>Inicia sesión con <strong><?= htmlspecialchars($config['smtp']['usuario'] ?? 'obradearteh@gmail.com') ?></strong> en <a href="https://myaccount.google.com" target="_blank" style="color: #E85A0C; text-decoration: underline;">myaccount.google.com</a>.</li>
                    <li>Ve a la sección <strong>Seguridad</strong> y asegúrate de tener encendida la <strong>"Verificación en dos pasos"</strong>.</li>
                    <li>Ingresa directamente a: <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: #E85A0C; text-decoration: underline; font-weight: 600;">https://myaccount.google.com/apppasswords</a>.</li>
                    <li>Escribe un nombre de referencia (por ejemplo: <em>ObraDeArteH Sistema</em>) y haz clic en <strong>Crear</strong>.</li>
                    <li>Google te mostrará un código amarillo de <strong>16 letras</strong> (ejemplo: <code>abcd efgh ijkl mnop</code>).</li>
                    <li>Copia ese código, pégalo en el campo de arriba y haz clic en <strong>Guardar Nueva Contraseña</strong>.</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>

