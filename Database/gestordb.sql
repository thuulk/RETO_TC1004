-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-11-2025 a las 04:42:54
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

CREATE DATABASE IF NOT EXISTS gestordb;
USE gestordb;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestordb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alarmas`
--

CREATE TABLE `alarmas` (
  `fechaAl` date NOT NULL,
  `horaAl` time NOT NULL,
  `alHumedad` tinyint(1) NOT NULL,
  `alTemperatura` tinyint(1) NOT NULL,
  `alCalidad_Aire` tinyint(1) NOT NULL,
  `alPresion` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas`
--

CREATE TABLE `consultas` (
  `consultaID` int(11) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `consultaFecha` date NOT NULL,
  `consultaHora` time NOT NULL,
  `datosFecha` date NOT NULL,
  `datosHora` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datosgenerales`
--

CREATE TABLE `datosgenerales` (
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `humedad` double NOT NULL,
  `temperatura` double NOT NULL,
  `calidad_Aire` double NOT NULL,
  `presion` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `matricula` varchar(20) NOT NULL,
  `contraseña` varchar(20) NOT NULL,
  `rol` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`matricula`, `contraseña`, `rol`) VALUES
('A0001', '12345', 'admin'),
('A00228324', '1234', 'empleado');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alarmas`
--
ALTER TABLE `alarmas`
  ADD PRIMARY KEY (`fechaAl`,`horaAl`);

--
-- Indices de la tabla `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`consultaID`),
  ADD KEY `matricula` (`matricula`),
  ADD KEY `datosFecha` (`datosFecha`,`datosHora`);

--
-- Indices de la tabla `datosgenerales`
--
ALTER TABLE `datosgenerales`
  ADD PRIMARY KEY (`fecha`,`hora`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`matricula`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `consultas`
--
ALTER TABLE `consultas`
  MODIFY `consultaID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alarmas`
--
ALTER TABLE `alarmas`
  ADD CONSTRAINT `alarmas_ibfk_1` FOREIGN KEY (`fechaAl`,`horaAl`) REFERENCES `datosgenerales` (`fecha`, `hora`);

--
-- Filtros para la tabla `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`matricula`) REFERENCES `usuarios` (`matricula`),
  ADD CONSTRAINT `consultas_ibfk_2` FOREIGN KEY (`datosFecha`,`datosHora`) REFERENCES `datosgenerales` (`fecha`, `hora`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
