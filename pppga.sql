-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-12-2025 a las 02:46:00
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
-- Base de datos: `pppga`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL,
  `dni_practicante` int(8) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_ingreso` time NOT NULL,
  `hora_salida` time NOT NULL,
  `horas_dia` decimal(4,2) GENERATED ALWAYS AS (timestampdiff(MINUTE,`hora_ingreso`,`hora_salida`) / 60) STORED,
  `tareas_realizadas` text DEFAULT NULL,
  `firma` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `estado` enum('EN_CURSO','COMPLETADO') DEFAULT 'EN_CURSO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`id_asistencia`, `dni_practicante`, `id_modulo`, `fecha`, `hora_ingreso`, `hora_salida`, `tareas_realizadas`, `firma`, `id_usuario`, `estado`) VALUES
(8, 72894561, 1, '2025-10-29', '10:34:00', '18:34:27', NULL, NULL, NULL, 'COMPLETADO'),
(9, 72894561, 1, '2025-10-30', '10:35:08', '16:35:15', NULL, NULL, NULL, 'COMPLETADO'),
(10, 72894561, 1, '2025-10-31', '16:30:50', '22:20:04', NULL, NULL, NULL, 'COMPLETADO'),
(11, 72894561, 1, '2025-10-01', '13:23:53', '20:24:05', NULL, NULL, NULL, 'COMPLETADO'),
(14, 74660226, 9, '2025-12-02', '19:57:18', '20:31:47', 'Instalación de sistemas operativos', NULL, NULL, 'COMPLETADO'),
(15, 72894561, 1, '2025-12-02', '20:26:28', '20:28:34', NULL, NULL, NULL, 'COMPLETADO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id_especialidades` int(11) NOT NULL,
  `nm_especialidades` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id_especialidades`, `nm_especialidades`) VALUES
(1, 'Gestión Administrativa'),
(2, 'Administración de Empresas'),
(3, 'Computación e Informática'),
(4, 'Desarrollo de Sistemas de Información'),
(5, 'Contabilidad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id_modulos` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `nm_modulos` varchar(255) NOT NULL,
  `horas_requeridas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id_modulos`, `id_especialidad`, `nm_modulos`, `horas_requeridas`) VALUES
(1, 2, 'Gestión Administrativa', 286),
(2, 2, 'Gestión de la Comercialización', 296),
(3, 2, 'Gestión de Recursos Financieros y Proyectos', 296),
(4, 1, 'Procedimientos Administrativos', 128),
(5, 1, 'Presupuestos y Servicios Especializados', 128),
(6, 1, 'Administración de Recursos y Supervisión del Plan Operativo', 128),
(7, 3, 'Soporte Técnico', 286),
(8, 3, 'Base de Datos', 296),
(9, 3, 'Multimedia', 296),
(10, 4, 'Gestión de Soporte y Tecnologías de Desarrollo de Software', 128),
(11, 4, 'Gestión de Base de Datos y Análisis de Sistemas de Información', 128),
(12, 4, 'Gestión de Calidad de Sistemas de Información', 128),
(13, 5, 'Desarrollo de Procesos Contables', 128),
(14, 5, 'Formulación y Supervisión de Operaciones Contables', 128),
(15, 5, 'Análisis Financiero', 128);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicantes`
--

CREATE TABLE `practicantes` (
  `dni` int(11) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `apellidos` varchar(255) NOT NULL,
  `semestre` varchar(255) NOT NULL,
  `id_especialidades` int(11) DEFAULT NULL,
  `id_modulos` int(11) DEFAULT NULL,
  `id_turnos` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `foto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practicantes`
--

INSERT INTO `practicantes` (`dni`, `nombres`, `apellidos`, `semestre`, `id_especialidades`, `id_modulos`, `id_turnos`, `id_usuario`, `foto`) VALUES
(72894561, 'Jose Antonio', 'Sanchez Gutierrez', 'VI', 2, 1, 2, 4, 'uploads/fotos_perfil/72894561_1764721737.png'),
(74660226, 'Luis Enrique', 'Aguilar Achas', 'VI', 3, 9, 2, 6, 'uploads/fotos_perfil/74660226_1764722785.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progreso_modulo`
--

CREATE TABLE `progreso_modulo` (
  `id_progreso` int(11) NOT NULL,
  `dni_practicante` int(8) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `horas_acumuladas` int(11) DEFAULT 0,
  `estado` enum('EN_CURSO','COMPLETADO') DEFAULT 'EN_CURSO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Administrador', 'Acceso total al sistema', 1, '2025-10-29 09:00:04', '2025-10-29 09:00:04'),
