<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <h1>ObraDeArteH</h1>
        <hr>
        <h2>Inicio de sesión</h2>
        <form action="Login/InisioSesion.php" method="post">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre"><br><br>

            <label for="contrasena">contraseña:</label>
            <input type="password" id="contrasena" name="contrasena"><br><br>

            <input type="submit" value="Inisiarsesion" class="transicion"><br>
        </form>
        <hr>
        <a href="Login/procesoCrearUsuario.php"><button>Crear cuenta</button></a><br>
        <br>
        <br>
        <footer>
        <p class="blanco">Contactos</p>
    </footer>
    </div>
</body>
</html>