<?php

require_once("../../DB/conexion.php");

$mensaje = "";
$tipoMensaje = "";

if (isset($_POST['guardar'])) {

    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];
    $categoria = $_POST['categoria'];

    $nombreImagen = $_FILES['imagen']['name'];

    $rutaTemporal = $_FILES['imagen']['tmp_name'];

    // Carpeta correspondiente a la categoría
    $rutaDestino = "../../img/catalogo/" . $categoria . "/" . $nombreImagen;

    if (move_uploaded_file($rutaTemporal, $rutaDestino)) {

        // Ruta que se guardará en la base de datos
        $rutaImagen = "img/catalogo/" . $categoria . "/" . $nombreImagen;

        $sql = "INSERT INTO catalogo (descripcion, nombre, precio, categoria, ruta_imagen)
                VALUES (:descripcion, :nombre, :precio, :categoria, :ruta_imagen)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':descripcion' => $descripcion,
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':categoria' => $categoria,
            ':ruta_imagen' => $rutaImagen
        ]);

        $mensaje = "Publicación creada correctamente.";
        $tipoMensaje = "exito";

    } else {

        $mensaje = "Error al guardar la imagen.";
        $tipoMensaje = "error";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear publicación</title>

    <link rel="stylesheet" href="../../assets/estilos.css">
</head>

<body>

    <div class="pagina">

        <div class="tarjeta tarjeta--marco">

            <a href="Rpubli.php" class="enlace-volver">Volver</a>

            <div class="tarjeta__encabezado">

                <h1 class="marca">Obra de Arte</h1>

                <hr class="filete">

                <h2 class="titulo-seccion">Crear publicación</h2>

            </div>

            <div class="tarjeta__cuerpo">

                <?php if ($mensaje): ?>

                    <div class="alerta alerta--<?= $tipoMensaje ?>">
                        <p><?= htmlspecialchars($mensaje) ?></p>
                    </div>

                <?php endif; ?>


                <form action="" method="POST" enctype="multipart/form-data" class="formulario">

                    <div class="campo">

                        <label for="nombre">Nombre:</label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            required
                            class="entrada"
                        >

                    </div>


                    <div class="campo">

                        <label for="precio">Precio:</label>

                        <input
                            type="text"
                            id="precio"
                            name="precio"
                            required
                            class="entrada"
                        >

                    </div>


                    <div class="campo">

                        <label for="categoria">Categoría:</label>

                        <select
                            id="categoria"
                            name="categoria"
                            required
                            class="entrada"
                        >

                            <option value="">Seleccione una categoría</option>

                            <option value="cocina">Cocina</option>
                            <option value="habitacion">Habitación</option>
                            <option value="patio">Patio</option>
                            <option value="sala">Sala</option>
                            <option value="baño">Baño</option>

                        </select>

                    </div>


                    <div class="campo">

                        <label for="imagen">Seleccione una imagen:</label>

                        <input
                            type="file"
                            id="imagen"
                            name="imagen"
                            required
                            class="entrada"
                        >

                    </div>


                    <div class="campo">

                        <label for="descripcion">Descripción:</label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="5"
                            required
                            class="entrada"
                        ></textarea>

                    </div>


                    <input
                        type="submit"
                        name="guardar"
                        value="Guardar publicación"
                        class="boton boton--primario"
                    >

                </form>

            </div>

        </div>

    </div>

</body>

</html>