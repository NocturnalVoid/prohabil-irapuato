# ProHabil — Guía de Instalación (Entorno Docker)

Plataforma de contratación de oficios para Irapuato, Guanajuato.
*Esta aplicación ha sido modernizada para ejecutarse en contenedores aislados, eliminando dependencias locales como XAMPP.*

---

## Estructura de archivos

El proyecto utiliza una arquitectura de microservicios con un contenedor para el servidor web y otro para la base de datos.

```text
mi-proyecto-docker/
├── docker-compose.yml     ← Orquestador de servicios (Web + BD)
├── Dockerfile             ← Construcción de imagen PHP 8.2 + Apache + Drivers
└── src/                   ← Raíz del proyecto web (Reemplaza a htdocs)
    ├── index.html         ← Página principal (landing + modales)
    ├── dashboard.php      ← Panel de usuario
    ├── logout.php         ← Script de cierre de sesión
    ├── sql/
    │   └── prohabil.sql   ← Script de BD (Auto-ejecutado por Docker al iniciar)
    └── php/
        ├── config.php       ← Configuración y conexión PDO
        ├── registro.php     ← Endpoint POST: registrar usuario
        ├── login.php        ← Endpoint POST: iniciar sesión
        └── trabajadores.php ← Endpoint GET: listar trabajadores

---

## Requisitos

- ** Docker Engine o Docker Desktop (Configurado con WSL2 en Windows). **

-- ** Terminal interactiva (WSL Ubuntu/Debian, PowerShell o Bash). **

-- ** Opcional: ** DBeaver o DataGrip para visualizar la base de datos externamente.

---

## Pasos para instalar

### 1. Preparar el entorno

Coloca la carpeta del proyecto en tu sistema de archivos de Linux (WSL), no en el sistema de archivos de Windows, para garantizar el máximo rendimiento.
Ejemplo: ~/mi-proyecto-docker/

### 2. Configurar la conexión a la Base de Datos

Asegúrate de que el archivo src/php/config.php tenga las credenciales correctas. Nota: En Docker, el host ya no es localhost, sino el nombre del servicio del contenedor de base de datos (bd).

define('DB_HOST', 'bd');           // Nombre del contenedor en docker-compose.yml
define('DB_NAME', 'prohabil_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Contraseña definida en el contenedor

### 3. Levantar los servidores

Abre tu terminal en la carpeta principal del proyecto (mi-proyecto-docker) y ejecuta el siguiente comando para descargar las imágenes, compilar PHP y levantar los servicios:

** sudo docker compose up -d --build **

Se compila un servidor Apache con PHP 8.2 y extensiones mysqli/pdo.

Se levanta un servidor MariaDB.

MariaDB detecta el archivo src/sql/prohabil.sql y crea automáticamente las tablas, vistas, triggers y datos de ejemplo. ¡No necesitas phpMyAdmin!

### 4. Abrir plataforma

Abre tu navegador web y entra a:

http://localhost:8080/

## Base de datos y Gestión
Si deseas administrar la base de datos gráficamente, usa un cliente como DBeaver con las siguientes credenciales:

- Host: localhost (Windows enruta automáticamente al contenedor)

- Puerto: 3307

- Usuario: root

- Contraseña: '' // Contraseña definida en el contenedor

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Clientes y trabajadores (login unificado) |
| `trabajadores` | Datos extra del trabajador (oficio, precio, etc.) |
| `oficios` | Catálogo de 15 oficios disponibles |
| `solicitudes` | Contrataciones entre cliente y trabajador |
| `calificaciones` | Reseñas y estrellas |
| `sesiones` | Tokens de sesión seguros |

---

## Endpoints PHP

Nota: Todos los endpoints ahora apuntan al puerto 8080.
```
POST http://localhost:8080/php/registro.php

Body JSON:
{
  "tipo": "cliente",           // o "trabajador"
  "nombre": "Juan",
  "apellido_paterno": "García",
  "apellido_materno": "Torres",
  "telefono": "4621234567",
  "correo": "juan@correo.com",
  "contrasena": "MiPass123",
  "colonia": "Centro",
  "direccion": "Calle 5 #22",
  "fecha_nacimiento": "1990-03-15",
  "genero": "masculino",

  // Solo si tipo = "trabajador":
  "oficio_id": 1,
  "años_experiencia": 8,
  "descripcion": "Experto en obra negra…",
  "precio_hora": 120,
  "precio_dia": 700
}
```

### Login
```
POST http://localhost:8080/php/login.php

Body JSON:
{
  "correo": "juan@correo.com",
  "contrasena": "MiPass123"
}
```

### Listar trabajadores
```
GET http://localhost:8080/php/trabajadores.php
GET http://localhost:8080/php/trabajadores.php?oficio=1
```

---

## Seguridad implementada

- Aislamiento de red: Base de datos encapsulada en la red de Docker (solo expone el puerto que decidamos).

- Contraseñas con bcrypt (costo 12).

- Consultas con PDO preparadas (anti SQL Injection).

- Sanitización de entradas con htmlspecialchars.

- Sesiones con httponly y samesite=Strict.

---

## 📞 Soporte

Plataforma desarrollada para Irapuato, Guanajuato, México.  
© 2025 ProHabil
