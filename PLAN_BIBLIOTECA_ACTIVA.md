# Plan de diseño y despliegue local — Biblioteca Activa

## 1. Propósito del plan

Diseñar y construir una aplicación web para una biblioteca escolar híbrida que permita consultar el acervo, conocer la disponibilidad de ejemplares y operar préstamos de forma clara y ordenada. El sistema responderá primero a las necesidades de las áreas de Ingeniería, Administración y Derecho.

El proyecto se trabajará durante nueve semestres mediante entregas incrementales. Cada semestre debe producir una evidencia demostrable, evaluable y reutilizable en el siguiente módulo.

### Alcance de este documento

Incluye:

- Diseño de experiencia de usuario e interfaz.
- Arquitectura funcional y técnica necesaria para implementar las interfaces.
- Landing page, inicio de sesión y paneles por rol.
- Catálogo, ejemplares, disponibilidad, préstamos y reservas.
- Instalación y despliegue reproducible en `localhost`.
- Preparación técnica para publicar posteriormente en un dominio.

No incluye:

- Reorganización física de estanterías.
- Captura completa o escaneo masivo del acervo.
- Compra de infraestructura, dominio o alojamiento.
- Publicación en producción.
- Integraciones externas, aplicación móvil o recomendaciones con IA.
- Distribución de libros digitalizados sin autorización de derechos de autor.

## 2. Enfoque UDG aplicado

Se adopta un enfoque modular y basado en competencias: cada etapa combina análisis, diseño, implementación, validación y una evidencia concreta. El avance no se mide solo por actividades realizadas, sino por prototipos y versiones funcionales que demuestren las competencias adquiridas.

Cada semestre cerrará con:

1. Un incremento funcional o de diseño demostrable.
2. Evidencia técnica y visual.
3. Pruebas con criterios de aceptación.
4. Retroalimentación del asesor y de usuarios representativos.
5. Una lista priorizada de mejoras para el siguiente semestre.

Este uso del enfoque UDG es académico y metodológico. La interfaz tendrá identidad propia de **Biblioteca Activa**; el uso de logotipos, escudos o lineamientos gráficos oficiales de la UDG requeriría autorización y un manual de identidad aplicable.

## 3. Usuarios y roles

El sistema tendrá exactamente tres roles. Los permisos se implementarán con middleware, policies y gates de Laravel; el rol no dependerá solamente de ocultar botones en la interfaz.

| Capacidad | Estudiante | Bibliotecario | Admin |
|---|:---:|:---:|:---:|
| Consultar y filtrar el catálogo | Sí | Sí | Sí |
| Ver disponibilidad y ubicación | Sí | Sí | Sí |
| Consultar sus préstamos e historial | Sí | No aplica | No aplica |
| Crear o cancelar una reserva propia | Sí | En apoyo al usuario | Sí |
| Registrar préstamos, renovaciones y devoluciones | No | Sí | Sí |
| Crear y editar libros y ejemplares | No | Sí | Sí |
| Dar de baja lógica a ejemplares | No | Sí | Sí |
| Administrar usuarios y asignar roles | No | No | Sí |
| Configurar reglas del sistema | No | No | Sí |
| Consultar auditoría global | No | No | Sí |

Regla de seguridad: ningún usuario puede cambiar su propio rol. Debe existir al menos un administrador activo y las bajas deben ser lógicas cuando haya historial asociado.

## 4. Stack propuesto

### Aplicación

- **Laravel 13** como base de la aplicación.
- **PHP 8.5** y Composer.
- **Livewire 4**, Blade y Alpine.js para interfaces reactivas sin crear una SPA separada.
- **Tailwind CSS** con Vite para el sistema visual.
- **MongoDB** como base NoSQL.
- Paquete oficial `mongodb/laravel-mongodb`.
- Autenticación propia de Laravel con recuperación de contraseña preparada; en `localhost`, el correo se registrará en logs.
- Pest o PHPUnit para pruebas; pruebas de componentes Livewire para los flujos críticos.

