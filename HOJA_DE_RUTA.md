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

- [ ] Paso 2 (zona horaria): verificar en tu PC que tu PHP conoce la regla actual de Paraguay. En la terminal de Laragon: php -r "date_default_timezone_set('America/Asuncion'); echo date('P T');"  Debe imprimir -03:00 -03. Si imprime -04:00 -04, tu base de zonas horarias de PHP esta vieja: en public/index.php cambiar 'America/Asuncion' por 'Etc/GMT+3' (fijo UTC-3).
- [ ] Paso 2: probar en el navegador que "Mis reportes" muestre el boton Editar en un reporte recien creado, y que la columna "Creado" muestre la hora local correcta.

- [x] Paso 3 (mensajes agrupados): migracion ejecutada por FabianTkk (2026-09-23). Se encontro un caso no contemplado en la primera version de la migracion (envios_wa ya tenia filas duplicadas de antes, una por reporte viejo, para el mismo alumno+semana+telefono) que rompia el ALTER TABLE ADD UNIQUE KEY con error 1062. Se agrego la PARTE 2.5 a database/migration_envios_agrupados.sql: fusiona esas filas duplicadas quedandose con la de estado mas avanzado (entregado > enviado > error > pendiente) antes de crear la restriccion. Probado con 2 y con 3 duplicados simultaneos, y que la restriccion se cree sin error despues.
- [ ] Paso 3: probar en el navegador: como docente, cargar un reporte de un estudiante que ya tenga un reporte de otro docente esa semana, y confirmar el mensaje "sumado al envio de WhatsApp de esta semana". Como admin, abrir Envios WhatsApp y verificar la columna "Docente(s)" y el boton "Ver detalle" con varios docentes.

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

### Paso 2 (HECHO en codigo; falta commit y verificar en tu PC): zona horaria

Problema: public/index.php no fija la zona horaria de PHP. Si php.ini tiene UTC, despues de las 21:00 en Paraguay la fecha por defecto de la asistencia es la de manana. Ademas la ventana de 48 horas para editar reportes compara created_at de MySQL (zona de MySQL) con time() de PHP (zona de PHP); si no coinciden, la ventana se corre horas.

Plan:
1. Ver el valor actual: php -i | findstr date.timezone, y en MySQL: SELECT @@global.time_zone, @@session.time_zone, NOW();
2. Fijar date_default_timezone_set('America/Asuncion') al inicio de public/index.php.
3. En app/config/Database.php ejecutar SET time_zone = '-03:00' al conectar (Paraguay usa UTC-3 todo el anio desde octubre de 2024; verificar que siga vigente al hacerlo).
4. Prueba: cambiar la hora del sistema a las 22:00 y verificar la fecha por defecto de asistencia; crear un reporte y verificar que el boton editar/eliminar vence exactamente a las 48 horas.

Resultado (2026-09-20):
- public/index.php fija date_default_timezone_set('America/Asuncion') antes de todo lo demas.
- app/config/Database.php ejecuta SET time_zone con el mismo desfase que PHP al abrir cada conexion. Asi NOW() y CURRENT_TIMESTAMP de MySQL siempre coinciden con la hora de PHP, sin importar como este configurado el servidor MySQL. Todas las columnas de fecha-hora son TIMESTAMP (se guardan en UTC), por eso el cambio no altera ningun dato existente.
- La regla de las 48 horas usaba dos relojes: MySQL (NOW()) para editar/eliminar y PHP (strtotime/time) para mostrar el boton. Ahora DocenteGestionController::reportes() calcula la columna editable en SQL y la vista docente/reportes.php la usa. Un solo reloj, sin depender de la zona.
- Pruebas (con faketime y MySQL simulado en distintas zonas): a las 22:30 hora de Paraguay el sistema viejo proponia la fecha de manana en la asistencia y ahora propone la de hoy. Con MySQL en hora local y PHP en UTC, el boton Editar desaparecia 3 horas antes de vencer; con la configuracion inversa aparecia 3 horas despues y el clic daba error. Ahora coincide en ambos casos. Con MySQL en +09:00, NOW() ya coincide con PHP.
- Regla desde ahora: cualquier script nuevo que se ejecute fuera de public/index.php (por ejemplo por linea de comandos o una tarea programada) debe llamar a date_default_timezone_set('America/Asuncion') antes de usar fechas o Database.

### Paso 3 (HECHO en codigo; falta commit y migracion): un solo mensaje de WhatsApp por estudiante y semana, y tutor equivocado en el saludo

Pedido del usuario (2026-09-20): que el mensaje de WhatsApp reuna los reportes de todos los docentes de un estudiante en un solo mensaje por semana, en lugar de mandar un mensaje por cada reporte. Esto absorbe tambien la decision pendiente del Paso 4 (reportes duplicados): se eligio la variante "varios reportes, un solo mensaje" en lugar de "un solo reporte por semana", porque no se pierde el aporte individual de cada docente.

