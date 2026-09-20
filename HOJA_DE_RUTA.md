# Hoja de ruta EduNexo

Ultima actualizacion: 2026-09-20
Como retomar en una conversacion nueva: decirle a Claude "lee HOJA_DE_RUTA.md, revisa git log y seguimos en el paso marcado SIGUIENTE". Al terminar cada paso, marcar la casilla y anotar la fecha en el registro del final.

## 1. Estado actual

Hecho y listo para commit (modulo docente "Mis estudiantes", asistencia y reportes semanales):

- [x] Mensaje de WhatsApp (CronController): cuenta dias distintos y no filas. Un estudiante ausente 1 dia en 7 materias cuenta 1 ausencia, no 6 ni 7.
- [x] Semana sin asistencia registrada: el mensaje dice "Sin registro de asistencia esta semana" en lugar de "Asistencia completa".
- [x] periodo_semana se normaliza siempre al lunes (al guardar el reporte y al armar el mensaje, para reportes viejos). Antes un reporte cargado un viernes no incluia las faltas de lunes a jueves.
- [x] dias_ausente ya no se escribe a mano: se calcula desde la tabla asistencias (al crear y editar el reporte). El historial de reportes y la tarjeta "Mas ausencias" del dashboard tambien leen de asistencias.
- [x] Materias y cursos inactivos ya no aparecen en el selector de asistencia, en "Mis materias" ni en avisos.
- [x] Pantalla "Mis estudiantes": estado del dia (sin registrar, parcial, registrada), columna "Faltas del mes", botones todos presentes/ausentes, buscador, contador, y casilla "Aplicar a mis N materias de este curso".
- [x] Validaciones de asistencia: fecha real, no futura, solo lunes a viernes; si hoy es fin de semana abre el viernes anterior; fecha en formato invalido en la URL ya no rompe la pagina.
- [x] Observaciones de asistencia se guardan en texto plano (antes se escapaban dos veces).
- [x] Migracion database/migration_asistencia_justificada.sql ahora es idempotente (se puede ejecutar varias veces).

Archivos tocados: app/controllers/DocenteGestionController.php, CronController.php, DashboardController.php, DocenteMateriasController.php, app/views/docente/estudiantes.php, reportes.php, database/migration_asistencia_justificada.sql.

## 2. Pendiente inmediato (antes de seguir con codigo)

- [ ] Ejecutar database/migration_asistencia_justificada.sql en la base edunexo_db (si no se hizo). Sin la columna asistencias.justificada, la pantalla de asistencia falla al cargar.
- [ ] Confirmar que la base real tiene la tabla solicitudes_cambio_reportes (database/migration_solicitudes_cambio_reportes.sql). El dump edunexo.sql que se le paso a Claude no la tenia.
- [ ] Regenerar el dump completo de la base (mysqldump) y reemplazar el desactualizado. Hacerlo cuando terminen los pasos de esta hoja que tocan la base (3, 4 y 5).
- [ ] Probar en el navegador lo que no se pudo probar en las pruebas automaticas: botones "Todos presentes/ausentes", buscador, contador, casilla "Aplicar a todas".
- [ ] Si git diff muestra archivos enteros como cambiados, es por saltos de linea (LF contra CRLF). Con "git diff --ignore-space-at-eol --stat" se ve el cambio real.

