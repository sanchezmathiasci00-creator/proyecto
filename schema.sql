-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-12-2025 a las 12:46:15
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id_especialidades` int(11) NOT NULL,
  `nm_especialidades` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id_turnos` int(11) NOT NULL,
  `nm_turnos` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id_especialidades` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id_turnos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

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
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`dni_practicante`) REFERENCES `practicantes` (`dni`) ON DELETE SET NULL ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;