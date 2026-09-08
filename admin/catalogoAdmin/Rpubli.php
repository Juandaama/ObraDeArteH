<?php

require_once("../../DB/conexion.php");

$publicaciones = $pdo->query(
    "SELECT id_imagen, descripcion, nombre, precio, categoria, ruta_imagen
     FROM catalogo
     ORDER BY id_imagen DESC"
)->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catálogo - Administrador</title>

    <link rel="stylesheet" href="../../assets/estilos.css">
</head>

<body>

    <div class="contenido-admin">

        <a href="../panel.php" class="enlace-volver">Volver</a>

        <div class="contenido-admin__encabezado">

            <h1 class="contenido-admin__titulo">
                Catálogo de Publicaciones
            </h1>

            <p class="contenido-admin__descripcion">
                Gestiona las obras y publicaciones del sistema
            </p>

        </div>


        <div style="whith: 20%;">

            <div style="margin-bottom: 24px;">

                <a href="Cpubli.php"
                   class="boton boton--primario boton--inline">
                    Crear publicación
                </a>

            </div>


            <div class="tabla-contenedor">

                <table class="tabla">

                    <thead>

                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($publicaciones)): ?>

                            <tr>

                                <td colspan="6"
                                    style="text-align: center; padding: 24px; color: var(--color-texto-secundario);">

                                    No hay publicaciones en el catálogo.

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($publicaciones as $publicacion): ?>

                                <tr>

                                    <td>

                                        <img
                                            src="../../<?= htmlspecialchars($publicacion['ruta_imagen']) ?>"
                                            alt="Publicación"
                                            style="max-width: 100px; max-height: 100px; object-fit: cover; border: 1px solid var(--color-borde); display: block;"
                                        >

                                    </td>


                                    <td style="white-space: normal; max-width: 180px;">

                                        <?= htmlspecialchars($publicacion['nombre']) ?>

                                    </td>


                                    <td style="white-space: normal; max-width: 150px;">

                                        <?= htmlspecialchars($publicacion['precio']) ?>

                                    </td>


                                    <td style="white-space: normal; max-width: 120px;">

                                        <?= htmlspecialchars($publicacion['categoria']) ?>

                                    </td>


                                    <td style="white-space: normal; max-width: 320px;">

                                        <?= htmlspecialchars($publicacion['descripcion']) ?>

                                    </td>


                                    <td>

                                        <div class="acciones-fila">

                                            <a
                                                href="Upubli.php?id=<?= $publicacion['id_imagen'] ?>"
                                                class="boton boton--secundario boton--pequeno"
                                            >
                                                Editar
                                            </a>


                                            <form
                                                action="Dpubli.php"
                                                method="POST"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id_imagen"
                                                    value="<?= $publicacion['id_imagen'] ?>"
                                                >

                                                <input
                                                    type="submit"
                                                    name="eliminar"
                                                    class="boton boton--peligro boton--pequeno"
                                                    value="Eliminar"
                                                >

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</body>

</html>