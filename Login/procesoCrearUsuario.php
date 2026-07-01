<?php

include("../DB/conexion.php");

if($_POST) {
  $sql = "INSERT INTO usuarios
  (nombre, apellido, telefono, correo, contrasena)
  VALUES (?,?,?,?,?)";
  $stmt = $conexion->prepare($sql);
  $stmt->execute([$_POST['nombre'],
                  $_POST['apellido'],
                  $_POST['telefono'],
                  $_POST['correo'],
                  $_POST['contrasena']]);

    header("Location: ../index.php");
}
