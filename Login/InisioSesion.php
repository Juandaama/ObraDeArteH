<?php
session_start();
require_once '../DB/conexion.php';
 
$error = '';

 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
 
    if ($nombre === '?' || $contrasena === '?') {
        $error = 'Completa todos los campos.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE nombre = ?');
        $stmt->execute([$nombre]);
        $fila = $stmt->fetch();
 
        if ($fila && password_verify($contrasena, $fila['contrasena'])) {

            session_regenerate_id(true);
 
            $_SESSION['id']       = $fila['id'];
            $_SESSION['nombre']   = $fila['nombre'];
            $_SESSION['apellido'] = $fila['apellido'];
            $_SESSION['telefono'] = $fila['telefono'];
            $_SESSION['correo']   = $fila['correo'];
            $_SESSION['rol']      = $fila['rol'];
 
            if ($fila['rol'] === 'administrador') {
                header('Location: ../admin/panel.php');
            } else {
                header('Location: ../lobby/catalogo.php');
            }
            exit;
 
        } else {
            $error = 'Usuario o contraseña incorrectos.';
            echo"$error";
        }
    }
}