- [ ] Paso 1 (escapado doble): ejecutar la limpieza de datos viejos. Primero copia de la base: mysqldump -u root edunexo_db > backup_antes_de_limpiar.sql . Luego, en la carpeta del proyecto: php database/limpiar_entidades.php (solo muestra que cambiaria) y, si la lista se ve bien, php database/limpiar_entidades.php --apply
- [ ] Paso 1: probar en el navegador (no se pudo automatizar): con un usuario, un tutor y un estudiante cuyo nombre tenga apostrofe o comillas (por ejemplo D'Angelo), abrir el boton de editar de cada pantalla admin y ver que el formulario muestre el nombre bien; y en Envios WhatsApp abrir "Ver detalle" de un reporte.

## 3. Pasos siguientes, en orden

### Paso 1 (HECHO en codigo; falta commit y limpieza de datos): escapado doble de SecurityHelper::sanitize()

Problema: sanitize() escapa el HTML al guardar y las vistas lo escapan otra vez al mostrar. Un texto como O'Brien se muestra como O&#039;Brien; en un textarea de edicion, cada vez que se guarda se codifica un nivel mas (&amp;#039;...). Ademas el mensaje de WhatsApp toma los textos crudos de la base, asi que las entidades HTML llegan al tutor.

Datos ya afectados (usan sanitize): reportes.incidentes_disciplinarios, solicitudes_cambio_reportes.motivo, avisos.titulo y descripcion, usuarios.nombre y email (perfil). Faltan los que use el panel admin y notas/evaluaciones: hay que inventariarlos.

Plan:
1. Inventario: buscar todos los usos. En Windows: findstr /s /n "sanitize(" app\*.php
2. Auditoria de salida (bloqueante): revisar que TODA vista que imprime texto del usuario use htmlspecialchars($x, ENT_QUOTES, 'UTF-8'). Buscar impresiones sin escapar. Si se quita el escapado de entrada sin este paso, aparece riesgo de XSS.
3. Cambiar sanitize(): que solo haga trim, quite caracteres de control y (opcional) limite largo, sin escapar. Agregar un helper de salida SecurityHelper::e() para usar en las vistas.
4. Limpiar datos viejos con un script unico (database/limpiar_entidades.php): html_entity_decode(..., ENT_QUOTES | ENT_HTML5, 'UTF-8') sobre las columnas del inventario. Hacer mysqldump antes.
5. Prueba: crear y editar un reporte, aviso y perfil con el texto  O'Brien & "comillas" <b>x</b>  y verificar: en la base queda crudo; el formulario lo muestra igual una sola vez; editar 3 veces no lo cambia; el mensaje de WhatsApp lo muestra bien; el HTML de la pagina lo muestra escapado (ver codigo fuente).

Resultado (2026-09-20):
- SecurityHelper::sanitize() ahora solo hace trim y quita caracteres de control. Ya no escapa HTML ni usa strip_tags (strip_tags borraba texto legitimo: "menor que <5 anios" quedaba "menor que "). Se agregaron SecurityHelper::e() (escapar para HTML) y SecurityHelper::jsArg() (pasar un valor a JavaScript dentro de un onclick).
- Auditoria de salida: se revisaron las 32 vistas y todas las impresiones. Las consultas SQL ya usaban parametros, asi que quitar el escapado no abre inyeccion SQL. Se corrigieron 7 onclick con addslashes (estudiantes, tutores, usuarios, materias, cursos, tipos de evaluacion y envios WhatsApp): cuatro de ellos se volvian explotables sin el escapado de entrada, porque una comilla doble en un nombre rompia el atributo HTML. Tambien se escapa el contenido de verDetalle() (innerHTML) y la inicial del avatar en los dos layouts.
- Nuevo: database/limpiar_entidades.php (decodifica los datos viejos; modo prueba por defecto, --apply para escribir; seguro de repetir).
- Regla desde ahora: todo texto de usuario se guarda crudo y se escapa SIEMPRE al imprimir (htmlspecialchars o SecurityHelper::e()); en onclick usar SecurityHelper::jsArg(); en innerHTML de JavaScript escapar con una funcion como escHtml().
- Pruebas: 70 combinaciones (7 vistas por 10 textos hostiles) sin fallas, y contra las vistas viejas fallaban; edicion repetida 3 veces del mismo reporte deja el texto identico (antes crecia a &amp;amp;amp;#039;); limpieza con casos de 1 y 2 niveles y controles que no deben tocarse.

### Paso 2 (SIGUIENTE): zona horaria

Problema: public/index.php no fija la zona horaria de PHP. Si php.ini tiene UTC, despues de las 21:00 en Paraguay la fecha por defecto de la asistencia es la de manana. Ademas la ventana de 48 horas para editar reportes compara created_at de MySQL (zona de MySQL) con time() de PHP (zona de PHP); si no coinciden, la ventana se corre horas.

Plan:
1. Ver el valor actual: php -i | findstr date.timezone, y en MySQL: SELECT @@global.time_zone, @@session.time_zone, NOW();
2. Fijar date_default_timezone_set('America/Asuncion') al inicio de public/index.php.
3. En app/config/Database.php ejecutar SET time_zone = '-03:00' al conectar (Paraguay usa UTC-3 todo el anio desde octubre de 2024; verificar que siga vigente al hacerlo).
4. Prueba: cambiar la hora del sistema a las 22:00 y verificar la fecha por defecto de asistencia; crear un reporte y verificar que el boton editar/eliminar vence exactamente a las 48 horas.

### Paso 3: tutor equivocado en el saludo y envios de WhatsApp

Problemas:
- CronController arma el saludo con una consulta LEFT JOIN de tutores_estudiantes y tutores sin filtrar por el telefono del envio. Si un estudiante tiene dos tutores, el saludo puede nombrar a uno distinto del que recibe el mensaje.
- guardarReporte crea un solo envio (el primer tutor activo), pero prepararEnviosWhatsApp crea uno por cada tutor activo. Son dos criterios distintos.
- Si el estudiante no tiene tutor activo, guardarReporte inserta un envio con telefono vacio.

Plan:
1. En CronController unir el tutor por telefono: ... JOIN tutores t ON t.id = te.tutor_id AND t.telefono = ew.destinatario_telefono.
2. Decidir la politica (recomendado: un envio por cada tutor activo) y usarla igual en guardarReporte y prepararEnviosWhatsApp, idealmente en un metodo privado compartido.
3. Si no hay tutor activo, no crear el envio y avisar al docente con un mensaje.
4. Prueba: estudiante con dos tutores; verificar que cada mensaje saluda al tutor correcto.

### Paso 4: reportes duplicados por estudiante y semana

Problema: dos docentes (o el mismo docente dos veces) pueden crear un reporte del mismo estudiante y semana, y el tutor recibe dos mensajes casi iguales (la asistencia y las notas del mensaje son de todo el estudiante, no de la materia).

Decision de producto pendiente (elegir una):
- A) Un reporte por estudiante y semana, sin importar el docente (UNIQUE estudiante_id + periodo_semana). Simple, un solo mensaje.
- B) Un reporte por estudiante, semana y docente (UNIQUE estudiante_id + usuario_id + periodo_semana), y el mensaje agrupa lo de todos los docentes. Mas trabajo en CronController.

