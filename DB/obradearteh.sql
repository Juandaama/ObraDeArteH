-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2026 a las 01:03:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `obradearteh`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo`
--

CREATE TABLE `catalogo` (
  `id_imagen` int(11) NOT NULL,
  `descripcion` varchar(200) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio` varchar(100) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `ruta_imagen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalogo`
--

INSERT INTO `catalogo` (`id_imagen`, `descripcion`, `nombre`, `precio`, `categoria`, `ruta_imagen`) VALUES
(16, 'Patio inspirado en un estilo natural y moderno, diseñado para disfrutar de espacios exteriores cómodos y agradables. Combina materiales resistentes, vegetación y zonas de descanso para crear un ambien', 'Patio natura', '40.000 m2', 'patio', 'img/catalogo/patio/Patio.webp'),
(17, 'Espacio de estilo moderno y funcional, ideal para quienes buscan una sala fresca y actual. Su diseño combina colores neutros, elementos decorativos sencillos y una distribución práctica para aprovecha', 'Sala compacta', '56.000 m2', 'sala', 'img/catalogo/sala/sala.jpg'),
(18, 'Sala de diseño elegante y contemporáneo, pensada para crear un ambiente amplio y sofisticado. Integra acabados modernos, iluminación decorativa y una distribución que favorece la comodidad y la conviv', 'Sala élite', '35.000 m2', 'sala', 'img/catalogo/sala/sala2.avif'),
(19, 'Cocina de estilo moderno y minimalista, diseñada para aprovechar al máximo el espacio. Combina acabados en tonos neutros, superficies elegantes y detalles en madera que aportan calidez y funcionalidad', 'Cocina nórdica', '200.000 m2', 'cocina', 'img/catalogo/cocina/cocina.webp'),
(20, 'Baño de estilo moderno y relajante, con acabados en tonos neutros, iluminación cálida y materiales de fácil mantenimiento. Su diseño busca aprovechar el espacio y crear un ambiente cómodo, elegante y ', 'Baño Zen', '80.000 m2', 'baño', 'img/catalogo/baño/Baño.webp'),
(21, 'Habitación contemporánea diseñada para brindar comodidad y tranquilidad. Combina tonos suaves, iluminación ambiental y detalles en madera para crear un espacio acogedor, moderno y armonioso.', 'Suite', '72.000 m2', 'habitacion', 'img/catalogo/habitacion/habitacion.webp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id_cotizacion` int(11) NOT NULL,
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
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`id_cotizacion`, `id_usuario`, `nombre_cliente`, `correo_cliente`, `telefono_cliente`, `categoria`, `id_imagen_ref`, `nombre_proyecto_ref`, `presupuesto_estimado`, `detalles`, `estado`, `respuesta_admin`, `fecha_creacion`) VALUES
(1, 8, 'Camila Suarez', 'camila@gmail.com', '3226557667', 'patio', NULL, '', '', 'Hola', 'Aprobada', 'OKEY', '2026-09-22 20:37:17'),
(2, 8, 'Camila Suarez', 'camila@gmail.com', '3226557667', 'sala', 18, 'Sala', '20.000.000 COP', 'Esto pero con un peluche', 'Aprobada', 'ESTA PUBLICACION FUE APROBADA', '2026-10-01 22:45:18'),
(3, 10, 'Enrique Pacheco', 'andres88408489@gmail.com', '3246258786', 'habitacion', 21, 'Suite', '', 'Me interesa este tipo de habitación y quiero agendar una cita próximamente', 'Aprobada', 'Excelente, nos veremos pronto', '2026-10-12 21:31:09'),
(4, 11, 'michel Chirinos', 'michelsyo2023@gmail.com', '3335557777', 'baño', 20, 'Baño Zen', '', 'Hola soy michel', 'Aprobada', 'Funciona?', '2026-10-12 21:35:00'),
(5, 11, 'michel Chirinos', 'michelsyo2023@gmail.com', '3335557777', 'habitacion', 21, 'Suite', '', 'PARA MAÑANA', 'Aprobada', 'hola', '2026-10-12 21:37:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `apellido` varchar(200) NOT NULL,
  `edad` int(11) DEFAULT NULL,
  `telefono` bigint(200) NOT NULL,
  `correo` varchar(200) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `rol` enum('usuario','administrador') NOT NULL DEFAULT 'usuario',
  `contrasena` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `edad`, `telefono`, `correo`, `foto_perfil`, `descripcion`, `rol`, `contrasena`) VALUES
(4, 'Hugo Fernando', 'Osorio Ríos', 50, 3113875849, 'hugofernandoosorios@gmail.com', 'img/perfiles/perfil_admin_1789861544_797.jfif', '', 'administrador', '$2y$10$NzPRzW6qpbsMX/ULbyHmZuPcoUaheqqpa8SvMz0HQSuLXqw.munEi'),
(8, 'Camila', 'Suarez', 29, 3226557667, 'camila@gmail.com', NULL, NULL, 'usuario', '$2y$10$CxaPugZRuzSnYSfmV9qVjeuT/i4j8moWFirTikZZBsK2tg2RTP6G2'),
(9, 'Andres', 'Pacheco', 44, 300666343, 'jojojo@gmail.com', '', '', 'administrador', '$2y$10$RFs2671WHer01tlFxkB9r.2Qs3Rm5a30kKAjZ0i0Q7QPEuWCzLth.'),
(10, 'Enrique', 'Pacheco', NULL, 3246258786, 'andres88408489@gmail.com', NULL, NULL, 'usuario', '$2y$10$jxzZiomPbqpSZ/RFgEOTY.7yZ/FeVLxHDAak4elH.RnLQLISSiqCi'),
(11, 'michel', 'Chirinos', NULL, 3335557777, 'michelsyo2023@gmail.com', NULL, NULL, 'usuario', '$2y$10$lpAGH.dx7K1XxooP0w6ZmeE4MzQEptyH0iUZkzR4rLj5NWkTtvlHS');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `catalogo`
--
ALTER TABLE `catalogo`
  ADD PRIMARY KEY (`id_imagen`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id_cotizacion`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `catalogo`
--
ALTER TABLE `catalogo`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id_cotizacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
