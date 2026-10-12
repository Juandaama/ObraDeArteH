<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../DB/conexion.php';

if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'administrador') {
    header('Location: ../../index.php');
    exit;
}

$usuarioId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0);

if ($usuarioId <= 0) {
    header('Location: administracion_Usuarios.php');
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cotizaciones` (
          `id_cotizacion` int(11) NOT NULL AUTO_INCREMENT,
          `id_usuario` int(11) DEFAULT NULL,
          `nombre_cliente` varchar(200) NOT NULL,
          `correo_cliente` varchar(200) NOT NULL,
          `telefono_cliente` varchar(50) NOT NULL,
          `categoria` varchar(100) NOT NULL,
          `id_imagen_ref` int(11) DEFAULT NULL,
          `nombre_proyecto_ref` varchar(250) DEFAULT NULL,
          `presupuesto_estimado` varchar(100) DEFAULT NULL,
          `detalles` text NOT NULL,
          `estado` enum('Pendiente','En revisión','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
          `respuesta_admin` text DEFAULT NULL,
          `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_cotizacion`),
          KEY `idx_usuario` (`id_usuario`),
          KEY `idx_estado` (`estado`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
} catch (Exception $e) {
}

$stmtUsr = $pdo->prepare("SELECT id, nombre, apellido, edad, telefono, correo, rol FROM usuarios WHERE id = ? LIMIT 1");
$stmtUsr->execute([$usuarioId]);
$usuario = $stmtUsr->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Usuario no encontrado</title>
            <link rel='stylesheet' href='../../assets/estilos.css'>
        </head>
        <body style='padding: 40px;'>
            <div class='tarjeta tarjeta--marco' style='max-width: 500px; margin: 40px auto; text-align: center; padding: 30px;'>
                <h2 style='color: var(--color-acento-oscuro);'>Usuario no encontrado</h2>
                <p>El usuario con ID #{$usuarioId} no existe en el sistema.</p>
                <a href='administracion_Usuarios.php' class='boton boton--primario boton--pequeno'>Volver a Usuarios</a>
            </div>
        </body>
        </html>
    ");
}

$mensaje = '';
$tipoMensaje = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'crear') {
        $categoria       = trim($_POST['categoria'] ?? '');
        $nombreProyecto  = trim($_POST['nombre_proyecto_ref'] ?? '');
        $presupuesto     = trim($_POST['presupuesto_estimado'] ?? '');
        $detalles        = trim($_POST['detalles'] ?? '');
        $estado          = trim($_POST['estado'] ?? 'Pendiente');
        $respuestaAdmin  = trim($_POST['respuesta_admin'] ?? '');
        $nombreCliente   = trim($_POST['nombre_cliente'] ?? ($usuario['nombre'] . ' ' . $usuario['apellido']));
        $correoCliente   = trim($_POST['correo_cliente'] ?? $usuario['correo']);
        $telefonoCliente = trim($_POST['telefono_cliente'] ?? $usuario['telefono']);

        $estadosPermitidos = ['Pendiente', 'En revisión', 'Aprobada', 'Rechazada'];
        if (!in_array($estado, $estadosPermitidos)) {
            $estado = 'Pendiente';
        }

        if (empty($categoria) || empty($detalles)) {
            $mensaje = 'Por favor completa la categoría y los detalles de la cotización.';
            $tipoMensaje = 'error';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO cotizaciones 
                (id_usuario, nombre_cliente, correo_cliente, telefono_cliente, categoria, nombre_proyecto_ref, presupuesto_estimado, detalles, estado, respuesta_admin)
                VALUES 
                (:id_usuario, :nombre_cliente, :correo_cliente, :telefono_cliente, :categoria, :nombre_proyecto_ref, :presupuesto_estimado, :detalles, :estado, :respuesta_admin)
            ");
            $stmt->execute([
                ':id_usuario'           => $usuarioId,
                ':nombre_cliente'       => $nombreCliente,
                ':correo_cliente'       => $correoCliente,
                ':telefono_cliente'     => $telefonoCliente,
                ':categoria'            => $categoria,
                ':nombre_proyecto_ref'  => $nombreProyecto,
                ':presupuesto_estimado' => $presupuesto,
                ':detalles'             => $detalles,
                ':estado'               => $estado,
                ':respuesta_admin'      => $respuestaAdmin
            ]);
            $mensaje = 'Cotización creada exitosamente para ' . htmlspecialchars($usuario['nombre']) . '.';
            $tipoMensaje = 'exito';
        }

    } elseif ($accion === 'actualizar') {
        $idCotizacion   = (int)($_POST['id_cotizacion'] ?? 0);
        $categoria      = trim($_POST['categoria'] ?? '');
        $nombreProyecto = trim($_POST['nombre_proyecto_ref'] ?? '');
        $presupuesto    = trim($_POST['presupuesto_estimado'] ?? '');
        $detalles       = trim($_POST['detalles'] ?? '');
        $nuevoEstado    = trim($_POST['estado'] ?? 'Pendiente');
        $respuestaAdmin = trim($_POST['respuesta_admin'] ?? '');

        $estadosPermitidos = ['Pendiente', 'En revisión', 'Aprobada', 'Rechazada'];
        if (in_array($nuevoEstado, $estadosPermitidos) && $idCotizacion > 0 && !empty($categoria) && !empty($detalles)) {
            $stmtConsulta = $pdo->prepare("SELECT * FROM cotizaciones WHERE id_cotizacion = ? LIMIT 1");
            $stmtConsulta->execute([$idCotizacion]);
            $cotizacionActual = $stmtConsulta->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                UPDATE cotizaciones 
                SET categoria = :categoria,
                    nombre_proyecto_ref = :nombre_proyecto_ref,
                    presupuesto_estimado = :presupuesto,
                    detalles = :detalles,
                    estado = :estado,
                    respuesta_admin = :respuesta 
                WHERE id_cotizacion = :id AND (id_usuario = :id_usuario OR (id_usuario IS NULL AND correo_cliente = :correo))
            ");
            $stmt->execute([
                ':categoria'           => $categoria,
                ':nombre_proyecto_ref' => $nombreProyecto,
                ':presupuesto'         => $presupuesto,
                ':detalles'            => $detalles,
                ':estado'              => $nuevoEstado,
                ':respuesta'           => $respuestaAdmin,
                ':id'                  => $idCotizacion,
                ':id_usuario'          => $usuarioId,
                ':correo'              => $usuario['correo']
            ]);
            $mensaje = 'Cotización #' . $idCotizacion . ' actualizada correctamente.';
            $tipoMensaje = 'exito';

            if ($cotizacionActual) {
                $estadoAnterior    = $cotizacionActual['estado'];
                $respuestaAnterior = $cotizacionActual['respuesta_admin'] ?? '';
                $cambioEstado      = ($estadoAnterior !== $nuevoEstado);
                $cambioRespuesta   = ($respuestaAdmin !== $respuestaAnterior);

                if ($cambioEstado || $cambioRespuesta) {
                    require_once __DIR__ . '/../../servicios/correo_servicio.php';

                    $destinatarioCorreo = !empty($usuario['correo']) ? trim($usuario['correo']) : trim($cotizacionActual['correo_cliente'] ?? '');
                    $destinatarioNombre = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
                    if (empty($destinatarioNombre)) {
                        $destinatarioNombre = trim($cotizacionActual['nombre_cliente'] ?? '');
                    }

                    $resEnvio = enviarNotificacionEstadoCotizacion([
                        'id_cotizacion'       => $idCotizacion,
                        'nombre_cliente'      => $destinatarioNombre,
                        'correo_cliente'      => $destinatarioCorreo,
                        'nuevo_estado'        => $nuevoEstado,
                        'estado_anterior'     => $estadoAnterior,
                        'respuesta_admin'     => $respuestaAdmin,
                        'categoria'           => $categoria,
                        'nombre_proyecto_ref' => $nombreProyecto,
                        'presupuesto'         => $presupuesto,
                        'detalles'            => $detalles,
                        'fecha_creacion'      => $cotizacionActual['fecha_creacion']
                    ]);

                    if ($resEnvio['exito']) {
                        $mensaje .= ' Notificación por correo enviada con éxito a ' . htmlspecialchars($destinatarioCorreo) . '.';
                    } else {
                        $mensaje .= ' La cotización se actualizó, pero no se pudo despachar el correo a ' . htmlspecialchars($destinatarioCorreo) . '. (' . htmlspecialchars($resEnvio['mensaje']) . ').';
                        $tipoMensaje = 'advertencia';
                    }
                }
            }
        } else {
            $mensaje = 'No se pudo actualizar la cotización. Verifica que todos los campos requeridos estén completos.';
            $tipoMensaje = 'error';
        }

    } elseif ($accion === 'eliminar') {
        $idCotizacion = (int)($_POST['id_cotizacion'] ?? 0);
        if ($idCotizacion > 0) {
            $stmt = $pdo->prepare("
                DELETE FROM cotizaciones 
                WHERE id_cotizacion = :id AND (id_usuario = :id_usuario OR (id_usuario IS NULL AND correo_cliente = :correo))
            ");
            $stmt->execute([
                ':id'         => $idCotizacion,
                ':id_usuario' => $usuarioId,
                ':correo'     => $usuario['correo']
            ]);
            $mensaje = 'Cotización #' . $idCotizacion . ' eliminada del sistema.';
            $tipoMensaje = 'exito';
        }
    }
}

$filtroEstado = trim($_GET['estado'] ?? 'todas');
$busqueda     = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM cotizaciones WHERE (id_usuario = :id_usuario OR (id_usuario IS NULL AND correo_cliente = :correo))";
$params = [
    ':id_usuario' => $usuarioId,
    ':correo'     => $usuario['correo']
];

if ($filtroEstado !== 'todas' && in_array($filtroEstado, ['Pendiente', 'En revisión', 'Aprobada', 'Rechazada'])) {
    $sql .= " AND estado = :estado";
    $params[':estado'] = $filtroEstado;
}

if (!empty($busqueda)) {
    $sql .= " AND (categoria LIKE :q OR nombre_proyecto_ref LIKE :q OR detalles LIKE :q OR presupuesto_estimado LIKE :q OR id_cotizacion LIKE :q)";
    $params[':q'] = "%$busqueda%";
}

$sql .= " ORDER BY id_cotizacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$baseCond = "(id_usuario = ? OR (id_usuario IS NULL AND correo_cliente = ?))";
$uParams  = [$usuarioId, $usuario['correo']];

$stmtTodas = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE $baseCond");
$stmtTodas->execute($uParams);
$totalTodas = (int)$stmtTodas->fetchColumn();

$stmtPend = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE $baseCond AND estado = 'Pendiente'");
$stmtPend->execute($uParams);
$totalPendientes = (int)$stmtPend->fetchColumn();

$stmtRev = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE $baseCond AND estado = 'En revisión'");
$stmtRev->execute($uParams);
$totalRevision = (int)$stmtRev->fetchColumn();

$stmtApr = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE $baseCond AND estado = 'Aprobada'");
$stmtApr->execute($uParams);
$totalAprobadas = (int)$stmtApr->fetchColumn();

$stmtRec = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE $baseCond AND estado = 'Rechazada'");
$stmtRec->execute($uParams);
$totalRechazadas = (int)$stmtRec->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizaciones de <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?> - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/estilos.css">
    <link rel="stylesheet" href="../../assets/estilos_adicionales.css">
</head>
<body>
    <div class="contenido-admin">
        <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-bottom: 12px;">
            <a href="administracion_Usuarios.php" class="enlace-volver">&larr; Volver a Usuarios</a>
            <span style="color: var(--color-borde);">&bull;</span>
            <a href="../panel.php" class="enlace-volver">Panel Principal</a>
            <span style="color: var(--color-borde);">&bull;</span>
            <a href="../cotizaciones/administracion_Cotizaciones.php" class="enlace-volver">Todas las Cotizaciones</a>
        </div>

        <div class="contenido-admin__encabezado">
            <h1 class="contenido-admin__titulo">Cotizaciones de <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></h1>
            <p class="contenido-admin__descripcion">
                Administración y seguimiento de las solicitudes realizadas únicamente por este usuario (ID: #<?= $usuario['id'] ?>).
            </p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alerta alerta--<?= $tipoMensaje ?>">
                <p><?= htmlspecialchars($mensaje) ?></p>
            </div>
        <?php endif; ?>

        <div class="tarjeta tarjeta--marco" style="margin-bottom: 24px; padding: 20px 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                        <span class="insignia <?= $usuario['rol'] === 'administrador' ? 'insignia--administrador' : '' ?>">
                            <?= htmlspecialchars(ucfirst($usuario['rol'])) ?>
                        </span>
                        <span style="font-size: 0.8rem; color: var(--color-texto-secundario);">ID Usuario: #<?= $usuario['id'] ?></span>
                    </div>
                    <h2 style="margin: 0 0 6px; font-family: var(--fuente-titulo); font-size: 1.35rem; color: var(--color-texto);">
                        <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                    </h2>
                    <div style="font-size: 0.86rem; color: var(--color-texto-secundario); display: flex; flex-wrap: wrap; gap: 14px;">
                        <span><strong>&#9993; Correo:</strong> <?= htmlspecialchars($usuario['correo']) ?></span>
                        <span><strong>&#9742; Teléfono:</strong> <?= htmlspecialchars($usuario['telefono']) ?></span>
                        <?php if (!empty($usuario['edad'])): ?>
                            <span><strong>Edad:</strong> <?= htmlspecialchars($usuario['edad']) ?> años</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <button type="button" class="boton boton--primario boton--pequeno" onclick="abrirModalCrear()">
                        + Nueva Cotización
                    </button>
                    <form action="actualizar_Usuarios.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                        <button type="submit" class="boton boton--secundario boton--pequeno">
                            Editar Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="metricas-grid" style="margin-bottom: 24px;">
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Total Usuario</span>
                </div>
                <div class="metrica-tarjeta__valor"><?= $totalTodas ?></div>
            </div>
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Pendientes</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: #b37400;"><?= $totalPendientes ?></div>
            </div>
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">En Revisión</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: #125586;"><?= $totalRevision ?></div>
            </div>
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Aprobadas</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: #1e701e;"><?= $totalAprobadas ?></div>
            </div>
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Rechazadas</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: var(--color-acento-oscuro);"><?= $totalRechazadas ?></div>
            </div>
        </div>

        <div class="catalogo-controles" style="margin-bottom: 24px;">
            <form method="GET" action="" style="display: flex; flex-direction: column; gap: 16px;">
                <input type="hidden" name="id" value="<?= $usuarioId ?>">
                <div class="catalogo-controles__superior">
                    <div class="buscador-caja" style="max-width: 100%;">
                        <input 
                            type="text" 
                            name="q" 
                            class="buscador-caja__input" 
                            placeholder="Buscar en cotizaciones de este usuario por categoría, referencia, detalles..." 
                            value="<?= htmlspecialchars($busqueda) ?>"
                        >
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="boton boton--primario boton--pequeno">Buscar</button>
                        <?php if (!empty($busqueda) || $filtroEstado !== 'todas'): ?>
                            <a href="cotizaciones_usuario.php?id=<?= $usuarioId ?>" class="boton boton--secundario boton--pequeno">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="filtros-grupo">
                    <a href="?id=<?= $usuarioId ?>&estado=todas<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'todas' ? 'filtro-chip--activo' : '' ?>">
                        Todas (<?= $totalTodas ?>)
                    </a>
                    <a href="?id=<?= $usuarioId ?>&estado=Pendiente<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'Pendiente' ? 'filtro-chip--activo' : '' ?>">
                        Pendientes (<?= $totalPendientes ?>)
                    </a>
                    <a href="?id=<?= $usuarioId ?>&estado=<?= urlencode('En revisión') ?><?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'En revisión' ? 'filtro-chip--activo' : '' ?>">
                        En Revisión (<?= $totalRevision ?>)
                    </a>
                    <a href="?id=<?= $usuarioId ?>&estado=Aprobada<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'Aprobada' ? 'filtro-chip--activo' : '' ?>">
                        Aprobadas (<?= $totalAprobadas ?>)
                    </a>
                    <a href="?id=<?= $usuarioId ?>&estado=Rechazada<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'Rechazada' ? 'filtro-chip--activo' : '' ?>">
                        Rechazadas (<?= $totalRechazadas ?>)
                    </a>
                </div>
            </form>
        </div>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Área / Espacio</th>
                        <th>Proyecto Ref.</th>
                        <th>Presupuesto</th>
                        <th>Detalles / Requerimientos</th>
                        <th>Estado</th>
                        <th>Respuesta Admin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cotizaciones)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px 20px; color: var(--color-texto-secundario);">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                    <p style="margin: 0; font-size: 1rem;">No se encontraron cotizaciones para este usuario con los filtros seleccionados.</p>
                                    <button type="button" class="boton boton--primario boton--pequeno" onclick="abrirModalCrear()">
                                        + Crear Cotización para <?= htmlspecialchars($usuario['nombre']) ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cotizaciones as $c): ?>
                            <?php
                                $claseEstado = 'insignia--pendiente';
                                if ($c['estado'] === 'En revisión') $claseEstado = 'insignia--revision';
                                elseif ($c['estado'] === 'Aprobada') $claseEstado = 'insignia--aprobada';
                                elseif ($c['estado'] === 'Rechazada') $claseEstado = 'insignia--rechazada';
                            ?>
                            <tr>
                                <td><strong>#<?= $c['id_cotizacion'] ?></strong></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($c['fecha_creacion']))) ?></td>
                                <td>
                                    <span class="insignia"><?= htmlspecialchars(ucfirst($c['categoria'])) ?></span>
                                </td>
                                <td style="white-space: normal; max-width: 140px;">
                                    <?= htmlspecialchars($c['nombre_proyecto_ref'] ?: 'General') ?>
                                </td>
                                <td><?= htmlspecialchars($c['presupuesto_estimado'] ?: 'No especificado') ?></td>
                                <td style="max-width: 220px; white-space: normal; font-size: 0.82rem; line-height: 1.4;">
                                    <?php 
                                        $detallesCortos = mb_substr($c['detalles'], 0, 80);
                                        echo htmlspecialchars($detallesCortos);
                                        if (mb_strlen($c['detalles']) > 80) echo '...';
                                    ?>
                                </td>
                                <td>
                                    <span class="insignia <?= $claseEstado ?>">
                                        <?= htmlspecialchars($c['estado']) ?>
                                    </span>
                                </td>
                                <td style="max-width: 160px; white-space: normal; font-size: 0.82rem; color: var(--color-texto-secundario);">
                                    <?= !empty($c['respuesta_admin']) ? htmlspecialchars(mb_substr($c['respuesta_admin'], 0, 50)) . (mb_strlen($c['respuesta_admin']) > 50 ? '...' : '') : '<em>Sin respuesta</em>' ?>
                                </td>
                                <td>
                                    <div class="acciones-fila">
                                        <button 
                                            type="button" 
                                            class="boton boton--secundario boton--pequeno"
                                            onclick='abrirModalGestion(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                        >
                                            Editar
                                        </button>
                                        <form 
                                            action="" 
                                            method="POST" 
                                            id="form-del-cot-<?= $c['id_cotizacion'] ?>"
                                            style="margin: 0;"
                                        >
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_usuario" value="<?= $usuarioId ?>">
                                            <input type="hidden" name="id_cotizacion" value="<?= $c['id_cotizacion'] ?>">
                                            <button 
                                                type="button" 
                                                class="boton boton--peligro boton--pequeno"
                                                onclick="pedirConfirmacion('¿Seguro de que deseas eliminar la cotización #<?= $c['id_cotizacion'] ?> de <?= htmlspecialchars($usuario['nombre']) ?>?', 'form-del-cot-<?= $c['id_cotizacion'] ?>', 'form')"
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="modalCrear">
        <div class="modal-caja modal-cotizacion-caja tarjeta tarjeta--marco">
            <div class="tarjeta__encabezado">
                <h2 class="marca" style="font-size: 1.3rem;">Obra de Arte</h2>
                <hr class="filete">
                <h3 class="titulo-seccion">Nueva Cotización para <?= htmlspecialchars($usuario['nombre']) ?></h3>
            </div>

            <div style="background-color: var(--color-fondo); border: 1px solid var(--color-borde); padding: 12px; margin-bottom: 16px; font-size: 0.84rem;">
                <div><strong>Usuario asignado:</strong> <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?> (ID: #<?= $usuario['id'] ?>)</div>
                <div><strong>Correo:</strong> <?= htmlspecialchars($usuario['correo']) ?> | <strong>Teléfono:</strong> <?= htmlspecialchars($usuario['telefono']) ?></div>
            </div>

            <form action="" method="POST" class="formulario">
                <input type="hidden" name="accion" value="crear">
                <input type="hidden" name="id_usuario" value="<?= $usuarioId ?>">
                <input type="hidden" name="nombre_cliente" value="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>">
                <input type="hidden" name="correo_cliente" value="<?= htmlspecialchars($usuario['correo']) ?>">
                <input type="hidden" name="telefono_cliente" value="<?= htmlspecialchars($usuario['telefono']) ?>">

                <div class="formulario-grid">
                    <div class="campo">
                        <label for="crearCategoria">Área / Categoría *</label>
                        <select id="crearCategoria" name="categoria" required class="entrada">
                            <option value="Cocina">Cocina</option>
                            <option value="Baño">Baño</option>
                            <option value="Habitación">Habitación</option>
                            <option value="Sala">Sala</option>
                            <option value="Patio">Patio</option>
                            <option value="Fachada">Fachada</option>
                            <option value="Remodelación Integral">Remodelación Integral</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="crearEstado">Estado Inicial *</label>
                        <select id="crearEstado" name="estado" required class="entrada">
                            <option value="Pendiente" selected>Pendiente</option>
                            <option value="En revisión">En revisión</option>
                            <option value="Aprobada">Aprobada</option>
                            <option value="Rechazada">Rechazada</option>
                        </select>
                    </div>
                </div>

                <div class="formulario-grid">
                    <div class="campo">
                        <label for="crearProyectoRef">Proyecto de Referencia</label>
                        <input 
                            type="text" 
                            id="crearProyectoRef" 
                            name="nombre_proyecto_ref" 
                            class="entrada" 
                            placeholder="Ej: Cocina Moderna Minimalista"
                        >
                    </div>

                    <div class="campo">
                        <label for="crearPresupuesto">Presupuesto Estimado</label>
                        <input 
                            type="text" 
                            id="crearPresupuesto" 
                            name="presupuesto_estimado" 
                            class="entrada" 
                            placeholder="Ej: $ 5.000.000 - $ 10.000.000"
                        >
                    </div>
                </div>

                <div class="campo">
                    <label for="crearDetalles">Detalles y Requerimientos de la Solicitud *</label>
                    <textarea 
                        id="crearDetalles" 
                        name="detalles" 
                        required 
                        class="entrada" 
                        rows="4" 
                        placeholder="Describe los requerimientos, medidas, acabados o especificaciones del proyecto..."
                    ></textarea>
                </div>

                <div class="campo">
                    <label for="crearRespuesta">Notas Administrativas / Respuesta Preliminar</label>
                    <textarea 
                        id="crearRespuesta" 
                        name="respuesta_admin" 
                        class="entrada" 
                        rows="2" 
                        placeholder="Observaciones iniciales para esta solicitud..."
                    ></textarea>
                </div>

                <div class="formulario-acciones">
                    <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModalCrear()">
                        Cancelar
                    </button>
                    <button type="submit" class="boton boton--primario boton--pequeno">
                        Crear Cotización
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalGestion">
        <div class="modal-caja modal-cotizacion-caja tarjeta tarjeta--marco">
            <div class="tarjeta__encabezado">
                <h2 class="marca" style="font-size: 1.3rem;">Obra de Arte</h2>
                <hr class="filete">
                <h3 class="titulo-seccion" id="gestionTitulo">Gestionar Cotización</h3>
            </div>

            <div style="background-color: var(--color-fondo); border: 1px solid var(--color-borde); padding: 14px; margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.84rem; margin-bottom: 8px;">
                    <div><strong>Cliente:</strong> <span id="infoCliente"></span></div>
                    <div><strong>Fecha:</strong> <span id="infoFecha"></span></div>
                    <div><strong>Correo:</strong> <span id="infoCorreo"></span></div>
                    <div><strong>Teléfono:</strong> <span id="infoTelefono"></span></div>
                </div>
            </div>

            <form action="" method="POST" class="formulario">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_usuario" value="<?= $usuarioId ?>">
                <input type="hidden" name="id_cotizacion" id="gestionIdCotizacion" value="">

                <div class="formulario-grid">
                    <div class="campo">
                        <label for="gestionCategoria">Área / Categoría *</label>
                        <select id="gestionCategoria" name="categoria" required class="entrada">
                            <option value="Cocina">Cocina</option>
                            <option value="Baño">Baño</option>
                            <option value="Habitación">Habitación</option>
                            <option value="Sala">Sala</option>
                            <option value="Patio">Patio</option>
                            <option value="Fachada">Fachada</option>
                            <option value="Remodelación Integral">Remodelación Integral</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="gestionEstado">Estado de la Cotización *</label>
                        <select id="gestionEstado" name="estado" required class="entrada">
                            <option value="Pendiente">Pendiente</option>
                            <option value="En revisión">En revisión</option>
                            <option value="Aprobada">Aprobada</option>
                            <option value="Rechazada">Rechazada</option>
                        </select>
                    </div>
                </div>

                <div class="formulario-grid">
                    <div class="campo">
                        <label for="gestionProyectoRef">Proyecto de Referencia</label>
                        <input 
                            type="text" 
                            id="gestionProyectoRef" 
                            name="nombre_proyecto_ref" 
                            class="entrada"
                        >
                    </div>

                    <div class="campo">
                        <label for="gestionPresupuesto">Presupuesto Estimado</label>
                        <input 
                            type="text" 
                            id="gestionPresupuesto" 
                            name="presupuesto_estimado" 
                            class="entrada"
                        >
                    </div>
                </div>

                <div class="campo">
                    <label for="gestionDetalles">Detalles y Requerimientos de la Solicitud *</label>
                    <textarea 
                        id="gestionDetalles" 
                        name="detalles" 
                        required 
                        class="entrada" 
                        rows="4"
                    ></textarea>
                </div>

                <div class="campo">
                    <label for="gestionRespuesta">Respuesta o Notas Administrativas</label>
                    <textarea 
                        id="gestionRespuesta" 
                        name="respuesta_admin" 
                        class="entrada" 
                        rows="3" 
                        placeholder="Escribe comentarios, presupuesto final estimado o indicaciones para el cliente..."
                    ></textarea>
                </div>

                <div class="formulario-acciones">
                    <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModalGestion()">
                        Cancelar
                    </button>
                    <button type="submit" class="boton boton--primario boton--pequeno">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-caja tarjeta tarjeta--marco">
            <p class="modal-mensaje" id="modalMensajeTexto"></p>
            <div class="modal-acciones">
                <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModalConfirmacion()">Cancelar</button>
                <button type="button" class="boton boton--peligro boton--pequeno" id="btnModalConfirmar">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        function abrirModalCrear() {
            document.getElementById('modalCrear').classList.add('modal-overlay--activo');
        }

        function cerrarModalCrear() {
            document.getElementById('modalCrear').classList.remove('modal-overlay--activo');
        }

        function abrirModalGestion(datos) {
            document.getElementById('gestionTitulo').textContent = `Gestionar Cotización #${datos.id_cotizacion}`;
            document.getElementById('gestionIdCotizacion').value = datos.id_cotizacion;
            document.getElementById('infoCliente').textContent = datos.nombre_cliente;
            document.getElementById('infoFecha').textContent = datos.fecha_creacion;
            document.getElementById('infoCorreo').textContent = datos.correo_cliente;
            document.getElementById('infoTelefono').textContent = datos.telefono_cliente;

            document.getElementById('gestionCategoria').value = datos.categoria;
            document.getElementById('gestionEstado').value = datos.estado;
            document.getElementById('gestionProyectoRef').value = datos.nombre_proyecto_ref || '';
            document.getElementById('gestionPresupuesto').value = datos.presupuesto_estimado || '';
            document.getElementById('gestionDetalles').value = datos.detalles || '';
            document.getElementById('gestionRespuesta').value = datos.respuesta_admin || '';

            document.getElementById('modalGestion').classList.add('modal-overlay--activo');
        }

        function cerrarModalGestion() {
            document.getElementById('modalGestion').classList.remove('modal-overlay--activo');
        }

        let accionPendiente = null;

        function pedirConfirmacion(mensaje, destino, tipo) {
            document.getElementById('modalMensajeTexto').textContent = mensaje;
            accionPendiente = { destino: destino, tipo: tipo };
            document.getElementById('modalConfirmacion').classList.add('modal-overlay--activo');
        }

        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').classList.remove('modal-overlay--activo');
            accionPendiente = null;
        }

        document.getElementById('btnModalConfirmar').addEventListener('click', function() {
            if (!accionPendiente) return;
            if (accionPendiente.tipo === 'link') {
                window.location.href = accionPendiente.destino;
            } else if (accionPendiente.tipo === 'form') {
                document.getElementById(accionPendiente.destino).submit();
            }
        });

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalCrear();
                    cerrarModalGestion();
                    cerrarModalConfirmacion();
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalCrear();
                cerrarModalGestion();
                cerrarModalConfirmacion();
            }
        });
    </script>
</body>
</html>
