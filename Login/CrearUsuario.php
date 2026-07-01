<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div>
        <form action="procesoCrearUsuario.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required><br><br>

        <label for="apellido">Apellido:</label>
        <input type="text" id="apellido" name="apellido" required><br><br>

        <label for="telefono">Telefono:</label>
        <input type="numerico" id="telefono" name="telefono" required><br><br>

        <label for="correo"> Correo:</label>
        <input type="email" id="correo" name="correo" required><br><br>

        <label for="contrasena">contraseña:</label>
        <input type="password" id="contrasena" name="contrasena" required><br><br>

        <button type="submit">Crear Usuario</button>
        </form>
    </div>
</body>
</html>