
# Proyecto: Sistema de Gestión Odontológica (OdontoSmart)

---

## 1. Introducción

OdontoSmart es una aplicación web diseñada para la gestión integral de una clínica odontológica. El sistema permite administrar usuarios, citas, pacientes, inventario, ventas y facturación electrónica de manera centralizada.

El objetivo de este manual es proporcionar a los desarrolladores y administradores de sistemas la información técnica necesaria para instalar, configurar, mantener y escalar la aplicación.

### Tecnologías Principales
*   **Lenguaje Backend:** PHP (Versión recomendada 8.0 o superior).
*   **Base de Datos:** MySQL.
*   **Frontend:** HTML5, CSS3, JavaScript (Vanilla).
*   **Servidor Web:** Apache (Compatible con XAMPP/Laragon).
*   **Librerías Adicionales:** SweetAlert2 (Alertas), FontAwesome (Iconos).

---

## 2. Requisitos del Sistema

Para el correcto funcionamiento de OdontoSmart, el entorno de despliegue debe cumplir con los siguientes requisitos:

### Hardware (Mínimo recomendado)
*   **CPU:** 2 Cores.
*   **RAM:** 4 GB.
*   **Almacenamiento:** 10 GB disponibles (Escalable según volumen de bitácoras y datos).

### Software
*   **Sistema Operativo:** Windows / Linux / macOS.
*   **Servidor Web:** Apache con mod_rewrite habilitado.
*   **PHP:** v8.0, v8.1, v8.2, o v8.3.
    *   **Extensiones Requeridas:**
        *   `mysqli`: Para conexión a base de datos.
        *   `session`: Para manejo de sesiones de usuario.
        *   `json`: Para manipulación de datos JSON.
        *   `mbstring`: Para manejo de cadenas multibyte.
*   **Base de Datos:** MySQL 5.7+.

---

## 3. Instalación y Configuración

### 3.1 Despliegue de Archivos
1.  Clonar el repositorio o descomprimir el código fuente en el directorio público del servidor web (ej. `C:/laragon/www/odontosmart` o `/var/www/html/odontosmart`).
2.  Asegurar permisos de lectura/escritura en las carpetas de logs si las hubiera.

### 3.2 Configuración de Base de Datos
1.  Acceder a su gestor de base de datos (phpMyAdmin, HeidiSQL, DBeaver).
2.  Crear una base de datos vacía llamada `odontosmart_db` (o el nombre de su preferencia).
3.  Importar el esquema inicial y datos semilla desde:
    *   `DB/odontosmart_db.sql`: Estructura de tablas y datos iniciales.
4.  Configurar la conexión en el archivo `config/conexion.php`:

```php
// config/conexion.php
$host = "localhost";
$user = "root";       // Cambiar por usuario de producción
$password = "";       // Cambiar por contraseña segura
$dbname = "odontosmart_db";
```

### 3.3 Verificación
Acceder a la URL del proyecto (ej. `http://odontosmart.test/` o `http://localhost/odontosmart/`) y verificar que cargue la página de inicio o login.

---

## 4. Arquitectura del Proyecto

El proyecto sigue una estructura modular organizada de la siguiente manera:

### Estructura de Directorios

```plaintext
odontosmart/
├── auth/                 # Módulo de Autenticación
│   ├── autenticar.php  # Lógica de login (Validación y Sesión)
│   ├── iniciar_sesion.php         # Interfaz de inicio de sesión
│   └── cerrar_sesion.php        # Cierre de sesión
├── config/               # Configuraciones Globales
│   ├── conexion.php      # Conexión DB (MySQLi)
│   └── csrf.php          # Protección Cross-Site Request Forgery
├── DB/                   # Scripts SQL
│   ├── odontosmart_db.sql
├── modulos/              # Módulos Funcionales (Lógica de Negocio)
│   ├── citas/            # Gestión de Agenda y Citas
│   ├── usuarios/         # ABM de Usuarios y Roles
│   └── ventas/           # Carrito, Pagos, Facturación, Servicios
├── public/               # Assets públicos y Home page
├── views/                # Componentes visuales reutilizables (Navbar, Footer)
└── index.php             # Punto de entrada principal (Landing Page)
```

### Flujo de la Aplicación
1.  **Entrada:** El usuario accede a `index.php` o `auth/login.php`.
2.  **Autenticación:** Las credenciales se validan contra la tabla `usuarios` (passwords hasheados).
3.  **Seguridad:** Cada formulario POST incluye un token CSRF generado en `config/csrf.php`.
4.  **Base de Datos:** Los módulos interactúan con la DB usando `mysqli` y *Prepared Statements*.
5.  **Manejo de Errores:** Las excepciones son capturadas globalmente y registradas en la tabla `bitacoras` mediante el SP `sp_bitacora_error`.

---

## 5. Descripción de Módulos (Detalles Técnicos)

