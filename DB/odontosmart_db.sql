-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251031.ff9df302b7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 19, 2025 at 10:39 PM
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_actualizar_usuario` (IN `p_id_usuario` INT, IN `p_nombre` VARCHAR(100), IN `p_apellido1` VARCHAR(100), IN `p_apellido2` VARCHAR(100), IN `p_email` VARCHAR(150), IN `p_telefono` VARCHAR(30), IN `p_identificacion` VARCHAR(50), IN `p_id_rol` INT, IN `p_ip` VARCHAR(45), IN `p_modulo` VARCHAR(100), IN `p_user_agent` VARCHAR(255), OUT `p_resultado` VARCHAR(20))   BEGIN
    DECLARE v_existente INT DEFAULT 0;
    DECLARE v_tiene_odo INT DEFAULT 0;

    -- 1) Verificar si hay OTRO usuario con el mismo correo o identificación
    SELECT COUNT(*)
      INTO v_existente
      FROM usuarios
     WHERE (email = p_email OR identificacion = p_identificacion)
       AND id_usuario <> p_id_usuario;

    IF v_existente > 0 THEN
        -- Hay duplicado, no se actualiza
        SET p_resultado = 'DUPLICADO';

        -- Bitácora intento fallido (mismo orden que tu tabla)
        INSERT INTO bitacoras (
            id_usuario,
            accion,
            modulo,
            ip,
            user_agent,
            detalles
        ) VALUES (
            p_id_usuario,
            'Intento fallido de actualización',
            p_modulo,
            p_ip,
            p_user_agent,
            'Correo o identificación duplicados'
        );

    ELSE
        -- 2) Actualizar datos del usuario
        UPDATE usuarios
           SET nombre         = p_nombre,
               apellido1      = p_apellido1,
               apellido2      = p_apellido2,
               email          = p_email,
               telefono       = p_telefono,
               identificacion = p_identificacion,
               id_rol         = p_id_rol
         WHERE id_usuario     = p_id_usuario;

        -- 3) Manejo especial según rol (relación con ODONTOLOGOS)
        IF p_id_rol = 2 THEN
            -- Si es MÉDICO: asegurar odontólogo ACTIVO
            SELECT COUNT(*)
              INTO v_tiene_odo
              FROM odontologos
             WHERE id_usuario = p_id_usuario;

            IF v_tiene_odo = 0 THEN
                INSERT INTO odontologos (id_usuario, estado)
                VALUES (p_id_usuario, 'ACTIVO');
            ELSE
                UPDATE odontologos
                   SET estado = 'ACTIVO'
                 WHERE id_usuario = p_id_usuario;
            END IF;
        ELSE
            -- Si el rol ya NO es médico: marcar INACTIVO en odontologos
            UPDATE odontologos
               SET estado = 'INACTIVO'
             WHERE id_usuario = p_id_usuario;
        END IF;

        -- 4) Bitácora de actualización correcta
        INSERT INTO bitacoras (
            id_usuario,
            accion,
            modulo,
            ip,
            user_agent,
            detalles
        ) VALUES (
            p_id_usuario,
            'Usuario actualizado',
            p_modulo,
            p_ip,
            p_user_agent,
            CONCAT('Usuario actualizado: ', p_email)
        );

        SET p_resultado = 'OK';
    END IF;
END$$

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

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_citas_crear` (IN `p_id_cliente` INT, IN `p_id_odontologo` INT, IN `p_fecha_cita` DATETIME, IN `p_motivo` TEXT, IN `p_id_usuario` INT, IN `p_ip` VARCHAR(50), IN `p_modulo` VARCHAR(100), IN `p_user_agent` TEXT, OUT `p_resultado` VARCHAR(20))   BEGIN
    DECLARE v_id_cita INT;

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
    );

    IF ROW_COUNT() > 0 THEN
        SET v_id_cita = LAST_INSERT_ID();

        -- Registrar en bitácora éxito
        INSERT INTO bitacoras(
            id_usuario,
            accion,
            modulo,
            ip,
            user_agent,
            detalles
        )
        VALUES(
            p_id_usuario,
            'Cita agendada',
            p_modulo,
            p_ip,
            p_user_agent,
            CONCAT(
                'Cita ID ',
                v_id_cita,
                ' para fecha ',
                p_fecha_cita
            )
        );

        SET p_resultado = 'OK';
    ELSE
        SET p_resultado = 'ERROR';

        -- Registrar error en bitácora
        INSERT INTO bitacoras(
            id_usuario,
            accion,
            modulo,
            ip,
            user_agent,
            detalles
        )
        VALUES(
            p_id_usuario,
            'Error al agendar cita',
            p_modulo,
            p_ip,
            p_user_agent,
            CONCAT(
                'No se pudo crear cita para fecha ',
                p_fecha_cita
            )
        );
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_usuario` (IN `p_nombre` VARCHAR(100), IN `p_apellido1` VARCHAR(100), IN `p_apellido2` VARCHAR(100), IN `p_email` VARCHAR(120), IN `p_telefono` VARCHAR(20), IN `p_tipo_doc` VARCHAR(20), IN `p_identificacion` VARCHAR(50), IN `p_password` VARCHAR(255), IN `p_id_rol` INT, IN `p_ip` VARCHAR(45), IN `p_modulo` VARCHAR(100), IN `p_user_agent` VARCHAR(255), OUT `p_resultado` VARCHAR(20))   BEGIN
    DECLARE v_existente        INT DEFAULT 0;
    DECLARE v_nuevo_id_usuario INT DEFAULT 0;

    -- 1) Validar duplicados por email o identificación
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
            modulo,
            user_agent,
            detalles
        ) VALUES (
            NULL,
            'Intento fallido de creación de usuario',
            p_ip,
            p_modulo,
            p_user_agent,
            'Datos duplicados (email o identificación)'
        );

    ELSE
        
        -- 2) Crear usuario
        INSERT INTO usuarios (
            nombre,
            apellido1,
            apellido2,
            email,
            telefono,
            tipo_doc,
            identificacion,
            password,
            id_rol
        )
        VALUES (
            p_nombre,
            p_apellido1,
            p_apellido2,
            p_email,
            p_telefono,
            p_tipo_doc,
            p_identificacion,
            p_password,
            p_id_rol
        );

        -- Guardar ID del usuario recién creado
        SET v_nuevo_id_usuario = LAST_INSERT_ID();
        SET p_resultado        = 'OK';

        -- 3) Si el rol es MÉDICO (id_rol = 2), crear registro en ODONTOLOGOS
        IF p_id_rol = 2 THEN
            INSERT INTO odontologos (
                id_usuario,
                estado
            ) VALUES (
                v_nuevo_id_usuario,
                'ACTIVO'
            );
        END IF;

        -- 3.1) Si el rol es CLIENTE (id_rol = 3), crear registro en CLIENTES
        IF p_id_rol = 3 THEN
            INSERT INTO clientes (
                id_usuario
            ) VALUES (
                v_nuevo_id_usuario
            );
        END IF;

        -- 4) Registrar en bitácora creación exitosa
        INSERT INTO bitacoras(
            id_usuario,
            accion,
            ip,
            modulo,
            user_agent,
            detalles
        ) VALUES (
            v_nuevo_id_usuario,
            'Usuario creado',
            p_ip,
            p_modulo,
            p_user_agent,
            CONCAT('Usuario creado: ', p_email, ' (rol ', p_id_rol, ')')
        );

    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_productos_crear` (IN `p_id_categoria` INT, IN `p_nombre` VARCHAR(255), IN `p_descripcion` TEXT, IN `p_unidad` VARCHAR(50), IN `p_precio` DECIMAL(10,2), IN `p_costo_unidad` DECIMAL(10,2), IN `p_stock_total` INT, IN `p_stock_minimo` INT, IN `p_fecha_caducidad` DATE, IN `p_id_usuario` INT, IN `p_ip` VARCHAR(50), IN `p_modulo` VARCHAR(100), IN `p_user_agent` VARCHAR(255), OUT `p_resultado` VARCHAR(50))   BEGIN
    DECLARE existe INT DEFAULT 0;

    -- 1) Validar fecha de caducidad (no se permiten fechas en el pasado)
    IF p_fecha_caducidad IS NOT NULL 
       AND p_fecha_caducidad < CURDATE() THEN
        
        SET p_resultado = 'CADUCADO';

        INSERT INTO bitacoras(
            id_usuario,
            accion,
            modulo,
            ip,
            user_agent,
            detalles
        )
        VALUES (
            p_id_usuario,
            'Intento fallido de creación de producto',
            p_modulo,
            p_ip,
            p_user_agent,
            CONCAT(
                'Fecha de caducidad en el pasado para producto: ',
                p_nombre,
                ' (',
                p_fecha_caducidad,
                ')'
            )
        );

    ELSE
        -- 2) Validar si ya existe un producto con el mismo nombre en la misma categoría
        SELECT COUNT(*)
          INTO existe
          FROM productos
         WHERE nombre = p_nombre
           AND id_categoria = p_id_categoria;

        IF existe > 0 THEN
            SET p_resultado = 'DUPLICADO';

            INSERT INTO bitacoras(
                id_usuario,
                accion,
                modulo,
                ip,
                user_agent,
                detalles
            )
            VALUES(
                p_id_usuario,
                'Intento fallido de creación de producto',
                p_modulo,
                p_ip,
                p_user_agent,
                CONCAT('Producto duplicado: ', p_nombre)
            );
        ELSE
            -- 3) Crear el producto
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
            );

            INSERT INTO bitacoras(
                id_usuario,
                accion,
                modulo,
                ip,
                user_agent,
                detalles
            )
            VALUES(
                p_id_usuario,
                'Producto creado',
                p_modulo,
                p_ip,
                p_user_agent,
                CONCAT(
                    'Producto: ',
                    p_nombre,
                    ' (cat ',
                    p_id_categoria,
                    ')'
                )
            );

            SET p_resultado = 'OK';
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_USUARIO_BITACORA` (IN `p_id_usuario` INT, IN `p_accion` VARCHAR(100), IN `p_modulo` VARCHAR(100), IN `p_ip` VARCHAR(45), IN `p_user_agent` VARCHAR(255), IN `p_detalles` TEXT)   BEGIN
    INSERT INTO bitacoras (
        id_usuario,
        accion,
        modulo,
        fecha,
        ip,
        user_agent,
        detalles
    )
    VALUES (
        p_id_usuario,
        p_accion,
        p_modulo,
        NOW(),
        p_ip,
        p_user_agent,
        p_detalles
    );
END$$

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

-- --------------------------------------------------------

--
-- Table structure for table `bitacoras`
--

CREATE TABLE `bitacoras` (
  `id_bitacora` bigint NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(100) DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `detalles` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bitacoras`
