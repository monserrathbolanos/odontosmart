-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251031.ff9df302b7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 05, 2025 at 04:22 AM
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_usuario` (IN `p_nombre_completo` VARCHAR(100), IN `p_email` VARCHAR(120), IN `p_telefono` VARCHAR(20), IN `p_tipo_doc` VARCHAR(20), IN `p_identificacion` VARCHAR(50), IN `p_password` VARCHAR(255), IN `p_id_rol` INT, IN `p_ip` VARCHAR(50), OUT `p_resultado` VARCHAR(20))   BEGIN
    DECLARE v_existente INT DEFAULT 0;

    -- Validar duplicados por email o identificación
    SELECT COUNT(*)
    INTO v_existente
    FROM usuarios
    WHERE email = p_email
       OR identificacion = p_identificacion;

    IF v_existente > 0 THEN
        
        SET p_resultado = 'DUPLICADO';

        -- Registrar en bitácora intento fallido
        INSERT INTO bitacoras(
            id_usuario,
            accion,
            ip,
            detalles
        ) VALUES (
            NULL,
            'Intento fallido de creación',
            p_ip,
            'Datos duplicados'
        );

    ELSE
        
        -- Crear usuario
        INSERT INTO usuarios (
            nombre_completo,
            email,
            telefono,
            tipo_doc,
            identificacion,
            password,
            id_rol
        )
        VALUES (
            p_nombre_completo,
            p_email,
            p_telefono,
            p_tipo_doc,
            p_identificacion,
            p_password,
            p_id_rol
        );

        SET p_resultado = 'OK';

        -- Registrar en bitácora creación exitosa
        INSERT INTO bitacoras(
            id_usuario,
            accion,
            ip,
            detalles
        ) VALUES (
            LAST_INSERT_ID(),
            'Usuario creado',
            p_ip,
            CONCAT('Usuario: ', p_email)
        );

    END IF;

END$$

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
(2, 11, '2025-11-24 11:09:38', '2025-11-28 17:24:11', NULL, NULL, 0),
(3, 12, NULL, NULL, NULL, 'Termina el control por seis meses.', 1),
(4, 13, NULL, NULL, NULL, 'No pudo asister, se procede a re-agendar.', 1),
(5, 6, '2025-11-28 17:28:10', NULL, NULL, NULL, 0),
(6, 14, '2025-11-28 19:40:36', NULL, NULL, NULL, 0);

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
(13, 9, 'Cita agendada', '2025-11-24 11:37:48', '::1', 'Cita ID 13 para fecha 2025-11-28 14:30:00'),
(14, 3, 'Producto creado', '2025-11-27 19:39:11', '::1', 'Producto: Mariguanol (cat 1)'),
(15, 3, 'Venta registrada', '2025-11-27 19:57:00', '::1', 'Venta ID 3 por 1000.00'),
(16, 3, 'Venta registrada', '2025-11-27 20:39:32', '::1', 'Venta ID 4 por 18000.00'),
(17, 3, 'Venta registrada', '2025-11-27 20:41:16', '::1', 'Venta ID 5 por 20000.00'),
(18, 3, 'Producto creado', '2025-11-27 20:54:44', '::1', 'Producto: Vicodin (cat 1)'),
(19, 3, 'Venta registrada', '2025-11-27 21:21:25', '::1', 'Venta ID 6 por 200000.00'),
(20, 3, 'Venta registrada', '2025-11-27 21:32:36', '::1', 'Venta ID 8 por 6000.00'),
(21, 3, 'Venta registrada', '2025-11-27 21:39:15', '::1', 'Venta ID 9 por 4000.00'),
(22, 3, 'Venta registrada', '2025-11-28 12:09:25', '::1', 'Venta ID 10 por 20000.00'),
(23, 3, 'Venta registrada', '2025-11-28 12:31:25', '::1', 'Venta ID 11 por 2000.00'),
(24, 3, 'Venta registrada', '2025-11-28 12:39:00', '::1', 'Venta ID 12 por 7000.00'),
(25, 3, 'Venta registrada', '2025-11-28 12:43:15', '::1', 'Venta ID 13 por 10500.00'),
(26, 3, 'Venta registrada', '2025-11-28 12:48:54', '::1', 'Venta ID 14 por 2000.00'),
(27, 3, 'Venta registrada', '2025-11-28 12:59:51', '::1', 'Venta ID 15 por 3390.00'),
(28, 3, 'Venta registrada', '2025-11-28 13:08:09', '::1', 'Venta ID 16 por 3390.00'),
(29, 3, 'Venta registrada', '2025-11-28 13:13:03', '::1', 'Venta ID 17 por 6780.00'),
(30, 14, 'Usuario creado', '2025-11-28 13:14:26', '::1', 'Usuario: josueacunaflores@gmail.com'),
(31, 14, 'Venta registrada', '2025-11-28 13:46:27', '::1', 'Venta ID 18 por 28815.00'),
(32, 14, 'Venta registrada', '2025-11-28 13:49:20', '::1', 'Venta ID 19 por 1130.00'),
(33, 14, 'Venta registrada', '2025-11-28 13:54:48', '::1', 'Venta ID 20 por 22600.00'),
(34, 14, 'Venta registrada', '2025-11-28 14:13:02', '::1', 'Venta ID 21 por 2260.00'),
(35, 14, 'Venta registrada', '2025-11-28 14:27:41', '::1', 'Venta ID 22 por 2260.00'),
(36, 14, 'Venta registrada', '2025-11-28 14:42:28', '::1', 'Venta ID 23 por 2260.00'),
(37, 14, 'Venta registrada', '2025-11-28 14:59:33', '::1', 'Venta ID 24 por 3390.00'),
(38, 14, 'Venta registrada', '2025-11-28 15:00:44', '::1', 'Venta ID 25 por 4520.00'),
(39, 14, 'Venta registrada', '2025-11-28 15:06:39', '::1', 'Venta ID 26 por 2260.00'),
(40, 14, 'Venta registrada', '2025-11-28 15:10:01', '::1', 'Venta ID 27 por 2260.00'),
(41, 14, 'Venta registrada', '2025-11-28 15:12:26', '::1', 'Venta ID 28 por 2260.00'),
(42, 14, 'Venta registrada', '2025-11-28 15:12:45', '::1', 'Venta ID 29 por 2825.00'),
(43, 14, 'Venta registrada', '2025-11-28 16:31:40', '::1', 'Venta ID 30 por 4068.00'),
(44, 14, 'Venta registrada', '2025-11-28 16:35:12', '::1', 'Venta ID 31 por 3729.00'),
(45, 14, 'Venta registrada', '2025-11-28 16:36:27', '::1', 'Venta ID 32 por 1695.00'),
(46, 3, 'Gestión cita: iniciar_atencion', '2025-11-28 17:24:11', '::1', 'Cita ID 11. Hora de inicio registrada'),
(47, 14, 'Cita agendada', '2025-11-28 17:25:11', '::1', 'Cita ID 14 para fecha 2025-11-29 10:00:00'),
(48, 3, 'Gestión cita: guardar_atencion', '2025-11-28 17:27:34', '::1', 'Cita ID 13. Atención guardada'),
(49, 3, 'Gestión cita: registrar_llegada', '2025-11-28 17:28:10', '::1', 'Cita ID 6. Hora de llegada registrada'),
(50, 14, 'Usuario actualizado', '2025-11-28 17:34:22', '::1', 'Usuario actualizado: josueacunaflores@gmail.com'),
(51, 15, 'Usuario creado', '2025-11-28 18:02:16', '::1', 'Usuario: isaac@gmail.com'),
(52, 15, 'Venta registrada', '2025-11-28 18:04:15', '::1', 'Venta ID 33 por 1695.00'),
(53, 15, 'Venta registrada', '2025-11-28 18:11:25', '::1', 'Venta ID 34 por 1695.00'),
(54, 15, 'Venta registrada', '2025-11-28 18:14:11', '::1', 'Venta ID 35 por 16950.00'),
(55, 14, 'Venta registrada', '2025-11-28 18:16:34', '::1', 'Venta ID 36 por 1695.00'),
(56, 14, 'Venta registrada', '2025-11-28 18:23:00', '::1', 'Venta ID 37 por 1695.00'),
(57, 14, 'Venta registrada', '2025-11-28 18:26:01', '::1', 'Venta ID 38 por 1695.00'),
(58, 14, 'Venta registrada', '2025-11-28 18:32:15', '::1', 'Venta ID 39 por 2034.00'),
(59, 14, 'Venta registrada', '2025-11-28 18:39:04', '::1', 'Venta ID 40 por 1695.00'),
(60, 3, 'Venta registrada', '2025-11-28 19:00:54', '::1', 'Venta ID 41 por 3390.00'),
(61, 16, 'Usuario creado', '2025-11-28 19:21:25', '::1', 'Usuario: Oscar@gmail.com'),
(62, 17, 'Usuario creado', '2025-11-28 19:23:41', '::1', 'Usuario: Oscar2@gmail.com'),
(63, 16, 'Venta registrada', '2025-11-28 19:32:35', '::1', 'Venta ID 42 por 1695.00'),
(64, 16, 'Venta registrada', '2025-11-28 19:35:41', '::1', 'Venta ID 43 por 4520.00'),
(65, 14, 'Usuario actualizado', '2025-11-28 19:40:01', '::1', 'Usuario actualizado: josueacunaflores@gmail.com'),
(66, 3, 'Gestión cita: registrar_llegada', '2025-11-28 19:40:36', '::1', 'Cita ID 14. Hora de llegada registrada'),
(67, 20, 'Usuario creado', '2025-12-04 21:26:12', '::1', 'Usuario: Luis@utc.ac.cr'),
(68, 3, 'Venta registrada', '2025-12-04 21:35:13', '::1', 'Venta ID 44 por 12204.00'),
(69, 3, 'Venta registrada', '2025-12-04 21:35:47', '::1', 'Venta ID 45 por 1695.00'),
(70, 21, 'Usuario creado', '2025-12-04 21:41:00', '::1', 'Usuario: Luissana@utc.ac.cr'),
(71, 21, 'Venta registrada', '2025-12-04 21:55:14', '::1', 'Venta ID 58 por 6780.00');

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
(1, 7, '2025-11-22 03:22:54'),
(47, 21, '2025-12-05 03:57:07');

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
(1, 1, 2, 2),
(63, 47, 10, 2);

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
(6, 8, 2, '2025-11-24 08:00:00', 'atendida', ''),
(10, 8, 2, '2025-12-01 09:00:00', 'atendida', ''),
(11, 11, 2, '2025-12-03 13:00:00', 'atendida', 'Cita'),
(12, 10, 2, '2025-12-03 09:00:00', 'atendida', ''),
(13, 10, 5, '2025-11-28 14:30:00', 'atendida', ''),
(14, 12, 6, '2025-11-29 10:00:00', 'atendida', 'Revision anual');

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
(11, 10, 'Pandora', 'Aguilar', '817743429', 'pandora@gmail.com', '2025-11-24 09:43:25'),
(12, 14, 'Josue Acuna Flores', '', '62043116', 'josueacunaflores@gmail.com', '2025-11-28 13:46:27'),
(13, 15, 'Isaac Rodriguez', '', '85102283', 'isaac@gmail.com', '2025-11-28 18:04:15'),
(14, 16, 'Oscar Marin Oconor', '', '87898789', 'Oscar@gmail.com', '2025-11-28 19:32:34'),
(15, 21, 'Luis Barquero Sanabria', '', '+50662658945', 'Luissana@utc.ac.cr', '2025-12-04 21:44:15');

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
  `total` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_detalle`, `id_venta`, `id_producto`, `id_lote`, `cantidad`, `precio_unitario`, `total`, `descuento`) VALUES
