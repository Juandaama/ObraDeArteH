<?php

require_once("../../DB/conexion.php");

if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die("No se especificó la publicación.");
}

$id_imagen = $_GET['id'];
$error = "";

// Obtener la publicación actual
$sql = "SELECT id_imagen, descripcion, nombre, precio, categoria, ruta_imagen
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


// Actualizar publicación
if (isset($_POST['actualizar'])) {

    $nuevoNombre = $_POST['nombre'];
    $nuevoPrecio = $_POST['precio'];
    $nuevaDescripcion = $_POST['descripcion'];
    $nuevaCategoria = $_POST['categoria'];


    // Comprobar si se seleccionó una nueva imagen
    if (!empty($_FILES['imagen']['name'])) {

        $nombreNuevaImagen = $_FILES['imagen']['name'];

        $rutaTemporal = $_FILES['imagen']['tmp_name'];

        // Nueva ubicación de la imagen
        $rutaNuevaImagen = "../../img/catalogo/" . $nuevaCategoria . "/" . $nombreNuevaImagen;


        if (move_uploaded_file($rutaTemporal, $rutaNuevaImagen)) {

            // Eliminar imagen anterior
            $rutaImagenAnterior = "../../" . $publicacion['ruta_imagen'];

            if (file_exists($rutaImagenAnterior)) {
                unlink($rutaImagenAnterior);
            }


            // Nueva ruta que se guardará en la base de datos
            $nuevaRutaImagen = "img/catalogo/" . $nuevaCategoria . "/" . $nombreNuevaImagen;


            $sql = "UPDATE catalogo
                    SET descripcion = :descripcion,
                        nombre = :nombre,
                        precio = :precio,
                        categoria = :categoria,
                        ruta_imagen = :ruta_imagen
                    WHERE id_imagen = :id_imagen";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([

                ':descripcion' => $nuevaDescripcion,
                ':nombre' => $nuevoNombre,
                ':precio' => $nuevoPrecio,
                ':categoria' => $nuevaCategoria,
                ':ruta_imagen' => $nuevaRutaImagen,
                ':id_imagen' => $id_imagen
            ]);


            header("Location: Rpubli.php");
            exit;


        } else {

            $error = "Error al guardar la nueva imagen.";

        }


    } else {

        /*
         * Si no se seleccionó una nueva imagen, comprobamos
         * si la categoría cambió.
         */

        if ($nuevaCategoria != $publicacion['categoria']) {

            // Obtener nombre del archivo actual
            $nombreArchivoActual = basename($publicacion['ruta_imagen']);

            // Ruta física actual
            $rutaImagenAnterior = "../../" . $publicacion['ruta_imagen'];

            // Nueva ruta física
            $rutaNuevaImagen = "../../img/catalogo/" . $nuevaCategoria . "/" . $nombreArchivoActual;


            // Mover la imagen a la nueva categoría
            if (file_exists($rutaImagenAnterior)) {

                if (rename($rutaImagenAnterior, $rutaNuevaImagen)) {

                    $nuevaRutaImagen = "img/catalogo/" . $nuevaCategoria . "/" . $nombreArchivoActual;

                } else {

                    $error = "No se pudo mover la imagen a la nueva categoría.";

                }

            } else {

                $error = "No se encontró la imagen actual.";

            }


        } else {

            // La categoría no cambió
            $nuevaRutaImagen = $publicacion['ruta_imagen'];

        }


        // Si no hubo errores, actualizar los datos
        if (!$error) {

            $sql = "UPDATE catalogo
                    SET descripcion = :descripcion,
                        nombre = :nombre,
                        precio = :precio,
                        categoria = :categoria,
                        ruta_imagen = :ruta_imagen
                    WHERE id_imagen = :id_imagen";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':descripcion' => $nuevaDescripcion,
                ':nombre' => $nuevoNombre,
                ':precio' => $nuevoPrecio,
                ':categoria' => $nuevaCategoria,
                ':ruta_imagen' => $nuevaRutaImagen,
                ':id_imagen' => $id_imagen
            ]);


            header("Location: Rpubli.php");
            exit;

        }

    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar publicación</title>

    <link rel="stylesheet" href="../../assets/estilos.css">

</head>

<body>

    <div class="pagina">

        <div class="tarjeta tarjeta--marco">

            <a href="Rpubli.php" class="enlace-volver">
                Volver
            </a>


            <div class="tarjeta__encabezado">

                <h1 class="marca">
                    Obra de Arte
                </h1>

                <hr class="filete">

                <h2 class="titulo-seccion">
                    Editar publicación
                </h2>

            </div>


            <div class="tarjeta__cuerpo">

                <?php if ($error): ?>

                    <div class="alerta alerta--error">

                        <p>
                            <?= htmlspecialchars($error) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <div class="campo" style="text-align: center; margin-bottom: 20px;">

                    <label>
                        Imagen actual:
                    </label>

                    <div style="margin-top: 8px;">

                        <img
                            src="../../<?= htmlspecialchars($publicacion['ruta_imagen']); ?>"
                            alt="Imagen actual"
                            style="max-width: 100%; max-height: 200px; object-fit: contain; border: 1px solid var(--color-borde); display: inline-block;"
                        >

                    </div>

                </div>


                <form
                    action=""
                    method="POST"
                    enctype="multipart/form-data"
                    class="formulario"
                >


                    <div class="campo">

                        <label for="nombre">
                            Nombre:
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="<?= htmlspecialchars($publicacion['nombre']); ?>"
                            required
                            class="entrada"
                        >

                    </div>


                    <div class="campo">

                        <label for="precio">
                            Precio:
                        </label>

                        <input
                            type="text"
                            id="precio"
                            name="precio"
                            value="<?= htmlspecialchars($publicacion['precio']); ?>"
                            required
                            class="entrada"
                        >

                    </div>


                    <div class="campo">

                        <label for="categoria">
                            Categoría:
                        </label>

                        <select
                            id="categoria"
                            name="categoria"
                            required
                            class="entrada"
                        >

                            <option value="cocina"
                                <?= $publicacion['categoria'] == 'cocina' ? 'selected' : '' ?>>
                                Cocina
                            </option>

                            <option value="habitacion"
                                <?= $publicacion['categoria'] == 'habitacion' ? 'selected' : '' ?>>
                                Habitación
                            </option>

                            <option value="patio"
                                <?= $publicacion['categoria'] == 'patio' ? 'selected' : '' ?>>
                                Patio
                            </option>

                            <option value="sala"
                                <?= $publicacion['categoria'] == 'sala' ? 'selected' : '' ?>>
                                Sala
                            </option>

                            <option value="baño"
                                <?= $publicacion['categoria'] == 'baño' ? 'selected' : '' ?>>
                                Baño
                            </option>

                        </select>

                    </div>


                    <div class="campo">

                        <label for="imagen">
                            Nueva imagen (opcional):
                        </label>

                        <input
                            type="file"
                            id="imagen"
                            name="imagen"
                            class="entrada"
                        >

                    </div>


                    <div class="campo">

                        <label for="descripcion">
                            Descripción:
                        </label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="5"
                            required
                            class="entrada"
                        ><?= htmlspecialchars($publicacion['descripcion']); ?></textarea>

                    </div>


                    <input
                        type="submit"
                        class="boton boton--primario"
                        name="actualizar"
                        value="Guardar cambios"
                    >

                </form>

            </div>

        </div>

    </div>

</body>

</html>