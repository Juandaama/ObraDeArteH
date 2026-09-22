<?php
session_start();
require_once '../DB/conexion.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cotizaciones` (
          `id_cotizacion` int(11) NOT NULL AUTO_INCREMENT,
          `id_usuario` int(11) DEFAULT NULL,
          `nombre_cliente` varchar(200) NOT NULL,
          `correo_cliente` varchar(200) NOT NULL,
          `telefono_cliente` varchar(50) NOT NULL,
          `categoria` varchar(100) NOT NULL,
          `id_imagen_ref` int(11) DEFAULT NULL,
          `nombre_proyecto_ref` varchar(250) DEFAULT NULL,
          `presupuesto_estimado` varchar(100) DEFAULT NULL,
          `detalles` text NOT NULL,
          `estado` enum('Pendiente','En revisión','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
          `respuesta_admin` text DEFAULT NULL,
          `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_cotizacion`),
          KEY `idx_usuario` (`id_usuario`),
          KEY `idx_estado` (`estado`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
} catch (Exception $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreCliente    = trim($_POST['nombre_cliente'] ?? '');
    $correoCliente    = trim($_POST['correo_cliente'] ?? '');
    $telefonoCliente  = trim($_POST['telefono_cliente'] ?? '');
    $categoria        = trim($_POST['categoria'] ?? '');
    $idImagenRef      = !empty($_POST['id_imagen_ref']) ? (int)$_POST['id_imagen_ref'] : null;
    $nombreProyecto   = trim($_POST['nombre_proyecto_ref'] ?? '');
    $presupuesto      = trim($_POST['presupuesto_estimado'] ?? '');
    $detalles         = trim($_POST['detalles'] ?? '');
    $idUsuario        = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;

    if (empty($nombreCliente) || empty($correoCliente) || empty($telefonoCliente) || empty($categoria) || empty($detalles)) {
        header('Location: catalogo.php?msg=campos_requeridos');
        exit;
    }

    if (!filter_var($correoCliente, FILTER_VALIDATE_EMAIL)) {
        header('Location: catalogo.php?msg=correo_invalido');
        exit;
    }

    if (!preg_match('/^[0-9+() -]{7,25}$/', $telefonoCliente)) {
        header('Location: catalogo.php?msg=telefono_invalido');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO cotizaciones 
            (id_usuario, nombre_cliente, correo_cliente, telefono_cliente, categoria, id_imagen_ref, nombre_proyecto_ref, presupuesto_estimado, detalles, estado)
            VALUES 
            (:id_usuario, :nombre_cliente, :correo_cliente, :telefono_cliente, :categoria, :id_imagen_ref, :nombre_proyecto_ref, :presupuesto_estimado, :detalles, 'Pendiente')
        ");

        $stmt->execute([
            ':id_usuario'           => $idUsuario,
            ':nombre_cliente'       => $nombreCliente,
            ':correo_cliente'       => $correoCliente,
            ':telefono_cliente'     => $telefonoCliente,
            ':categoria'            => $categoria,
            ':id_imagen_ref'        => $idImagenRef,
            ':nombre_proyecto_ref'  => $nombreProyecto,
            ':presupuesto_estimado' => $presupuesto,
            ':detalles'             => $detalles
        ]);

        header('Location: catalogo.php?msg=cotizacion_exitosa');
        exit;

    } catch (PDOException $e) {
        header('Location: catalogo.php?msg=error_servidor');
        exit;
    }
}

header('Location: catalogo.php');
exit;

