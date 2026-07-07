<?php
session_start();
require_once '../DB/conexion.php';
 
$error = '';
$exito = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $apellido   = trim($_POST['apellido'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $correo     = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
 
    if ($nombre === '' || $apellido === '' || $telefono === '' || $correo === '' || $contrasena === '') {
        $error = 'Completa todos los campos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es válido.';
    } elseif (strlen($contrasena) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
        $stmt->execute([$correo]);
 
        if ($stmt->fetch()) {
            $error = 'Ya existe una cuenta con ese correo.';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
 
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nombre, apellido, telefono, correo, contrasena)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nombre, $apellido, $telefono, $correo, $hash]);
 
            $exito = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <h1>ObraDeArteH</h1>
        <hr>
        <h2>Crear cuenta</h2>
 
        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
 
        <?php if ($exito): ?>
            <p style="color:green;"><?= htmlspecialchars($exito) ?></p>
            <a href="../index.php"><button>Ir a iniciar sesión</button></a>
        <?php else: ?>
            <form action="procesoCrearUsuario.php" method="post">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required><br><br>
 
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required><br><br>
 
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" required><br><br>
 
                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo" required><br><br>
 
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required><br><br>
 
                <input type="submit" value="Crear cuenta" class="transicion">
            </form>
        <?php endif; ?>
 
        <br>
        <hr>
        <a href="../index.php" class="transicion"><button>Volver</button></a>
    </div>
</body>
</html>