<?php
session_start();
require_once '../../DB/conexion.php';

// Asegurar existencia de las columnas necesarias en la base de datos
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'foto_perfil'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER correo");
    }
    $colCheck2 = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'descripcion'")->fetch();
    if (!$colCheck2) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN descripcion TEXT NULL AFTER foto_perfil");
    }
    $colCheck3 = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'edad'")->fetch();
    if (!$colCheck3) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN edad INT NULL AFTER apellido");
    }
} catch (Exception $e) {
    // Continuar con la ejecución
}

// Determinar permisos y modo
$esAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador');
$modo = $_GET['modo'] ?? ($esAdmin ? 'edicion' : 'vista');

// Si no es administrador, forzar siempre modo solo lectura / vista
if (!$esAdmin) {
    $modo = 'vista';
}

$error = '';
$exito = '';

// Obtener datos del perfil del administrador
$adminId = $esAdmin && isset($_SESSION['id']) ? $_SESSION['id'] : null;

if ($adminId) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$adminId]);
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE rol = 'administrador' ORDER BY id ASC LIMIT 1");
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Valores por defecto si no existen
if (!$perfil) {
    $perfil = [
        'id'          => 0,
        'nombre'      => 'Administrador',
        'apellido'    => 'ObraDeArteH',
        'edad'        => 35,
        'telefono'    => '3113875849',
        'correo'      => 'contacto@obradearteh.com',
        'descripcion' => 'Especialistas en remodelación arquitectónica, diseño de interiores y optimización de espacios de alta calidad.',
        'foto_perfil' => ''
    ];
}

// Inicializar variables con datos existentes
$nombre      = $perfil['nombre'] ?? '';
$apellido    = $perfil['apellido'] ?? '';
$edad        = $perfil['edad'] ?? '';
$telefono    = $perfil['telefono'] ?? '';
$correo      = $perfil['correo'] ?? '';
$descripcion = $perfil['descripcion'] ?? '';
$fotoPerfil  = $perfil['foto_perfil'] ?? '';

// Procesar actualización (solo administrador en modo edición)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esAdmin && $modo === 'edicion') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $apellido    = trim($_POST['apellido'] ?? '');
    $edad        = trim($_POST['edad'] ?? '');
    $telefono    = trim($_POST['telefono'] ?? '');
    $correo      = trim($_POST['correo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validaciones estrictas reteniendo valores
    if ($nombre === '' || $apellido === '' || $telefono === '' || $correo === '') {
        $error = 'Por favor completa todos los campos obligatorios.';
    } elseif (!preg_match('/^[0-9]{7,15}$/', $telefono)) {
        $error = 'El número de teléfono solo debe contener números (entre 7 y 15 dígitos).';
    } elseif (!empty($edad) && (!is_numeric($edad) || $edad < 18 || $edad > 100)) {
        $error = 'Por favor ingresa una edad válida en números (entre 18 y 100 años).';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido con dominio completo (ejemplo: usuario@gmail.com).';
    } else {
        // Verificar si el correo ya pertenece a otro usuario
        $stmtCorreo = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ? LIMIT 1");
        $stmtCorreo->execute([$correo, $perfil['id']]);
        if ($stmtCorreo->fetch()) {
            $error = 'Ese correo electrónico ya está registrado por otro usuario.';
        } else {
            // Manejo de subida de foto de perfil
            $fotoFinal = $fotoPerfil;
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $dirDestino = '../../img/perfiles/';
                if (!is_dir($dirDestino)) {
                    mkdir($dirDestino, 0777, true);
                }

                $archivoTmp = $_FILES['foto_perfil']['tmp_name'];
                $nombreOrig = $_FILES['foto_perfil']['name'];
                $tamano     = $_FILES['foto_perfil']['size'];
                $ext        = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));
                $extValidas = ['jpg', 'jpeg', 'png', 'webp', 'jfif'];

                if (!in_array($ext, $extValidas)) {
                    $error = 'Formato de imagen no permitido. Usa JPG, PNG, WEBP o JFIF.';
                } elseif ($tamano > 5 * 1024 * 1024) {
                    $error = 'La imagen es demasiado pesada. El tamaño máximo es de 5MB.';
                } else {
                    $nuevoNombre = 'perfil_admin_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $rutaDestino = $dirDestino . $nuevoNombre;

                    if (move_uploaded_file($archivoTmp, $rutaDestino)) {
                        $fotoFinal = 'img/perfiles/' . $nuevoNombre;
                        $fotoPerfil = $fotoFinal;
                    } else {
                        $error = 'Error al guardar la imagen en el servidor.';
                    }
                }
            }

            // Si no hay errores, actualizar en la base de datos
            if (empty($error)) {
                $stmtUpdate = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, edad = ?, telefono = ?, correo = ?, descripcion = ?, foto_perfil = ? WHERE id = ?");
                $edadFinal = !empty($edad) ? (int)$edad : null;
                $stmtUpdate->execute([$nombre, $apellido, $edadFinal, $telefono, $correo, $descripcion, $fotoFinal, $perfil['id']]);

                // Actualizar sesión si es el usuario actual
                $_SESSION['nombre']   = $nombre;
                $_SESSION['apellido'] = $apellido;
                $_SESSION['telefono'] = $telefono;
                $_SESSION['correo']   = $correo;

                $exito = '¡Perfil de administrador actualizado correctamente!';
                $perfil['nombre']      = $nombre;
                $perfil['apellido']    = $apellido;
                $perfil['edad']        = $edadFinal;
                $perfil['telefono']    = $telefono;
                $perfil['correo']      = $correo;
                $perfil['descripcion'] = $descripcion;
                $perfil['foto_perfil']  = $fotoFinal;
            }
        }
    }
}

