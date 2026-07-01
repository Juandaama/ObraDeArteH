<?php

session_start();

include ('../DB/conexion.php');

if (isset($_POST['nombre']) && isset($_POST['contrasena'])){
    function validate($date){
        $date = trim ($date);
        $date = stripcslashes ($date);
        $date = htmlspecialchars ($date);
        return $date;
    }
}
$nombre = validate($_POST['nombre']);
$contrasena = validate($_POST['contrasena']);

if(empty($nombre)){
    header("Location: ../index.php?error=el nombre es requerido");
    exit();
}else if(empty($contrasena)){
    header("Location: ../index.php?error=la contraseña es requerida");
    exit();
}else {

    $sql = "SELET * FROM usuarios WHERE nombre == '$nombre' AND contrasena == '$contrasena'";
    $result = mysqli_query($conexion , $sql);

    if (mysqli_num_rows($result)=== 1){
        $row = mysqli_fetch_assoc($result);
        if($row['nombre'] === $nombre &&  $row['contrasena'] === $contrasena){
            $_SESSION['id'] = $row['id'];
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['apellido'] = $row['apellido'];
            $_SESSION['telefono'] = $row['telefono'];
            $_SESSION['correo'] = $row['correo'];
            $_SESSION['rol'] = $row['rol'];
            header("Location: ../lobby/catalogo.php");   
        }else{
        header("Location: ../index.php?error=el nombre o la contraseña es incorrecto");
        exit();
        }
    }
}

?>