--

INSERT INTO `bitacoras` (`id_bitacora`, `id_usuario`, `accion`, `modulo`, `fecha`, `ip`, `user_agent`, `detalles`) VALUES
(2, 1, 'Usuario creado', 'Registro público - Crear usuario', '2025-12-19 16:39:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'Usuario creado: admin@gmail.com (rol 3)');

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

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `id_usuario`, `fecha_registro`) VALUES
(1, 1, '2025-12-19 16:39:08');

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
  `id_usuario` int NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
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
  `metodo` varchar(50) DEFAULT NULL,
  `digitos_tarjeta` varchar(4) DEFAULT NULL,
  `vencimiento` varchar(7) DEFAULT NULL
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
(1, 'CEPILLO DENTAL ADULTO SUAVE', 'Cepillo dental para adultos con cerdas suaves de nylon, diseño ergonómico antideslizante y cabezal compacto. Ideal para higiene bucal diaria y uso clínico en consultas odontológicas.', 'Unidad', 5, 2500.00, 1450.00, 113, 25, '2025-12-19 12:01:25', '2025-12-19 22:32:49', '2027-11-30', 'activo', 0.00);

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
(3, 'Black Friday 2025', 'Descuento especial del 25% en productos seleccionados', 'porcentaje', 25.00, '2025-11-28 00:00:00', '2025-11-30 23:59:59', 1, '2025-12-19 15:34:32', 1),
(4, 'Navidad 2025', 'Descuento de ₡3000 en productos de higiene', 'monto_fijo', 3000.00, '2025-12-15 00:00:00', '2025-12-25 23:59:59', 1, '2025-12-19 15:34:32', 1),
(5, 'Año Nuevo 2026', 'Comienza el año con una sonrisa - 15% de descuento', 'porcentaje', 15.00, '2026-01-01 00:00:00', '2026-01-07 23:59:59', 1, '2025-12-19 15:34:32', 1),
(6, 'Higiene 5%', '5% de descuento en productos de higiene', 'porcentaje', 5.00, '2025-12-01 00:00:00', '2025-12-31 23:59:59', 1, '2025-12-19 15:40:49', 1);

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
(3, 6, 1, '2025-12-19 15:44:35');

