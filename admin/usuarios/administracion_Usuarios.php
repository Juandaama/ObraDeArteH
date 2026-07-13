<?php
require_once '../../DB/conexion.php';

$usuarios = $pdo->query('SELECT id, nombre, apellido, telefono, correo , rol FROM usuarios ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>administracion_Usuarios</title>
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
            <table border=1 class="tabla">
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
                                        <input type="submit" value="Editar" class="boton boton--secundario boton--pequeno">
                                    </form>
                                    <form action="eliminar_Usuario.php" method="post">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <input type="submit" value="Eliminar" class="boton boton--peligro boton--pequeno">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
