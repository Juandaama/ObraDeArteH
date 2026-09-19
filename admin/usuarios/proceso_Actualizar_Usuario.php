<?php
session_start();
require_once "../../DB/conexion.php";

if (!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    die("ID de usuario no válido.");
}

$id       = (int)$_POST['id'];
$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo   = trim($_POST['correo'] ?? '');
$rol      = trim($_POST['rol'] ?? 'usuario');

$error = '';

if ($nombre === '' || $apellido === '' || $telefono === '' || $correo === '') {
    $error = 'Todos los campos son obligatorios.';
} elseif (!preg_match('/^[0-9]{7,15}$/', $telefono)) {
    $error = 'El número de teléfono solo debe contener números (entre 7 y 15 dígitos).';
} elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $error = 'Ingresa un correo electrónico válido con dominio completo (ejemplo: usuario@gmail.com).';
} else {
    // Verificar si el correo pertenece a otro usuario
    $stmtCorreo = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ? LIMIT 1");
    $stmtCorreo->execute([$correo, $id]);
    if ($stmtCorreo->fetch()) {
        $error = 'Ese correo electrónico ya está registrado por otro usuario.';
    } else {
        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?, correo = ?, rol = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellido, $telefono, $correo, $rol, $id]);

        // Si se actualizó el usuario en sesión
        if (isset($_SESSION['id']) && $_SESSION['id'] === $id) {
            $_SESSION['nombre']   = $nombre;
            $_SESSION['apellido'] = $apellido;
            $_SESSION['telefono'] = $telefono;
            $_SESSION['correo']   = $correo;
            $_SESSION['rol']      = $rol;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= empty($error) ? 'Usuario Actualizado' : 'Error al actualizar' ?> - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco">
            <div class="tarjeta__encabezado">
                <div class="marca-logo">
                    <img src="../../img/Logo.PNG" alt="Obra de Arte Arquitectura" class="marca-logo__imagen">
                </div>
                <hr class="filete">
                <h2 class="titulo-seccion"><?= empty($error) ? 'Actualización Exitosa' : 'Aviso del Sistema' ?></h2>
            </div>
            <div class="tarjeta__cuerpo">
                <?php if (!empty($error)): ?>
                    <div class="alerta alerta--error">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php else: ?>
                    <div class="alerta alerta--exito">
                        <p>Los datos del usuario han sido actualizados correctamente en el sistema.</p>
                    </div>
                <?php endif; ?>

                <div class="acciones-secundarias" style="margin-top: 20px;">
                    <a href="administracion_Usuarios.php" class="boton boton--primario">Volver a Administración de Usuarios</a>
                </div>

                <hr class="filete filete--secundario">
                <div class="tarjeta__pie">
                    <footer class="pie">
                        <p class="pie__texto">ObraDeArteH &bull; Remodelación y Diseño</p>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php exit(); ?>