<<?php

include("../../DB/conexion.php");

if (!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    die("ID de usuario no válido.");
}

$id = $_POST['id'];

$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header("Location: administracion_Usuarios.php");
exit();

?>