-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251031.ff9df302b7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 24, 2025 at 08:10 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_citas_admin_accion` (IN `p_accion` VARCHAR(50), IN `p_id_cita` INT, IN `p_observaciones` TEXT, IN `p_requiere_control` TINYINT, IN `p_id_usuario` INT, IN `p_ip` VARCHAR(50), OUT `p_resultado` VARCHAR(50))   BEGIN
    DECLARE v_msg VARCHAR(255);


    IF p_accion IN ('registrar_llegada','iniciar_atencion','finalizar_atencion','guardar_atencion') THEN
        INSERT INTO atencion_cita (id_cita)
        SELECT p_id_cita
        WHERE NOT EXISTS (
            SELECT 1 FROM atencion_cita WHERE id_cita = p_id_cita
        );
    END IF;


    IF p_accion = 'registrar_llegada' THEN
        
        UPDATE atencion_cita
        SET hora_llegada = NOW()
        WHERE id_cita = p_id_cita;
        
        SET v_msg = 'Hora de llegada registrada';

    ELSEIF p_accion = 'iniciar_atencion' THEN
        
        UPDATE atencion_cita
        SET hora_inicio_atencion = NOW()
        WHERE id_cita = p_id_cita;
        
        SET v_msg = 'Hora de inicio registrada';

    ELSEIF p_accion = 'finalizar_atencion' THEN
        
        UPDATE atencion_cita
        SET hora_fin_atencion = NOW()
        WHERE id_cita = p_id_cita;
        
        SET v_msg = 'Hora de fin registrada';

    ELSEIF p_accion = 'cancelar_cita' THEN
        
        UPDATE citas
        SET estado = 'cancelada'
        WHERE id_cita = p_id_cita;
        
        SET v_msg = 'Cita cancelada';

    ELSEIF p_accion = 'guardar_atencion' THEN
        
        UPDATE atencion_cita
        SET observaciones    = p_observaciones,
            requiere_control = p_requiere_control
        WHERE id_cita = p_id_cita;

        UPDATE citas
        SET estado = 'atendida'
        WHERE id_cita = p_id_cita;

        SET v_msg = 'Atención guardada';

    END IF;

    IF ROW_COUNT() > 0 THEN
        SET p_resultado = 'OK';
    ELSE
        SET p_resultado = 'SIN_CAMBIO';
    END IF;


    INSERT INTO bitacoras(id_usuario, accion, ip, detalles)
    VALUES(
        p_id_usuario,
        CONCAT('Gestión cita: ', p_accion),
        p_ip,
        CONCAT('Cita ID ', p_id_cita, '. ', IFNULL(v_msg,''))
    );

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_citas_crear` (IN `p_id_cliente` INT, IN `p_id_odontologo` INT, IN `p_fecha_cita` DATETIME, IN `p_motivo` VARCHAR(255), IN `p_id_usuario` INT, IN `p_ip` VARCHAR(50), OUT `p_resultado` VARCHAR(50))   BEGIN
    DECLARE
        v_id_cita INT ;
        -- Insertar la cita
    INSERT INTO citas(
        id_cliente,
        id_odontologo,
        fecha_cita,
        estado,
        motivo
    )
VALUES(
    p_id_cliente,
    p_id_odontologo,
    p_fecha_cita,
    'pendiente',
    p_motivo
) ; IF ROW_COUNT() > 0 THEN
SET
    v_id_cita = LAST_INSERT_ID() ;
    -- Registrar en bitácora
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    p_id_usuario,
    'Cita agendada',
    p_ip,
    CONCAT(
        'Cita ID ',
        v_id_cita,
        ' para fecha ',
        p_fecha_cita
    )
) ;
SET
    p_resultado = 'OK' ; ELSE