### Decisión de compatibilidad

Al iniciar la implementación se ejecutará un *spike* técnico de compatibilidad con Laravel 13, Livewire 4 y la versión estable vigente de `mongodb/laravel-mongodb`. Debe demostrar autenticación, lectura, escritura, paginación e índices. Si la matriz oficial no certifica todavía Laravel 13, el proyecto se fijará temporalmente en Laravel 12 sin modificar el diseño funcional.

Las versiones exactas quedarán bloqueadas en `composer.lock` y `package-lock.json`; no se usarán dependencias flotantes en la versión demostrable.

### Entorno local recomendado

**Docker Compose** será la ruta oficial para que el entorno sea reproducible y parecido al futuro servidor:

```text
Navegador
   |
   v
Nginx :8080  --->  Laravel/PHP-FPM  --->  MongoDB (replica set local)
                           |
                           +---------> almacenamiento local persistente
```

Servicios mínimos:

- `nginx`: entrada en `http://localhost:8080`.
- `app`: PHP-FPM, Laravel y la extensión PHP de MongoDB.
- `mongo`: base de datos con volumen persistente.
- `node`: solo para instalar y compilar los recursos durante desarrollo, o ejecutado desde el contenedor de aplicación.

Configuración local inicial:

- `APP_ENV=local` y `APP_DEBUG=true` solo en `localhost`.
- `SESSION_DRIVER=file`.
- `CACHE_STORE=file`.
- `QUEUE_CONNECTION=sync`.
- `MAIL_MAILER=log`.
- Credenciales en `.env`, nunca en el repositorio.
- Datos de demostración mediante seeders idempotentes.

El replica set local permite probar operaciones consistentes que afecten préstamo y disponibilidad. Las credenciales, `APP_DEBUG` y los controladores de sesión/caché se revisarán antes del futuro despliegue público.

## 5. Modelo de información NoSQL

No se trasladará un esquema relacional literalmente a MongoDB. Se diseñarán documentos según los patrones de lectura y las operaciones atómicas del sistema.

| Colección | Responsabilidad | Campos clave |
|---|---|---|
| `users` | Identidad y autorización | nombre, correo, matrícula/código, `role`, estado |
| `books` | Registro bibliográfico de una obra | título, autores, ISBN, editorial, año, idioma, resumen, Dewey, materias, carrera, portada |
| `copies` | Ejemplares físicos de una obra | `book_id`, código de barras, ubicación, estado, condición |
| `loans` | Circulación e historial | `copy_id`, estudiante, fechas, estado, renovaciones, operador |
| `reservations` | Cola de solicitudes | libro, estudiante, fecha, posición/estado, vencimiento |
| `audit_logs` | Trazabilidad administrativa | actor, acción, entidad, cambios, fecha, IP |
| `settings` | Reglas configurables | días de préstamo, renovaciones, límites, identidad visual |

Decisiones importantes:

- **Obra y ejemplar se separan.** Una obra puede tener varios ejemplares con distinta ubicación, condición y disponibilidad.
- Los autores y materias de una obra pueden almacenarse como arreglos BSON nativos.
- Los documentos históricos conservarán una instantánea mínima del título y del usuario para que los reportes no pierdan significado si cambia el registro principal.
- La disponibilidad no se calculará con un contador manipulable desde la interfaz; provendrá del estado de los ejemplares y de préstamos activos.
- Se utilizará baja lógica para usuarios, libros y ejemplares con historial.

Índices mínimos:

- Único: `users.email`, `users.student_code`, `copies.barcode` e ISBN cuando exista y sea confiable.
- Búsqueda: título normalizado, autores, materias, Dewey y carrera.
- Operación: estado de ejemplar, vencimiento de préstamo, reservas activas y fecha.
- Restricción: un solo préstamo activo por ejemplar mediante índice parcial o una operación atómica equivalente.

