<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../DB/conexion.php';

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

$usuarios = $pdo->query('
    SELECT u.id, u.nombre, u.apellido, u.edad, u.telefono, u.correo, u.rol,
           (SELECT COUNT(*) FROM cotizaciones c WHERE c.id_usuario = u.id OR (c.id_usuario IS NULL AND c.correo_cliente = u.correo)) AS total_cotizaciones
    FROM usuarios u 
    ORDER BY u.id DESC
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración Usuarios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/estilos.css">
    <link rel="stylesheet" href="../../assets/estilos_adicionales.css">
</head>
<body>
    <div class="contenido-admin">
        <a href="../panel.php" class="enlace-volver">Volver</a>
        <div class="contenido-admin__encabezado">
            <h1 class="contenido-admin__titulo">Administración Usuarios</h1>
            <p class="contenido-admin__descripcion">Gestiona las cuentas del sistema y consulta sus solicitudes de cotización</p>
        </div>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Edad</th>
                        <th>Telefono</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px; color: var(--color-texto-secundario);">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($u['apellido']); ?></td>
                                <td><?php echo !empty($u['edad']) ? htmlspecialchars($u['edad']) . ' años' : '-'; ?></td>
                                <td><?php echo htmlspecialchars($u['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                <td>
                                    <span class="insignia<?php echo $u['rol'] === 'administrador' ? ' insignia--administrador' : ''; ?>">
                                        <?php echo htmlspecialchars(ucfirst($u['rol'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-fila">
                                        <a
                                            href="cotizaciones_usuario.php?id=<?= $u['id'] ?>"
                                            class="boton boton--primario boton--pequeno"
                                            title="Ver cotizaciones de <?= htmlspecialchars($u['nombre']) ?>"
                                        >
                                            Cotizaciones<?= (isset($u['total_cotizaciones']) && $u['total_cotizaciones'] > 0) ? ' (' . $u['total_cotizaciones'] . ')' : '' ?>
                                        </a>
                                        <form action="actualizar_Usuarios.php" method="post">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <input
                                                type="submit"
                                                value="Editar"
                                                class="boton boton--secundario boton--pequeno"
                                            >
                                        </form>
                                        <form action="eliminar_Usuario.php" method="post" id="form-del-usr-<?= $u['id'] ?>">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button
                                                type="button"
                                                class="boton boton--peligro boton--pequeno"
                                                onclick="pedirConfirmacion('¿Seguro de que quieres eliminar este usuario?', 'form-del-usr-<?= $u['id'] ?>', 'form')"
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

    <!-- Modal de Confirmación -->
    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-caja tarjeta tarjeta--marco">
            <p class="modal-mensaje" id="modalMensajeTexto"></p>
            <div class="modal-acciones">
                <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="boton boton--peligro boton--pequeno" id="btnModalConfirmar">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        let accionPendiente = null;

        function pedirConfirmacion(mensaje, destino, tipo) {
            document.getElementById('modalMensajeTexto').textContent = mensaje;
            accionPendiente = { destino: destino, tipo: tipo };
            document.getElementById('modalConfirmacion').classList.add('modal-overlay--activo');
        }

        function cerrarModal() {
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

        document.getElementById('modalConfirmacion').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
