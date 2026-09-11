<?php
require_once 'DB/conexion.php';

// Definición de las 5 categorías oficiales con sus mensajes generales y variados
$categoriasConfig = [
    'cocina'     => [
        'mensaje'  => 'Remodelaciones para tu cocina',
        'fallback' => 'img/catalogo/cocina/sala.jpg',
        'alt'      => 'Remodelación de Cocinas'
    ],
    'patio'      => [
        'mensaje'  => 'Todo tipo de diseños para tu patio',
        'fallback' => 'img/catalogo/patio/EduardoMonte.jfif',
        'alt'      => 'Remodelación de Patios'
    ],
    'sala'       => [
        'mensaje'  => 'Grandes transformaciones para tu sala',
        'fallback' => 'img/catalogo/sala/sala.jpg',
        'alt'      => 'Remodelación de Salas'
    ],
    'baño'       => [
        'mensaje'  => 'Renovaciones completas para tu baño',
        'fallback' => 'img/catalogo/baño/CDC.png',
        'alt'      => 'Remodelación de Baños'
    ],
    'habitacion' => [
        'mensaje'  => 'Espacios confortables para tu habitación',
        'fallback' => 'img/catalogo/habitacion/IECM.png',
        'alt'      => 'Remodelación de Habitaciones'
    ]
];

// Agrupar publicaciones por categoría desde la base de datos
$imagenesPorCategoria = [];
foreach ($categoriasConfig as $catKey => $config) {
    $imagenesPorCategoria[$catKey] = [];
}

try {
    $stmt = $pdo->query("SELECT id_imagen, nombre, descripcion, categoria, ruta_imagen FROM catalogo ORDER BY id_imagen DESC");
    $publicacionesDB = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($publicacionesDB as $pub) {
        $catKey = strtolower(trim($pub['categoria'] ?? ''));
        if (isset($categoriasConfig[$catKey])) {
            $ruta = trim($pub['ruta_imagen'] ?? '');
            if (str_starts_with($ruta, '../../')) {
                $ruta = substr($ruta, 6);
            }
            if (!empty($ruta) && file_exists($ruta)) {
                $imagenesPorCategoria[$catKey][] = [
                    'ruta'   => $ruta,
                    'nombre' => $pub['nombre'] ?? ''
                ];
            }
        }
    }
} catch (Exception $e) {
    // Si ocurre un error, se usarán los fallbacks
}