## 6. Arquitectura de información e interfaces

### 6.1 Landing page pública

Objetivo: explicar el beneficio de la biblioteca y conducir al inicio de sesión sin sobrecargar la pantalla.

Secciones:

1. Encabezado con marca **Biblioteca Activa** y botón visible **Iniciar sesión**.
2. Hero con propuesta de valor: localizar libros y consultar disponibilidad en menos tiempo.
3. Beneficios: búsqueda rápida, ubicación clara, disponibilidad actualizada.
4. Áreas principales: Ingeniería, Administración y Derecho.
5. Explicación breve de cómo funciona.
6. Llamado final a iniciar sesión.
7. Pie con contacto, aviso de privacidad y accesibilidad.

No se mostrarán cifras inventadas, testimonios ficticios ni archivos digitales sin permisos.

### 6.2 Autenticación

- Inicio de sesión con correo institucional y contraseña.
- Mostrar/ocultar contraseña y estado claro de error.
- Recuperación de contraseña preparada.
- Redirección al panel correspondiente al rol.
- Cierre de sesión accesible desde el menú de usuario.
- Protección frente a intentos repetidos y mensajes que no revelen si una cuenta existe.

### 6.3 Panel del estudiante

- Inicio con búsqueda principal y estado de sus préstamos.
- Catálogo con filtros por autor, materia, carrera, año, Dewey y disponibilidad.
- Detalle de obra con metadatos, ejemplares disponibles y ubicación.
- Mis préstamos, fechas de vencimiento e historial.
- Mis reservas y opción de cancelarlas.
- Perfil básico; sin capacidad para cambiar rol o matrícula.

### 6.4 Panel del bibliotecario

- Resumen operativo: préstamos activos, vencidos, devoluciones del día y reservas pendientes.
- Flujo rápido de préstamo/devolución por código de barras.
- Catálogo: altas y edición de obras.
- Ejemplares: alta, ubicación, condición y baja lógica.
- Reservas y renovaciones.
- Reportes operativos descargables en CSV cuando exista un caso real de uso.

### 6.5 Panel del administrador

- Capacidades del bibliotecario.
- Alta, bloqueo y reactivación de usuarios.
- Asignación controlada de los tres roles.
- Reglas de préstamo y parámetros institucionales.
- Auditoría de acciones sensibles.
- Indicadores globales sin exponer información personal innecesaria.

## 7. Sistema de diseño con UI/UX Pro Max

UI/UX Pro Max se utilizará como capa de inteligencia y control de calidad del diseño, no como una librería de producción. Su flujo se aplicará así:

1. Definir contexto, usuarios, tareas críticas y stack Laravel + Livewire.
2. Generar y documentar el sistema visual.
3. Diseñar componentes y patrones reutilizables.
4. Implementar los componentes en Blade/Livewire/Tailwind.
5. Ejecutar una revisión de antipatrones, consistencia, accesibilidad y respuesta móvil antes de cada entrega.

### Dirección visual

- Personalidad: académica, accesible, confiable y contemporánea.
- Diseño sobrio; evitar efectos decorativos que compitan con el catálogo.
- Tipografía legible y autoalojada; una familia para interfaz y, si aporta valor, una serif discreta para encabezados editoriales.
- Tokens semánticos: `primary`, `accent`, `surface`, `text`, `muted`, `success`, `warning`, `danger` y `focus`.
- Colores institucionales definitivos sujetos a validación; nunca depender solo del color para comunicar estado.
- Espaciado consistente en escala de 4/8 px, radios moderados y sombras sutiles.
- Iconografía de una sola familia, siempre acompañada de etiqueta cuando la acción no sea universal.

### Componentes base

