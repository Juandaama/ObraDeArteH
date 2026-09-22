<?php
session_start();
require_once '../../DB/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../../index.php');
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

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'actualizar') {
        $idCotizacion   = (int)($_POST['id_cotizacion'] ?? 0);
        $nuevoEstado    = trim($_POST['estado'] ?? 'Pendiente');
        $respuestaAdmin = trim($_POST['respuesta_admin'] ?? '');

        $estadosPermitidos = ['Pendiente', 'En revisión', 'Aprobada', 'Rechazada'];
        if (in_array($nuevoEstado, $estadosPermitidos) && $idCotizacion > 0) {
            $stmt = $pdo->prepare("
                UPDATE cotizaciones 
                SET estado = :estado, respuesta_admin = :respuesta 
                WHERE id_cotizacion = :id
            ");
            $stmt->execute([
                ':estado'    => $nuevoEstado,
                ':respuesta' => $respuestaAdmin,
                ':id'        => $idCotizacion
            ]);
            $mensaje = 'Cotización #' . $idCotizacion . ' actualizada correctamente.';
            $tipoMensaje = 'exito';
        }
    } elseif ($_POST['accion'] === 'eliminar') {
        $idCotizacion = (int)($_POST['id_cotizacion'] ?? 0);
        if ($idCotizacion > 0) {
            $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id_cotizacion = :id");
            $stmt->execute([':id' => $idCotizacion]);
            $mensaje = 'Cotización #' . $idCotizacion . ' eliminada del sistema.';
            $tipoMensaje = 'exito';
        }
    }
}


$filtroEstado = trim($_GET['estado'] ?? 'todas');
$busqueda     = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM cotizaciones WHERE 1=1";
$params = [];

if ($filtroEstado !== 'todas' && in_array($filtroEstado, ['Pendiente', 'En revisión', 'Aprobada', 'Rechazada'])) {
    $sql .= " AND estado = :estado";
    $params[':estado'] = $filtroEstado;
}

if (!empty($busqueda)) {
    $sql .= " AND (nombre_cliente LIKE :q OR correo_cliente LIKE :q OR telefono_cliente LIKE :q OR categoria LIKE :q OR nombre_proyecto_ref LIKE :q)";
    $params[':q'] = "%$busqueda%";
}

$sql .= " ORDER BY id_cotizacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);


$totalTodas       = $pdo->query("SELECT COUNT(*) FROM cotizaciones")->fetchColumn();
$totalPendientes  = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'Pendiente'")->fetchColumn();
$totalRevision    = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'En revisión'")->fetchColumn();
$totalAprobadas   = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'Aprobada'")->fetchColumn();
$totalRechazadas  = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'Rechazada'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Cotizaciones - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/estilos.css">
    <link rel="stylesheet" href="../../assets/estilos_adicionales.css">
