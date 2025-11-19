-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251031.ff9df302b7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 19, 2025 at 11:15 PM
-- Server version: 8.4.3
-- PHP Version: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `odontosmart_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `agendar_medico`
--

CREATE TABLE `agendar_medico` (
  `id_agenda` int NOT NULL,
  `id_odontologo` int NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `disponible` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `atencion_cita`
--

CREATE TABLE `atencion_cita` (
  `id_atencion` int NOT NULL,
  `id_cita` int NOT NULL,
  `hora_llegada` datetime DEFAULT NULL,
  `hora_inicio_atencion` datetime DEFAULT NULL,
  `hora_fin_atencion` datetime DEFAULT NULL,
  `observaciones` text,
  `requiere_control` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_stock`
--

CREATE TABLE `auditoria_stock` (
  `id_auditoria` int NOT NULL,
  `id_producto` int NOT NULL,
  `id_lote` int DEFAULT NULL,
  `cantidad_movida` int NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `motivo` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario_responsable` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bitacoras`
--

CREATE TABLE `bitacoras` (
  `id_bitacora` bigint NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `accion` varchar(255) DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(50) DEFAULT NULL,
  `detalles` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carrito`
--

CREATE TABLE `carrito` (
  `id_carrito` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carrito_detalle`
--

CREATE TABLE `carrito_detalle` (
  `id_detalle` int NOT NULL,
  `id_carrito` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categoria_productos`
--

CREATE TABLE `categoria_productos` (
  `id_categoria` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `citas`
--

CREATE TABLE `citas` (
  `id_cita` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_odontologo` int NOT NULL,
  `fecha_cita` datetime NOT NULL,
  `estado` enum('pendiente','confirmada','cancelada','atendida') DEFAULT 'pendiente',
  `motivo` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(120) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle` int NOT NULL,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `id_lote` int DEFAULT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historial_clinico`
--

CREATE TABLE `historial_clinico` (
  `id_historial` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_cita` int NOT NULL,
  `descripcion` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventario_ingresos`
--

CREATE TABLE `inventario_ingresos` (
  `id_ingreso` int NOT NULL,
  `id_producto` int NOT NULL,
  `id_lote` int DEFAULT NULL,
  `cantidad` int NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `registrado_por` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lote_producto`
--

CREATE TABLE `lote_producto` (
  `id_lote` int NOT NULL,
  `id_producto` int NOT NULL,
  `numero_lote` varchar(50) NOT NULL,
  `fecha_caducidad` date NOT NULL,
  `cantidad` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `odontologos`
--

CREATE TABLE `odontologos` (
  `id_odontologo` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(120) DEFAULT NULL,
  `id_usuario` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int NOT NULL,
  `id_venta` int NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  `metodo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permisos`
--

CREATE TABLE `permisos` (
  `id_permiso` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `id_producto` int NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text,
  `unidad` varchar(50) DEFAULT NULL,
  `id_categoria` int DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `costo_unidad` decimal(10,2) DEFAULT NULL,
  `stock_total` int NOT NULL,
  `stock_minimo` int DEFAULT '0',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_caducidad` date DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id_rol` int NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`, `descripcion`) VALUES
(1, 'Administrador', 'Acceso completo al sistema, gestión de usuarios, inventario y reportes'),
(2, 'Médico', 'Acceso a agenda, citas y historial clínico de pacientes'),
(3, 'Cliente', 'Acceso limitado para agendar citas y consultar información personal'),
(4, 'Recepcionista', 'Gestión de citas, clientes y apoyo administrativo');

-- --------------------------------------------------------

--
-- Table structure for table `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id_rol_permiso` int NOT NULL,
  `id_rol` int NOT NULL,
  `id_permiso` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `identificacion` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_rol` int NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_completo`, `email`, `telefono`, `identificacion`, `password`, `id_rol`, `fecha_creacion`, `estado`) VALUES
(1, 'Josue Acuna Flores', 'josueacunaflores@gmail.com', '62043116', '207820616', 'Jacuna546*', 1, '2025-11-18 21:08:03', ''),
(2, 'brayan', 'brayan@correo.com', '1', '1', '$2y$12$jw1a3U3OjSqYNxmKN/SGZO7n9TSGtiMtpFEd6a9oC2EqXTIKx5K1y', 1, '2025-11-19 17:12:55', 'activo');

-- --------------------------------------------------------

--
-- Table structure for table `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_cliente` int NOT NULL,
  `fecha_venta` datetime DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `impuestos` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agendar_medico`
--
ALTER TABLE `agendar_medico`
  ADD PRIMARY KEY (`id_agenda`),
  ADD KEY `id_odontologo` (`id_odontologo`);

--
-- Indexes for table `atencion_cita`
--
ALTER TABLE `atencion_cita`
  ADD PRIMARY KEY (`id_atencion`),
  ADD KEY `id_cita` (`id_cita`);

--
-- Indexes for table `auditoria_stock`
--
ALTER TABLE `auditoria_stock`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `usuario_responsable` (`usuario_responsable`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indexes for table `bitacoras`
--
ALTER TABLE `bitacoras`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id_carrito`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `carrito_detalle`
--
ALTER TABLE `carrito_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_carrito` (`id_carrito`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indexes for table `categoria_productos`
--
ALTER TABLE `categoria_productos`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indexes for table `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_odontologo` (`id_odontologo`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indexes for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indexes for table `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_cita` (`id_cita`);

--
-- Indexes for table `inventario_ingresos`
--
ALTER TABLE `inventario_ingresos`
  ADD PRIMARY KEY (`id_ingreso`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indexes for table `lote_producto`
--
ALTER TABLE `lote_producto`
  ADD PRIMARY KEY (`id_lote`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indexes for table `odontologos`
--
ALTER TABLE `odontologos`
  ADD PRIMARY KEY (`id_odontologo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_venta` (`id_venta`);

--
-- Indexes for table `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indexes for table `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`id_rol_permiso`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`email`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indexes for table `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agendar_medico`
--
ALTER TABLE `agendar_medico`
  MODIFY `id_agenda` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `atencion_cita`
--
ALTER TABLE `atencion_cita`
  MODIFY `id_atencion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_stock`
--
ALTER TABLE `auditoria_stock`
  MODIFY `id_auditoria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bitacoras`
--
ALTER TABLE `bitacoras`
  MODIFY `id_bitacora` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id_carrito` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carrito_detalle`
--
ALTER TABLE `carrito_detalle`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categoria_productos`
--
ALTER TABLE `categoria_productos`
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historial_clinico`
--
ALTER TABLE `historial_clinico`
  MODIFY `id_historial` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventario_ingresos`
--
ALTER TABLE `inventario_ingresos`
  MODIFY `id_ingreso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lote_producto`
--
ALTER TABLE `lote_producto`
  MODIFY `id_lote` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `odontologos`
--
ALTER TABLE `odontologos`
  MODIFY `id_odontologo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rol_permisos`
--
ALTER TABLE `rol_permisos`
  MODIFY `id_rol_permiso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agendar_medico`
--
ALTER TABLE `agendar_medico`
  ADD CONSTRAINT `agendar_medico_ibfk_1` FOREIGN KEY (`id_odontologo`) REFERENCES `odontologos` (`id_odontologo`);

--
-- Constraints for table `atencion_cita`
--
ALTER TABLE `atencion_cita`
  ADD CONSTRAINT `atencion_cita_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`);

--
-- Constraints for table `auditoria_stock`
--
ALTER TABLE `auditoria_stock`
  ADD CONSTRAINT `auditoria_stock_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `auditoria_stock_ibfk_2` FOREIGN KEY (`usuario_responsable`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `auditoria_stock_ibfk_3` FOREIGN KEY (`id_lote`) REFERENCES `lote_producto` (`id_lote`);

--
-- Constraints for table `bitacoras`
--
ALTER TABLE `bitacoras`
  ADD CONSTRAINT `bitacoras_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Constraints for table `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Constraints for table `carrito_detalle`
--
ALTER TABLE `carrito_detalle`
  ADD CONSTRAINT `carrito_detalle_ibfk_1` FOREIGN KEY (`id_carrito`) REFERENCES `carrito` (`id_carrito`),
  ADD CONSTRAINT `carrito_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Constraints for table `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`id_odontologo`) REFERENCES `odontologos` (`id_odontologo`);

--
-- Constraints for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`),
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `detalle_venta_ibfk_3` FOREIGN KEY (`id_lote`) REFERENCES `lote_producto` (`id_lote`);

--
-- Constraints for table `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD CONSTRAINT `historial_clinico_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `historial_clinico_ibfk_2` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`);

--
-- Constraints for table `inventario_ingresos`
--
ALTER TABLE `inventario_ingresos`
  ADD CONSTRAINT `inventario_ingresos_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `inventario_ingresos_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `inventario_ingresos_ibfk_3` FOREIGN KEY (`id_lote`) REFERENCES `lote_producto` (`id_lote`);

--
-- Constraints for table `lote_producto`
--
ALTER TABLE `lote_producto`
  ADD CONSTRAINT `lote_producto_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Constraints for table `odontologos`
--
ALTER TABLE `odontologos`
  ADD CONSTRAINT `odontologos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Constraints for table `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`);

--
-- Constraints for table `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_productos` (`id_categoria`);

--
-- Constraints for table `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  ADD CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`);

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

--
-- Constraints for table `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
