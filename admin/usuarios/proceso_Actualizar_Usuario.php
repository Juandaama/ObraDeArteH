<?php
include("../../DB/conexion.php");

if (!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    die("ID de usuario no válido.");
}

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$telefono = $_POST['telefono'];
$correo = $_POST['correo'];
$rol = $_POST['rol'];

$sql = "UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?,correo = ?,rol = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nombre, $apellido, $telefono, $correo, $rol, $id]);

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuario actualizado</title>
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>
<body>
    <div class="mensaje-confirmacion">
        <p class="mensaje-confirmacion__texto">Usuario actualizado correctamente.</p>
        <a href="administracion_Usuarios.php" class="boton boton--secundario boton--pequeno">Volver</a>
    </div>
</body>
</html>';
exit();
?>