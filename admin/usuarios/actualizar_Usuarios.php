<?php
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco">
            <a href="administracion_Usuarios.php" class="enlace-volver">Volver</a>
            <div class="tarjeta__encabezado">
                <h1 class="marca">Obra de Arte</h1>
                <hr class="filete">
                <h2 class="titulo-seccion">Editar Usuario</h2>
            </div>
            <div class="tarjeta__cuerpo">
                <form action="proceso_Actualizar_Usuario.php" method="POST" class="formulario">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                    <div class="campo">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?= htmlspecialchars($usuario['nombre']) ?>" required class="entrada"><br><br>
                    </div>

                    <div class="campo">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido"
                               value="<?= htmlspecialchars($usuario['apellido']) ?>" required class="entrada"><br><br>
                    </div>

                    <div class="campo">
                        <label for="telefono">Telefono:</label>
                        <input type="text" id="telefono" name="telefono"
                               value="<?= htmlspecialchars($usuario['telefono']) ?>" required class="entrada"><br><br>
                    </div>

                    <div class="campo">
                        <label for="correo">Correo:</label>
                        <input type="email" id="correo" name="correo"
                               value="<?= htmlspecialchars($usuario['correo']) ?>" required class="entrada"><br><br>
                    </div>

                    <div class="campo">
                        <label for="rol">Rol("usuario" o "administrador"):</label>
                        <select name="rol" id="rol" class="entrada">
                            <option value="usuario" <?=$usuario['rol'] === 'usuario' ? 'selected': '' ?>>Usuario</option>
                            <option value="administrador" <?=$usuario['rol'] === 'administrador' ? 'selected': '' ?>>Administrador</option>
                        </select>
                    </div>

                    <button type="submit" class="boton boton--primario">Actualizar Usuario</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>