// Preparar exactamente un slide por cada categoría para no saturar el carrusel
$slidesCarrusel = [];
foreach ($categoriasConfig as $catKey => $config) {
    $imgs = $imagenesPorCategoria[$catKey];
    if (empty($imgs)) {
        $imgs = [
            [
                'ruta'   => $config['fallback'],
                'nombre' => ''
            ]
        ];
    }
    $slidesCarrusel[] = [
        'categoria' => $catKey,
        'mensaje'   => $config['mensaje'],
        'alt'       => $config['alt'],
        'imagenes'  => $imgs,
        'principal' => $imgs[0]['ruta'],
        'jsonImgs'  => htmlspecialchars(json_encode(array_column($imgs, 'ruta')), ENT_QUOTES, 'UTF-8')
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión - ObraDeArteH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/estilos.css">
</head>
<body>
    <div class="pagina pagina--inicio">
        <div class="contenedor-inicio">
            <!-- Columna Izquierda: Formulario de Inicio de Sesión -->
            <div class="contenedor-inicio__login">
                <div class="tarjeta tarjeta--marco">
                    <div class="tarjeta__encabezado">
                        <div class="marca-logo">
                            <img src="img/Logo.PNG" alt="Obra de Arte Arquitectura" class="marca-logo__imagen">
                        </div>
                        <hr class="filete">
                        <h2 class="titulo-seccion">Inicio de sesión</h2>
                    </div>
                    <div class="tarjeta__cuerpo">
                        <form action="Login/InisioSesion.php" method="post" class="formulario">
                            <div class="campo">
                                <label for="nombre">Nombre de usuario:</label>
                                <input type="text" id="nombre" name="nombre" class="entrada" required placeholder="Ingresa tu nombre">
                            </div>

                            <div class="campo">
                                <label for="contrasena">Contraseña:</label>
                                <input type="password" id="contrasena" name="contrasena" class="entrada" required placeholder="Ingresa tu contraseña">
                            </div>

                            <input type="submit" value="Iniciar sesión" class="boton boton--primario">
                        </form>

                        <hr class="filete filete--secundario">

                        <div class="acciones-secundarias">
                            <a href="Login/procesoCrearUsuario.php" class="boton boton--secundario">Crear cuenta</a>
                        </div>

                        <div class="tarjeta__pie">
                            <footer class="pie">
                                <p class="pie__texto">ObraDeArteH &bull; Remodelación y Diseño</p>
                            </footer>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Información Descriptiva y Carrusel de Remodelaciones -->
            <div class="contenedor-inicio__info tarjeta tarjeta--marco">
                <div>
                    <div class="info-destacada__encabezado">
                        <span class="info-destacada__lema">Diseño & Remodelación</span>
                        <h2 class="info-destacada__titulo">Transformamos cada rincón de tu hogar</h2>
                    </div>

                    <!-- Carrusel de imágenes de remodelaciones: Una sola diapositiva por categoría -->
                    <div class="carrusel" id="carruselRemodelaciones">
                        <div class="carrusel__pistas" id="carruselPistas">
                            <?php foreach ($slidesCarrusel as $slide): ?>
                                <div class="carrusel__slide" data-categoria="<?= $slide['categoria'] ?>" data-imagenes='<?= $slide['jsonImgs'] ?>'>
                                    <img src="<?= htmlspecialchars($slide['principal']) ?>" alt="<?= $slide['alt'] ?>" class="carrusel__imagen">
                                    <div class="carrusel__etiqueta"><?= htmlspecialchars($slide['mensaje']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="carrusel__boton carrusel__boton--anterior" id="carruselPrev" aria-label="Anterior">&lsaquo;</button>
                        <button type="button" class="carrusel__boton carrusel__boton--siguiente" id="carruselNext" aria-label="Siguiente">&rsaquo;</button>
                    </div>

                    <div class="carrusel__indicadores" id="carruselIndicadores">
                        <?php foreach ($slidesCarrusel as $index => $slide): ?>
                            <button type="button" class="carrusel__dot <?= $index === 0 ? 'carrusel__dot--activo' : '' ?>" data-slide="<?= $index ?>" aria-label="Slide <?= $index + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-destacada__cuerpo">
                    <h3 class="titulo-seccion" style="margin-bottom: 8px;">El lugar ideal para remodelar tu hogar</h3>
                    <p class="info-destacada__descripcion">
                        Convertimos tus ideas en realidad con proyectos de remodelación personalizados para baños, cocinas, patios, salas y habitaciones. Calidad, estilo y acabados que elevan el valor y la armonía de tu casa.
                    </p>

                    <div class="info-destacada__servicios">
                        <div class="info-destacada__servicio">
                            <span class="info-destacada__icono">&#10022;</span>
                            <span>Salas y Comedores</span>
                        </div>
                        <div class="info-destacada__servicio">
                            <span class="info-destacada__icono">&#10022;</span>
                            <span>Cocinas Integrales</span>
                        </div>
                        <div class="info-destacada__servicio">
                            <span class="info-destacada__icono">&#10022;</span>
                            <span>Baños Modernos</span>
                        </div>
                        <div class="info-destacada__servicio">
                            <span class="info-destacada__icono">&#10022;</span>
                            <span>Habitaciones y Patios</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para interactividad del carrusel y rotación por categoría -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pistas = document.getElementById('carruselPistas');
            const slides = document.querySelectorAll('.carrusel__slide');
            const dots = document.querySelectorAll('.carrusel__dot');
            const prevBtn = document.getElementById('carruselPrev');
            const nextBtn = document.getElementById('carruselNext');
            const carrusel = document.getElementById('carruselRemodelaciones');

            let actual = 0;
            const total = slides.length;
            let intervaloCarrusel = null;

            if (total <= 1) {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                const ind = document.getElementById('carruselIndicadores');
                if (ind) ind.style.display = 'none';
                return;
            }

            // 1. Navegación entre categorías del carrusel
            function irASlide(indice) {
                actual = (indice + total) % total;
                pistas.style.transform = `translateX(-${actual * 100}%)`;
                dots.forEach((dot, i) => {
                    dot.classList.toggle('carrusel__dot--activo', i === actual);
                });
            }

            function siguiente() {
                irASlide(actual + 1);
            }

            function anterior() {
                irASlide(actual - 1);
            }

            function iniciarAutoCarrusel() {
                detenerAutoCarrusel();
                intervaloCarrusel = setInterval(siguiente, 4500);
            }

            function detenerAutoCarrusel() {
                if (intervaloCarrusel) {
                    clearInterval(intervaloCarrusel);
                    intervaloCarrusel = null;
                }
            }

            if (nextBtn) nextBtn.addEventListener('click', () => { siguiente(); iniciarAutoCarrusel(); });
            if (prevBtn) prevBtn.addEventListener('click', () => { anterior(); iniciarAutoCarrusel(); });

            dots.forEach(dot => {
                dot.addEventListener('click', function() {
                    const indice = parseInt(this.getAttribute('data-slide'), 10);
                    irASlide(indice);
                    iniciarAutoCarrusel();
                });
            });

            // 2. Intercambio automático de imágenes para categorías con múltiples fotos
            const slidesMultiples = [];
            slides.forEach(slide => {
                try {
                    const lista = JSON.parse(slide.getAttribute('data-imagenes') || '[]');
                    if (Array.isArray(lista) && lista.length > 1) {
                        slidesMultiples.push({
                            imgElement: slide.querySelector('.carrusel__imagen'),
                            imagenes: lista,
                            indice: 0
                        });
                    }
                } catch (err) {}
            });

            if (slidesMultiples.length > 0) {
                setInterval(() => {
                    slidesMultiples.forEach(item => {
                        item.indice = (item.indice + 1) % item.imagenes.length;
                        const proximaFoto = item.imagenes[item.indice];
                        if (item.imgElement) {
                            item.imgElement.style.opacity = '0';
                            setTimeout(() => {
                                item.imgElement.src = proximaFoto;
                                item.imgElement.style.opacity = '1';
                            }, 350);
                        }
                    });
                }, 3800);
            }

            // 3. Pausa en hover y soporte táctil para móviles
            if (carrusel) {
                carrusel.addEventListener('mouseenter', detenerAutoCarrusel);
                carrusel.addEventListener('mouseleave', iniciarAutoCarrusel);

                let inicioX = 0;
                carrusel.addEventListener('touchstart', (e) => {
                    inicioX = e.touches[0].clientX;
                    detenerAutoCarrusel();
                }, { passive: true });

                carrusel.addEventListener('touchend', (e) => {
                    const finX = e.changedTouches[0].clientX;
                    const diff = inicioX - finX;
                    if (Math.abs(diff) > 40) {
                        if (diff > 0) siguiente();
                        else anterior();
                    }
                    iniciarAutoCarrusel();
                }, { passive: true });
            }

            iniciarAutoCarrusel();
        });
    </script>
</body>
</html>