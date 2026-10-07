# 📚 EduNexo — Documentación del Sistema

Sistema web académico para el **seguimiento de estudiantes** y **envío de reportes semanales a tutores vía WhatsApp**, usando [Evolution API](https://github.com/EvolutionAPI/evolution-api).

---

## 🗂️ Estructura General del Proyecto

```
edunexo/
├── public/              ← Punto de entrada (Front Controller)
│   ├── index.php        ← Router principal, maneja TODAS las rutas
│   ├── css/             ← Estilos globales
│   ├── .htaccess        ← Redirige todo a index.php
│   └── ...
│
├── cron/                ← Envio semanal automatico (consola)
│   ├── procesar_envios.php   ← Encola y envia los reportes pendientes
│   └── enviar_semanal.bat    ← Lo que ejecuta el Programador de tareas de Windows
│
├── app/
│   ├── config/          ← Configuración de conexión a BD
│   │   └── Database.php
│   ├── controllers/     ← Lógica de negocio (18 archivos)
│   ├── helpers/         ← Utilidades reutilizables
│   ├── models/          ← Modelos de datos
│   └── views/           ← Interfaz visual (HTML/PHP)
│       ├── admin/       ← Vistas del panel Administrador
│       ├── docente/     ← Vistas del panel Docente
│       ├── auth/        ← Login / Registro
│       ├── dashboard/   ← Dashboard por rol
│       └── layouts/     ← Cabeceras y plantillas compartidas
│
├── database/            ← Scripts SQL
├── edunexo_completo.sql ← Volcado completo de la BD
└── start-evolution.bat  ← Script para levantar Evolution API
```

---

## 🔁 Cómo Funciona el Router

**Archivo:** [`public/index.php`](file:///c:/laragon/www/edunexo/public/index.php)

Todo pasa por acá. El `.htaccess` redirige cualquier URL a `index.php`, que lee `$_GET['url']` y hace un `switch` para elegir qué controlador ejecutar. También gestiona:
- ✅ Sesiones y timeout (2 horas)
- ✅ Token CSRF global para todos los formularios
- ✅ Autoloader de clases (namespace `App\`)

---

## 🏗️ Roles del Sistema

| Rol | Descripción |
|-----|-------------|
| `admin` | Panel completo: gestión de usuarios, cursos, materias, tutores, reportes, envíos WA |
| `docente` | Registra asistencia, notas, reportes y ve sus envíos WA |

---

## 🟢 WHATSAPP — El Mensaje Predeterminado

> **Esta es la parte que más te interesa.**

### ¿Dónde se construye el mensaje?

📄 **[`app/controllers/CronController.php`](file:///c:/laragon/www/edunexo/app/controllers/CronController.php) — método `generarYProcesarEnvio()`**

Ese método construye dinámicamente el texto completo del mensaje. Así queda el mensaje final (las secciones sin datos se omiten):

```
Hola {NombreTutor}, le enviamos el reporte semanal de {NombreEstudiante} — semana del {dd/mm/YYYY} al {dd/mm/YYYY}.

ASISTENCIA:
Ausencias: X (clases registradas esta semana: N).
- Sin justificar: Materia (dd/mm, dd/mm); Otra materia (dd/mm)
- Justificadas: Materia (dd/mm)

REPORTES DE TUS DOCENTES:   (solo si algun docente cargo reporte esa semana)
- {Docente}: {Calificacion} | Comportamiento: {..} | Tareas incompletas: N

CALIFICACIONES:
- [Materia]: [TipoEval] "Título" del dd/mm — puntaje/máximo

OBSERVACIONES:
- [Materia]: texto de observación

AVISOS:
- [dd/mm/YYYY]: [Título del aviso] — descripción

Este es un mensaje automatico del sistema, por favor no responda a este chat.
Ante cualquier consulta, comuniquese con la secretaria del colegio al {telefonoColegio}.
EduNexo — {NombreColegio}
```

### ¿Qué datos entran en cada sección?

| Sección | Origen |
|---------|--------|
| ASISTENCIA | Tabla `asistencias`, lunes a viernes de la semana, clase por clase: cada docente toma la asistencia de su materia, así que el tutor ve a qué materias faltó y no un "día de ausencia" por faltar a una sola clase. Sin ausencias: "Sin ausencias (clases registradas esta semana: N)" |
| REPORTES DE TUS DOCENTES | Tabla `reportes`: uno por cada docente que cargó algo esa semana. Si ninguno cargó, la sección no aparece |
| CALIFICACIONES y OBSERVACIONES | Tabla `notas`, filtradas por la fecha de **carga** (`notas.updated_at`), de lunes 00:00 al lunes siguiente 00:00. No cuenta la fecha de la evaluación y se omiten las filas sin puntaje ni observación |
| AVISOS | Tabla `avisos`, por `fecha_aviso` dentro de lunes a viernes |
| Pie | Nombre y teléfono del colegio desde la tabla `configuracion` |

El mensaje sale aunque no haya reporte de docente, siempre que haya asistencia o notas esa semana. Si no hay nada de nada, el envío queda en `error`.

### ¿Quién envía realmente el mensaje?

📄 **[`app/helpers/WhatsAppHelper.php`](file:///c:/laragon/www/edunexo/app/helpers/WhatsAppHelper.php)**

- Método `enviar()` — Hace la llamada HTTP (cURL) a Evolution API
- Limpia el número telefónico, obtiene credenciales de la tabla `configuracion` de la BD, y hace POST a `http://localhost:8080/message/sendText/{instancia}`
- Valores por defecto: `DEFAULT_API_URL = 'http://localhost:8080'` y `DEFAULT_INSTANCE = 'edunexo'`. La API key ya no esta en el codigo: se lee del archivo `.env` (`EVOLUTION_API_KEY`) mediante `App\Config\Env`

---

## 📋 Flujo Completo de un Envío WA

```mermaid
flowchart TD
    A[Docente crea Reporte] --> B[Se inserta en tabla 'reportes']
    B --> C[Se crea fila en 'envios_wa' con estado='pendiente']
    A2[Asistencia o notas cargadas en la semana,\nsin reporte de docente] --> C2[CronController::sincronizarEnvios\ncrea la fila en 'envios_wa']
    C --> D{¿Quién procesa?}
    C2 --> D
    D --> E[Admin presiona botón manual]
    D --> F[Viernes: Programador de tareas de Windows\ncron/enviar_semanal.bat]
    E --> G[AdminEnviosWaController::enviarIndividual\no procesarPendientes]
    F --> H[CronController::ejecutarProcesoSemanal]
    G --> I[CronController::generarYProcesarEnvio]
    H --> I
    I --> J[Construye el texto del mensaje]
    J --> K[WhatsAppHelper::enviar]
    K --> L[Evolution API POST a WhatsApp]
    L --> M[Actualiza 'envios_wa' a 'enviado' o 'error']
```

---

## ⏰ Envío automático semanal

Un evento de MySQL no puede llamar a PHP, así que el envío automático lo dispara el **Programador de tareas de Windows**, que ejecuta `cron/enviar_semanal.bat` (y este llama a `cron/procesar_envios.php`).

Qué hace cada corrida:
1. Calcula la semana a cubrir: de viernes a domingo la semana en curso; de lunes a jueves la semana anterior.
2. Encola lo que falte: un envío por cada reporte de docente y uno por cada estudiante con asistencia o notas esa semana, aunque no tenga reporte. Solo mira esa semana, nunca semanas viejas.
3. Si WhatsApp no está conectado, no envía nada y deja los envíos en `pendiente` para la próxima corrida.
4. Envía los pendientes (1,5 s entre mensajes) y escribe el resultado en `logs/envios_semanales.log`.

Se puede lanzar a mano desde la terminal de Laragon: `cron\enviar_semanal.bat`, con `--solo-encolar` (no envía, solo cuenta) o `--semana=AAAA-MM-DD` (fuerza la semana).

Requisitos: la PC encendida, Laragon (MySQL) y Evolution API (`start-evolution.bat`) corriendo a la hora de la tarea.

### Revisión antes de enviar y reportes editados después del envío

En Admin > Envíos WhatsApp:

- **Vista previa** (icono de mensaje en cada fila): muestra el texto exacto que recibirá el tutor, armado con la misma función que usa el envío real (`CronController::construirMensaje`), sin enviar ni escribir nada. Si el mensaje lleva texto escrito por docentes (incidentes u observaciones de notas), la fila avisa "Incluye texto de docentes: revisalo" y la vista previa lo marca.
- **Retener / Liberar** (solo envíos pendientes): un envío retenido (`envios_wa.retenido = 1`) no sale en el envío automático del viernes ni en "Procesar Pendientes". Se puede enviar a mano con su botón "Enviar", que además lo libera. Por defecto todo sale; retener es la válvula de seguridad.
- **Regla de reenvío:** un mensaje que ya salió NO se reenvía solo cuando un docente crea o edita un reporte después. El envío queda como `enviado`, la fila muestra "Reporte editado tras el envío" (`reportes.updated_at > envios_wa.fecha_hora_envio`) y aparece el botón **Rectificar**, que abre una vista previa de un mensaje corto con solo los reportes nuevos o editados y permite enviarlo. Al enviarse, `fecha_hora_envio` pasa a ser ahora y el aviso se apaga. Si el envío todavía estaba `pendiente` o en `error`, el mensaje se arma al enviar con los reportes actuales.

---

## 🗂️ Controladores — Qué hace cada uno

| Archivo | Ruta(s) | Función Principal |
|---------|---------|-------------------|
| [`AuthController.php`](file:///c:/laragon/www/edunexo/app/controllers/AuthController.php) | `/login`, `/register`, `/logout` | Autenticación y sesiones |
| [`DashboardController.php`](file:///c:/laragon/www/edunexo/app/controllers/DashboardController.php) | `/dashboard` | Dashboard por rol (admin/docente) |
| [`AdminUsuariosController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminUsuariosController.php) | `/admin/usuarios` | ABM de usuarios del sistema |
| [`AdminEstudiantesController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminEstudiantesController.php) | `/admin/estudiantes` | ABM de estudiantes |
| [`AdminTutoresController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminTutoresController.php) | `/admin/tutores` | ABM de tutores (padres/madres) |
| [`AdminCursosController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminCursosController.php) | `/admin/cursos` | ABM de cursos/grados |
| [`AdminMateriasController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminMateriasController.php) | `/admin/materias` | ABM de materias |
| [`AdminAsignacionesController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminAsignacionesController.php) | `/admin/asignaciones` | Asignar docentes a curso+materia |
| [`AdminTipoEvaluacionController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminTipoEvaluacionController.php) | `/admin/tipos_evaluacion` | Tipos de evaluación (examen, tarea, etc.) |
| [`AdminReportesController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminReportesController.php) | `/admin/reportes` | Ver y gestionar todos los reportes |
| [`AdminEnviosWaController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminEnviosWaController.php) | `/admin/envios-wa` | **Panel WA: ver, enviar, test directo** |
| [`AdminConfiguracionController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminConfiguracionController.php) | `/admin/configuracion` | Nombre colegio, teléfono, credenciales Evolution API |
| [`AdminLogsController.php`](file:///c:/laragon/www/edunexo/app/controllers/AdminLogsController.php) | `/admin/logs` | Logs de validación de envíos |
| [`DocenteGestionController.php`](file:///c:/laragon/www/edunexo/app/controllers/DocenteGestionController.php) | `/docente/*` | Asistencia, reportes, envíos WA docente, avisos, perfil |
| [`DocenteEvaluacionesController.php`](file:///c:/laragon/www/edunexo/app/controllers/DocenteEvaluacionesController.php) | `/docente/evaluaciones` | ABM de evaluaciones por materia |
| [`DocenteNotasController.php`](file:///c:/laragon/www/edunexo/app/controllers/DocenteNotasController.php) | `/docente/notas` | Carga masiva de calificaciones |
| [`DocenteMateriasController.php`](file:///c:/laragon/www/edunexo/app/controllers/DocenteMateriasController.php) | `/docente/materias` | Ver materias asignadas |
| [`CronController.php`](file:///c:/laragon/www/edunexo/app/controllers/CronController.php) | `/cron/procesar-envios` (solo localhost) | **Motor de envíos WA: encola, arma el mensaje y envía** |

---

## 🛠️ Helpers — Utilidades

| Archivo | Qué hace |
|---------|----------|
| [`WhatsAppHelper.php`](file:///c:/laragon/www/edunexo/app/helpers/WhatsAppHelper.php) | Envía mensajes via Evolution API. Consulta estado de instancia. Lee credenciales de la BD |
| [`SecurityHelper.php`](file:///c:/laragon/www/edunexo/app/helpers/SecurityHelper.php) | Genera/valida tokens CSRF. Sanitiza inputs. Protección XSS |

---

## ⚙️ Configuración — Dónde se guardan los datos de Evolution API

Los datos de la API se guardan en la **tabla `configuracion` (id=1)** de la base de datos y se editan desde el panel admin en `/admin/configuracion`:

| Campo en BD | Descripción | Valor por defecto |
|-------------|-------------|-------------------|
| `instancia_evolution` | Nombre de la instancia en Evolution API | `edunexo` |
| `url_evolution` | URL base del servidor | `http://localhost:8080` |
| `apikey_evolution` | (columna opcional, no existe en la base actual) la API key se lee de `.env` | — |
| `telefono_wa_remitente` | Teléfono del colegio (aparece en el pie del mensaje WA) | — |
| `nombre_colegio` | Nombre del colegio (aparece en el pie del mensaje WA) | — |

> [!TIP]
> Si querés cambiar el **texto del pie de mensaje** o cualquier otra sección del mensaje WA, editá directamente el método `generarYProcesarEnvio()` en [`CronController.php`](file:///c:/laragon/www/edunexo/app/controllers/CronController.php) (la parte que dice "4. Construir mensaje del reporte").

---

## 🗄️ Tablas Principales de la BD

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Docentes y admins del sistema |
| `estudiantes` | Alumnos |
| `tutores` | Padres/madres/encargados |
| `tutores_estudiantes` | Relación tutor ↔ estudiante |
| `cursos` | Grados/cursos del colegio |
| `materias` | Asignaturas |
| `curso_materia_docente` | Qué docente enseña qué materia en qué curso |
| `asistencias` | Registro de presencia/ausencia por fecha |
| `evaluaciones` | Evaluaciones creadas por los docentes |
| `notas` | Calificaciones de los estudiantes |
| `reportes` | Reportes semanales generados por docentes |
| `envios_wa` | Cola y estado de cada envío por WhatsApp |
| `avisos` | Avisos publicados por docentes para los cursos |
| `configuracion` | Configuración global del sistema (Evolution API, datos colegio) |
| `logs_validacion` | Log de intentos de envío y validaciones de teléfono |
| `solicitudes_cambio_reportes` | Solicitudes de edición fuera del plazo de 48h |

---

## 🔗 Rutas Clave del Sistema

| URL | Descripción |
|-----|-------------|
| `/edunexo/login` | Inicio de sesión |
| `/edunexo/dashboard` | Panel según rol |
| `/edunexo/admin/envios-wa` | **Panel de envíos WhatsApp (admin)** |
| `/edunexo/admin/configuracion` | Configurar Evolution API y datos del colegio |
| `/edunexo/admin/reportes` | Ver todos los reportes |
| `/edunexo/docente/envios-wa` | Envíos WA desde la vista docente |
| `/edunexo/docente/reportes` | Crear/editar reportes semanales |
| `/edunexo/docente/estudiantes` | Tomar asistencia |
| `/edunexo/docente/notas` | Cargar calificaciones |
| `/edunexo/cron/procesar-envios` | Mismo proceso semanal por HTTP (solo localhost). El envío programado usa `cron/enviar_semanal.bat` |
