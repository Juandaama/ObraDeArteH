<?php
session_start();
include("../../DB/conexion.php");

if (!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    die("ID de usuario no válido.");
}

$id = $_POST['id'];

$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Usuario no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco">
            <a href="administracion_Usuarios.php" class="enlace-volver">&larr; Volver a la lista</a>
            <div class="tarjeta__encabezado">
                <div class="marca-logo">
                    <img src="../../img/Logo.PNG" alt="Obra de Arte Arquitectura" class="marca-logo__imagen">
                </div>
                <hr class="filete">
                <h2 class="titulo-seccion">Editar Usuario</h2>
            </div>
            <div class="tarjeta__cuerpo">
                <form action="proceso_Actualizar_Usuario.php" method="POST" class="formulario" id="form-actualizar-usuario">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                    <div class="campo">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?= htmlspecialchars($usuario['nombre']) ?>" required class="entrada" placeholder="Nombre del usuario">
                    </div>

                    <div class="campo">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido"
                               value="<?= htmlspecialchars($usuario['apellido']) ?>" required class="entrada" placeholder="Apellido del usuario">
                    </div>

                    <div class="campo">
                        <label for="telefono">Teléfono (Solo números):</label>
                        <input type="tel" id="telefono" name="telefono"
                               value="<?= htmlspecialchars($usuario['telefono']) ?>" required class="entrada"
                               inputmode="numeric" pattern="[0-9]{7,15}" title="Ingresa un número telefónico de 7 a 15 dígitos sin letras"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Ej: 3113875849">
                    </div>

                    <div class="campo">
                        <label for="correo">Correo electrónico:</label>
                        <input type="email" id="correo" name="correo"
                               value="<?= htmlspecialchars($usuario['correo']) ?>" required class="entrada"
                               pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                               title="Ingresa un correo válido con dominio completo (ej: usuario@gmail.com)" placeholder="ejemplo@gmail.com">
                    </div>

                    <div class="campo">
                        <label for="rol">Rol:</label>
                        <select name="rol" id="rol" class="entrada">
                            <option value="usuario" <?=$usuario['rol'] === 'usuario' ? 'selected': '' ?>>Usuario</option>
                            <option value="administrador" <?=$usuario['rol'] === 'administrador' ? 'selected': '' ?>>Administrador</option>
                        </select>
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
    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-caja tarjeta tarjeta--marco">
            <p class="modal-mensaje">¿Confirmas que deseas guardar los cambios de este usuario?</p>
            <div class="modal-acciones">
                <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="boton boton--peligro boton--pequeno" id="btnModalConfirmar">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        function validarYPedirConfirmacion() {
            const form = document.getElementById('form-actualizar-usuario');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const tel = document.getElementById('telefono');
            if (tel && !/^[0-9]{7,15}$/.test(tel.value.trim())) {
                alert('El teléfono debe contener únicamente números (entre 7 y 15 dígitos).');
                tel.focus();
                return;
            }

            const correo = document.getElementById('correo');
            if (correo && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(correo.value.trim())) {
                alert('Ingresa un correo electrónico válido con dominio completo (ej: usuario@gmail.com).');
                correo.focus();
                return;
            }

            document.getElementById('modalConfirmacion').classList.add('modal-overlay--activo');
        }

        function cerrarModal() {
            document.getElementById('modalConfirmacion').classList.remove('modal-overlay--activo');
        }

        document.getElementById('btnModalConfirmar').addEventListener('click', function() {
            document.getElementById('form-actualizar-usuario').submit();
        });

        document.getElementById('modalConfirmacion').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>