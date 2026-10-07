# EduNexo

Sistema web academico para el seguimiento de estudiantes y envio de reportes semanales a tutores via WhatsApp.

Desarrollado como proyecto de tesis en FACUTEC — Universidad Evangelica del Paraguay.

---

## Tecnologias

- PHP 8.x (MVC sin framework)
- MySQL 8.4
- Bootstrap 5.3
- Evolution API (WhatsApp)
- Laragon (entorno local)

---

## Requisitos

- [Laragon](https://laragon.org) (incluye Apache y MySQL 8)
- PHP 8.1 o superior
- MySQL 8.0 o superior
- Una instancia de [Evolution API](https://github.com/EvolutionAPI/evolution-api) corriendo (para el modulo de WhatsApp)

---

## Instalacion local

### 1. Clonar el repositorio

```bash
cd C:\laragon\www
git clone https://github.com/TU_USUARIO/edunexo.git
```

### 2. Importar la base de datos

Abrir HeidiSQL (o cualquier cliente MySQL) y ejecutar los siguientes archivos en orden:

```
edunexo_completo.sql
```

> La base de datos se llama `edunexo_db`. Se crea automáticamente al ejecutar el script.

### 3. Configurar la conexion a la base de datos

Abrir `app/config/Database.php` y ajustar los valores segun tu entorno:

```php
$dsn  = "mysql:host=localhost;dbname=edunexo_db;charset=utf8mb4";
$user = "root";
$pass = "";  // Cambiar si tu MySQL tiene contrasena
```

### 4. Crear el archivo .env

Copiar `.env.example` como `.env` (en la raiz del proyecto) y completar `EVOLUTION_API_KEY`.
El `.env` no se sube al repositorio.

### 5. Crear el primer administrador

El script SQL no trae usuarios. Desde la terminal de Laragon:

```bash
cd C:\laragon\www\edunexo
php database/crear_admin.php
```

### 6. Acceder al sistema

Con Laragon corriendo, abrir en el navegador:

```
http://localhost/edunexo
```

---

## Usuarios

El repositorio no incluye usuarios ni contrasenas. El primer administrador se crea con `php database/crear_admin.php`.
Los docentes pueden registrarse desde `/register`, pero su cuenta queda pendiente: un administrador debe activarla
desde Usuarios antes de que puedan iniciar sesion.

---

## Estructura del proyecto

```
edunexo/
├── app/
│   ├── config/         # Conexion a la base de datos
│   ├── controllers/    # Logica de cada modulo
│   ├── helpers/        # SecurityHelper, WhatsAppHelper
│   ├── models/         # Modelos de datos
│   └── views/          # Vistas PHP
│       ├── admin/      # Vistas del administrador
│       ├── auth/       # Login y registro
│       ├── dashboard/  # Paneles principales
│       ├── docente/    # Vistas del docente
│       └── layouts/    # Header y footer reutilizables
├── database/           # Scripts SQL de migracion
├── public/             # Front controller (index.php) y assets
│   └── css/            # Estilos del dashboard
├── .htaccess           # Redireccion a public/
├── edunexo_completo.sql
└── README.md
```

---

## Modulos implementados

- [x] Autenticacion con roles (admin / docente)
- [x] Gestion de estudiantes y tutores
- [x] Gestion de cursos y materias
- [x] Asignacion de docentes a cursos/materias
- [x] Tipos de evaluacion configurables
- [x] Evaluaciones y carga de notas por curso
- [x] Reportes semanales
- [x] Envio via WhatsApp (Evolution API)
- [x] Trazabilidad de envios y logs de validacion

---

## Configuracion de Evolution API

El sistema requiere una instancia de Evolution API para el modulo de WhatsApp.
La URL, la instancia y la API key se leen del archivo `.env` (ver `.env.example`).
`EVOLUTION_API_KEY` debe ser igual a `AUTHENTICATION_API_KEY` del `.env` de evolution-api.

> El `.env` esta excluido del repositorio. Nunca subas una API key al codigo.

## Envio semanal automatico

Los reportes salen solos los viernes mediante el Programador de tareas de Windows (un evento de MySQL no puede llamar a PHP).
Crear la tarea una sola vez, desde una terminal (cmd) normal:

```bash
schtasks /create /tn "EduNexo envios semanales" /tr "C:\laragon\www\edunexo\cron\enviar_semanal.bat" /sc weekly /d FRI /st 18:00
```

Para probar sin enviar nada: `cron\enviar_semanal.bat --solo-encolar`. El resultado de cada corrida queda en `logs/envios_semanales.log`.
Requiere la PC encendida, Laragon y Evolution API corriendo a esa hora. Mas detalle en `edunexo_sistema.md`.

---

## Notas de seguridad

- El archivo `app/config/Database.php` contiene las credenciales de la base de datos. **No subas contrasenas reales al repositorio.**
- Todos los formularios usan tokens CSRF.
- Las contrasenas se almacenan con `password_hash()` (bcrypt).
- El acceso a cada ruta esta protegido por verificacion de rol en sesion.

---

## Autores

- Rodney Fabian Farinha De Leon
- Cinthia Elizabeth Gonzalez

FACUTEC — Universidad Evangelica del Paraguay, 2026