Resultado (2026-09-20):
- envios_wa dejo de estar atado a un reporte especifico (reporte_id). Ahora tiene sus propias columnas estudiante_id + periodo_semana, con una restriccion unica junto con destinatario_telefono: un solo envio por alumno, semana y tutor, sin importar cuantos docentes reporten esa semana.
- reportes tiene su propia restriccion unica (estudiante_id, usuario_id, periodo_semana): un docente no puede cargar dos reportes del mismo estudiante la misma semana (mensaje claro si lo intenta), pero DOS DOCENTES DISTINTOS si pueden, y sus reportes se juntan en un solo mensaje.
- Al guardar o editar un reporte (DocenteGestionController::sincronizarEnvioWhatsApp), se encola (o reactiva a 'pendiente') el envio de cada tutor activo del estudiante para esa semana. Si ya habia un envio de otro docente, no se crea uno nuevo: se reutiliza. Si el estudiante no tiene tutor activo, se avisa al docente y no se encola nada (antes se guardaba un envio con telefono vacio).
- CronController arma el mensaje con una seccion nueva "REPORTES DE TUS DOCENTES" que lista a cada docente que reporto esa semana con su calificacion, comportamiento, tareas y incidentes; la asistencia, notas y avisos siguen siendo unicos para todo el mensaje (ya eran globales, no por reporte). Si se borraran todos los reportes de la semana antes de enviarse, el envio se marca 'error' con un mensaje claro en lugar de mandar un mensaje vacio.
- De paso quedó resuelto el bug del tutor equivocado: el saludo busca al tutor por el TELEFONO que realmente recibe el mensaje (t.telefono = ew.destinatario_telefono), no por el primer tutor que aparezca del estudiante. Aplica tambien en el listado admin (antes mostraba un tutor cualquiera del estudiante, sin relacion con el telefono real).
- Borrar un reporte (dentro de las 48hs) ya no borra el envio agrupado: reporte_id en envios_wa pasa a ON DELETE SET NULL (antes era CASCADE); el mensaje se arma igual con los reportes que queden.
- prepararEnviosWhatsApp() (boton manual del docente) y el auto-sync de AdminEnviosWaController::index() usan la misma agrupacion (INSERT IGNORE / ON DUPLICATE KEY), y ya no crean filas con telefono vacio cuando no hay tutor activo.
- Vista admin de Envios WhatsApp: la columna "Docente" ahora es "Docente(s)" con la lista de nombres y, si hay mas de uno, un aviso "N reportes en este mensaje". "Ver detalle" muestra un bloque por cada docente (calificacion, comportamiento, tareas, incidentes) en lugar de un unico reporte.
- Regla desde ahora: nada en el codigo debe asumir que un envios_wa tiene un solo reporte. Cualquier vista o consulta nueva que muestre "el reporte" de un envio debe buscar TODOS los reportes de ese estudiante+semana (normalizando al lunes), no seguir reporte_id.

Pruebas (con MariaDB real, dos docentes distintos del mismo curso reportando al mismo estudiante):
- Migracion aplicada dos veces sin error (idempotente); confirmado que detecta duplicados existentes antes de agregar la restriccion.
- Dos docentes reportan a Carlos la misma semana: sigue habiendo UN SOLO envios_wa, con 2 filas en reportes; el mensaje final trae a los dos docentes.
- Saludo con el tutor correcto: un estudiante con dos tutores (dos telefonos) recibe dos mensajes, cada uno saludando al tutor dueño de ESE telefono.
- Editar un reporte que ya estaba 'enviado' lo vuelve a poner 'pendiente' (se re-encola para el proximo envio).
- Borrar uno de los dos reportes de la semana no borra el envio: el mensaje se arma con el que queda.
- Reporte creado para un estudiante sin tutor activo: no se encola ningun envio, se avisa al docente.
- El mismo docente no puede cargar 2 reportes del mismo estudiante/semana (mensaje de error claro).
- Listado admin agrupado: columna "Docente(s)" muestra los 2 nombres separados por coma y el aviso "2 reportes en este mensaje"; "Ver detalle" simulado en un DOM de prueba (node) arma los 2 bloques correctamente, incluso con textos hostiles (apostrofes, comillas, <img onerror>) que quedan escapados como texto y no como HTML/JS ejecutable.

### Paso 4 (SIGUIENTE, reducido): la columna estudiantes.curso (texto) duplica a estudiantes.curso_id

(El resto del Paso 4 original -reportes duplicados- ya se resolvio como parte del Paso 3 de arriba, con la variante "varios reportes, un solo mensaje".)

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
- [ ] public/migrate.php es un script de migracion dentro de la carpeta publica que ejecuta un ALTER TABLE sin pedir login. En su forma actual probablemente falla al abrirse (su require usa la ruta relativa app/Config/Database.php, que no existe desde public/), asi que hoy es un riesgo latente y no uno activo. Igual no debe estar en el repositorio: si alguien lo arregla, cualquiera que conozca la URL podria ejecutarlo. Borrarlo (git rm public/migrate.php); la migracion ya esta aplicada si la columna directorio_evolution existe en la tabla configuracion.
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
- 2026-09-20: paso 2 (zona horaria) hecho en codigo y probado. Pendiente: commit, verificar la zona en tu PHP y probar Mis reportes en el navegador. Siguiente: paso 3.
- 2026-09-20: paso 3 (mensajes de WhatsApp agrupados por estudiante+semana, en lugar de uno por reporte; de paso corrige el tutor equivocado del saludo) hecho en codigo y probado. Esto absorbio la decision pendiente del viejo Paso 4 (reportes duplicados). Pendiente: commit, ejecutar migration_envios_agrupados.sql, probar en el navegador. Siguiente: paso 4 (la columna estudiantes.curso duplicada).
- 2026-09-23: migracion del paso 3 ejecutada en la base real; encontro un caso de datos duplicados no contemplado (ver seccion 2), se corrigio migration_envios_agrupados.sql agregando la fusion de duplicados, probado. Pendiente: commit del fix, probar el flujo en el navegador (crear reportes de 2 docentes distintos para el mismo alumno/semana y ver el mensaje agrupado). Siguiente: paso 4 (la columna estudiantes.curso duplicada).
