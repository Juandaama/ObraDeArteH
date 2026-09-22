<?php
session_start();
require_once '../DB/conexion.php';

// Verificar que el usuario tenga rol de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../index.php');
    exit;
}

// Asegurar existencia de la tabla cotizaciones
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
    // Continuar si ya existe
}

// Obtener estadísticas generales para el panel
$totalPubs = 0;
$totalUsuarios = 0;
$totalCotizaciones = 0;
$cotizacionesPendientes = 0;
$cotizacionesRecientes = [];

try {
    $totalPubs = (int)$pdo->query("SELECT COUNT(*) FROM catalogo")->fetchColumn();
    $totalUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $totalCotizaciones = (int)$pdo->query("SELECT COUNT(*) FROM cotizaciones")->fetchColumn();
    $cotizacionesPendientes = (int)$pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'Pendiente'")->fetchColumn();

    $stmtRecientes = $pdo->query("
        SELECT id_cotizacion, nombre_cliente, correo_cliente, telefono_cliente, categoria, nombre_proyecto_ref, estado, fecha_creacion
        FROM cotizaciones 
        ORDER BY id_cotizacion DESC 
        LIMIT 5
    ");
    $cotizacionesRecientes = $stmtRecientes ? $stmtRecientes->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    // Manejar fallas de consulta
}

$nombreAdmin = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/estilos.css">
    <link rel="stylesheet" href="../assets/estilos_adicionales.css">
</head>
<body>
    <?php include 'menuAdmin.php'; ?>

    <div class="contenido-admin">
        <div class="contenido-admin__encabezado">
            <span class="info-destacada__lema">Panel de Control</span>
            <h1 class="contenido-admin__titulo">Bienvenido, <?= $nombreAdmin ?></h1>
            <p class="contenido-admin__descripcion">
                Centro de gestión y modificaciones del sistema ObraDeArteH. Supervisa proyectos, cuentas de usuarios y cotizaciones.
            </p>
        </div>

        <!-- Tarjetas de Métricas Generales -->
        <div class="metricas-grid">
            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Proyectos en Catálogo</span>
                    <span class="metrica-tarjeta__icono">&#128444;</span>
                </div>
                <div class="metrica-tarjeta__valor"><?= $totalPubs ?></div>
            </div>

            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Usuarios Registrados</span>
                    <span class="metrica-tarjeta__icono">&#128101;</span>
                </div>
                <div class="metrica-tarjeta__valor"><?= $totalUsuarios ?></div>
            </div>

            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Total Cotizaciones</span>
                    <span class="metrica-tarjeta__icono">&#128221;</span>
                </div>
                <div class="metrica-tarjeta__valor"><?= $totalCotizaciones ?></div>
            </div>

            <div class="metrica-tarjeta">
                <div class="metrica-tarjeta__encabezado">
                    <span class="metrica-tarjeta__etiqueta">Cotizaciones Pendientes</span>
                    <span class="metrica-tarjeta__icono">&#9203;</span>
                </div>
                <div class="metrica-tarjeta__valor" style="color: var(--color-acento);"><?= $cotizacionesPendientes ?></div>
            </div>
        </div>

        <!-- Módulos de Acceso Rápido -->
        <h2 class="titulo-seccion" style="margin-bottom: 16px;">Módulos del Sistema</h2>
        <div class="modulos-admin-grid">
            <a href="catalogoAdmin/Rpubli.php" class="modulo-admin-tarjeta">
                <div>
                    <h3 class="modulo-admin-tarjeta__titulo">Catálogo</h3>
                    <p class="modulo-admin-tarjeta__desc">Publica, edita o elimina proyectos arquitectónicos y fotografías de obras.</p>
                </div>
                <span class="modulo-admin-tarjeta__enlace">Administrar &rarr;</span>
            </a>

            <a href="usuarios/administracion_Usuarios.php" class="modulo-admin-tarjeta">
                <div>
                    <h3 class="modulo-admin-tarjeta__titulo">Usuarios</h3>
                    <p class="modulo-admin-tarjeta__desc">Gestiona las cuentas de usuarios registrados, roles y permisos de acceso.</p>
                </div>
                <span class="modulo-admin-tarjeta__enlace">Administrar &rarr;</span>
            </a>

            <a href="cotizaciones/administracion_Cotizaciones.php" class="modulo-admin-tarjeta">
                <div>
                    <h3 class="modulo-admin-tarjeta__titulo">Cotizaciones</h3>
                    <p class="modulo-admin-tarjeta__desc">Revisa solicitudes de clientes, evalúa presupuestos y asigna estados.</p>
                </div>
                <span class="modulo-admin-tarjeta__enlace">Administrar &rarr;</span>
            </a>

            <a href="usuarios/perfilAdmin.php" class="modulo-admin-tarjeta">
                <div>
                    <h3 class="modulo-admin-tarjeta__titulo">Mi Perfil</h3>
                    <p class="modulo-admin-tarjeta__desc">Actualiza tu información personal, datos de contacto y fotografía de perfil.</p>
                </div>
                <span class="modulo-admin-tarjeta__enlace">Ver Perfil &rarr;</span>
            </a>
        </div>

        <!-- Tabla de Cotizaciones Recientes -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 class="titulo-seccion">Últimas Cotizaciones Recibidas</h2>
            <a href="cotizaciones/administracion_Cotizaciones.php" class="boton boton--secundario boton--pequeno">
                Ver Todas
            </a>
        </div>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Área</th>
                        <th>Ref. Proyecto</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cotizacionesRecientes)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 28px; color: var(--color-texto-secundario);">
                                No hay solicitudes de cotización recientes.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cotizacionesRecientes as $cot): ?>
                            <?php
                                $claseEstado = 'insignia--pendiente';
                                if ($cot['estado'] === 'En revisión') $claseEstado = 'insignia--revision';
                                elseif ($cot['estado'] === 'Aprobada') $claseEstado = 'insignia--aprobada';
                                elseif ($cot['estado'] === 'Rechazada') $claseEstado = 'insignia--rechazada';
                            ?>
                            <tr>
                                <td><strong>#<?= $cot['id_cotizacion'] ?></strong></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cot['fecha_creacion']))) ?></td>
                                <td><?= htmlspecialchars($cot['nombre_cliente']) ?></td>
                                <td style="font-size: 0.8rem;">
                                    <div><?= htmlspecialchars($cot['telefono_cliente']) ?></div>
                                    <div style="color: var(--color-texto-secundario);"><?= htmlspecialchars($cot['correo_cliente']) ?></div>
                                </td>
                                <td><span class="insignia"><?= htmlspecialchars(ucfirst($cot['categoria'])) ?></span></td>
                                <td><?= htmlspecialchars($cot['nombre_proyecto_ref'] ?: 'General') ?></td>
                                <td>
                                    <span class="insignia <?= $claseEstado ?>">
                                        <?= htmlspecialchars($cot['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="cotizaciones/administracion_Cotizaciones.php" class="boton boton--secundario boton--pequeno">
                                        Revisar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>