- Encabezado público y navegación lateral del panel.
- Botones, enlaces, campos, selectores, autocompletado y carga de archivos.
- Barra de búsqueda y filtros responsivos.
- Tarjeta y fila de libro; insignias de disponibilidad y estado.
- Tabla adaptable, paginación, ordenamiento y acciones por fila.
- Modal de confirmación, notificaciones, estados vacíos, skeletons y errores.
- Tarjetas de indicador, panel de actividad y menú de usuario.

### Accesibilidad y respuesta

- Meta de conformidad: WCAG 2.2 AA.
- Navegación completa con teclado, foco visible y orden lógico.
- Etiquetas y mensajes de validación asociados a cada campo.
- Contraste AA, objetivos táctiles de al menos 44 px y respeto a `prefers-reduced-motion`.
- Diseño *mobile first* en 360 px, con validación adicional en 768, 1024 y 1440 px.
- En móvil, las tablas operativas se convierten en tarjetas o permiten desplazamiento anunciado; no se ocultan datos esenciales.

## 8. Plan de nueve semestres

| Semestre | Objetivo | Trabajo de diseño e implementación | Evidencia de cierre | Criterio de aprobación |
|---|---|---|---|---|
| **1. Descubrimiento** | Convertir el contexto en requisitos verificables | Inventario de necesidades, alcance, personas, recorridos actuales, historias de usuario, riesgos y métricas base | Informe breve, mapa de experiencia y backlog priorizado | Estudiantes, bibliotecario y asesor validan el problema y el MVP |
| **2. Arquitectura UX** | Definir cómo se organiza y navega el producto | Mapa del sitio, flujos por rol, wireframes de baja fidelidad, matriz de permisos y primer modelo NoSQL | Prototipo navegable de baja fidelidad | Cinco tareas críticas se completan sin callejones ni permisos ambiguos |
| **3. Sistema visual y base técnica** | Crear el lenguaje visual y un entorno reproducible | UI/UX Pro Max, tokens, componentes, prototipo de alta fidelidad; Docker, Laravel, Livewire y prueba MongoDB | Biblioteca de componentes, landing y login implementados en `localhost` | Instalación limpia y acceso por rol funcionan; revisión visual y AA inicial aprobadas |
| **4. Catálogo y búsqueda** | Hacer localizable el acervo | CRUD de obras/ejemplares, Dewey, ubicaciones, búsqueda, filtros y detalle | Incremento funcional con datos semilla representativos | Buscar por título, autor, materia y Dewey devuelve resultados correctos y paginados |
| **5. Experiencia del estudiante** | Permitir autogestión básica | Panel, disponibilidad, préstamos, historial y reservas; pruebas con estudiantes | Flujo completo de consulta y reserva | Un estudiante no accede a operaciones internas y completa tareas clave sin ayuda |
| **6. Operación bibliotecaria** | Resolver la circulación diaria | Panel bibliotecario, préstamo, devolución, renovación, reservas y estados de ejemplar | Demostración operativa con casos normales y de error | No hay doble préstamo; cambios de disponibilidad son consistentes y auditables |
| **7. Administración y control** | Completar gobierno del sistema | Usuarios, roles, reglas, auditoría e indicadores; endurecimiento de autorización | Panel admin y reporte de pruebas de permisos | La matriz de roles pasa pruebas automatizadas y ningún rol se eleva por interfaz o URL |
| **8. Calidad y despliegue local** | Estabilizar la aplicación | Pruebas funcionales, accesibilidad, rendimiento, seguridad básica, respaldo/restauración y optimización responsiva | Release candidate local, checklist y reporte de defectos resueltos | Instalación desde cero documentada; Lighthouse y revisión manual no muestran fallos críticos |
| **9. Piloto y entrega** | Validar el producto completo y dejarlo listo para publicar | Piloto controlado, ajustes de usabilidad, manuales, video/demo, respaldo final y lista de producción | Versión `1.0.0`, memoria técnica y paquete de entrega | Aceptación del asesor y responsables; cero defectos bloqueantes y migración a dominio documentada |

### Hitos de producto

