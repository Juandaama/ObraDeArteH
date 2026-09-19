<?php
session_start();
require_once '../DB/conexion.php';
 
$error = '';
$exito = '';

$nombre     = '';
$apellido   = '';
$telefono   = '';
$correo     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $apellido   = trim($_POST['apellido'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $correo     = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
 
    if ($nombre === '' || $apellido === '' || $telefono === '' || $correo === '' || $contrasena === '') {
        $error = 'Por favor completa todos los campos.';
    } elseif (!preg_match('/^[0-9]{7,15}$/', $telefono)) {
        $error = 'El número de teléfono solo debe contener números (entre 7 y 15 dígitos).';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido con dominio completo (ejemplo: usuario@gmail.com).';
    } elseif (strlen($contrasena) < 6) {
        $error = 'La contraseña debe tener como mínimo 6 caracteres.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
        $stmt->execute([$correo]);
 
        if ($stmt->fetch()) {
            $error = 'Ya existe una cuenta registrada con ese correo electrónico.';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
 
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nombre, apellido, telefono, correo, contrasena)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nombre, $apellido, $telefono, $correo, $hash]);
 
            $exito = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
            // Limpiar campos tras éxito
            $nombre = '';
            $apellido = '';
            $telefono = '';
            $correo = '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco">
            <div class="tarjeta__encabezado">
                <div class="marca-logo">
                    <img src="../img/Logo.PNG" alt="Obra de Arte Arquitectura" class="marca-logo__imagen">
                </div>
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
                        <a href="../index.php" class="boton boton--primario">Ir a iniciar sesión</a>
                    </div>
                <?php else: ?>
                    <form action="procesoCrearUsuario.php" method="post" class="formulario" id="form-crear-cuenta">
                        <div class="campo">
                            <label for="nombre">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required class="entrada" placeholder="Ingresa tu nombre">
                        </div>

                        <div class="campo">
                            <label for="apellido">Apellido:</label>
                            <input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($apellido) ?>" required class="entrada" placeholder="Ingresa tu apellido">
                        </div>

                        <div class="campo">
                            <label for="telefono">Teléfono:</label>
                            <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono) ?>" required class="entrada" inputmode="numeric" pattern="[0-9]{7,15}" title="Ingresa un número telefónico de 7 a 15 dígitos sin letras" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Ej: 3113875849">
                        </div>

                        <div class="campo">
                            <label for="correo">Correo electrónico:</label>
                            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo) ?>" required class="entrada" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" title="Ingresa un correo válido con dominio completo (ej: usuario@gmail.com)" placeholder="ejemplo@gmail.com">
                        </div>

                        <div class="campo">
                            <label for="contrasena">Contraseña:</label>
                            <input type="password" id="contrasena" name="contrasena" required minlength="6" class="entrada" placeholder="Mínimo 6 caracteres">
                        </div>

                        <input type="submit" value="Crear cuenta" class="boton boton--primario">
                    </form>
                <?php endif; ?>

                <hr class="filete filete--secundario">
                <div class="acciones-secundarias">
                    <a href="../index.php" class="boton boton--secundario">Volver</a>
                </div>

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