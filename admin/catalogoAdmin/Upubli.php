<?php

require_once("../../DB/conexion.php");

if (!isset($_GET['id'])) {
    die("No se especificó la publicación.");
}

$id_imagen = $_GET['id'];

$sql = "SELECT id_imagen, nombre_imagen, descripcion
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

if (isset($_POST['actualizar'])) {

    $nuevaDescripcion = $_POST['descripcion'];

    if (!empty($_FILES['imagen']['name'])) {

        $nombreNuevaImagen = $_FILES['imagen']['name'];

        $rutaTemporal = $_FILES['imagen']['tmp_name'];

        $rutaNuevaImagen = "../../img/catalogo/" . $nombreNuevaImagen;

        if (move_uploaded_file($rutaTemporal, $rutaNuevaImagen)) {

            $rutaImagenAnterior = "../../img/catalogo/" . $publicacion['nombre_imagen'];

            if (file_exists($rutaImagenAnterior)) {
                unlink($rutaImagenAnterior);
            }

            $sql = "UPDATE catalogo
                    SET nombre_imagen = :nombre_imagen,
                        descripcion = :descripcion
                    WHERE id_imagen = :id_imagen";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':nombre_imagen' => $nombreNuevaImagen,
                ':descripcion' => $nuevaDescripcion,
                ':id_imagen' => $id_imagen
            ]);

            header("Location: Upubli.php?id=" . $id_imagen);
            exit;


        } else {

            echo "Error al guardar la nueva imagen.";

        }

    } else {

        $sql = "UPDATE catalogo
                SET descripcion = :descripcion
                WHERE id_imagen = :id_imagen";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':descripcion' => $nuevaDescripcion,
            ':id_imagen' => $id_imagen
        ]);

        header("Location: Rpubli.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar publicación</title>
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>

<body>

    <h1>Editar publicación</h1>


    <p>Imagen actual:</p>

    <img
        src="../../img/catalogo/<?php echo htmlspecialchars($publicacion['nombre_imagen']); ?>"
        width="300"
    >


    <p>
        <strong>Descripción actual:</strong>
    </p>

    <p>
        <?php echo htmlspecialchars($publicacion['descripcion']); ?>
    </p>


    <hr>


    <form action="" method="POST" enctype="multipart/form-data">

        <label>Nueva imagen (opcional):</label><br>

        <input type="file" name="imagen">

        <br><br>


        <label>Nueva descripción:</label><br>

        <textarea
            name="descripcion"
            rows="5"
            cols="50"
            required
        ><?php echo htmlspecialchars($publicacion['descripcion']); ?></textarea>

        <br><br>


        <input
            type="submit"
            name="actualizar"
            value="Guardar cambios"
        >

    </form>

</body>

</html>