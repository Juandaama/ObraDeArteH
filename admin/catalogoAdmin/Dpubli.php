<?php

require_once("../../DB/conexion.php");

if (!isset($_POST['id_imagen'])) {
    die("No se especificó la publicación que se desea eliminar.");
}

$id_imagen = $_POST['id_imagen'];

$sql = "SELECT nombre_imagen
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

$rutaImagen = "../../img/catalogo/" . $publicacion['nombre_imagen'];

if (file_exists($rutaImagen)) {
    unlink($rutaImagen);
}

$sql = "DELETE FROM catalogo
        WHERE id_imagen = :id_imagen";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id_imagen' => $id_imagen
]);

header("Location: Rpubli.php");
exit;

?>