SET
    p_resultado = 'ERROR' ;
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    p_id_usuario,
    'Error al agendar cita',
    p_ip,
    CONCAT(
        'No se pudo crear cita para fecha ',
        p_fecha_cita
    )
) ;
    END IF ; END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_usuario` (IN `p_nombre_completo` VARCHAR(255), IN `p_email` VARCHAR(255), IN `p_password` VARCHAR(255), IN `p_id_rol` INT, IN `p_telefono` VARCHAR(50), IN `p_identificacion` VARCHAR(50), IN `p_ip` VARCHAR(50), OUT `p_resultado` VARCHAR(255))   BEGIN
    DECLARE
        existe INT DEFAULT 0 ;
        -- Validar duplicados
    SELECT
        COUNT(*)
    INTO existe
FROM
    usuarios
WHERE
    email = p_email OR nombre_completo = p_nombre_completo OR identificacion = p_identificacion ; IF existe > 0 THEN
SET
    p_resultado = 'DUPLICADO' ;
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    NULL,
    'Intento fallido de creación',
    p_ip,
    'Datos duplicados'
) ; ELSE
-- Insertar usuario
INSERT INTO usuarios(
    nombre_completo,
    email,
    PASSWORD,
    id_rol,
    telefono,
    identificacion
)
VALUES(
    p_nombre_completo,
    p_email,
    p_password,
    p_id_rol,
    p_telefono,
    p_identificacion
) ;
-- Registrar bitácora
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    LAST_INSERT_ID(), 'Usuario creado', p_ip, CONCAT('Usuario: ', p_email)) ;
SET
    p_resultado = 'OK' ;
END IF ; END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_productos_crear` (IN `p_id_categoria` INT, IN `p_nombre` VARCHAR(255), IN `p_descripcion` TEXT, IN `p_unidad` VARCHAR(50), IN `p_precio` DECIMAL(10,2), IN `p_costo_unidad` DECIMAL(10,2), IN `p_stock_total` INT, IN `p_stock_minimo` INT, IN `p_fecha_caducidad` DATE, IN `p_id_usuario` INT, IN `p_ip` VARCHAR(50), OUT `p_resultado` VARCHAR(50))   BEGIN
    DECLARE
        existe INT DEFAULT 0 ;
    SELECT
        COUNT(*)
    INTO existe
FROM
    productos
WHERE
    nombre = p_nombre AND id_categoria = p_id_categoria ; IF existe > 0 THEN
SET
    p_resultado = 'DUPLICADO' ;
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    p_id_usuario,
    'Intento fallido de creación de producto',
    p_ip,
    CONCAT('Producto duplicado: ', p_nombre)
) ; ELSE
INSERT INTO productos(
    id_categoria,
    nombre,
    descripcion,
    unidad,
    precio,
    costo_unidad,
    stock_total,
    stock_minimo,
    fecha_caducidad,
    estado
)
VALUES(
    p_id_categoria,
    p_nombre,
    p_descripcion,
    p_unidad,
    p_precio,
    p_costo_unidad,
    p_stock_total,
    p_stock_minimo,
    p_fecha_caducidad,
    'activo'
) ;
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    p_id_usuario,
    'Producto creado',
    p_ip,
    CONCAT(
        'Producto: ',
        p_nombre,
        ' (cat ',
        p_id_categoria,
        ')'
    )
) ;
SET
    p_resultado = 'OK' ;
END IF ; END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_usuarios_actualizar` (IN `p_id_usuario` INT, IN `p_nombre_completo` VARCHAR(255), IN `p_email` VARCHAR(255), IN `p_telefono` VARCHAR(50), IN `p_identificacion` VARCHAR(50), IN `p_id_rol` INT, IN `p_ip` VARCHAR(50), OUT `p_resultado` VARCHAR(50))   BEGIN
    DECLARE
        existe INT DEFAULT 0 ;
        -- Verificar si hay OTRO usuario con el mismo correo o cédula
    SELECT
        COUNT(*)
    INTO existe
FROM
    usuarios
WHERE
    (
        email = p_email OR identificacion = p_identificacion
    ) AND id_usuario <> p_id_usuario ; IF existe > 0 THEN
SET
    p_resultado = 'DUPLICADO' ;
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    p_id_usuario,
    'Intento fallido de actualización',
    p_ip,
    'Correo o identificación duplicados'
) ; ELSE
UPDATE
    usuarios
SET
    nombre_completo = p_nombre_completo,
    email = p_email,
    telefono = p_telefono,
    identificacion = p_identificacion,
    id_rol = p_id_rol
WHERE
    id_usuario = p_id_usuario ;
INSERT INTO bitacoras(
    id_usuario,
    accion,
    ip,
    detalles
)
VALUES(
    p_id_usuario,
    'Usuario actualizado',
    p_ip,
    CONCAT('Usuario actualizado: ', p_email)
) ;
SET
    p_resultado = 'OK' ;
END IF ; END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ventas_registrar_bitacora` (IN `p_id_usuario` INT, IN `p_id_venta` INT, IN `p_total` DECIMAL(10,2), IN `p_ip` VARCHAR(50), OUT `p_resultado` VARCHAR(20))   BEGIN
    INSERT INTO bitacoras(
        id_usuario,
        accion,
        ip,
        detalles
    )
