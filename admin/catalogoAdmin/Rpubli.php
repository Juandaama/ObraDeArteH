<?php

require_once("../../DB/conexion.php");

$publicaciones = $pdo->query(
    "SELECT id_imagen, nombre_imagen, descripcion
     FROM catalogo
     ORDER BY id_imagen DESC"
)->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Catálogo - Administrador</title>
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>

<body>
    <BUtton><a href="../panel.php">volver</a></BUtton>

    <h1>Catálogo de publicaciones</h1>

    <a href="Cpubli.php">Crear publicación</a>

    <hr>

    <?php if (empty($publicaciones)): ?>

        <p>No hay publicaciones en el catálogo.</p>

    <?php else: ?>

        <?php foreach ($publicaciones as $publicacion): ?>

            <div>

                <img
                    src="../../img/catalogo/<?php echo htmlspecialchars($publicacion['nombre_imagen']); ?>"
                    width="300"
                >

                <p>
                    <strong>Descripción:</strong>
                </p>

                <p>
                    <?php echo htmlspecialchars($publicacion['descripcion']); ?>
                </p>

                <a href="Upubli.php?id=<?= $publicacion['id_imagen'] ?>">
                    Editar
                    <form action="Dpubli.php" method="POST">
                        <input type="hidden" name="id_imagen" value="<?= $publicacion['id_imagen'] ?>">
                        <input type="submit" name="eliminar" value="Eliminar">
                    </form>
                </a>

                <hr>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</body>

</html>