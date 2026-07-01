<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div>
        <h1>ObraDeArteH</h1>
        <hr>
        <?php
            if (isset($_GET['error'])){
            ?>
            <p class="error">
                <?php
                    echo $_GET['error']
                ?>
            </p>
        <?php
           }
        ?>
        <hr>
        <h2>Inicio de sesión</h2>
        <form action="Login/InisioSesion.php" method="post">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre"><br><br>

            <label for="contrasena">contraseña:</label>
            <input type="password" id="contrasena" name="contrasena"><br><br>

            <input type="submit" value="Inisiarsesion" class="transicion"><br>
        </form>
        <a href="Login/CrearUsuario.php"><button>Crear cuenta</button></a><br>
        <br>
        <br>
        <footer>
        <p class="blanco">Contactos</p>
    </footer>
    </div>
</body>
</html>