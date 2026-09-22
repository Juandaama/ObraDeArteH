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


$idUsuarioSesion   = $_SESSION['id'] ?? null;
$nombreUsuario     = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
$correoUsuario     = $_SESSION['correo'] ?? '';
$telefonoUsuario   = $_SESSION['telefono'] ?? '';

$publicaciones = [];
try {
    $stmt = $pdo->query("SELECT id_imagen, nombre, precio, categoria, descripcion, ruta_imagen FROM catalogo ORDER BY id_imagen DESC");
    $publicaciones = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    $publicaciones = [];
}

$misCotizaciones = [];
if ($idUsuarioSesion) {
    try {
        $stmtCot = $pdo->prepare("SELECT * FROM cotizaciones WHERE id_usuario = ? ORDER BY id_cotizacion DESC");
        $stmtCot->execute([$idUsuarioSesion]);
        $misCotizaciones = $stmtCot->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $misCotizaciones = [];
    }
}

$msg = $_GET['msg'] ?? '';
$alertaMensaje = '';
$alertaTipo = '';

if ($msg === 'cotizacion_exitosa') {
    $alertaMensaje = '¡Tu solicitud de cotización ha sido enviada con éxito! Nos comunicaremos contigo a la mayor brevedad.';
    $alertaTipo = 'exito';
} elseif ($msg === 'campos_requeridos') {
    $alertaMensaje = 'Por favor completa todos los campos obligatorios del formulario.';
    $alertaTipo = 'error';
} elseif ($msg === 'correo_invalido') {
    $alertaMensaje = 'Por favor ingresa un correo electrónico válido.';
    $alertaTipo = 'error';
} elseif ($msg === 'telefono_invalido') {
    $alertaMensaje = 'Por favor ingresa un número de teléfono válido.';
    $alertaTipo = 'error';
} elseif ($msg === 'error_servidor') {
    $alertaMensaje = 'Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo.';
    $alertaTipo = 'error';
}

