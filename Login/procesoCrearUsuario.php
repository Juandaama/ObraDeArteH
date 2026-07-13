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
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco">
            <div class="tarjeta__encabezado">
                <h1 class="marca">ObraDeArteH</h1>
                <hr class="filete">
                <h2 class="titulo-seccion">Crear cuenta</h2>
            </div>

            <div class="tarjeta__cuerpo">
                <?php if ($error): ?>
                    <div class="alerta alerta--error">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($exito): ?>
                    <div class="alerta alerta--exito">
                        <p><?= htmlspecialchars($exito) ?></p>
                    </div>
                    <div class="acciones-secundarias">
                        <a href="../index.php"><button class="boton boton--primario">Ir a iniciar sesión</button></a>
                    </div>
                <?php else: ?>
                    <form action="procesoCrearUsuario.php" method="post" class="formulario">
                        <div class="campo">
                            <label for="nombre">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" required class="entrada"><br><br>
                        </div>

                        <div class="campo">
                            <label for="apellido">Apellido:</label>
                            <input type="text" id="apellido" name="apellido" required class="entrada"><br><br>
                        </div>

                        <div class="campo">
                            <label for="telefono">Teléfono:</label>
                            <input type="text" id="telefono" name="telefono" required class="entrada"><br><br>
                        </div>

                        <div class="campo">
                            <label for="correo">Correo:</label>
                            <input type="email" id="correo" name="correo" required class="entrada"><br><br>
                        </div>

                        <div class="campo">
                            <label for="contrasena">Contraseña:</label>
                            <input type="password" id="contrasena" name="contrasena" required class="entrada"><br><br>
                        </div>

                        <input type="submit" value="Crear cuenta" class="boton boton--primario transicion">
                    </form>
                <?php endif; ?>

                <br>
                <hr class="filete filete--secundario">
                <div class="acciones-secundarias">
                    <a href="../index.php" class="transicion"><button class="boton boton--secundario">Volver</button></a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>