- **Semestre 3:** base visual y técnica navegable.
- **Semestre 4:** MVP de catálogo.
- **Semestre 6:** MVP operativo de biblioteca.
- **Semestre 8:** candidato estable en `localhost`.
- **Semestre 9:** versión lista para una publicación posterior.

## 9. Flujo de trabajo dentro de cada semestre

Cada semestre se divide en cinco cortes:

1. **Planeación:** objetivo, historias, riesgos y criterio de aceptación.
2. **Diseño:** flujo, wireframe o componente y revisión con usuarios.
3. **Construcción:** incremento pequeño, versionado y datos de prueba.
4. **Verificación:** pruebas automáticas, revisión manual y validación de permisos.
5. **Evidencia:** demo, capturas, bitácora de decisiones, resultados y backlog siguiente.

Se recomienda trabajar en iteraciones de dos semanas y mantener una rama principal siempre demostrable. Una funcionalidad no se considera terminada si carece de estados de carga, vacío, error, permisos, respuesta móvil y prueba del camino principal.

## 10. Despliegue en `localhost`

### Entregables técnicos

- `compose.yaml` y archivos de imagen necesarios.
- `.env.example` sin secretos.
- Scripts o comandos documentados para instalar, iniciar, detener, probar, sembrar y respaldar.
- Volúmenes persistentes para MongoDB y archivos de la aplicación.
- Health checks para aplicación y base de datos.
- Seeders con una cuenta por rol y un conjunto pequeño de libros/ejemplares ficticios.
- Copia de seguridad y restauración verificadas con `mongodump`/`mongorestore`.

### Secuencia objetivo

```powershell
Copy-Item .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
```

La secuencia final puede cambiar según la imagen elegida, pero debe permitir que una persona con Docker Desktop y Git levante el proyecto desde cero siguiendo solo el README.

### Definición de entorno local aprobado

- La landing responde en `http://localhost:8080`.
- Los tres usuarios semilla pueden iniciar sesión y ven su panel correcto.
- MongoDB conserva los datos después de reiniciar contenedores.
- La aplicación puede reconstruirse sin archivos secretos del desarrollador.
- Las pruebas automatizadas pasan en una instalación limpia.
- El respaldo puede restaurarse en un volumen vacío.
- No hay errores de consola, rutas rotas ni recursos mixtos.

## 11. Preparación para el dominio futuro

La publicación no forma parte del alcance actual, pero el diseño debe evitar retrabajo. Antes de publicar se requerirá:

- Servidor compatible con la versión fijada de PHP y la extensión MongoDB, o contenedores administrados.
- MongoDB administrado o replica set con respaldos y monitoreo.
- DNS, TLS/HTTPS y redirección obligatoria a HTTPS.
- `APP_ENV=production`, `APP_DEBUG=false` y secretos fuera del repositorio.
- Almacenamiento persistente para portadas y documentos autorizados.
- Correo transaccional real para restablecimiento de contraseña.
- Tareas programadas, rotación de logs, monitoreo y alertas.
- Política de privacidad, retención de datos y consentimiento institucional.
- Procedimiento de despliegue, rollback, respaldo y restauración ensayado.

La URL y los recursos nunca se codificarán de forma fija a `localhost`; se obtendrán de la configuración del entorno.

## 12. Pruebas y criterios de calidad

### Funcionales

- Autenticación, recuperación y cierre de sesión.
- Autorización de rutas y acciones para los tres roles.
- Alta y edición de obra y ejemplar.
- Búsqueda combinada, filtros y paginación.
- Préstamo, devolución, renovación y reserva.
- Casos límite: ejemplar no disponible, usuario bloqueado, préstamo vencido y doble operación.

### UX y accesibilidad

