

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración del Catálogo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>

    <?php 
            include 'menuAdmin.php';
     ?>

    <h1>Administración del Catálogo</h1>

    <form action="" method="POST" enctype="multipart/form-data">

        <label>Seleccione una imagen:</label><br>
        <input type="file" name="imagen" required><br><br>

        <label>Descripción:</label><br>
        <textarea name="descripcion" rows="5" cols="50" required></textarea><br><br>

        <input type="submit" name="guardar" value="Guardar publicación">

    </form>

    <?php

        require_once("../DB/conexion.php");

        if (isset($_POST['guardar'])) {

            // Nombre de la imagen
            $nombreImagen = $_FILES['imagen']['name'];

            // Ruta temporal de la imagen
            $rutaTemporal = $_FILES['imagen']['tmp_name'];

            // Carpeta donde se guardará
            $rutaDestino = "../img/catalogo/" . $nombreImagen;

            // Mover la imagen a la carpeta del proyecto
            if (move_uploaded_file($rutaTemporal, $rutaDestino)) {

                // Obtener la descripción
                $descripcion = $_POST['descripcion'];

                // Insertar en la base de datos
                $sql = "INSERT INTO catalogo (nombre_imagen, descripcion)
                        VALUES (:nombre_imagen, :descripcion)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':nombre_imagen' => $nombreImagen,
                    ':descripcion' => $descripcion
                ]);

                echo "<p>Imagen y descripción guardadas correctamente.</p>";

            } else {

                echo "<p>Error al guardar la imagen.</p>";

            }
        }

    ?>

</body>
</html>