-- --------------------------------------------------------

--
-- Table structure for table `restablecer_contrasenas`
--

CREATE TABLE `restablecer_contrasenas` (
  `id` int NOT NULL,
  `id_usuario` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `expira` datetime NOT NULL
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
  `nombre` varchar(100) DEFAULT NULL,
  `apellido1` varchar(100) DEFAULT NULL,
  `apellido2` varchar(100) DEFAULT NULL,
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

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido1`, `apellido2`, `email`, `telefono`, `identificacion`, `tipo_doc`, `password`, `id_rol`, `fecha_creacion`) VALUES
(1, 'Administrador', 'Manager', 'Manager', 'admin@gmail.com', '+50610228523', '12345678A', 'PASAPORTE', '$2y$12$0qciXcRZvvRjz6u2f8h7fOeO/pr6gtD/icFB/WyK6f6CxPhX8RWwa', 1, '2025-12-19 16:39:08');

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
,`creado_por_nombre` varchar(302)
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
  ADD UNIQUE KEY `uk_cliente_usuario` (`id_usuario`);

--
-- Indexes for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`),
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
  ADD UNIQUE KEY `uq_odontologos_usuario` (`id_usuario`),
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
  ADD UNIQUE KEY `uq_promocion_producto` (`id_promocion`,`id_producto`),
  ADD KEY `id_promocion` (`id_promocion`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indexes for table `restablecer_contrasenas`
--
ALTER TABLE `restablecer_contrasenas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

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
  MODIFY `id_atencion` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bitacoras`
--
ALTER TABLE `bitacoras`
  MODIFY `id_bitacora` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id_permiso` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id_promocion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `promocion_productos`
--
ALTER TABLE `promocion_productos`
  MODIFY `id_promocion_producto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `restablecer_contrasenas`
--
ALTER TABLE `restablecer_contrasenas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rol_permisos`
--
ALTER TABLE `rol_permisos`
  MODIFY `id_rol_permiso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventas_promociones`
--
ALTER TABLE `ventas_promociones`
  MODIFY `id_venta_promocion` int NOT NULL AUTO_INCREMENT;

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_promociones_activas`  AS SELECT `p`.`id_promocion` AS `id_promocion`, `p`.`nombre` AS `nombre`, `p`.`descripcion` AS `descripcion`, `p`.`tipo_descuento` AS `tipo_descuento`, `p`.`valor_descuento` AS `valor_descuento`, `p`.`fecha_inicio` AS `fecha_inicio`, `p`.`fecha_fin` AS `fecha_fin`, count(`pp`.`id_producto`) AS `total_productos`, concat(`u`.`nombre`,' ',`u`.`apellido1`,(case when ((`u`.`apellido2` is not null) and (`u`.`apellido2` <> '')) then concat(' ',`u`.`apellido2`) else '' end)) AS `creado_por_nombre` FROM ((`promociones` `p` left join `promocion_productos` `pp` on((`p`.`id_promocion` = `pp`.`id_promocion`))) left join `usuarios` `u` on((`p`.`creado_por` = `u`.`id_usuario`))) WHERE ((`p`.`activo` = 1) AND (now() between `p`.`fecha_inicio` and `p`.`fecha_fin`)) GROUP BY `p`.`id_promocion` ;

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
-- Constraints for table `bitacoras`
--
ALTER TABLE `bitacoras`
  ADD CONSTRAINT `bitacoras_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_bitacoras_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_clientes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`),
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `detalle_venta_ibfk_3` FOREIGN KEY (`id_lote`) REFERENCES `lote_producto` (`id_lote`);

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
-- Constraints for table `restablecer_contrasenas`
--
ALTER TABLE `restablecer_contrasenas`
  ADD CONSTRAINT `restablecer_contrasenas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

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
