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
database/migration_modulos_nuevos.sql
database/fix_alter_estudiantes.sql
```

> La base de datos se llama `edunexo_db`. Se crea automaticamente al ejecutar el primer SQL.

### 3. Configurar la conexion a la base de datos

Abrir `app/config/Database.php` y ajustar los valores segun tu entorno:

```php
$dsn  = "mysql:host=localhost;dbname=edunexo_db;charset=utf8mb4";
$user = "root";
$pass = "";  // Cambiar si tu MySQL tiene contrasena
```

### 4. Acceder al sistema

Con Laragon corriendo, abrir en el navegador:

```
http://localhost/edunexo
```

---

## Usuarios de prueba

| Usuario   | Contrasena | Rol     |
|-----------|------------|---------|
| Fabian03  | (la tuya)  | admin   |
| jperez    | 123456     | docente |

> Cambiar las contrasenas despues del primer acceso.

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
La configuracion de la API key y la URL del servidor se encuentra en `app/helpers/WhatsAppHelper.php`.

> No subas tu API key al repositorio. Usa variables de entorno o un archivo `.env` excluido del repo.

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