</head>
<body>
    <div class="contenido-admin">
        <a href="../panel.php" class="enlace-volver">Volver al Panel</a>

        <div class="contenido-admin__encabezado">
            <h1 class="contenido-admin__titulo">Gestión de Cotizaciones</h1>
            <p class="contenido-admin__descripcion">Revisa, responde y administra las solicitudes de remodelación recibidas</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta alerta--<?= $tipoMensaje ?>">
                <p><?= htmlspecialchars($mensaje) ?></p>
            </div>
        <?php endif; ?>

        <div class="metricas-grid" style="margin-bottom: 24px;">
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Todas</span>
                    <span class="metrica-tarjeta__icono">&#128196;</span>
                </div>
                <div class="metrica-tarjeta__valor"><?= $totalTodas ?></div>
            </div>
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Pendientes</span>
                    <span class="metrica-tarjeta__icono">&#9203;</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: #b37400;"><?= $totalPendientes ?></div>
            </div>
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">En Revisión</span>
                    <span class="metrica-tarjeta__icono">&#128269;</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: #125586;"><?= $totalRevision ?></div>
            </div>
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Aprobadas</span>
                    <span class="metrica-tarjeta__icono">&#9989;</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: #1e701e;"><?= $totalAprobadas ?></div>
            </div>
        </div>

        <div class="catalogo-controles" style="margin-bottom: 24px;">
            <form method="GET" action="" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="catalogo-controles__superior">
                    <div class="buscador-caja" style="max-width: 100%;">
                        <span class="buscador-caja__icono">&#128269;</span>
                        <input 
                            type="text" 
                            name="q" 
                            class="buscador-caja__input" 
                            placeholder="Buscar por cliente, correo, teléfono, categoría..." 
                            value="<?= htmlspecialchars($busqueda) ?>"
                        >
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="boton boton--primario boton--pequeno">Buscar</button>
                        <?php if (!empty($busqueda) || $filtroEstado !== 'todas'): ?>
                            <a href="administracion_Cotizaciones.php" class="boton boton--secundario boton--pequeno">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="filtros-grupo">
                    <a href="?estado=todas<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'todas' ? 'filtro-chip--activo' : '' ?>">
                        Todas (<?= $totalTodas ?>)
                    </a>
                    <a href="?estado=Pendiente<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'Pendiente' ? 'filtro-chip--activo' : '' ?>">
                        Pendientes (<?= $totalPendientes ?>)
                    </a>
                    <a href="?estado=<?= urlencode('En revisión') ?><?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'En revisión' ? 'filtro-chip--activo' : '' ?>">
                        En Revisión (<?= $totalRevision ?>)
                    </a>
                    <a href="?estado=Aprobada<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
                       class="filtro-chip <?= $filtroEstado === 'Aprobada' ? 'filtro-chip--activo' : '' ?>">
                        Aprobadas (<?= $totalAprobadas ?>)
                    </a>
                    <a href="?estado=Rechazada<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" 
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
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Área</th>
                        <th>Ref.</th>
                        <th>Presupuesto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cotizaciones)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 32px; color: var(--color-texto-secundario);">
                                No se encontraron solicitudes de cotización para los criterios seleccionados.
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
                                <td style="white-space: normal; max-width: 140px;">
                                    <strong><?= htmlspecialchars($c['nombre_cliente']) ?></strong>
                                </td>
                                <td style="font-size: 0.8rem; line-height: 1.4;">
                                    <div>&#9993; <?= htmlspecialchars($c['correo_cliente']) ?></div>
                                    <div>&#9742; <?= htmlspecialchars($c['telefono_cliente']) ?></div>
                                </td>
                                <td>
                                    <span class="insignia"><?= htmlspecialchars(ucfirst($c['categoria'])) ?></span>
                                </td>
                                <td style="white-space: normal; max-width: 120px;">
                                    <?= htmlspecialchars($c['nombre_proyecto_ref'] ?: 'General') ?>
                                </td>
                                <td><?= htmlspecialchars($c['presupuesto_estimado'] ?: 'No espec.') ?></td>
                                <td>
                                    <span class="insignia <?= $claseEstado ?>">
                                        <?= htmlspecialchars($c['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-fila">
                                        <button 
                                            type="button" 
                                            class="boton boton--secundario boton--pequeno"
                                            onclick='abrirModalGestion(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                        >
                                            Gestionar
                                        </button>
                                        <form 
                                            action="" 
                                            method="POST" 
                                            id="form-del-cot-<?= $c['id_cotizacion'] ?>"
                                            style="margin: 0;"
                                        >
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_cotizacion" value="<?= $c['id_cotizacion'] ?>">
                                            <button 
                                                type="button" 
                                                class="boton boton--peligro boton--pequeno"
                                                onclick="pedirConfirmacion('¿Seguro de que deseas eliminar la cotización #<?= $c['id_cotizacion'] ?> de <?= htmlspecialchars($c['nombre_cliente']) ?>?', 'form-del-cot-<?= $c['id_cotizacion'] ?>', 'form')"
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
                    <div><strong>Área / Espacio:</strong> <span id="infoArea"></span></div>
                    <div><strong>Presupuesto:</strong> <span id="infoPresupuesto"></span></div>
                </div>
                <div style="font-size: 0.84rem;">
                    <strong>Proyecto Ref:</strong> <span id="infoRef"></span>
                </div>
                <div style="margin-top: 8px; font-size: 0.85rem; border-top: 1px solid var(--color-borde); padding-top: 6px;">
                    <strong>Detalles solicitados:</strong>
                    <p id="infoDetalles" style="margin: 4px 0 0; white-space: pre-line; color: var(--color-texto); font-size: 0.84rem;"></p>
                </div>
            </div>

            <form action="" method="POST" class="formulario">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_cotizacion" id="gestionIdCotizacion" value="">

                <div class="campo">
                    <label for="gestionEstado">Estado de la Cotización *</label>
                    <select id="gestionEstado" name="estado" required class="entrada">
                        <option value="Pendiente">Pendiente</option>
                        <option value="En revisión">En revisión</option>
                        <option value="Aprobada">Aprobada</option>
                        <option value="Rechazada">Rechazada</option>
                    </select>
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
        function abrirModalGestion(datos) {
            document.getElementById('gestionTitulo').textContent = `Gestionar Cotización #${datos.id_cotizacion}`;
            document.getElementById('gestionIdCotizacion').value = datos.id_cotizacion;
            document.getElementById('infoCliente').textContent = datos.nombre_cliente;
            document.getElementById('infoFecha').textContent = datos.fecha_creacion;
            document.getElementById('infoCorreo').textContent = datos.correo_cliente;
            document.getElementById('infoTelefono').textContent = datos.telefono_cliente;
            document.getElementById('infoArea').textContent = datos.categoria.toUpperCase();
            document.getElementById('infoPresupuesto').textContent = datos.presupuesto_estimado || 'No especificado';
            document.getElementById('infoRef').textContent = datos.nombre_proyecto_ref || 'Ninguno (General)';
            document.getElementById('infoDetalles').textContent = datos.detalles;

            document.getElementById('gestionEstado').value = datos.estado;
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
                    cerrarModalGestion();
                    cerrarModalConfirmacion();
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalGestion();
                cerrarModalConfirmacion();
            }
        });
    </script>
</body>
</html>