(2, 'Usuario', 'Acceso limitado al sistema', 1, '2025-10-29 09:00:04', '2025-10-29 09:00:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id_turnos` int(11) NOT NULL,
  `nm_turnos` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id_turnos`, `nm_turnos`) VALUES
(1, 'Diurno'),
(2, 'Nocturno');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `dni_practicante` int(8) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `cargo` varchar(255) DEFAULT NULL,
  `usuario` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('PRACTICANTE','ASISTENTE','COORDINADOR','ADMIN') DEFAULT 'PRACTICANTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `dni_practicante`, `nombre`, `cargo`, `usuario`, `clave`, `rol`) VALUES
(1, NULL, 'Elmer Negrón', 'Coordinador', 'elmern', '1234', 'COORDINADOR'),
(2, NULL, 'Yoselin Benites', 'Asistente', 'yoselinb', '1234', 'ASISTENTE'),
(3, NULL, 'Harvey Sánchez', 'SysAdmin', 'harveys', 'alessia07', 'ADMIN'),
(4, 72894561, 'José Sanchez', 'Practicante', 'joses', '1234', 'PRACTICANTE'),
(6, 74660226, 'Luis Aguilar', 'Practicante', 'luisa', '1234', 'PRACTICANTE');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios2`
--

CREATE TABLE `usuarios2` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `dni` varchar(15) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuarios2`
--

INSERT INTO `usuarios2` (`id_usuario`, `id_rol`, `nombre`, `apellido`, `correo`, `usuario`, `clave`, `telefono`, `dni`, `direccion`, `foto_perfil`, `estado`, `ultimo_acceso`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'Benjamín', 'Huanca', 'admin@midominio.com', 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', '999888777', NULL, NULL, NULL, 1, NULL, '2025-10-29 09:00:04', '2025-10-29 09:00:04'),
(2, 2, 'Luis', 'Ramírez', 'lramirez@correo.com', 'lramirez', 'dfa7a2273567dcd1efffb9a46308e91c20fa13c44c3441bc69cd6a7869b3f7fd', '988776655', NULL, NULL, NULL, 1, NULL, '2025-10-29 09:00:04', '2025-10-29 09:00:04');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `dni_practicante` (`dni_practicante`),
  ADD KEY `id_modulo` (`id_modulo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id_especialidades`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id_modulos`),
  ADD KEY `fk_modulos_especialidades` (`id_especialidad`);

--
-- Indices de la tabla `practicantes`
--
ALTER TABLE `practicantes`
  ADD PRIMARY KEY (`dni`),
  ADD KEY `id_especialidades` (`id_especialidades`),
  ADD KEY `id_modulos` (`id_modulos`),
  ADD KEY `id_turnos` (`id_turnos`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `progreso_modulo`
--
ALTER TABLE `progreso_modulo`
  ADD PRIMARY KEY (`id_progreso`),
  ADD KEY `dni_practicante` (`dni_practicante`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id_turnos`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `dni_practicante` (`dni_practicante`);

--
-- Indices de la tabla `usuarios2`
--
ALTER TABLE `usuarios2`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_usuario_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id_especialidades` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `progreso_modulo`
--
ALTER TABLE `progreso_modulo`
  MODIFY `id_progreso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id_turnos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios2`
--
ALTER TABLE `usuarios2`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`dni_practicante`) REFERENCES `practicantes` (`dni`),
  ADD CONSTRAINT `asistencia_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulos`),
  ADD CONSTRAINT `asistencia_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD CONSTRAINT `fk_modulos_especialidades` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidades`);

--
-- Filtros para la tabla `practicantes`
--
ALTER TABLE `practicantes`
  ADD CONSTRAINT `practicantes_ibfk_1` FOREIGN KEY (`id_especialidades`) REFERENCES `especialidades` (`id_especialidades`),
  ADD CONSTRAINT `practicantes_ibfk_2` FOREIGN KEY (`id_modulos`) REFERENCES `modulos` (`id_modulos`),
  ADD CONSTRAINT `practicantes_ibfk_3` FOREIGN KEY (`id_turnos`) REFERENCES `turnos` (`id_turnos`),
  ADD CONSTRAINT `practicantes_ibfk_4` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `progreso_modulo`
--
ALTER TABLE `progreso_modulo`
  ADD CONSTRAINT `progreso_modulo_ibfk_1` FOREIGN KEY (`dni_practicante`) REFERENCES `practicantes` (`dni`),
  ADD CONSTRAINT `progreso_modulo_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulos`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`dni_practicante`) REFERENCES `practicantes` (`dni`);

--
-- Filtros para la tabla `usuarios2`
--
ALTER TABLE `usuarios2`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