$fallbacks = [
    'cocina'     => '../img/catalogo/cocina/cocina.webp',
    'habitacion' => '../img/catalogo/habitacion/habitacion.webp',
    'patio'      => '../img/catalogo/patio/Patio.webp',
    'sala'       => '../img/catalogo/sala/sala.jpg',
    'baño'       => '../img/catalogo/baño/Baño.jpg'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Obras y Diseños - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/estilos.css">
    <link rel="stylesheet" href="../assets/estilos_adicionales.css">
</head>
<body>
    <?php include 'menu.php'; ?>

    <div class="contenido-publico">
        <!-- Cabecera de la Sección -->
        <div class="cabecera-catalogo">
            <div>
                <span class="info-destacada__lema">Portafolio Arquitectónico</span>
                <h1 class="contenido-publico__titulo" style="margin-bottom: 6px;">Catálogo de Proyectos</h1>
                <p class="contenido-publico__descripcion">
                    Explora nuestras remodelaciones, filtra por espacios y cotiza el diseño de tus sueños.
                </p>
            </div>
            <div>
                <button type="button" class="boton boton--primario boton--inline" onclick="abrirModalCotizacion()">
                    Solicitar Cotización
                </button>
            </div>
        </div>

        <?php if ($alertaMensaje): ?>
            <div class="alerta alerta--<?= $alertaTipo ?>">
                <p><?= htmlspecialchars($alertaMensaje) ?></p>
            </div>
        <?php endif; ?>

        <div class="catalogo-controles">
            <div class="catalogo-controles__superior">
                <div class="buscador-caja">
                    <span class="buscador-caja__icono">&#128269;</span>
                    <input 
                        type="text" 
                        id="buscadorCatalogo" 
                        class="buscador-caja__input" 
                        placeholder="Buscar por nombre, descripción o categoría..."
                        autocomplete="off"
                    >
                </div>
                <div class="filtros-grupo" id="grupoFiltros">
                    <button type="button" class="filtro-chip filtro-chip--activo" data-categoria="todas">
                        Todas
                    </button>
                    <button type="button" class="filtro-chip" data-categoria="cocina">
                        Cocina
                    </button>
                    <button type="button" class="filtro-chip" data-categoria="habitacion">
                        Habitación
                    </button>
                    <button type="button" class="filtro-chip" data-categoria="patio">
                        Patio
                    </button>
                    <button type="button" class="filtro-chip" data-categoria="sala">
                        Sala
                    </button>
                    <button type="button" class="filtro-chip" data-categoria="baño">
                        Baño
                    </button>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span id="conteoResultados" style="font-size: 0.8rem; font-family: var(--fuente-subtitulo); text-transform: uppercase; color: var(--color-texto-secundario); letter-spacing: 0.05em;">
                    Cargando proyectos...
                </span>
            </div>
        </div>

        <div class="catalogo-grid" id="gridCatalogo">
            <?php if (empty($publicaciones)): ?>
                <div class="catalogo-vacio">
                    <div class="catalogo-vacio__icono">&#127968;</div>
                    <p class="catalogo-vacio__texto">Aún no hay proyectos registrados en el catálogo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($publicaciones as $item): ?>
                    <?php
                        $catNormalizada = strtolower(trim($item['categoria'] ?? ''));
                        // Normalizar ruta de la imagen
                        $rutaRaw = trim($item['ruta_imagen'] ?? '');
                        if (str_starts_with($rutaRaw, '../../')) {
                            $rutaFinal = '../' . substr($rutaRaw, 6);
                        } elseif (str_starts_with($rutaRaw, 'img/')) {
                            $rutaFinal = '../' . $rutaRaw;
                        } else {
                            $rutaFinal = $rutaRaw;
                        }

                        $rutaChequeo = str_replace('../', '', $rutaFinal);
                        if (empty($rutaFinal) || !file_exists('../' . $rutaChequeo)) {
                            $rutaFinal = $fallbacks[$catNormalizada] ?? '../img/Logo.PNG';
                        }
                    ?>
                    <article 
                        class="catalogo-tarjeta" 
                        data-id="<?= (int)$item['id_imagen'] ?>"
                        data-categoria="<?= htmlspecialchars($catNormalizada) ?>"
                        data-titulo="<?= htmlspecialchars($item['nombre']) ?>"
                        data-precio="<?= htmlspecialchars($item['precio']) ?>"
                        data-descripcion="<?= htmlspecialchars($item['descripcion']) ?>"
                        data-imagen="<?= htmlspecialchars($rutaFinal) ?>"
                    >
                        <div class="catalogo-tarjeta__imagen-marco" onclick="verDetalleObra(this.closest('.catalogo-tarjeta'))">
                            <img 
                                src="<?= htmlspecialchars($rutaFinal) ?>" 
                                alt="<?= htmlspecialchars($item['nombre']) ?>" 
                                class="catalogo-tarjeta__imagen"
                                loading="lazy"
                            >
                            <span class="catalogo-tarjeta__categoria">
                                <?= htmlspecialchars(ucfirst($item['categoria'])) ?>
                            </span>
                        </div>
                        <div class="catalogo-tarjeta__cuerpo">
                            <h2 class="catalogo-tarjeta__titulo"><?= htmlspecialchars($item['nombre']) ?></h2>
                            <?php if (!empty($item['precio'])): ?>
                                <div class="catalogo-tarjeta__precio">
                                    Precio ref: <?= htmlspecialchars($item['precio']) ?>
                                </div>
                            <?php endif; ?>
                            <p class="catalogo-tarjeta__descripcion">
                                <?= htmlspecialchars($item['descripcion']) ?>
                            </p>
                            <div class="catalogo-tarjeta__acciones">
                                <button 
                                    type="button" 
                                    class="boton boton--secundario"
                                    onclick="verDetalleObra(this.closest('.catalogo-tarjeta'))"
                                >
                                    Ver Detalle
                                </button>
                                <button 
                                    type="button" 
                                    class="boton boton--primario"
                                    onclick="cotizarProyecto(this.closest('.catalogo-tarjeta'))"
                                >
                                    Cotizar
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sección de Mis Cotizaciones (Solo si el usuario ha solicitado cotizaciones) -->
        <?php if (!empty($misCotizaciones)): ?>
            <div class="seccion-mis-cotizaciones tarjeta--marco">
                <div class="contenido-admin__encabezado" style="margin-bottom: 20px;">
                    <h2 class="contenido-admin__titulo" style="font-size: 1.3rem;">Mis Cotizaciones Solicitadas</h2>
                    <p class="contenido-admin__descripcion">Historial y estado de tus solicitudes de remodelación</p>
                </div>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Categoría</th>
                                <th>Proyecto Ref.</th>
                                <th>Presupuesto</th>
                                <th>Estado</th>
                                <th>Respuesta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($misCotizaciones as $cot): ?>
                                <?php
                                    $claseEstado = 'insignia--pendiente';
                                    if ($cot['estado'] === 'En revisión') $claseEstado = 'insignia--revision';
                                    elseif ($cot['estado'] === 'Aprobada') $claseEstado = 'insignia--aprobada';
                                    elseif ($cot['estado'] === 'Rechazada') $claseEstado = 'insignia--rechazada';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($cot['fecha_creacion']))) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($cot['categoria'])) ?></td>
                                    <td><?= htmlspecialchars($cot['nombre_proyecto_ref'] ?: 'General') ?></td>
                                    <td><?= htmlspecialchars($cot['presupuesto_estimado'] ?: 'No especificado') ?></td>
                                    <td>
                                        <span class="insignia <?= $claseEstado ?>">
                                            <?= htmlspecialchars($cot['estado']) ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 250px; white-space: normal;">
                                        <?= htmlspecialchars($cot['respuesta_admin'] ?: 'En espera de revisión...') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="modalCotizacion">
        <div class="modal-caja modal-cotizacion-caja tarjeta tarjeta--marco">
            <div class="tarjeta__encabezado">
                <h2 class="marca" style="font-size: 1.4rem;">Obra de Arte</h2>
                <hr class="filete">
                <h3 class="titulo-seccion" id="modalCotizacionTitulo">Solicitar Cotización</h3>
            </div>
            
            <form action="guardar_cotizacion.php" method="POST" class="formulario" id="formCotizacion">
                <input type="hidden" name="id_imagen_ref" id="cotIdImagenRef" value="">
                <input type="hidden" name="nombre_proyecto_ref" id="cotNombreProyectoRef" value="">

                <div id="bannerProyectoRef" style="display: none; background-color: var(--color-fondo); border-left: 3px solid var(--color-acento); padding: 8px 12px; margin-bottom: 12px; font-size: 0.85rem;">
                    <strong>Proyecto referenciado:</strong> <span id="textoProyectoRef"></span>
                </div>

                <div class="formulario-grid">
                    <div class="campo">
                        <label for="cotNombre">Nombre Completo *</label>
                        <input 
                            type="text" 
                            id="cotNombre" 
                            name="nombre_cliente" 
                            required 
                            class="entrada" 
                            placeholder="Tu nombre y apellido"
                            value="<?= htmlspecialchars($nombreUsuario) ?>"
                        >
                    </div>

                    <div class="campo">
                        <label for="cotCorreo">Correo Electrónico *</label>
                        <input 
                            type="email" 
                            id="cotCorreo" 
                            name="correo_cliente" 
                            required 
                            class="entrada" 
                            placeholder="correo@ejemplo.com"
                            value="<?= htmlspecialchars($correoUsuario) ?>"
                        >
                    </div>
                </div>

                <div class="formulario-grid">
                    <div class="campo">
                        <label for="cotTelefono">Teléfono / WhatsApp *</label>
                        <input 
                            type="text" 
                            id="cotTelefono" 
                            name="telefono_cliente" 
                            required 
                            class="entrada" 
                            placeholder="Ej. 3001234567"
                            value="<?= htmlspecialchars($telefonoUsuario) ?>"
                        >
                    </div>

                    <div class="campo">
                        <label for="cotCategoria">Espacio o Categoría *</label>
                        <select id="cotCategoria" name="categoria" required class="entrada">
                            <option value="">Selecciona un área...</option>
                            <option value="cocina">Cocina</option>
                            <option value="habitacion">Habitación</option>
                            <option value="patio">Patio</option>
                            <option value="sala">Sala</option>
                            <option value="baño">Baño</option>
                            <option value="general">Remodelación General / Toda la Casa</option>
                        </select>
                    </div>
                </div>

                <div class="campo">
                    <label for="cotPresupuesto">Presupuesto Estimado (Opcional)</label>
                    <input 
                        type="text" 
                        id="cotPresupuesto" 
                        name="presupuesto_estimado" 
                        class="entrada" 
                        placeholder="Ej. $5.000.000 - $10.000.000 COP"
                    >
                </div>

                <div class="campo">
                    <label for="cotDetalles">Detalles y Requerimientos del Proyecto *</label>
                    <textarea 
                        id="cotDetalles" 
                        name="detalles" 
                        required 
                        class="entrada" 
                        rows="4" 
                        placeholder="Describe las medidas, materiales, cambios o ideas que tienes en mente para este espacio..."
                    ></textarea>
                </div>

                <div class="formulario-acciones">
                    <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModalCotizacion()">
                        Cancelar
                    </button>
                    <button type="submit" class="boton boton--primario boton--pequeno">
                        Enviar Cotización
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================
         Modal Visor de Detalle de Obra
         ========================================================= -->
    <div class="modal-overlay" id="modalVisor">
        <div class="modal-caja modal-visor-caja tarjeta tarjeta--marco">
            <div>
                <img src="" alt="Detalle del proyecto" id="visorImagen" class="modal-visor__imagen">
            </div>
            <div class="modal-visor__contenido">
                <div>
                    <span class="insignia" id="visorCategoria" style="margin-bottom: 10px; display: inline-block;"></span>
                    <h2 class="contenido-publico__titulo" id="visorTitulo" style="margin-bottom: 8px;"></h2>
                    <div class="catalogo-tarjeta__precio" id="visorPrecio" style="font-size: 1.1rem; margin-bottom: 12px;"></div>
                    <hr class="filete" style="margin: 12px 0 16px; width: 48px;">
                    <p class="contenido-publico__descripcion" id="visorDescripcion" style="line-height: 1.6; margin-bottom: 24px;"></p>
                </div>
                <div class="formulario-acciones" style="margin-top: auto;">
                    <button type="button" class="boton boton--secundario boton--pequeno" onclick="cerrarModalVisor()">
                        Cerrar
                    </button>
                    <button type="button" class="boton boton--primario boton--pequeno" id="btnVisorCotizar">
                        Cotizar Este Proyecto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buscador = document.getElementById('buscadorCatalogo');
            const chips = document.querySelectorAll('.filtro-chip');
            const tarjetas = document.querySelectorAll('.catalogo-tarjeta');
            const conteoResultados = document.getElementById('conteoResultados');
            const grid = document.getElementById('gridCatalogo');

            let categoriaActual = 'todas';
            let terminoBusqueda = '';

            function aplicarFiltros() {
                let visibles = 0;

                tarjetas.forEach(tarjeta => {
                    const cat = tarjeta.getAttribute('data-categoria') || '';
                    const titulo = (tarjeta.getAttribute('data-titulo') || '').toLowerCase();
                    const desc = (tarjeta.getAttribute('data-descripcion') || '').toLowerCase();
                    const precio = (tarjeta.getAttribute('data-precio') || '').toLowerCase();

                    const coincideCategoria = (categoriaActual === 'todas' || cat === categoriaActual);

                    const coincideTermino = !terminoBusqueda || 
                        titulo.includes(terminoBusqueda) || 
                        desc.includes(terminoBusqueda) || 
                        cat.includes(terminoBusqueda) || 
                        precio.includes(terminoBusqueda);

                    if (coincideCategoria && coincideTermino) {
                        tarjeta.style.display = 'flex';
                        visibles++;
                    } else {
                        tarjeta.style.display = 'none';
                    }
                });

                let avisoVacio = document.getElementById('avisoSinCoincidencias');
                if (visibles === 0 && tarjetas.length > 0) {
                    if (!avisoVacio) {
                        avisoVacio = document.createElement('div');
                        avisoVacio.id = 'avisoSinCoincidencias';
                        avisoVacio.className = 'catalogo-vacio';
                        avisoVacio.innerHTML = `
                            <div class="catalogo-vacio__icono">&#128269;</div>
                            <p class="catalogo-vacio__texto">No se encontraron proyectos para los filtros seleccionados.</p>
                        `;
                        grid.appendChild(avisoVacio);
                    }
                } else if (avisoVacio) {
                    avisoVacio.remove();
                }

                if (conteoResultados) {
                    conteoResultados.textContent = `Mostrando ${visibles} de ${tarjetas.length} proyectos`;
                }
            }

            buscador.addEventListener('input', function() {
                terminoBusqueda = this.value.trim().toLowerCase();
                aplicarFiltros();
            });

            chips.forEach(chip => {
                chip.addEventListener('click', function() {
                    chips.forEach(c => c.classList.remove('filtro-chip--activo'));
                    this.classList.add('filtro-chip--activo');
                    categoriaActual = this.getAttribute('data-categoria');
                    aplicarFiltros();
                });
            });

            aplicarFiltros();

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    cerrarModalCotizacion();
                    cerrarModalVisor();
                }
            });

            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        cerrarModalCotizacion();
                        cerrarModalVisor();
                    }
                });
            });
        });

        function abrirModalCotizacion(proyecto = null) {
            const modal = document.getElementById('modalCotizacion');
            const tituloModal = document.getElementById('modalCotizacionTitulo');
            const idRef = document.getElementById('cotIdImagenRef');
            const nomRef = document.getElementById('cotNombreProyectoRef');
            const bannerRef = document.getElementById('bannerProyectoRef');
            const textoRef = document.getElementById('textoProyectoRef');
            const selectCat = document.getElementById('cotCategoria');

            if (proyecto) {
                tituloModal.textContent = `Cotizar Diseño: ${proyecto.titulo}`;
                idRef.value = proyecto.id || '';
                nomRef.value = proyecto.titulo || '';
                textoRef.textContent = `${proyecto.titulo} (${proyecto.categoria.toUpperCase()})`;
                bannerRef.style.display = 'block';

                const catValue = proyecto.categoria.toLowerCase();
                if (selectCat.querySelector(`option[value="${catValue}"]`)) {
                    selectCat.value = catValue;
                }
            } else {
                tituloModal.textContent = 'Solicitar Cotización';
                idRef.value = '';
                nomRef.value = '';
                bannerRef.style.display = 'none';
            }

            modal.classList.add('modal-overlay--activo');
        }

        function cerrarModalCotizacion() {
            const modal = document.getElementById('modalCotizacion');
            modal.classList.remove('modal-overlay--activo');
        }

        function cotizarProyecto(tarjeta) {
            const id = tarjeta.getAttribute('data-id');
            const titulo = tarjeta.getAttribute('data-titulo');
            const categoria = tarjeta.getAttribute('data-categoria');
            abrirModalCotizacion({ id, titulo, categoria });
        }

        let proyectoActivoVisor = null;

        function verDetalleObra(tarjeta) {
            const id = tarjeta.getAttribute('data-id');
            const titulo = tarjeta.getAttribute('data-titulo');
            const categoria = tarjeta.getAttribute('data-categoria');
            const precio = tarjeta.getAttribute('data-precio');
            const descripcion = tarjeta.getAttribute('data-descripcion');
            const imagen = tarjeta.getAttribute('data-imagen');

            proyectoActivoVisor = { id, titulo, categoria, precio, descripcion, imagen };

            document.getElementById('visorImagen').src = imagen;
            document.getElementById('visorCategoria').textContent = categoria.toUpperCase();
            document.getElementById('visorTitulo').textContent = titulo;
            document.getElementById('visorPrecio').textContent = precio ? `Precio ref: ${precio}` : '';
            document.getElementById('visorDescripcion').textContent = descripcion;

            const btnCotizar = document.getElementById('btnVisorCotizar');
            btnCotizar.onclick = function() {
                cerrarModalVisor();
                abrirModalCotizacion(proyectoActivoVisor);
            };

            document.getElementById('modalVisor').classList.add('modal-overlay--activo');
        }

        function cerrarModalVisor() {
            document.getElementById('modalVisor').classList.remove('modal-overlay--activo');
            proyectoActivoVisor = null;
        }
    </script>
</body>
</html>