- Pruebas de tareas con al menos tres estudiantes y una persona responsable de biblioteca en los hitos principales.
- Navegación por teclado y lector de pantalla en flujos críticos.
- Contraste, zoom al 200 %, reflujo y mensajes de error.
- Revisión en móvil, tableta y escritorio.
- Checklist UI/UX Pro Max contra inconsistencias y antipatrones.

### Seguridad y privacidad

- Hash de contraseñas, CSRF, validación del lado servidor y limitación de intentos.
- Policies para cada operación sensible.
- Carga de portadas restringida por tipo, tamaño y nombre generado.
- Auditoría de roles, usuarios, préstamos y bajas.
- Datos semilla completamente ficticios.

### Rendimiento inicial

- Consultas paginadas e indexadas; no cargar el catálogo completo en memoria.
- Imágenes optimizadas y dimensiones reservadas para evitar saltos visuales.
- Objetivo de respuesta local inferior a 500 ms para listados comunes con el conjunto de prueba, excluyendo la primera compilación.
- Sin consultas repetitivas evitables al renderizar componentes Livewire.

## 13. Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Compatibilidad entre Laravel vigente y el ODM de MongoDB | Alto | *Spike* del semestre 3, versiones bloqueadas y alternativa temporal en Laravel 12 |
| Datos bibliográficos inconsistentes | Alto | Reglas de normalización, campos obligatorios, catálogos controlados y revisión bibliotecaria |
| Confundir obra con ejemplar | Alto | Colecciones y pantallas separadas, código de barras único por ejemplar |
| Doble préstamo por concurrencia | Alto | Operación atómica/transacción, índice y prueba concurrente |
| Alcance excesivo durante nueve semestres | Medio | MVP cerrado, backlog futuro separado y aprobación de cambios |
| Diseño atractivo pero poco usable | Medio | Pruebas de tareas, accesibilidad y revisión UI/UX Pro Max en cada hito |
| Dependencia del equipo de un desarrollador | Medio | Docker, README, datos semilla, decisiones documentadas y respaldo verificable |
| Uso indebido de identidad institucional o contenido digital | Alto | Autorización previa, identidad propia y control de derechos |

## 14. Definición de terminado de la versión 1.0

La versión se considerará lista cuando:

- Existan landing, login y paneles diferenciados para los tres roles.
- El catálogo permita buscar por título, autor, materia, carrera y Dewey.
- La disponibilidad se corresponda con ejemplares y préstamos activos.
- El ciclo de préstamo, devolución, renovación y reserva esté probado.
- Las acciones sensibles estén autorizadas en servidor y registradas.
- Los flujos críticos cumplan accesibilidad AA sin defectos bloqueantes.
- El proyecto pueda levantarse desde cero en `localhost` con instrucciones reproducibles.
- Existan respaldo/restauración, manual de usuario, manual técnico y checklist para dominio.
- El asesor y los responsables institucionales firmen la aceptación del incremento final.

## 15. Backlog posterior, fuera del alcance 1.0

- Integración con plataformas educativas.
- Inicio de sesión institucional/SSO.
- Aplicación móvil.
- Recomendaciones personalizadas.
- Atlas Search o motor especializado de búsqueda.
- Notificaciones por correo, mensajería o push.
- Repositorio de documentos digitales con control de licencias.
- Importación/exportación en formatos bibliográficos como MARC21.

## 16. Referencias técnicas

- [Formación modular y proyectos modulares de CUCEI-UDG](https://www.cucei.udg.mx/carreras/ingquimica/es/node/88)
- [Documentación de Laravel 13](https://laravel.com/docs)
- [Instalación de Livewire 4](https://livewire.laravel.com/docs/4.x/installation)
- [Integración oficial Laravel MongoDB](https://www.mongodb.com/docs/drivers/php/laravel-mongodb/current/)
- [Compatibilidad de características de Laravel MongoDB](https://www.mongodb.com/docs/drivers/php/laravel-mongodb/current/feature-compatibility/)
- [UI/UX Pro Max: soporte para Laravel, Blade y Livewire](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill)

