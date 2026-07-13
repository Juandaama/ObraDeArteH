<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión</title>
    <link rel="stylesheet" href="assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco">
            <div class="tarjeta__encabezado">
                <h1 class="marca">ObraDeArteH</h1>
                <hr class="filete">
                <h2 class="titulo-seccion">Inicio de sesión</h2>
            </div>
            <div class="tarjeta__cuerpo">
                <form action="Login/InisioSesion.php" method="post" class="formulario">
                    <div class="campo">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="entrada"><br><br>
                    </div>

                    <div class="campo">
                        <label for="contrasena">contraseña:</label>
                        <input type="password" id="contrasena" name="contrasena" class="entrada"><br><br>
                    </div>

                    <input type="submit" value="Inisiarsesion" class="boton boton--primario"><br>
                </form>
                <hr class="filete filete--secundario">
                <div class="acciones-secundarias">
                    <a href="Login/procesoCrearUsuario.php"><button class="boton boton--secundario">Crear cuenta</button></a><br>
                </div>
                <br>
                <br>
                <div class="tarjeta__pie">
                    <footer class="pie">
                        <p class="pie__texto">Contactos</p>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</body>
</html>