VALUES(
    p_id_usuario,
    'Venta registrada',
    p_ip,
    CONCAT(
        'Venta ID ',
        p_id_venta,
        ' por ',
        p_total
    )
) ;
SET
    p_resultado = 'OK' ; END$$

DELIMITER ;

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

--
-- Dumping data for table `atencion_cita`
--

INSERT INTO `atencion_cita` (`id_atencion`, `id_cita`, `hora_llegada`, `hora_inicio_atencion`, `hora_fin_atencion`, `observaciones`, `requiere_control`) VALUES
(1, 4, '2025-11-23 13:40:17', NULL, NULL, 'Proxima cita, seguir con el control.', 1),
(2, 11, '2025-11-24 11:09:38', NULL, NULL, NULL, 0),
(3, 12, NULL, NULL, NULL, 'Termina el control por seis meses.', 1);

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

--
-- Dumping data for table `bitacoras`
--

INSERT INTO `bitacoras` (`id_bitacora`, `id_usuario`, `accion`, `fecha`, `ip`, `detalles`) VALUES
(1, 9, 'Usuario creado', '2025-11-23 18:59:24', '::1', 'Usuario: valeria@hotmail.com'),
(2, 10, 'Usuario creado', '2025-11-24 09:36:53', '::1', 'Usuario: pandora@gmail.com'),
(3, 11, 'Usuario creado', '2025-11-24 10:11:16', '::1', 'Usuario: sofia@gmail.com'),
(4, 12, 'Usuario creado', '2025-11-24 10:12:10', '::1', 'Usuario: Hector@gmail.com'),
(5, 13, 'Usuario creado', '2025-11-24 10:12:54', '::1', 'Usuario: ariana@gmail.com'),
(6, 10, 'Usuario actualizado', '2025-11-24 10:37:29', '::1', 'Usuario actualizado: pandora@gmail.com'),
(7, 9, 'Cita agendada', '2025-11-24 10:43:39', '::1', 'Cita ID 12 para fecha 2025-12-03 09:00:00'),
(8, 7, 'Gestión cita: registrar_llegada', '2025-11-24 11:09:38', '::1', 'Cita ID 11. Hora de llegada registrada'),
(9, 7, 'Gestión cita: guardar_atencion', '2025-11-24 11:11:07', '::1', 'Cita ID 12. Atención guardada'),
(10, 7, 'Gestión cita: cancelar_cita', '2025-11-24 11:11:10', '::1', 'Cita ID 10. Cita cancelada'),
(11, 7, 'Producto creado', '2025-11-24 11:21:20', '::1', 'Producto: Resina (cat 3)'),
(12, 9, 'Venta registrada', '2025-11-24 11:37:15', '::1', 'Venta ID 2 por 20000.00'),
(13, 9, 'Cita agendada', '2025-11-24 11:37:48', '::1', 'Cita ID 13 para fecha 2025-11-28 14:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `carrito`
--

CREATE TABLE `carrito` (
  `id_carrito` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `carrito`
--

INSERT INTO `carrito` (`id_carrito`, `id_usuario`, `fecha_creacion`) VALUES
(1, 7, '2025-11-22 03:22:54');

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

--
-- Dumping data for table `carrito_detalle`
--

INSERT INTO `carrito_detalle` (`id_detalle`, `id_carrito`, `id_producto`, `cantidad`) VALUES
(1, 1, 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `categoria_productos`
--

CREATE TABLE `categoria_productos` (
  `id_categoria` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categoria_productos`
--

INSERT INTO `categoria_productos` (`id_categoria`, `nombre`, `descripcion`) VALUES
(1, 'Medicamentos', NULL),
(2, 'Servicios', NULL),
(3, 'Equipo médico complejo', NULL),
(4, 'Instrumento dental', NULL),
(5, 'Productos de higiene', NULL);

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

--
-- Dumping data for table `citas`
--

INSERT INTO `citas` (`id_cita`, `id_cliente`, `id_odontologo`, `fecha_cita`, `estado`, `motivo`) VALUES
(2, 8, 1, '2025-11-25 10:30:00', 'cancelada', 'Extraccion de muelas.'),
(3, 8, 2, '2025-11-24 09:30:00', 'cancelada', 'Caries en varios dientes.'),
(4, 8, 2, '2025-11-26 10:30:00', 'atendida', 'Revision general.'),
(5, 8, 2, '2025-11-24 11:00:00', 'pendiente', 'Revision general.'),
(6, 8, 2, '2025-11-24 08:00:00', 'pendiente', ''),
(10, 8, 2, '2025-12-01 09:00:00', 'atendida', ''),
(11, 11, 2, '2025-12-03 13:00:00', 'pendiente', 'Cita'),
(12, 10, 2, '2025-12-03 09:00:00', 'atendida', ''),
(13, 10, 5, '2025-11-28 14:30:00', 'pendiente', '');

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL,
  `id_usuario` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(120) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `id_usuario`, `nombre`, `apellido`, `telefono`, `correo`, `fecha_registro`) VALUES
(8, 8, 'Brayan Aguilar', '', '85743426', 'brayan@gmail.com', '2025-11-23 10:59:41'),
(9, 3, 'Admin', 'Admin', '85102283', 'admin@gmail.com', '2025-11-24 09:43:25'),
(10, 9, 'Valeria', 'Bolanos', '85743422', 'valeria@hotmail.com', '2025-11-24 09:43:25'),
(11, 10, 'Pandora', 'Aguilar', '817743429', 'pandora@gmail.com', '2025-11-24 09:43:25');

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

--
-- Dumping data for table `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_detalle`, `id_venta`, `id_producto`, `id_lote`, `cantidad`, `precio_unitario`, `total`) VALUES
(1, 1, 5, NULL, 1, 20000.00, 20000.00),
(2, 2, 5, NULL, 1, 20000.00, 20000.00),
(3, 2, 5, NULL, 1, 20000.00, 20000.00);

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

--
-- Dumping data for table `odontologos`
--

INSERT INTO `odontologos` (`id_odontologo`, `nombre`, `apellido`, `especialidad`, `telefono`, `correo`, `id_usuario`) VALUES
(1, 'isaac Rodríguez Víquez', '', 'Odontología General', '85102283', 'viquezisaac373@gmail.com', 2),
(2, 'Monserrath Bolaños Alfaro', '', 'Odontología General', '86743429', 'monserrath@gmail.com', 5),
(4, 'Carey Aguilar', '', 'Odontología General', '85753421', 'carey@gmail.com', 6),
(5, 'Pandora Aguilar', '', 'Odontología General', '817743429', 'pandora@gmail.com', 10),
(6, 'Hector Castro', '', 'Odontología General', '87654376', 'Hector@gmail.com', 12);

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

--
-- Dumping data for table `permisos`
--

INSERT INTO `permisos` (`id_permiso`, `nombre`, `descripcion`) VALUES
(1, 'ver_info_clinica', 'Puede ver la página de información de la clínica'),
(2, 'ver_servicios', 'Puede ver la lista de servicios'),
(3, 'ir_a_pagar', 'Puede ir a la pantalla de pago'),
(4, 'ver_inventario', 'Puede ver el inventario general'),
(5, 'control_inventario', 'Puede gestionar el inventario'),
(6, 'gestion_usuarios', 'Puede gestionar usuarios'),
(7, 'ver_historial_ventas', 'Puede ver el historial de ventas'),
(8, 'gestion_citas', 'Puede gestionar las citas'),
(9, 'agendar_cita', 'Puede agendar una cita en la clínica');

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

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre`, `descripcion`, `unidad`, `id_categoria`, `precio`, `costo_unidad`, `stock_total`, `stock_minimo`, `fecha_creacion`, `actualizado_en`, `fecha_caducidad`, `estado`) VALUES
(1, 'Anestesia', 'Anestesia general', '2', 1, 10000.00, 1000000.00, 2, 1, '2025-11-18 21:36:51', '2025-11-19 03:36:51', '2026-01-15', 'activo'),
(2, 'Anestesiate', 'Anestesia servicio', '2', 2, 10000.00, 1000000.00, 2, 1, '2025-11-18 21:37:44', '2025-11-19 03:37:44', '2026-01-15', 'activo'),
(3, 'Anestesiate3', 'Anestesia servicio', '2', 4, 10000.00, 1000000.00, 2, 1, '2025-11-18 21:45:33', '2025-11-19 03:45:33', '2026-01-15', 'activo'),
(4, 'Fluor', 'Prodicto para la prevencio de caries y el fortalecimiento de dientes.', 'Unidad', 1, 2000.00, 1500.00, 10, 1, '2025-11-21 17:57:26', '2025-11-21 23:57:26', '2025-12-21', 'activo'),
(5, 'Revision General', 'Revision general del estado del paciente. ', 'Hora', 2, 20000.00, 20000.00, 17, 0, '2025-11-21 17:58:48', '2025-11-24 17:37:15', '2025-11-21', 'activo'),
(6, 'Fluor', 'Evitar caries. ', 'Litro', 1, 4000.00, 500.00, 10, 1, '2025-11-23 14:24:30', '2025-11-23 20:24:30', '2025-11-30', 'activo'),
(7, 'Jeringa', 'Uso diario.', 'Caja', 4, 1000.00, 200.00, 20, 1, '2025-11-23 14:25:49', '2025-11-23 20:25:49', '2025-11-16', 'activo'),
(8, 'Resina', 'Para arreglar quebraduras.', 'Litro', 3, 15000.00, 5000.00, 50, 10, '2025-11-24 11:21:20', '2025-11-24 17:21:20', '2026-01-30', 'activo');

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

--
-- Dumping data for table `rol_permisos`
--

INSERT INTO `rol_permisos` (`id_rol_permiso`, `id_rol`, `id_permiso`) VALUES
(1, 3, 1),
(2, 3, 2),
(3, 3, 3),
(4, 1, 1),
(5, 1, 2),
(6, 1, 3),
(7, 1, 4),
(8, 1, 5),
(9, 1, 6),
(10, 1, 7),
(11, 2, 1),
(12, 2, 2),
(13, 2, 8),
(14, 4, 1),
(15, 4, 2),
(16, 4, 3),
(17, 4, 8),
(18, 3, 9),
(19, 2, 9),
(20, 4, 9),
(21, 1, 8),
(22, 4, 8);

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
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_completo`, `email`, `telefono`, `identificacion`, `password`, `id_rol`, `fecha_creacion`) VALUES
(2, 'isaac Rodríguez Víquez', 'viquezisaac373@gmail.com', '85102283', '208630471', '$2y$12$Z5fUbAyvOplt6tMfRzZU0u9W0fFiY1eYq7tl0oSOE5E2vSm3Jo0L2', 2, '2025-11-19 17:20:08'),
(3, 'Admin', 'admin@gmail.com', '85102283', '1512356213', '$2y$12$7hX2eSPgIfJ4aGEHehJNze6BGBBF0IeR9vrG.XthD2DVgXOiyT8GG', 3, '2025-11-19 17:39:46'),
(4, 'admin2', 'admin298@gmail.com', '124387365', '123456789', '$2y$12$D/iF9YxUXRsIQarahDNrb.Rw54O1XDvVSqN.AoAMULFS2uqiWRwWS', 4, '2025-11-19 17:46:20'),
(5, 'Monserrath Bolaños Alfaro', 'monserrath@gmail.com', '86743429', '207870964', '$2y$12$S/wmxfTRiTBbjplYLM3JF.4G1Rm0CATrHVVLx/dh6HCJ/8T6uJ4OS', 2, '2025-11-19 19:11:49'),
(6, 'Carey Aguilar', 'carey@gmail.com', '85753421', '27870961', '$2y$12$1.Lj3WgQ0pV7ms//2LiJOuDVqJyfGfZidkQTbE4EeSYqlpSJplnXe', 2, '2025-11-20 14:29:12'),
(7, 'Veronica Alfaro', 'veronica@gmail.com', '83213475', '205020970', '$2y$12$aTeFhIr4ojmUl8qBHaPBEOQiJNI2GkOLW9DRcnxrdVbdyZpEPRq3u', 1, '2025-11-20 14:51:42'),
(8, 'Brayan Aguilar', 'brayan@gmail.com', '85743426', '207870973', '$2y$12$O5ZVhb3jZx27z.pfNjgNc.1SgtVLBZIXwMwC58PL8a4aCrGytno9S', 3, '2025-11-23 10:57:39'),
(9, 'Valeria Bolanos', 'valeria@hotmail.com', '85743422', '207870912', '$2y$12$omXuF294aj/yYx2sglVEXubXvNzFDrOkIylBzlgw0NDX/0X7M841u', 3, '2025-11-23 18:59:24'),
(10, 'Pandora Aguilar', 'pandora@gmail.com', '817743429', '107870964', '$2y$12$KN.6C2bPrtnMAKGs2bdm5OQuQv6o.HYODESmWeqj3CmvheShqbyQW', 2, '2025-11-24 09:36:53'),
(11, 'Sofia Castro', 'sofia@gmail.com', '24947678', '107870961', '$2y$12$aVstIjAcNadhgeEny37vyO06cJ1/.4icwxtHnyAGBqm6qFFoh1mRS', 1, '2025-11-24 10:11:16'),
(12, 'Hector Castro', 'Hector@gmail.com', '87654376', '107870962', '$2y$12$ENsjdHyacbLhB8Xcs6.9lOmFGzNBodoJNIyV.onZi/74DszG7UyBe', 2, '2025-11-24 10:12:10'),
(13, 'Ariana Garita', 'ariana@gmail.com', '83213466', '107870967', '$2y$12$6KE5I6OdQUdy0SdqoVcTveO7WGVqMaWQELeTqAGGhGLJ9yZ.0IDai', 4, '2025-11-24 10:12:54');

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
-- Dumping data for table `ventas`
--

INSERT INTO `ventas` (`id_venta`, `id_usuario`, `id_cliente`, `fecha_venta`, `subtotal`, `impuestos`, `total`, `metodo_pago`, `estado`) VALUES
(1, 8, 8, '2025-11-23 10:59:41', 20000.00, 0.00, 20000.00, 'Tarjeta', 1),
(2, 9, 9, '2025-11-24 11:37:15', 20000.00, 0.00, 20000.00, 'Tarjeta', 1);

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
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `fk_clientes_usuarios` (`id_usuario`);

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
  MODIFY `id_atencion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `auditoria_stock`
--
ALTER TABLE `auditoria_stock`
  MODIFY `id_auditoria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bitacoras`
--
ALTER TABLE `bitacoras`
  MODIFY `id_bitacora` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id_carrito` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `carrito_detalle`
--
ALTER TABLE `carrito_detalle`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categoria_productos`
--
ALTER TABLE `categoria_productos`
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id_odontologo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rol_permisos`
--
ALTER TABLE `rol_permisos`
  MODIFY `id_rol_permiso` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

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