// Determinar ruta de foto para mostrar
$rutaFotoDisplay = '../../img/Logo.PNG';
if (!empty($fotoPerfil)) {
    if (str_starts_with($fotoPerfil, '../../')) {
        $rutaFotoDisplay = $fotoPerfil;
    } else {
        $rutaFotoDisplay = '../../' . ltrim($fotoPerfil, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modo === 'edicion' ? 'Editar Perfil Administrador' : 'Perfil Administrador' ?> - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/estilos.css">
</head>
<body>
    <div class="pagina">
        <div class="tarjeta tarjeta--marco tarjeta--perfil">
            <!-- Enlace Volver -->
            <?php if ($modo === 'edicion'): ?>
                <a href="../panel.php" class="enlace-volver">&larr; Volver al Panel Admin</a>
            <?php else: ?>
                <a href="../../lobby/catalogo.php" class="enlace-volver">&larr; Volver al Catálogo</a>
            <?php endif; ?>

            <!-- Encabezado con Logo de la Empresa -->
            <div class="tarjeta__encabezado">
                <div class="marca-logo">
                    <img src="../../img/Logo.PNG" alt="Obra de Arte Arquitectura" class="marca-logo__imagen">
                </div>
                <hr class="filete">
                <h2 class="titulo-seccion">
                    <?= $modo === 'edicion' ? 'Mi Perfil de Administrador' : 'Perfil del Administrador' ?>
                </h2>
            </div>

            <div class="tarjeta__cuerpo">
                <!-- Alertas de Estado -->
                <?php if (!empty($error)): ?>
                    <div class="alerta alerta--error">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($exito)): ?>
                    <div class="alerta alerta--exito">
                        <p><?= htmlspecialchars($exito) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($modo === 'edicion' && $esAdmin): ?>
                    <!-- ==========================================
                         MODO EDICIÓN (Solo Administrador)
                         ========================================== -->
                    <form action="perfilAdmin.php?modo=edicion" method="POST" enctype="multipart/form-data" class="formulario" id="form-perfil-admin">
                        <!-- Sección de Foto de Perfil con Vista Previa -->
                        <div class="perfil-avatar-seccion">
                            <div class="perfil-avatar-marco">
                                <img src="<?= htmlspecialchars($rutaFotoDisplay) ?>" alt="Foto de perfil" class="perfil-avatar-img" id="img-preview">
                            </div>
                            <div class="campo" style="max-width: 320px; width: 100%; margin-bottom: 8px;">
                                <label for="foto_perfil">Cambiar foto de perfil:</label>
                                <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="entrada" onchange="previsualizarFoto(event)">
                            </div>
                        </div>

                        <!-- Mini apartados: Nombre y Apellido -->
                        <div class="perfil-grid">
                            <div class="campo">
                                <label for="nombre">Nombre *:</label>
                                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required class="entrada" placeholder="Ingresa tu nombre">
                            </div>

                            <div class="campo">
                                <label for="apellido">Apellido *:</label>
                                <input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($apellido) ?>" required class="entrada" placeholder="Ingresa tu apellido">
                            </div>
                        </div>

                        <!-- Mini apartados: Edad y Teléfono -->
                        <div class="perfil-grid">
                            <div class="campo">
                                <label for="edad">Edad:</label>
                                <input type="number" id="edad" name="edad" value="<?= htmlspecialchars($edad) ?>" min="18" max="100" class="entrada" placeholder="Ej: 35">
                            </div>

                            <div class="campo">
                                <label for="telefono">Teléfono *:</label>
                                <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono) ?>" required class="entrada" inputmode="numeric" pattern="[0-9]{7,15}" title="Ingresa un teléfono válido de 7 a 15 números" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Ej: 3113875849">
                            </div>
                        </div>

                        <!-- Apartado: Correo Electrónico -->
                        <div class="campo">
                            <label for="correo">Correo Electrónico *:</label>
                            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo) ?>" required class="entrada" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" title="Ingresa un correo con dominio válido (ej: usuario@gmail.com)" placeholder="ejemplo@obradearteh.com">
                        </div>

                        <!-- Apartado: Descripción / Biografía -->
                        <div class="campo perfil-bio-bloque">
                            <label for="descripcion">Descripción / Perfil Profesional:</label>
                            <textarea id="descripcion" name="descripcion" class="entrada" rows="4" placeholder="Describe tu experiencia, especialidades de remodelación y visión profesional..."><?= htmlspecialchars($descripcion) ?></textarea>
                        </div>

                        <!-- Botón Guardar con validación previa -->
                        <button type="button" class="boton boton--primario" onclick="validarYPedirConfirmacion()">Guardar Cambios</button>
                    </form>

                <?php else: ?>
                    <!-- ==========================================
                         MODO VISUALIZACIÓN / SOLO LECTURA (Lobby)
                         ========================================== -->
                    <div class="perfil-avatar-seccion">
                        <div class="perfil-avatar-marco">
                            <img src="<?= htmlspecialchars($rutaFotoDisplay) ?>" alt="Foto de perfil del Administrador" class="perfil-avatar-img">
                        </div>
                        <h3 class="perfil-nombre-titulo"><?= htmlspecialchars($nombre . ' ' . $apellido) ?></h3>
                        <span class="perfil-cargo-insignia">Administrador &bull; Arquitectura y Diseño</span>
                    </div>

                    <!-- Apartado de Descripción -->
                    <div class="perfil-bio-bloque">
                        <label style="font-family:var(--fuente-subtitulo); font-size:0.78rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-texto-secundario); font-weight:600;">
                            Acerca del Administrador:
                        </label>
                        <div class="perfil-bio-vista">
                            <?= !empty($descripcion) ? nl2br(htmlspecialchars($descripcion)) : 'Profesional encargado de la dirección arquitectónica, supervisión de proyectos y asesoría integral de remodelación.' ?>
                        </div>
                    </div>

                    <!-- Mini apartados de Información Personal / Contacto -->
                    <div class="perfil-datos-lista">
                        <div class="perfil-dato-item">
                            <div class="perfil-dato-item__etiqueta">Teléfono de Contacto</div>
                            <div class="perfil-dato-item__valor"><?= !empty($telefono) ? htmlspecialchars($telefono) : 'No especificado' ?></div>
                        </div>

                        <div class="perfil-dato-item">
                            <div class="perfil-dato-item__etiqueta">Correo Electrónico</div>
                            <div class="perfil-dato-item__valor"><?= !empty($correo) ? htmlspecialchars($correo) : 'No especificado' ?></div>
                        </div>

                        <?php if (!empty($edad)): ?>
                            <div class="perfil-dato-item">
                                <div class="perfil-dato-item__etiqueta">Edad</div>
                                <div class="perfil-dato-item__valor"><?= htmlspecialchars($edad) ?> años</div>
                            </div>
                        <?php endif; ?>

                        <div class="perfil-dato-item">
                            <div class="perfil-dato-item__etiqueta">Empresa</div>
                            <div class="perfil-dato-item__valor">Obra de Arte Arquitectura</div>
                        </div>
                    </div>

                    <?php if ($esAdmin): ?>
                        <div style="margin-top: 16px; text-align: center;">
                            <a href="perfilAdmin.php?modo=edicion" class="boton boton--primario">Editar este perfil</a>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

                <hr class="filete filete--secundario">
                <div class="tarjeta__pie">
                    <footer class="pie">
                        <p class="pie__texto">ObraDeArteH &bull; Remodelación y Diseño</p>
                    </footer>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación para Edición -->
    <div class="modal-overlay" id="modalConfirmacionPerfil">
        <div class="modal-caja tarjeta tarjeta--marco">
            <p class="modal-mensaje">¿Confirmas que deseas guardar los cambios en tu perfil de administrador?</p>
            <div class="modal-acciones">
                <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModalPerfil()">Cancelar</button>
                <button type="button" class="boton boton--peligro boton--pequeno" id="btnConfirmarPerfil">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        function previsualizarFoto(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('img-preview');
                    if (img) {
                        img.src = e.target.result;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function validarYPedirConfirmacion() {
            const form = document.getElementById('form-perfil-admin');
            if (!form) return;

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Validar teléfono solo números
            const telInput = document.getElementById('telefono');
            if (telInput && !/^[0-9]{7,15}$/.test(telInput.value.trim())) {
                alert('El número telefónico debe contener únicamente números (entre 7 y 15 dígitos).');
                telInput.focus();
                return;
            }

            // Validar correo con dominio completo
            const correoInput = document.getElementById('correo');
            if (correoInput && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(correoInput.value.trim())) {
                alert('Por favor ingresa un correo electrónico válido con dominio completo (ej: usuario@gmail.com).');
                correoInput.focus();
                return;
            }

            document.getElementById('modalConfirmacionPerfil').classList.add('modal-overlay--activo');
        }

        function cerrarModalPerfil() {
            const modal = document.getElementById('modalConfirmacionPerfil');
            if (modal) {
                modal.classList.remove('modal-overlay--activo');
            }
        }

        const btnConfirmar = document.getElementById('btnConfirmarPerfil');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function() {
                document.getElementById('form-perfil-admin').submit();
            });
        }

        const modalOverlay = document.getElementById('modalConfirmacionPerfil');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalPerfil();
                }
            });
        }
    </script>
</body>
</html>