### 5.1 Módulo de Autenticación (`auth/`)
Gestiona el acceso seguro al sistema.
*   **Funcionamiento:**
    *   `authenticate.php` recibe credenciales vía POST.
    *   Verifica email y contraseña (`password_verify` contra hash bcrypt).
    *   Valida Token CSRF.
    *   Si es exitoso, inicia `session_start()` y almacena datos del usuario en `$_SESSION['user']`.
    *   Regenera ID de sesión (`session_regenerate_id`) para evitar fijación de sesión.
*   **Roles:** Los permisos se cargan dinámicamente según el `id_rol` del usuario.

### 5.2 Módulo de Usuarios (`modulos/usuarios/`)
Permite la gestión (CRUD) de usuarios del sistema y pacientes.
*   **Archivos Clave:** `gestion_usuarios.php`, `create_users.php`.
*   **Validaciones:**
    *   Verificación de duplicados por Email o Identificación.
    *   Validación de formato de correo.
*   **Manejo de Errores:** Uso de `try-catch` para capturar intentos fallidos (ej. claves duplicadas) y mostrarlos vía alerta JS.

### 5.3 Módulo de Citas (`modulos/citas/`)
Controla la agenda médica.
*   **Lógica de Negocio (`agendar_cita.php`):**
    *   **Horarios:** Valida que la hora esté dentro del rango laboral (8:00 - 16:00) y en intervalos de 30 minutos.
    *   **Disponibilidad:** Consulta SQL para evitar solapamientos (`COUNT(*) WHERE fecha = ?`).
    *   **Registro:** Utiliza el SP `sp_citas_crear` para insertar la cita y loguear la acción atómicamente.

### 5.4 Módulo de Ventas y Facturación (`modulos/ventas/`)
El núcleo transaccional del sistema.
*   **Carrito de Compras:** Almacenado en tabla `carrito` (persistente en base de datos, no solo sesión).
*   **Procesamiento de Pago (`procesar_pago.php`):**
    *   **Transacciones ACID:** Se utiliza `$conn->begin_transaction()` para asegurar integridad.
    *   **Pasos del Proceso:**
        1.  Verificar stock disponible.
        2.  Crear registro en `ventas`.
        3.  Mover items de `carrito_detalle` a `detalle_venta`.
        4.  Descontar stock de `productos`.
        5.  Vaciar carrito.
        6.  `commit()` si todo es correcto, `rollback()` ante cualquier error.
    *   **Logging:** En caso de fallo (rollback), se abre una **nueva conexión** limpia para registrar el error en bitácora sin ser afectado por el fallo de la transacción principal.

---

## 6. Base de Datos (Esquema)

El sistema utiliza una base de datos relacional (MySQL) llamada `odontosmart_db`.

### 6.1 Tablas Principales
*   **`usuarios`**: Almacena credenciales, roles y datos personales.
*   **`roles` / `permisos`**: Sistema RBAC (Role-Based Access Control).
*   **`citas`**: Registro de agendamientos (fecha, hora, odontólogo, paciente).
*   **`productos`**: Inventario de insumos y servicios.
*   **`ventas`**: Cabecera de facturas (cliente, total, fecha).
*   **`detalle_venta`**: Líneas de factura (producto, cantidad, precio).
*   **`bitacoras`**: Registro de auditoría y errores del sistema.

### 6.2 Procedimientos Almacenados (Stored Procedures)
Los SP se utilizan para encapsular lógica compleja y asegurar consistencia.
*   `sp_citas_crear`: Inserta cita y registra la acción en bitácora.
*   `sp_ventas_registrar_bitacora`: Loguea ventas exitosas.
*   `sp_bitacora_error`: (**CRÍTICO**) Registra errores de sistema, recibiendo ID de usuario, acción, detalles e IP.

---

## 7. Seguridad

### 7.1 Cross-Site Request Forgery (CSRF)
*   **Implementación:** Archivo `config/csrf.php`.
*   **Mecanismo:** Se genera un token único por sesión (`$_SESSION['csrf_token']`).
*   **Validación:** Todas las solicitudes POST (Login, Pagos, Creación de usuarios) validan este token antes de procesar cualquier dato.

### 7.2 Inyección SQL
*   **Defensa:** Uso estricto de **Prepared Statements** (`$stmt->prepare`, `$stmt->bind_param`) en todos los módulos.
*   **Política:** No se concatenan variables directamente en las cadenas SQL.

### 7.3 Hashing de Contraseñas
*   **Algoritmo:** `PASSWORD_BCRYPT` (estándar de PHP).
*   **Funciones:** `password_hash()` al crear usuarios y `password_verify()` al hacer login.

### 7.4 Manejo de Errores y Fugas de Información
*   **Producción:** Los errores SQL (`mysqli_sql_exception`) son capturados por bloques `try-catch`.
*   **Usuario Final:** Se muestra un mensaje amigable o una alerta JavaScript.
*   **Log Interno:** El detalle técnico (Stack trace, mensaje SQL) se guarda internamente en la tabla `bitacoras` y NO se muestra en el navegador.

---



