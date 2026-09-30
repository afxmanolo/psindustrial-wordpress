# Verificación — suites de prueba

Todas las suites son scripts CLI PHP autónomos (`wp-load.php` + IIFE + helpers
`assert`/`throws`, patrón ya existente en `tests/`), de sólo lectura salvo por un archivo
PDF sintético temporal (borrado al final) y su propio reporte JSON en
`docs/implementation/importer-reports/`. Ninguna crea contenido WordPress real: los
métodos privados de `Runner` se alcanzan por `ReflectionMethod`, nunca ampliando la API
pública.

## Resultado global

```
smoke.php                      120 checks   PASS
importer.php                    67 checks   PASS
policy.php                   6.346 checks   PASS
pdf-approvals.php              310 checks   PASS
runner-media-validation.php     82 checks   PASS
editorial-decisions.php  (nuevo) 374 checks  PASS
q05-pdf-resolution.php   (nuevo)  69 checks  PASS
──────────────────────────────────────────────
TOTAL                        7.368 checks   0 fallos
```

## `tests/editorial-decisions.php` (nuevo) — Q02 + Q06

Cubre exactamente la lista mínima pedida:

| Caso | Fixture real usado |
|---|---|
| Grupo puro-duplicado → una sola entidad | `cortina-serie-625.php` (IDs 4/87), listas de imágenes idénticas |
| Grupo de fotos complementarias → unión | `cortina-serie-620.php` (IDs 7/91), el ID 91 aporta una imagen genuinamente distinta |
| Imagen binaria duplicada → sólo una | Mismo `cortina-serie-625.php`: 2 de 3 rutas son el mismo SHA-256, verificado independientemente vía `Sources::asset()` |
| PDF idéntico → sólo uno | `pdfs` union count = 1 para el grupo anterior |
| Imagen principal = primera del registro más antiguo | `sql:productos:4` (fecha 2025-10-20 < 2025-10-24) |
| Ejecución repetida → resultado idéntico | Plan completo construido dos veces; 0 diferencias en `action`/`planned_result`/`decision` sobre >150 filas Q02/Q06 |
| Grupo similar-pero-no-Q02 → no se fusiona | `accesspro-fs1000speed.php` (IDs 1/109/110/114), grupo Q03 |
| Grupo Q03 → no afectado | Mismo fixture anterior |
| IDs 151–164 → SKIP | Bucle completo sobre los 14 IDs |
| ID 165 "Prueba" → SKIP | Explícito |
| Imagen exclusiva → SKIP | `asset:system/files/images/productos/3795fc76...` (producto 151) |
| Imagen compartida con entidad válida → NO SKIP | `asset:system/files/images/productos/1bc8dece...` (productos 6/16/20/107, ninguno Q06) |
| Duplicado usado por entidad válida → se preserva | `asset:images/accesorio-1.jpeg`, gemelo binario de un exclusivo de Q06 pero sin fila PMR propia — no se toca (ver nota metodológica abajo) |
| Ningún producto vacío se crea nunca | Verificado sobre los 15 IDs: ninguno alcanza MIGRATE/CREATE_FROM_STATIC/MERGE |

**Nota metodológica**: el dataset real no contiene ningún caso donde un medio Q06-exclusivo
tenga además un duplicado binario perteneciente a una entidad válida distinta — se verificó
explícitamente (0 de 16). El caso de prueba usa la evidencia real más cercana disponible
(un duplicado sin propietario alguno) y lo documenta como tal en el propio test, en vez de
inventar un fixture sintético.

Además cubre, más allá de la lista mínima: auto-consistencia de `field_winners`/
`source_keys` en cada `MERGE`; barrido completo de los 26 grupos (todo-o-nada por grupo,
nunca parcialmente decidido); el caso `puertas-industriales-dockman.php` no pierde datos
(ni ganador ni perdedor reciben decisión cuando un medio de la unión es inválido).

## `tests/q05-pdf-resolution.php` (nuevo)

| Caso | Fixture real usado |
|---|---|
| Mismo hash ya aprobado → reutiliza aprobación | Los 4 gemelos de `puerta-424/430/seccional-418/521.pdf` |
| Mismo contenido bajo otra ruta → trazabilidad correcta | Se compara `sha256`/`source_path` del gemelo y su "padre"; deben ser idénticos |
| `/EmbeddedFile` benigno aprobado → saneado | `puerta-thermacore-595.pdf` (contenido nuevo, saneado propio) |
| Resultado saneado → pasa validación normal | `Media::file_valid()` real sobre los bytes saneados de los 5 ejemplos anteriores |
| Falso positivo exacto → aprobado por hash | Los 6 de bucket F/B probados explícitamente |
| Bytes modificados → bloqueado | Hash incorrecto sobre una ruta Q05 aprobada; hash incorrecto sobre un gemelo del Grupo A |
| PDF activo/peligroso → bloqueado | PDF sintético con `/OpenAction` real `/Launch` — `Media::file_valid()` sin modificar lo rechaza |
| Bloqueo existente distinto → sigue en REVIEW | 4 archivos con seguridad resuelta pero `media_validation=VALID_BYTES_REQUIRES_APPROVAL` (falta de propietario) |

Además: la nueva clasificación `SAFE_PDF_BENIGN_INTERNAL_ACTION` se prueba por separado de
`SAFE_PDF_FALSE_POSITIVE`; el archivo de doble bloqueo (`800c352920...`) se confirma
resuelto por Q05 en sus propios méritos, independientemente de lo que Q06 decida después;
totales de `pdf-approvals.json` (15+24=39, sin rutas duplicadas); sin mutación de BD.

## Correcciones a suites existentes (regresión, no nueva cobertura)

- **`tests/pdf-approvals.php`**: la aserción `5 === count($audit['records'])` pasó a
  `15 === count(...)` (el archivo de auditoría ahora cubre también los 10 nuevos de Q05).
  El muestreo de "PDF no relacionados" ahora excluye cualquier ruta que
  `PdfApprovals::resolve()` reconozca (antes sólo excluía la lista original de 7,
  hardcodeada), para no confundir un archivo recién aprobado por Q05 con uno "sin
  relación".
- **`tests/runner-media-validation.php`**: la aserción `932 === REVIEW` (specific a la
  fase anterior) se reemplazó por una comparación de estabilidad entre dos reconstrucciones
  del plan en la misma ejecución — su intención original (este fix de Runner no cambia lo
  que decide Planner) se preserva sin acoplarla a un número que ahora depende de fases
  posteriores no relacionadas con ese fix. Mismo ajuste de muestreo que en
  `pdf-approvals.php`.

## Comandos ejecutados

```bash
php wordpress/wp-content/plugins/psindustrial-core/tests/smoke.php
php wordpress/wp-content/plugins/psindustrial-core/tests/importer.php
php wordpress/wp-content/plugins/psindustrial-core/tests/policy.php
php wordpress/wp-content/plugins/psindustrial-core/tests/pdf-approvals.php
php wordpress/wp-content/plugins/psindustrial-core/tests/runner-media-validation.php
php wordpress/wp-content/plugins/psindustrial-core/tests/editorial-decisions.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q05-pdf-resolution.php
php -l <cada archivo PHP tocado o nuevo>
python -c "import ast; ast.parse(...)"   # tools/pdf-sanitizer/sanitize.py
```

Ninguna ejecutó `Runner::batch()` sobre un plan `full` con éxito — todas verifican que
sigue rechazado (`VALID_LOCAL_SUBSET_PLAN_REQUIRED` / excepción equivalente).