(1, 1, 5, NULL, 1, 20000.00, 20000.00, 0.00),
(2, 2, 5, NULL, 1, 20000.00, 20000.00, 0.00),
(3, 2, 5, NULL, 1, 20000.00, 20000.00, 0.00),
(4, 3, 9, NULL, 1, 1000.00, 1000.00, 0.00),
(5, 4, 4, NULL, 9, 2000.00, 18000.00, 0.00),
(6, 5, 6, NULL, 5, 4000.00, 20000.00, 0.00),
(7, 6, 5, NULL, 10, 20000.00, 200000.00, 0.00),
(8, 8, 10, NULL, 3, 2000.00, 6000.00, 0.00),
(9, 9, 10, NULL, 2, 2000.00, 4000.00, 0.00),
(10, 10, 5, NULL, 1, 20000.00, 20000.00, 0.00),
(11, 11, 10, NULL, 1, 2000.00, 2000.00, 0.00),
(12, 12, 11, NULL, 2, 2625.00, 7000.00, 0.00),
(13, 13, 11, NULL, 3, 2625.00, 10500.00, 0.00),
(14, 14, 10, NULL, 1, 1500.00, 2000.00, 0.00),
(15, 15, 10, NULL, 3, 1500.00, 4500.00, 0.00),
(16, 16, 10, NULL, 3, 1500.00, 4500.00, 0.00),
(17, 17, 10, NULL, 3, 2000.00, 6000.00, 0.00),
(18, 18, 10, NULL, 4, 2000.00, 8000.00, 0.00),
(19, 18, 11, NULL, 5, 3500.00, 17500.00, 0.00),
(20, 19, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(21, 20, 5, NULL, 2, 15000.00, 30000.00, 0.00),
(22, 21, 10, NULL, 2, 2000.00, 2000.00, 0.00),
(23, 22, 10, NULL, 2, 2000.00, 2000.00, 0.00),
(24, 23, 10, NULL, 2, 2000.00, 2000.00, 0.00),
(25, 24, 10, NULL, 2, 1500.00, 3000.00, 0.00),
(26, 25, 10, NULL, 2, 2000.00, 4000.00, 0.00),
(27, 26, 10, NULL, 1, 2000.00, 2000.00, 0.00),
(28, 27, 10, NULL, 1, 2000.00, 2000.00, 0.00),
(29, 28, 10, NULL, 1, 2000.00, 2000.00, 0.00),
(30, 29, 17, NULL, 1, 2500.00, 2500.00, 0.00),
(31, 30, 18, NULL, 2, 1800.00, 3600.00, 0.00),
(32, 31, 18, NULL, 1, 1800.00, 1800.00, 0.00),
(33, 31, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(34, 32, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(35, 33, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(36, 34, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(37, 35, 5, NULL, 1, 15000.00, 15000.00, 0.00),
(38, 36, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(39, 37, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(40, 38, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(41, 39, 18, NULL, 1, 1800.00, 1800.00, 0.00),
(42, 40, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(43, 41, 10, NULL, 2, 1500.00, 3000.00, 0.00),
(44, 42, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(45, 43, 6, NULL, 1, 4000.00, 4000.00, 0.00),
(46, 44, 18, NULL, 6, 1800.00, 10800.00, 0.00),
(47, 45, 10, NULL, 1, 1500.00, 1500.00, 0.00),
(48, 58, 10, NULL, 4, 1500.00, 6000.00, 0.00);

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

--
-- Dumping data for table `lote_producto`
--

INSERT INTO `lote_producto` (`id_lote`, `id_producto`, `numero_lote`, `fecha_caducidad`, `cantidad`) VALUES
(1, 9, '20506', '2025-11-27', 1),
(2, 10, '2006', '2025-11-28', 50);

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
  `metodo` varchar(50) DEFAULT NULL,
  `digitos_tarjeta` varchar(4) DEFAULT NULL,
  `vencimiento` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_venta`, `monto`, `fecha_pago`, `metodo`, `digitos_tarjeta`, `vencimiento`) VALUES
(1, 44, 12204.00, '2025-12-04 21:35:13', 'Tarjeta', '1234', '2025-12'),
(2, 45, 1695.00, '2025-12-04 21:35:47', 'Tarjeta', '3216', '2025-12'),
(3, 58, 6780.00, '2025-12-04 21:55:14', 'Tarjeta', '6513', '2026-11');

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
  `unidad` varchar(100) DEFAULT NULL,
  `id_categoria` int DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `costo_unidad` decimal(10,2) DEFAULT NULL,
  `stock_total` int NOT NULL,
  `stock_minimo` int DEFAULT '0',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_caducidad` date DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `descuento` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre`, `descripcion`, `unidad`, `id_categoria`, `precio`, `costo_unidad`, `stock_total`, `stock_minimo`, `fecha_creacion`, `actualizado_en`, `fecha_caducidad`, `estado`, `descuento`) VALUES
(1, 'Anestesia', 'Anestesia general', 'PC', 1, 10000.00, 1000000.00, 2, 1, '2025-11-18 21:36:51', '2025-11-28 02:49:43', '2026-01-15', 'activo', 0.00),
(2, 'Anestesiate', 'Anestesia servicio', 'PC', 2, 10000.00, 1000000.00, 2, 1, '2025-11-18 21:37:44', '2025-11-28 02:49:48', '2026-01-15', 'activo', 0.00),
(3, 'Anestesiate3', 'Anestesia servicio', 'PC', 4, 10000.00, 1000000.00, 2, 1, '2025-11-18 21:45:33', '2025-11-28 02:49:52', '2026-01-15', 'activo', 0.00),
(4, 'Fluor', 'Producto para la prevención de caries y el fortalecimiento de dientes.', 'PC', 1, 2000.00, 1500.00, 1, 1, '2025-11-21 17:57:26', '2025-11-28 02:49:17', '2025-12-21', 'activo', 0.00),
(5, 'Revision General', 'Revision general del estado del paciente. ', 'Hora', 2, 20000.00, 20000.00, 14, 0, '2025-11-21 17:58:48', '2025-11-29 00:14:11', '2025-11-21', 'activo', 0.00),
(6, 'Fluor', 'Evitar caries. ', 'Litro', 1, 4000.00, 500.00, 4, 1, '2025-11-23 14:24:30', '2025-11-29 01:35:41', '2025-11-30', 'activo', 0.00),
(7, 'Jeringa', 'Uso diario.', 'Caja', 4, 1000.00, 200.00, 20, 1, '2025-11-23 14:25:49', '2025-11-23 20:25:49', '2025-11-16', 'activo', 0.00),
(8, 'Resina', 'Para arreglar quebraduras.', 'Litro', 3, 15000.00, 5000.00, 50, 10, '2025-11-24 11:21:20', '2025-11-24 17:21:20', '2026-01-30', 'activo', 0.00),
(9, 'Mariguanol', 'unguento', 'Frasco', 1, 1000.00, 500.00, 0, 40, '2025-11-27 19:39:11', '2025-11-28 01:57:00', '2025-11-27', 'activo', 0.00),
(10, 'Vicodin', 'Anestésico', 'Frasco', 1, 2000.00, 1500.00, 50, 50, '2025-11-27 20:54:44', '2025-12-05 03:55:14', '2025-11-28', 'activo', 0.00),
(11, 'Pasta Dental Colgate', 'Pasta dental con flúor para protección completa', 'Unidad', 5, 3500.00, 2000.00, 40, 10, '2025-11-28 12:23:28', '2025-11-28 19:46:27', '2026-12-31', 'activo', 0.00),
(14, 'Enjuague Bucal Listerine', 'Enjuague bucal antiséptico 500ml', 'Botella', 5, 4500.00, 2500.00, 30, 8, '2025-11-28 12:23:28', '2025-11-28 18:23:28', '2026-09-30', 'activo', 0.00),
(15, 'Blanqueador Dental', 'Kit de blanqueamiento dental profesional', 'Kit', 5, 12000.00, 7000.00, 15, 5, '2025-11-28 12:23:28', '2025-11-28 18:23:28', '2026-03-31', 'activo', 0.00),
(17, 'Cepillo Dental Oral-B', 'Cepillo dental de cerdas suaves', 'Unidad', 5, 2500.00, 1500.00, 39, 10, '2025-11-28 14:47:41', '2025-11-28 21:12:45', '2027-01-31', 'activo', 0.00),
(18, 'Hilo Dental', 'Hilo dental encerado 50m', 'Unidad', 5, 1800.00, 1000.00, 50, 15, '2025-11-28 14:47:41', '2025-12-05 03:35:13', '2026-06-30', 'activo', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `promociones`
--

CREATE TABLE `promociones` (
  `id_promocion` int NOT NULL,
  `nombre` varchar(150) NOT NULL COMMENT 'Nombre de la promoción (ej: "Black Friday 2025")',
  `descripcion` text COMMENT 'Descripción detallada de la promoción',
  `tipo_descuento` enum('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje' COMMENT 'Tipo de descuento: porcentaje o monto fijo',
  `valor_descuento` decimal(10,2) NOT NULL COMMENT 'Valor del descuento (ej: 20 para 20% o 5000 para ₡5000)',
  `fecha_inicio` datetime NOT NULL COMMENT 'Fecha y hora de inicio de la promoción',
  `fecha_fin` datetime NOT NULL COMMENT 'Fecha y hora de fin de la promoción',
  `activo` tinyint(1) DEFAULT '1' COMMENT '1 = activa, 0 = inactiva',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_por` int NOT NULL COMMENT 'Usuario que creó la promoción'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Campañas promocionales estacionales';

--
-- Dumping data for table `promociones`
--

INSERT INTO `promociones` (`id_promocion`, `nombre`, `descripcion`, `tipo_descuento`, `valor_descuento`, `fecha_inicio`, `fecha_fin`, `activo`, `fecha_creacion`, `creado_por`) VALUES
(1, 'Black Friday 2025', 'Descuento especial del 25% en productos seleccionados', 'porcentaje', 25.00, '2025-11-30 00:00:00', '2025-12-05 14:48:55', 1, '2025-11-28 14:48:06', 7),
(2, 'Navidad 2025', 'Descuento de ₡3000 en productos de higiene', 'monto_fijo', 3000.00, '2025-12-15 00:00:00', '2025-12-25 23:59:59', 1, '2025-11-28 14:48:06', 7),
(3, 'Año Nuevo 2026', 'Comienza el año con una sonrisa - 15% de descuento', 'porcentaje', 15.00, '2026-01-01 00:00:00', '2026-01-07 23:59:59', 1, '2025-11-28 14:48:06', 7);

-- --------------------------------------------------------

--
-- Table structure for table `promocion_productos`
--

CREATE TABLE `promocion_productos` (
  `id_promocion_producto` int NOT NULL,
  `id_promocion` int NOT NULL,
  `id_producto` int NOT NULL,
  `fecha_asignacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Productos incluidos en promociones';

--
-- Dumping data for table `promocion_productos`
--

INSERT INTO `promocion_productos` (`id_promocion_producto`, `id_promocion`, `id_producto`, `fecha_asignacion`) VALUES
(1, 1, 5, '2025-11-28 14:48:24'),
(2, 1, 9, '2025-11-28 14:48:24'),
(3, 1, 10, '2025-11-28 14:48:24'),
(4, 1, 11, '2025-11-28 14:48:24'),
(7, 3, 5, '2025-11-28 14:48:24'),
(8, 3, 9, '2025-11-28 14:48:24');

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
  `tipo_doc` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_rol` int NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_completo`, `email`, `telefono`, `identificacion`, `tipo_doc`, `password`, `id_rol`, `fecha_creacion`) VALUES
(2, 'isaac Rodríguez Víquez', 'viquezisaac373@gmail.com', '85102283', '208630471', '', '$2y$12$Z5fUbAyvOplt6tMfRzZU0u9W0fFiY1eYq7tl0oSOE5E2vSm3Jo0L2', 2, '2025-11-19 17:20:08'),
(3, 'Admin', 'admin@gmail.com', '85102283', '1512356213', '', '$2y$12$7hX2eSPgIfJ4aGEHehJNze6BGBBF0IeR9vrG.XthD2DVgXOiyT8GG', 1, '2025-11-19 17:39:46'),
(4, 'admin2', 'admin298@gmail.com', '124387365', '123456789', '', '$2y$12$D/iF9YxUXRsIQarahDNrb.Rw54O1XDvVSqN.AoAMULFS2uqiWRwWS', 4, '2025-11-19 17:46:20'),
(5, 'Monserrath Bolaños Alfaro', 'monserrath@gmail.com', '86743429', '207870964', '', '$2y$12$S/wmxfTRiTBbjplYLM3JF.4G1Rm0CATrHVVLx/dh6HCJ/8T6uJ4OS', 2, '2025-11-19 19:11:49'),
(6, 'Carey Aguilar', 'carey@gmail.com', '85753421', '27870961', '', '$2y$12$1.Lj3WgQ0pV7ms//2LiJOuDVqJyfGfZidkQTbE4EeSYqlpSJplnXe', 2, '2025-11-20 14:29:12'),
(7, 'Veronica Alfaro', 'veronica@gmail.com', '83213475', '205020970', '', '$2y$12$aTeFhIr4ojmUl8qBHaPBEOQiJNI2GkOLW9DRcnxrdVbdyZpEPRq3u', 1, '2025-11-20 14:51:42'),
(8, 'Brayan Aguilar', 'brayan@gmail.com', '85743426', '207870973', '', '$2y$12$O5ZVhb3jZx27z.pfNjgNc.1SgtVLBZIXwMwC58PL8a4aCrGytno9S', 3, '2025-11-23 10:57:39'),
(9, 'Valeria Bolanos', 'valeria@hotmail.com', '85743422', '207870912', '', '$2y$12$omXuF294aj/yYx2sglVEXubXvNzFDrOkIylBzlgw0NDX/0X7M841u', 3, '2025-11-23 18:59:24'),
(10, 'Pandora Aguilar', 'pandora@gmail.com', '817743429', '107870964', '', '$2y$12$KN.6C2bPrtnMAKGs2bdm5OQuQv6o.HYODESmWeqj3CmvheShqbyQW', 2, '2025-11-24 09:36:53'),
(11, 'Sofia Castro', 'sofia@gmail.com', '24947678', '107870961', '', '$2y$12$aVstIjAcNadhgeEny37vyO06cJ1/.4icwxtHnyAGBqm6qFFoh1mRS', 1, '2025-11-24 10:11:16'),
(12, 'Hector Castro', 'Hector@gmail.com', '87654376', '107870962', '', '$2y$12$ENsjdHyacbLhB8Xcs6.9lOmFGzNBodoJNIyV.onZi/74DszG7UyBe', 2, '2025-11-24 10:12:10'),
(13, 'Ariana Garita', 'ariana@gmail.com', '83213466', '107870967', '', '$2y$12$6KE5I6OdQUdy0SdqoVcTveO7WGVqMaWQELeTqAGGhGLJ9yZ.0IDai', 4, '2025-11-24 10:12:54'),
(14, 'Josue Acuna Flores', 'josueacunaflores@gmail.com', '62043116', '2-0782-0616', 'Cedula', '$2y$12$rI4lgPKTFpnbtiOVfENNB.kkHkCGZsFi/f4rqNFcR2tnUzwkrvZuq', 1, '2025-11-28 13:14:26'),
(15, 'Isaac Rodriguez', 'isaac@gmail.com', '85102283', '208637471', '', '$2y$12$yLYIeQR9S9VYx3wlJ2sFKOdpUdfdcR3vVlPwN6ciSMcjv7hcr665y', 3, '2025-11-28 18:02:16'),
(16, 'Oscar Marin Oconor', 'Oscar@gmail.com', '87898789', '207207207a', '', '$2y$12$dMBWnHkmzH/ym3Pb4fR2HuNvTn29DEmdf/6BJcQPuXeDO9LbkNHUi', 3, '2025-11-28 19:21:25'),
(17, 'Oscar', 'Oscar2@gmail.com', '87898789a', '207207207', '', '$2y$12$pEqgODBR6OMzoFGKPR6A.enmgZNYME3eQk0KwsLUoa2YCIuPCmU5y', 3, '2025-11-28 19:23:41'),
(18, 'Juan Pérez', 'juan@email.com', '8888-8888', '1-2345-6789', 'Cedula', '123456', 1, '2025-12-04 20:05:26'),
(19, 'Carlos Villagran', 'carlos@villa.com', '62626262', '12345678A', 'DIMEX', '$2y$12$H4i.qkelVqPITsX6XXfJ6.vKdKh79UZEAl.d/3CAenYJwt/Deiou2', 3, '2025-12-04 20:42:57'),
(20, 'Luis Barquero Villalobos', 'Luis@utc.ac.cr', '65986598', '7896543', 'PASAPORTE', '$2y$12$Xiq0PPoYI7Fkgc8daPFBLubviYYfIwyMfRi9a0llpgCXN4K9ZOl6G', 4, '2025-12-04 21:26:12'),
(21, 'Luis Barquero Sanabria', 'Luissana@utc.ac.cr', '+50662658945', '98765432A', 'DIMEX', '$2y$12$ASXdf5RQOvv/9l4eBp5ws.MynnYL0qzYbE87qrllfOSWHNqYLvM/q', 3, '2025-12-04 21:41:00');

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
(2, 9, 9, '2025-11-24 11:37:15', 20000.00, 0.00, 20000.00, 'Tarjeta', 1),
(3, 3, 9, '2025-11-27 19:57:00', 1000.00, 0.00, 1000.00, 'Tarjeta', 1),
(4, 3, 9, '2025-11-27 20:39:32', 18000.00, 0.00, 18000.00, 'Tarjeta', 1),
(5, 3, 9, '2025-11-27 20:41:16', 20000.00, 0.00, 20000.00, 'Tarjeta', 1),
(6, 3, 9, '2025-11-27 21:21:25', 200000.00, 0.00, 200000.00, 'Tarjeta', 1),
(7, 3, 9, '2025-11-27 21:30:47', 4000.00, 0.00, 4000.00, 'Tarjeta', 1),
(8, 3, 9, '2025-11-27 21:32:36', 6000.00, 0.00, 6000.00, 'Tarjeta', 1),
(9, 3, 9, '2025-11-27 21:39:15', 4000.00, 0.00, 4000.00, 'Tarjeta', 1),
(10, 3, 9, '2025-11-28 12:09:25', 20000.00, 0.00, 20000.00, 'Tarjeta', 1),
(11, 3, 9, '2025-11-28 12:31:25', 2000.00, 0.00, 2000.00, 'Tarjeta', 1),
(12, 3, 9, '2025-11-28 12:39:00', 7000.00, 0.00, 7000.00, 'Tarjeta', 1),
(13, 3, 9, '2025-11-28 12:43:15', 10500.00, 0.00, 10500.00, 'Tarjeta', 1),
(14, 3, 9, '2025-11-28 12:48:54', 2000.00, 0.00, 2000.00, 'Tarjeta', 1),
(15, 3, 9, '2025-11-28 12:59:51', 4500.00, 390.00, 3390.00, 'Tarjeta', 1),
(16, 3, 9, '2025-11-28 13:08:09', 4500.00, 390.00, 3390.00, 'Tarjeta', 1),
(17, 3, 9, '2025-11-28 13:13:03', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(18, 14, 12, '2025-11-28 13:46:27', 25500.00, 3315.00, 28815.00, 'Tarjeta', 1),
(19, 14, 12, '2025-11-28 13:49:20', 1500.00, 130.00, 1130.00, 'Tarjeta', 1),
(20, 14, 12, '2025-11-28 13:54:47', 30000.00, 2600.00, 22600.00, 'Tarjeta', 1),
(21, 14, 12, '2025-11-28 14:13:02', 3000.00, 260.00, 2260.00, 'Tarjeta', 1),
(22, 14, 12, '2025-11-28 14:27:41', 3000.00, 260.00, 2260.00, 'Tarjeta', 1),
(23, 14, 12, '2025-11-28 14:42:28', 3000.00, 260.00, 2260.00, 'Tarjeta', 1),
(24, 14, 12, '2025-11-28 14:59:33', 3000.00, 390.00, 3390.00, 'Tarjeta', 1),
(25, 14, 12, '2025-11-28 15:00:44', 4000.00, 520.00, 4520.00, 'Tarjeta', 1),
(26, 14, 12, '2025-11-28 15:06:39', 2000.00, 260.00, 2260.00, 'Tarjeta', 1),
(27, 14, 12, '2025-11-28 15:10:01', 2000.00, 260.00, 2260.00, 'Tarjeta', 1),
(28, 14, 12, '2025-11-28 15:12:26', 2000.00, 260.00, 2260.00, 'Tarjeta', 1),
(29, 14, 12, '2025-11-28 15:12:45', 2500.00, 325.00, 2825.00, 'Tarjeta', 1),
(30, 14, 12, '2025-11-28 16:31:40', 3600.00, 468.00, 4068.00, 'Tarjeta', 1),
(31, 14, 12, '2025-11-28 16:35:12', 3300.00, 429.00, 3729.00, 'Tarjeta', 1),
(32, 14, 12, '2025-11-28 16:36:27', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(33, 15, 13, '2025-11-28 18:04:15', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(34, 15, 13, '2025-11-28 18:11:25', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(35, 15, 13, '2025-11-28 18:14:11', 15000.00, 1950.00, 16950.00, 'Tarjeta', 1),
(36, 14, 12, '2025-11-28 18:16:34', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(37, 14, 12, '2025-11-28 18:23:00', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(38, 14, 12, '2025-11-28 18:26:01', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(39, 14, 12, '2025-11-28 18:32:15', 1800.00, 234.00, 2034.00, 'Tarjeta', 1),
(40, 14, 12, '2025-11-28 18:39:04', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(41, 3, 9, '2025-11-28 19:00:54', 3000.00, 390.00, 3390.00, 'Tarjeta', 1),
(42, 16, 14, '2025-11-28 19:32:34', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(43, 16, 14, '2025-11-28 19:35:41', 4000.00, 520.00, 4520.00, 'Tarjeta', 1),
(44, 3, 9, '2025-12-04 21:35:13', 10800.00, 1404.00, 12204.00, 'Tarjeta', 1),
(45, 3, 9, '2025-12-04 21:35:47', 1500.00, 195.00, 1695.00, 'Tarjeta', 1),
(46, 21, 15, '2025-12-04 21:44:15', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(47, 21, 15, '2025-12-04 21:46:35', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(48, 21, 15, '2025-12-04 21:47:33', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(49, 21, 15, '2025-12-04 21:48:05', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(50, 21, 15, '2025-12-04 21:48:17', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(51, 21, 15, '2025-12-04 21:48:26', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(52, 21, 15, '2025-12-04 21:48:32', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(53, 21, 15, '2025-12-04 21:49:43', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(54, 21, 15, '2025-12-04 21:49:53', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(55, 21, 15, '2025-12-04 21:51:15', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(56, 21, 15, '2025-12-04 21:52:21', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(57, 21, 15, '2025-12-04 21:54:58', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(58, 21, 15, '2025-12-04 21:55:14', 6000.00, 780.00, 6780.00, 'Tarjeta', 1),
(59, 21, 15, '2025-12-04 21:57:26', 3000.00, 390.00, 3390.00, 'Tarjeta', 1),
(60, 21, 15, '2025-12-04 22:05:11', 3000.00, 390.00, 3390.00, 'Tarjeta', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ventas_promociones`
--

CREATE TABLE `ventas_promociones` (
  `id_venta_promocion` int NOT NULL,
  `id_venta` int NOT NULL,
  `id_promocion` int NOT NULL,
  `descuento_aplicado` decimal(10,2) NOT NULL COMMENT 'Monto total de descuento aplicado',
  `fecha_aplicacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Promociones aplicadas a ventas';

--
-- Dumping data for table `ventas_promociones`
--

INSERT INTO `ventas_promociones` (`id_venta_promocion`, `id_venta`, `id_promocion`, `descuento_aplicado`, `fecha_aplicacion`) VALUES
(1, 24, 1, 1000.00, '2025-11-28 14:59:33'),
(2, 31, 1, 500.00, '2025-11-28 16:35:12'),
(3, 32, 1, 500.00, '2025-11-28 16:36:27'),
(4, 33, 1, 500.00, '2025-11-28 18:04:15'),
(5, 34, 1, 500.00, '2025-11-28 18:11:25'),
(6, 35, 1, 5000.00, '2025-11-28 18:14:11'),
(7, 36, 1, 500.00, '2025-11-28 18:16:34'),
(8, 37, 1, 500.00, '2025-11-28 18:23:00'),
(9, 38, 1, 500.00, '2025-11-28 18:26:01'),
(10, 40, 1, 500.00, '2025-11-28 18:39:04'),
(11, 41, 1, 1000.00, '2025-11-28 19:00:54'),
(12, 42, 1, 500.00, '2025-11-28 19:32:34'),
(13, 45, 1, 500.00, '2025-12-04 21:35:47'),
(14, 58, 1, 2000.00, '2025-12-04 21:55:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_productos_con_promocion`
-- (See below for the actual view)
--
CREATE TABLE `v_productos_con_promocion` (
`id_producto` int
,`nombre` varchar(150)
,`precio` decimal(10,2)
,`id_promocion` int
,`nombre_promocion` varchar(150)
,`tipo_descuento` enum('porcentaje','monto_fijo')
,`valor_descuento` decimal(10,2)
,`precio_con_descuento` decimal(25,8)
,`monto_descuento` decimal(24,8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_promociones_activas`
-- (See below for the actual view)
--
CREATE TABLE `v_promociones_activas` (
`id_promocion` int
,`nombre` varchar(150)
,`descripcion` text
,`tipo_descuento` enum('porcentaje','monto_fijo')
,`valor_descuento` decimal(10,2)
,`fecha_inicio` datetime
,`fecha_fin` datetime
,`total_productos` bigint
,`creado_por_nombre` varchar(100)
);

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
-- Indexes for table `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id_promocion`),
  ADD KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indexes for table `promocion_productos`
--
ALTER TABLE `promocion_productos`
  ADD PRIMARY KEY (`id_promocion_producto`),
  ADD UNIQUE KEY `unique_promo_producto` (`id_promocion`,`id_producto`),
  ADD KEY `id_promocion` (`id_promocion`),
  ADD KEY `id_producto` (`id_producto`);

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
-- Indexes for table `ventas_promociones`
--
ALTER TABLE `ventas_promociones`
  ADD PRIMARY KEY (`id_venta_promocion`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_promocion` (`id_promocion`);

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
  MODIFY `id_atencion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `auditoria_stock`
--
ALTER TABLE `auditoria_stock`
  MODIFY `id_auditoria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bitacoras`
--
ALTER TABLE `bitacoras`
  MODIFY `id_bitacora` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id_carrito` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `carrito_detalle`
--
ALTER TABLE `carrito_detalle`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `categoria_productos`
--
ALTER TABLE `categoria_productos`
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

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
  MODIFY `id_lote` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `odontologos`
--
ALTER TABLE `odontologos`
  MODIFY `id_odontologo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id_promocion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `promocion_productos`
--
ALTER TABLE `promocion_productos`
  MODIFY `id_promocion_producto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `ventas_promociones`
--
ALTER TABLE `ventas_promociones`
  MODIFY `id_venta_promocion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

-- --------------------------------------------------------

--
-- Structure for view `v_productos_con_promocion`
--
DROP TABLE IF EXISTS `v_productos_con_promocion`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_productos_con_promocion`  AS SELECT `prod`.`id_producto` AS `id_producto`, `prod`.`nombre` AS `nombre`, `prod`.`precio` AS `precio`, `promo`.`id_promocion` AS `id_promocion`, `promo`.`nombre` AS `nombre_promocion`, `promo`.`tipo_descuento` AS `tipo_descuento`, `promo`.`valor_descuento` AS `valor_descuento`, (case when (`promo`.`tipo_descuento` = 'porcentaje') then (`prod`.`precio` - ((`prod`.`precio` * `promo`.`valor_descuento`) / 100)) when (`promo`.`tipo_descuento` = 'monto_fijo') then greatest((`prod`.`precio` - `promo`.`valor_descuento`),0) else `prod`.`precio` end) AS `precio_con_descuento`, (case when (`promo`.`tipo_descuento` = 'porcentaje') then ((`prod`.`precio` * `promo`.`valor_descuento`) / 100) when (`promo`.`tipo_descuento` = 'monto_fijo') then least(`promo`.`valor_descuento`,`prod`.`precio`) else 0 end) AS `monto_descuento` FROM ((`productos` `prod` join `promocion_productos` `pp` on((`prod`.`id_producto` = `pp`.`id_producto`))) join `promociones` `promo` on((`pp`.`id_promocion` = `promo`.`id_promocion`))) WHERE ((`promo`.`activo` = 1) AND (now() between `promo`.`fecha_inicio` and `promo`.`fecha_fin`) AND (`prod`.`estado` = 'activo')) ;

-- --------------------------------------------------------

--
-- Structure for view `v_promociones_activas`
--
DROP TABLE IF EXISTS `v_promociones_activas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_promociones_activas`  AS SELECT `p`.`id_promocion` AS `id_promocion`, `p`.`nombre` AS `nombre`, `p`.`descripcion` AS `descripcion`, `p`.`tipo_descuento` AS `tipo_descuento`, `p`.`valor_descuento` AS `valor_descuento`, `p`.`fecha_inicio` AS `fecha_inicio`, `p`.`fecha_fin` AS `fecha_fin`, count(`pp`.`id_producto`) AS `total_productos`, `u`.`nombre_completo` AS `creado_por_nombre` FROM ((`promociones` `p` left join `promocion_productos` `pp` on((`p`.`id_promocion` = `pp`.`id_promocion`))) left join `usuarios` `u` on((`p`.`creado_por` = `u`.`id_usuario`))) WHERE ((`p`.`activo` = 1) AND (now() between `p`.`fecha_inicio` and `p`.`fecha_fin`)) GROUP BY `p`.`id_promocion` ;

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
-- Constraints for table `promociones`
--
ALTER TABLE `promociones`
  ADD CONSTRAINT `fk_promociones_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Constraints for table `promocion_productos`
--
ALTER TABLE `promocion_productos`
  ADD CONSTRAINT `fk_promo_prod_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_promo_prod_promocion` FOREIGN KEY (`id_promocion`) REFERENCES `promociones` (`id_promocion`) ON DELETE CASCADE;

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

--
-- Constraints for table `ventas_promociones`
--
ALTER TABLE `ventas_promociones`
  ADD CONSTRAINT `fk_venta_promo_promocion` FOREIGN KEY (`id_promocion`) REFERENCES `promociones` (`id_promocion`),
  ADD CONSTRAINT `fk_venta_promo_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