Plan (con A):
1. Detectar duplicados existentes: SELECT estudiante_id, periodo_semana, COUNT(*) FROM reportes GROUP BY estudiante_id, periodo_semana HAVING COUNT(*) > 1;
2. Normalizar semanas viejas al lunes: UPDATE reportes SET periodo_semana = DATE_SUB(periodo_semana, INTERVAL WEEKDAY(periodo_semana) DAY); (puede generar duplicados nuevos: repetir el paso 1 despues).
3. Resolver duplicados a mano, luego agregar la restriccion UNIQUE en una migracion nueva en database/.
4. En guardarReporte capturar el error de duplicado y mostrar un mensaje claro ("Ya existe un reporte de ese estudiante para esa semana").

### Paso 5: estudiantes.curso (texto) duplica a estudiantes.curso_id

Problema: el nombre del curso esta guardado dos veces. Si se renombra un curso o se cambia el curso_id de un estudiante, el texto queda desactualizado. Los reportes, el dashboard y algunas vistas usan el texto; la asistencia usa curso_id.

Plan:
1. Inventario de usos de e.curso y estudiantes.curso en controladores y vistas.
2. Reemplazar cada uso por un JOIN a cursos (c.nombre).
3. Solo al final, migracion que elimina la columna estudiantes.curso. Hacer mysqldump antes.
4. Ajustar el alta y edicion de estudiantes en el panel admin para que ya no escriban la columna.

### Paso 6: mejoras de menor prioridad

- [ ] Estado de asistencia con mas valores (presente, ausente, tarde, justificada) en lugar de usar observacion para la tardanza. Requiere migrar asistencias.presente/justificada a una columna estado y ajustar CronController.
- [ ] Vista "todos mis estudiantes" que reuna los estudiantes de todos los cursos del docente, con las materias que comparte con cada uno.
- [ ] Anio lectivo: cursos y asistencias no tienen anio; el proximo anio se van a mezclar los datos.
- [ ] DocenteMateriasController tiene una rama para admin que no se puede alcanzar, porque docente_header.php redirige a login a quien no sea docente. Quitar la rama o crear la vista admin.
- [ ] Revisar si docente_header.php y docente_footer.php cargan Bootstrap JS dos veces.
- [ ] SecurityHelper::getClientIp() confia en el encabezado X-Forwarded-For, que el cliente puede falsificar. Quien lo cambie en cada intento evita el limite de intentos de login. Usar solo REMOTE_ADDR, salvo que haya un proxy confiable delante.
- [ ] La sintaxis VALUES() en INSERT ... ON DUPLICATE KEY UPDATE esta marcada como obsoleta en MySQL 8.0.20 o superior. Funciona, pero conviene reemplazarla cuando se actualice el motor (MariaDB no acepta la sintaxis nueva, asi que revisar cual se usa en produccion).

## 4. Como se probo lo hecho (para repetirlo despues de cada paso)

Las pruebas se hicieron con una base MariaDB temporal cargada con el esquema del dump, ejecutando los controladores desde la linea de comandos con datos de prueba. Datos usados: docente id 1 (Juan Perez) con las asignaciones 1 a 8 de 6to Grado (la 4, Ciencias Sociales, con materia inactiva); estudiantes 2 (Carlos Lopez) y 3 (Ramon Alvarez); docente id 3 (Javier Britez) con una sola materia (asignacion 10, 9no Grado).

Casos que hay que volver a comprobar despues de cada cambio:
1. Abrir la pantalla de asistencia sin registros: debe decir "Sin registrar" y mostrar 7 materias.
2. Guardar con "aplicar a todas": 14 filas (2 estudiantes por 7 materias). Volver a guardar no duplica.
3. Rechazos: sabado, fecha futura, fecha imposible, materia inactiva, materia de otro docente, estudiante de otro curso, registro con formato invalido.
4. Mensaje de WhatsApp: ausencia de un dia en varias materias cuenta 1; semana sin registros dice "Sin registro"; semana con todo presente dice "Asistencia completa"; reporte cargado un viernes incluye lunes a viernes.
5. Historial de reportes y dashboard: ausencias calculadas desde asistencias.

## 5. Registro

- 2026-09-20: fixes de asistencia, mensajes de WhatsApp y reportes semanales (seccion 1). Commit f33ca0e subido a GitHub.
- 2026-09-20: paso 1 (escapado doble de sanitize) hecho en codigo y probado. Pendiente: commit, ejecutar limpiar_entidades.php sobre la base, probar los modales en el navegador. Siguiente: paso 2.
