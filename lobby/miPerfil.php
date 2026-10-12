<?php
session_start();
require_once '../DB/conexion.php';

// Verificar que el usuario tenga sesión iniciada
if (!isset($_SESSION['id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = (int)$_SESSION['id'];

// Asegurar que la columna 'edad' exista en la tabla usuarios
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'edad'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN edad INT NULL AFTER apellido");
    }
} catch (Exception $e) {
    // Continuar ejecución
}

$error = '';
$exito = '';

// Obtener los datos actuales del usuario en sesión
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: ../Login/CerrarSesion.php');
    exit;
}

// Inicializar variables con los datos de la base de datos
$nombre   = $usuario['nombre'] ?? '';
$apellido = $usuario['apellido'] ?? '';
$edad     = $usuario['edad'] ?? '';
$telefono = $usuario['telefono'] ?? '';
$correo   = $usuario['correo'] ?? '';

// Procesar actualización cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $edad     = trim($_POST['edad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');

    if ($nombre === '' || $apellido === '' || $telefono === '' || $correo === '') {
        $error = 'Por favor completa todos los campos obligatorios.';
    } elseif (!preg_match('/^[0-9]{7,15}$/', $telefono)) {
        $error = 'El número de teléfono solo debe contener números (entre 7 y 15 dígitos).';
    } elseif (!empty($edad) && (!is_numeric($edad) || (int)$edad < 18 || (int)$edad > 100)) {
        $error = 'Por favor ingresa una edad válida en números (entre 18 y 100 años).';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido con dominio completo (ejemplo: usuario@gmail.com).';
    } else {
        // Verificar si el correo ya pertenece a otro usuario registrado
        $stmtCorreo = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ? LIMIT 1");
        $stmtCorreo->execute([$correo, $userId]);
        if ($stmtCorreo->fetch()) {
            $error = 'Ese correo electrónico ya está registrado por otro usuario.';
        } else {
            $edadFinal = (!empty($edad)) ? (int)$edad : null;
            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, edad = ?, telefono = ?, correo = ? WHERE id = ?");
            $stmtUpdate->execute([$nombre, $apellido, $edadFinal, $telefono, $correo, $userId]);

            // Actualizar datos en sesión
            $_SESSION['nombre']   = $nombre;
            $_SESSION['apellido'] = $apellido;
            $_SESSION['telefono'] = $telefono;
            $_SESSION['correo']   = $correo;

            $exito = '¡Tu perfil ha sido actualizado correctamente!';
            $usuario['nombre']   = $nombre;
            $usuario['apellido'] = $apellido;
            $usuario['edad']     = $edadFinal;
            $usuario['telefono'] = $telefono;
            $usuario['correo']   = $correo;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco tarjeta--perfil">

            <a href="catalogo.php" class="enlace-volver">&larr; Volver al Catálogo</a>

            <div class="tarjeta__encabezado">
                <div class="marca-logo">
                    <img src="../img/Logo.PNG" alt="Obra de Arte Arquitectura" class="marca-logo__imagen">
                </div>
                <hr class="filete">
                <h2 class="titulo-seccion">Mi Perfil</h2>
            </div>

            <div class="tarjeta__cuerpo">

                <?php if (!empty($error)): ?>
                    <div class="alerta alerta--error">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($exito)): ?>
                    <div class="alerta alerta--exito">
                        <p><?= htmlspecialchars($exito) ?></p>
                    </div>
                <?php endif; ?>

                <form action="miPerfil.php" method="POST" class="formulario" id="form-mi-perfil">

                    <!-- Fila 1: Nombre y Apellido -->
                    <div class="perfil-grid">
                        <div class="campo">
                            <label for="nombre">Nombre *:</label>
                            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required class="entrada" placeholder="Ingresa tu nombre">
                        </div>

                        <div class="campo">
                            <label for="apellido">Apellido *:</label>
                            <input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($apellido) ?>" required class="entrada" placeholder="Ingresa tu apellido">
                        </div>
                    </div>

                    <!-- Fila 2: Edad y Teléfono -->
                    <div class="perfil-grid">
                        <div class="campo">
                            <label for="edad">Edad:</label>
                            <input type="number" id="edad" name="edad" value="<?= htmlspecialchars($edad !== null ? $edad : '') ?>" min="18" max="100" class="entrada" placeholder="Ej: 28">
                        </div>

                        <div class="campo">
                            <label for="telefono">Teléfono *:</label>
                            <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono) ?>" required class="entrada" inputmode="numeric" pattern="[0-9]{7,15}" title="Ingresa un teléfono válido de 7 a 15 números" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Ej: 3113875849">
                        </div>
                    </div>

                    <!-- Fila 3: Correo Electrónico -->
                    <div class="campo">
                        <label for="correo">Correo Electrónico *:</label>
                        <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo) ?>" required class="entrada" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" title="Ingresa un correo con dominio válido (ej: usuario@gmail.com)" placeholder="ejemplo@obradearteh.com">
                    </div>

                    <button type="button" class="boton boton--primario" onclick="validarYPedirConfirmacion()">Guardar Cambios</button>
                </form>

                <hr class="filete filete--secundario">
                <div class="tarjeta__pie">
                    <footer class="pie">
                        <p class="pie__texto">ObraDeArteH &bull; Remodelación y Diseño</p>
                    </footer>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal-overlay" id="modalConfirmacionPerfil">
        <div class="modal-caja tarjeta tarjeta--marco">
            <p class="modal-mensaje">¿Confirmas que deseas guardar los cambios en tu perfil?</p>
            <div class="modal-acciones">
                <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModalPerfil()">Cancelar</button>
                <button type="button" class="boton boton--peligro boton--pequeno" id="btnConfirmarPerfil">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        function validarYPedirConfirmacion() {
            const form = document.getElementById('form-mi-perfil');
            if (!form) return;

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Validar teléfono solo números
            const telInput = document.getElementById('telefono');
            if (telInput && !/^[0-9]{7,15}$/.test(telInput.value.trim())) {
                alert('El número telefónico debe contener únicamente números (entre 7 y 15 dígitos).');
                telInput.focus();
                return;
            }

            // Validar edad si fue diligenciada (entre 18 y 100 años)
            const edadInput = document.getElementById('edad');
            if (edadInput && edadInput.value.trim() !== '') {
                const edadNum = parseInt(edadInput.value.trim(), 10);
                if (isNaN(edadNum) || edadNum < 18 || edadNum > 100) {
                    alert('Por favor ingresa una edad válida en números (entre 18 y 100 años).');
                    edadInput.focus();
                    return;
                }
            }

            // Validar correo con dominio completo
            const correoInput = document.getElementById('correo');
            if (correoInput && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(correoInput.value.trim())) {
                alert('Por favor ingresa un correo electrónico válido con dominio completo (ej: usuario@gmail.com).');
                correoInput.focus();
                return;
            }

            document.getElementById('modalConfirmacionPerfil').classList.add('modal-overlay--activo');
        }

        function cerrarModalPerfil() {
            const modal = document.getElementById('modalConfirmacionPerfil');
            if (modal) {
                modal.classList.remove('modal-overlay--activo');
            }
        }

        const btnConfirmar = document.getElementById('btnConfirmarPerfil');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function() {
                document.getElementById('form-mi-perfil').submit();
            });
        }

        const modalOverlay = document.getElementById('modalConfirmacionPerfil');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalPerfil();
                }
            });
        }
    </script>
</body>
</html>

