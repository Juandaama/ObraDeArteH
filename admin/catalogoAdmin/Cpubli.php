<?php

require_once("../../DB/conexion.php");

if (isset($_POST['guardar'])) {

    $nombreImagen = $_FILES['imagen']['name'];

    $rutaTemporal = $_FILES['imagen']['tmp_name'];

    $rutaDestino = "../../img/catalogo/" . $nombreImagen;

    if (move_uploaded_file($rutaTemporal, $rutaDestino)) {

        $descripcion = $_POST['descripcion'];

        $sql = "INSERT INTO catalogo (nombre_imagen, descripcion)
                VALUES (:nombre_imagen, :descripcion)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nombre_imagen' => $nombreImagen,
            ':descripcion' => $descripcion
        ]);

        echo "Publicación creada correctamente.";

    } else {

        echo "Error al guardar la imagen.";

        header("Location: Rpubli.php");
        exit;

    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear publicación</title>
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>

<body>

    <h1>Crear publicación</h1>

    <form action="" method="POST" enctype="multipart/form-data">

        <label>Seleccione una imagen:</label><br>

        <input type="file" name="imagen" required>

        <br><br>

        <label>Descripción:</label><br>

        <textarea name="descripcion" rows="5" cols="50" required></textarea>

        <br><br>

        <input type="submit" name="guardar" value="Guardar publicación">

    </form>

</body>
</html>