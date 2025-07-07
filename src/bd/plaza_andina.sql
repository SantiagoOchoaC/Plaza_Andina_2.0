-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-07-2025 a las 01:25:48
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
-- Base de datos: `plaza_andina`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `identificación` int(10) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `contraseña` varchar(40) NOT NULL,
  `rol` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`identificación`, `nombre`, `contraseña`, `rol`) VALUES
(0, 'Pepe', '1234', 'staff'),
(1, 'Juan', '1234', 'jefemeseros'),
(2, 'Maria', '1234', 'mesero'),
(3, 'Lucas', '1234', 'cocina'),
(4, 'Messi', '1234', 'coctelero'),
(5, 'Juana', '1234', 'barra'),
(6, 'Santiago', '1234', 'admin'),
(7, 'Ana Lopez', '1234', 'mesero'),
(8, 'Clarita', '1234', 'mesero'),
(9, 'Steven', '1234', 'mesero'),
(10, 'Rochi', '1234', 'cajero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesa`
--

CREATE TABLE `mesa` (
  `id` int(3) NOT NULL,
  `estado` varchar(40) NOT NULL,
  `tipo` varchar(40) NOT NULL,
  `mesero` varchar(40) DEFAULT NULL,
  `id_mesero` int(11) DEFAULT NULL,
  `fecha_asignacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `mesa`
--

INSERT INTO `mesa` (`id`, `estado`, `tipo`, `mesero`, `id_mesero`, `fecha_asignacion`) VALUES
(1, 'ATENDIENDO', 'NORMAL', '9', NULL, '2025-06-26 16:26:16'),
(2, 'ATENDIENDO', 'NORMAL', '2', NULL, '2025-06-25 07:30:03'),
(3, 'OCUPADA', 'NORMAL', '7', NULL, '2025-06-26 04:20:53'),
(4, 'DISPONIBLE', 'NORMAL', NULL, NULL, NULL),
(5, 'OCUPADA', 'NORMAL', '9', NULL, '2025-07-06 01:55:52'),
(6, 'OCUPADA', 'NORMAL', '9', NULL, '2025-06-26 07:55:55'),
(7, 'OCUPADA', 'NORMAL', '7', NULL, '2025-06-26 04:22:29'),
(8, 'OCUPADA', 'NORMAL', '7', NULL, '2025-06-26 04:20:58'),
(9, 'DISPONIBLE', 'NORMAL', NULL, NULL, NULL),
(10, 'OCUPADA', 'NORMAL', '9', NULL, '2025-06-26 07:51:57'),
(11, 'DISPONIBLE', 'NORMAL', NULL, NULL, NULL),
(12, 'DISPONIBLE', 'NORMAL', NULL, NULL, NULL),
(13, 'OCUPADA', 'NORMAL', '8', NULL, '2025-06-25 06:53:31'),
(14, 'OCUPADA', 'NORMAL', '8', NULL, '2025-06-26 04:21:06'),
(15, 'ATENDIENDO', 'NORMAL', '2', NULL, '2025-06-25 06:53:52'),
(16, 'ATENDIENDO', 'NORMAL', '2', NULL, '2025-06-25 06:54:00'),
(17, 'ATENDIENDO', 'NORMAL', '2', NULL, '2025-06-25 06:53:56'),
(18, 'OCUPADA', 'NORMAL', '8', NULL, '2025-06-25 06:53:38'),
(19, 'OCUPADA', 'NORMAL', '8', NULL, '2025-06-25 06:54:13'),
(20, 'OCUPADA', 'NORMAL', '8', NULL, '2025-06-25 06:54:10'),
(21, 'OCUPADA', 'NORMAL', '8', NULL, '2025-06-25 06:54:21'),
(22, 'OCUPADA', 'NORMAL', '8', NULL, '2025-06-25 06:54:17'),
(23, 'ATENDIENDO', 'MADERA', '2', NULL, '2025-06-25 06:53:48'),
(24, 'OCUPADA', 'MADERA', '9', NULL, '2025-06-26 15:51:43'),
(25, 'ATENDIENDO', 'MADERA', '2', NULL, '2025-06-25 06:54:25'),
(26, 'OCUPADA', 'MADERA', '9', NULL, '2025-06-26 15:51:45'),
(27, 'ATENDIENDO', 'MADERA', '2', NULL, '2025-06-25 06:54:33'),
(28, 'ATENDIENDO', 'MADERA', '2', NULL, '2025-06-25 06:54:28'),
(29, 'OCUPADA', 'MADERA', '8', NULL, '2025-06-25 06:55:06'),
(30, 'OCUPADA', 'MADERA', '8', NULL, '2025-06-25 06:54:44'),
(31, 'OCUPADA', 'MADERA', '7', NULL, '2025-06-26 04:21:09'),
(32, 'OCUPADA', 'MADERA', '8', NULL, '2025-06-25 06:54:49'),
(33, 'OCUPADA', 'MADERA', '7', NULL, '2025-06-26 04:21:12'),
(34, 'ATENDIENDO', 'MADERA', '2', NULL, '2025-07-07 23:46:53'),
(35, 'OCUPADA', 'MADERA', '8', NULL, '2025-06-25 06:56:03'),
(36, 'OCUPADA', 'MADERA', '8', NULL, '2025-06-25 06:55:02'),
(37, 'ATENDIENDO', 'MADERA', '2', NULL, '2025-06-25 06:55:09'),
(38, 'OCUPADA', 'MADERA', '7', NULL, '2025-06-25 06:55:12'),
(39, 'OCUPADA', 'MADERA', '8', NULL, '2025-06-25 06:55:30'),
(40, 'OCUPADA', 'MADERA', '7', NULL, '2025-06-25 06:55:21'),
(41, 'ATENDIENDO', 'MADERA', '2', NULL, '2025-06-25 06:55:16'),
(42, 'OCUPADA', 'MADERA', '7', NULL, '2025-06-26 15:51:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `leida` tinyint(1) DEFAULT 0,
  `leido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(10) NOT NULL,
  `productos` varchar(100) NOT NULL,
  `estado` varchar(30) NOT NULL,
  `mesero_id` int(10) NOT NULL,
  `mesa_id` int(10) NOT NULL,
  `detalle` varchar(40) NOT NULL,
  `fecha_hora` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `productos`, `estado`, `mesero_id`, `mesa_id`, `detalle`, `fecha_hora`) VALUES
(11, '1054,1087,1133,1077', 'pagado', 9, 9, '', ''),
(22, '1002,1003,1056,1075', 'entregado', 9, 9, '', ''),
(33, '1098,1071,1065,1132', 'pagado', 9, 4, '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_general`
--

CREATE TABLE `pedido_general` (
  `id` int(11) NOT NULL,
  `fecha_hora` varchar(40) DEFAULT NULL,
  `id_mesa` varchar(40) DEFAULT NULL,
  `id_mesero` varchar(40) DEFAULT NULL,
  `estado_general` varchar(40) DEFAULT NULL,
  `estado_barra` varchar(40) DEFAULT NULL,
  `estado_cocina` varchar(40) DEFAULT NULL,
  `estado_licor` varchar(40) DEFAULT NULL,
  `total` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plaza_andina`
--

CREATE TABLE `plaza_andina` (
  `cod` int(11) NOT NULL,
  `ubicación` varchar(45) NOT NULL,
  `noMesas` int(11) NOT NULL,
  `noMeseros` varchar(45) NOT NULL,
  `noMesasMadera` int(10) NOT NULL,
  `NoMesasOcp` int(10) NOT NULL,
  `NoMesasMaderaOcp` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `plaza_andina`
--

INSERT INTO `plaza_andina` (`cod`, `ubicación`, `noMesas`, `noMeseros`, `noMesasMadera`, `NoMesasOcp`, `NoMesasMaderaOcp`) VALUES
(12345678, 'Pitalito-Huila', 22, '7', 20, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` int(10) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `tipo` varchar(10) NOT NULL,
  `precio` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `nombre`, `tipo`, `precio`) VALUES
(1001, 'Aguardiente 260ml', 'licor', 17000),
(1002, 'Coctel Daiquiri lata 295ml', 'coctel', 9900),
(1003, 'Coctel Maracuyá 250ml', 'coctel', 5850),
(1004, 'Coctel Gin Tonic 280ml', 'coctel', 24900),
(1005, 'Coctel Sandía 473ml', 'coctel', 22800),
(1006, 'Coctel Blue Lagoon 250ml', 'coctel', 15500),
(1007, 'Cóctel Bosque de Indias 295ml', 'coctel', 9900),
(1008, 'Cóctel JonRon Mojito 250ml', 'coctel', 5850),
(1009, 'Cóctel JonRon Maracuyá 250ml', 'coctel', 5850),
(1010, 'Four Loko Lulo 473ml', 'coctel', 17000),
(1011, 'Aguardiente Antioqueño tapa roja 750ml', 'licor', 113900),
(1012, 'Aguardiente Antioqueño tapa azul 750ml', 'licor', 113900),
(1013, 'Ron Viejo de Caldas 750ml', 'licor', 136900),
(1014, 'Ron Habano Club 750ml', 'licor', 230000),
(1015, 'Vodka Absolut 700ml', 'licor', 176900),
(1016, 'Vodka Smirnoff 700ml', 'licor', 168900),
(1017, 'Whisky Jameson 750ml', 'licor', 218900),
(1018, 'Whisky Chivas Regal 750ml', 'licor', 397900),
(1019, 'Whisky Glenfiddich 12 años 750ml', 'licor', 281900),
(1020, 'Whisky Old Parr 750ml', 'licor', 252900),
(1021, 'Tequila Don Julio Blanco 750ml', 'licor', 304900),
(1022, 'Tequila José Cuervo Reposado 750ml', 'licor', 191900),
(1023, 'Gin Beefeater 750ml', 'licor', 249900),
(1024, 'Gin Hendricks 750ml', 'licor', 356900),
(1025, 'Baileys crema 700ml', 'licor', 18900),
(1026, 'Cointreau 700ml', 'licor', 56900),
(1027, 'Fernet Branca 750ml', 'licor', 198900),
(1028, 'Campari 750ml', 'licor', 145000),
(1029, 'Aperitivo Limoncello 750ml', 'licor', 165000),
(1030, 'Amaro Lucano 700ml', 'licor', 147800),
(1031, 'Licor de Café Coloma 750ml', 'licor', 110500),
(1032, 'Licor Antillano premium 750ml', 'licor', 160000),
(1033, 'Ron SantaFe 750ml', 'licor', 136900),
(1034, 'Vodka Like Citrus 300ml', 'licor', 3500),
(1035, 'Vodka Like Fresh Apple 300ml', 'licor', 3500),
(1036, 'Smirnoff Ice 275ml', 'licor', 8900),
(1037, 'Smirnoff Ice Green Apple 275ml', 'licor', 8900),
(1038, 'Crema de Ron Viejo de Caldas 700ml', 'licor', 59900),
(1039, 'Licor Triple Sec 750ml', 'licor', 90000),
(1040, 'Vermut Hotel Starlino 750ml', 'licor', 99000),
(1041, 'Silver Feijoa 750ml', 'licor', 169200),
(1042, 'St. Germain 700ml', 'licor', 180000),
(1043, 'Disaronno Amaretto 750ml', 'licor', 161700),
(1044, 'Monte Stambecco Amaro 700ml', 'licor', 99000),
(1045, 'Licor de Cassis Bols 700ml', 'licor', 65000),
(1046, 'Pacharan Baines 700ml', 'licor', 174000),
(1047, 'Licor de Genjibre 750ml', 'licor', 153900),
(1048, 'Sake Momo Kawa 750ml', 'licor', 156800),
(1049, 'Licor Latte Macchiato Bottega 500ml', 'licor', 114995),
(1050, 'Licor Gianduia Bottega 500ml', 'licor', 114995),
(1051, 'Porción papas a la francesa', 'comida', 10000),
(1052, 'Alitas BBQ 300g', 'comida', 15000),
(1053, 'Salchipapas', 'comida', 15000),
(1054, 'Empanadas cocteleras x4', 'comida', 10000),
(1055, 'Buñuelos de mazorca x6', 'comida', 12000),
(1056, 'Arepa de huevo', 'comida', 5000),
(1057, 'Almojábana', 'comida', 3000),
(1058, 'Carimañola de queso', 'comida', 4000),
(1059, 'Quibbe x4', 'comida', 8000),
(1060, 'Dedito de queso x5', 'comida', 7000),
(1061, 'Cocadas x3', 'comida', 5000),
(1062, 'Raspao', 'comida', 7000),
(1063, 'Panochas', 'comida', 3000),
(1064, 'Cazuela de mariscos', 'comida', 40000),
(1065, 'Cóctel de camarón', 'comida', 35000),
(1066, 'Mote de queso', 'comida', 20000),
(1067, 'Sopa de fríjol cabeza negra', 'comida', 18000),
(1068, 'Sancocho de chicharrón', 'comida', 25000),
(1069, 'Sopa de mondongo', 'comida', 25000),
(1070, 'Mazamorra de plátano', 'comida', 15000),
(1071, 'Chuzo desgranado', 'comida', 30000),
(1072, 'Patacón con queso', 'comida', 15000),
(1073, 'Arepa dulce', 'comida', 5000),
(1074, 'Empanada de pueblo bello', 'comida', 10000),
(1075, 'Bollo de mazorca', 'comida', 5000),
(1076, 'Bolímpan', 'comida', 6000),
(1077, 'Pan de bono x4', 'comida', 8000),
(1078, 'Mojarra frita', 'comida', 30000),
(1079, 'Bagre frito', 'comida', 30000),
(1080, 'Pargo guisado', 'comida', 35000),
(1081, 'Ensalada de ñame', 'comida', 15000),
(1082, 'Fríche costeño', 'comida', 28000),
(1083, 'Lebranche frito', 'comida', 30000),
(1084, 'Huevas de pescado', 'comida', 25000),
(1085, 'Arepuelas', 'comida', 5000),
(1086, 'Galleta María Luisa', 'comida', 2000),
(1087, 'Galleta de limón', 'comida', 2000),
(1088, 'Dulces de Semana Santa', 'comida', 3000),
(1089, 'Chepacorina', 'comida', 4000),
(1090, 'Enyucado', 'comida', 3000),
(1091, 'Diabolín', 'comida', 4000),
(1092, 'Casadilla de coco', 'comida', 3000),
(1093, 'Bizcochuelo', 'comida', 5000),
(1094, 'Queque de yuca', 'comida', 6000),
(1095, 'Suero atollabuey', 'comida', 7000),
(1096, 'Café caribeño', 'comida', 12000),
(1097, 'Chicha de arroz', 'comida', 15000),
(1098, 'Chicha de piña', 'comida', 15000),
(1099, 'Jugos variados 500ml', 'comida', 5000),
(1100, 'Agua de panela 500ml', 'comida', 5000),
(1121, 'Hamburguesa 3 Carnes Res 300 g', 'comida', 20900),
(1122, 'Hamburguesa Doble Carne 200 g', 'comida', 18900),
(1123, 'Hamburguesa Carne Desmechada 150 g', 'comida', 17900),
(1124, 'Hamburguesa Trancaita 150 g', 'comida', 18990),
(1125, 'Porción de Yuca Frita', 'comida', 4700),
(1126, 'Porción de Papas Fritas', 'comida', 4700),
(1127, 'Perro Caliente Sencillo', 'comida', 9000),
(1128, 'Perro Vaquero con Chips', 'comida', 14900),
(1129, 'Perro Tradicional', 'comida', 7500),
(1130, 'Perro Cine Colombia', 'comida', 19200),
(1131, 'Hamburguesa BBq Burger Doble', 'comida', 16400),
(1132, 'Hamburguesa Crispy Chicken Bbq', 'comida', 35900),
(1133, 'Hamburguesa Whopper Jr Doble', 'comida', 23900),
(1134, 'Perro Delicioso', 'comida', 6900),
(1135, 'Perro Medellín', 'comida', 5900),
(1136, 'Perro Gourmet', 'comida', 12500),
(1137, 'Burrito Carne Desmechada', 'comida', 18900),
(1138, 'Burrito de Pollo', 'comida', 18900),
(1139, 'Hamburguesa Económica 10 500', 'comida', 10500),
(1140, 'Perro Hotdog Premium', 'comida', 21000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_barra`
--

CREATE TABLE `ticket_barra` (
  `cod` int(10) NOT NULL,
  `cant` int(10) DEFAULT NULL,
  `detalle` varchar(40) DEFAULT NULL,
  `pedido_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_comida`
--

CREATE TABLE `ticket_comida` (
  `cod` int(10) NOT NULL,
  `cant` int(10) DEFAULT NULL,
  `detalle` varchar(40) DEFAULT NULL,
  `pedido_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_licor`
--

CREATE TABLE `ticket_licor` (
  `cod` int(10) NOT NULL,
  `cant` int(10) DEFAULT NULL,
  `detalle` varchar(40) DEFAULT NULL,
  `pedido_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`identificación`);

--
-- Indices de la tabla `mesa`
--
ALTER TABLE `mesa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_mesero` (`id_mesero`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`);

--
-- Indices de la tabla `pedido_general`
--
ALTER TABLE `pedido_general`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `plaza_andina`
--
ALTER TABLE `plaza_andina`
  ADD PRIMARY KEY (`cod`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `ticket_barra`
--
ALTER TABLE `ticket_barra`
  ADD PRIMARY KEY (`pedido_id`,`cod`);

--
-- Indices de la tabla `ticket_comida`
--
ALTER TABLE `ticket_comida`
  ADD PRIMARY KEY (`pedido_id`,`cod`);

--
-- Indices de la tabla `ticket_licor`
--
ALTER TABLE `ticket_licor`
  ADD PRIMARY KEY (`pedido_id`,`cod`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido_general`
--
ALTER TABLE `pedido_general`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `mesa`
--
ALTER TABLE `mesa`
  ADD CONSTRAINT `mesa_ibfk_1` FOREIGN KEY (`id_mesero`) REFERENCES `empleado` (`identificación`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`identificación`);

--
-- Filtros para la tabla `ticket_barra`
--
ALTER TABLE `ticket_barra`
  ADD CONSTRAINT `ticket_barra_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedido_general` (`id`);

--
-- Filtros para la tabla `ticket_comida`
--
ALTER TABLE `ticket_comida`
  ADD CONSTRAINT `ticket_comida_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedido_general` (`id`);

--
-- Filtros para la tabla `ticket_licor`
--
ALTER TABLE `ticket_licor`
  ADD CONSTRAINT `ticket_licor_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedido_general` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
