<?php

require_once("../../DB/conexion.php");

if (!isset($_POST['id_imagen'])) {
    die("No se especificó la publicación que se desea eliminar.");
}

$id_imagen = $_POST['id_imagen'];


// Buscar la ruta de la imagen
$sql = "SELECT ruta_imagen
        FROM catalogo
        WHERE id_imagen = :id_imagen";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id_imagen' => $id_imagen
]);

$publicacion = $stmt->fetch();

if (!$publicacion) {
    die("La publicación no existe.");
}


// Ruta física de la imagen
$rutaImagen = "../../" . $publicacion['ruta_imagen'];


// Eliminar la imagen del servidor
if (file_exists($rutaImagen)) {
    unlink($rutaImagen);
}


// Eliminar la publicación de la base de datos
$sql = "DELETE FROM catalogo
        WHERE id_imagen = :id_imagen";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id_imagen' => $id_imagen
]);


header("Location: Rpubli.php");
exit;

?>