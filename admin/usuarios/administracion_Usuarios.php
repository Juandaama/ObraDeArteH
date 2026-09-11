<?php
require_once '../../DB/conexion.php';

$usuarios = $pdo->query('SELECT id, nombre, apellido, telefono, correo , rol FROM usuarios ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración Usuarios</title>
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>
<body>
    <div class="contenido-admin">
        <a href="../panel.php" class="enlace-volver">Volver</a>
        <div class="contenido-admin__encabezado">
            <h1 class="contenido-admin__titulo">Administración Usuarios</h1>
            <p class="contenido-admin__descripcion">Gestiona las cuentas del sistema</p>
        </div>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Telefono</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: var(--color-texto-secundario);">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($u['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($u['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                <td>
                                    <span class="insignia<?php echo $u['rol'] === 'administrador' ? ' insignia--administrador' : ''; ?>">
                                        <?php echo htmlspecialchars(ucfirst($u['rol'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-fila">
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
