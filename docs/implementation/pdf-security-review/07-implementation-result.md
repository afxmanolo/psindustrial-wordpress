# Implementación de las decisiones PDF-A y PDF-B

Fecha: 2026-09-18. Rama `feature/review-rules`. Implementa exactamente las dos decisiones
humanas aprobadas sobre los 7 PDF auditados en esta misma carpeta. No se avanzó a reglas
MEDIUM/HIGH ni a ninguna otra decisión de
[10-human-decisions.md](../review-analysis/10-human-decisions.md).

## Decisiones aplicadas

**PDF-A** (5 archivos, Grupo A): copia sanitizada autorizada para WordPress; originales en
`/legacy` inmutables. **PDF-B** (2 archivos, Grupo B): excepción puntual gateada por SHA-256
exacto, clasificación `SAFE_PDF_FALSE_POSITIVE`, `reason_code = STRUCTURALLY_VERIFIED_NO_ACTIVE_CONTENT`.

Registro de la autorización humana:
[`pdf-approvals.json`](pdf-approvals.json) — 5 entradas `group_a_sanitization` + 2 entradas
`group_b_exception`, cada una gateada por `legacy_path` **y** `source_sha256` exactos.

## Arquitectura de la implementación

**`Policy.php` no se modificó.** Ya proponía correctamente MIGRATE para los 7 medios (la
evidencia de propiedad — relación SQL con un producto R-P01-aprobado — siempre fue
correcta); el bloqueo estaba en la capa de *validación* (`Sources::asset()` →
`Media::file_valid()`), no en la de *decisión*. Por tanto la separación conceptual
Planner + Policy + decisiones humanas queda intacta: las dos decisiones PDF viven en una
capa nueva y distinta, `PdfApprovals`, que **Policy nunca conoce ni consulta**.

```
docs/implementation/pdf-security-review/pdf-approvals.json   (decisión humana, gateada por hash)
                    │
tools/pdf-sanitizer/sanitize.py   (herramienta LOCAL, produce las copias + el registro)
                    │
docs/implementation/pdf-security-review/sanitization-audit.json   (resultado auditable)
                    │
migration/PdfApprovals.php   (PHP: resuelve legacy_path+hash → sustituto o excepción)
                    │
migration/Sources.php::asset()   (consulta PdfApprovals SÓLO para mime=application/pdf)
                    │
migration/Planner.php   (usa staged_path/staged_sha256 para el staging; registra explicabilidad)
```

Ningún archivo del "motor base" (`Runner.php`, `Storage.php`, `Identity.php`,
`Media.php`) fue modificado.

### Grupo A — sustitución de bytes, sin excepción de validez

`Sources::asset()` sigue calculando `mime`/`sha256`/`valid` **exactamente igual que antes**
sobre el archivo legacy. Si hay una aprobación Grupo A vigente (hash coincide) y la
copia sanitizada existe y su propio hash coincide con el registrado en
`sanitization-audit.json`, `asset()` añade dos campos nuevos, `staged_path` y
`staged_sha256`, apuntando a la copia sanitizada — y **sólo entonces** fuerza `valid=true`,
porque la copia sanitizada genuinamente pasa `Media::file_valid()` sin ninguna excepción
(se verifica explícitamente en los tests). El campo `sha256` que se compara contra
`media-master.csv` (la comprobación de que el legacy no ha cambiado) **nunca cambia de
significado** — sigue siendo siempre el hash del archivo original.

`Planner.php` usa `staged_path`/`staged_sha256` para `Storage::stage_asset()` (que se
mantiene sin cambios) y para `$e['data']['sha256']` — de modo que el `_psi_content_sha256`
final y el `source_hash` del plan reflejan correctamente el contenido realmente importado.

### Grupo B — excepción puntual gateada por hash exacto

Aquí sí hay una excepción real: `asset()` fuerza `valid=true` sin cambiar qué bytes se
importan (se sigue usando el original, porque no hay nada que sanear). La excepción sólo se
activa cuando `legacy_path` **y** `source_sha256` coinciden exactamente con una entrada de
`pdf-approvals.json`. Verificado explícitamente en los tests: los bytes originales de estos
2 archivos **siguen** fallando la expresión regular del importador por sí solos — la
excepción está haciendo trabajo real, no es un caso que ya pasaría igualmente.

### Por qué no hay puerta trasera general

- `PdfApprovals::resolve()` sólo se consulta cuando `mime === 'application/pdf'`.
- Compara `legacy_path` **y** `source_sha256` con `hash_equals()` — nunca sólo el nombre.
- Si el hash actual del archivo no coincide exactamente, `resolve()` devuelve `null` y el
  comportamiento es **idéntico** al de antes de esta fase.
- Se verificó con un PDF sintético, no aprobado, con una acción `/OpenAction` JavaScript
  real: `Media::file_valid()` (sin modificar) lo sigue rechazando correctamente, y
  `PdfApprovals::resolve()` no lo aprueba por coincidencia.
- Se verificó sobre una muestra de PDF no relacionados del catálogo: ninguno recibe
  `pdf_approval`, todos siguen usando sus propios bytes originales.

## Sanitización — método y resultado

`tools/pdf-sanitizer/sanitize.py` (herramienta LOCAL, `pikepdf`, ver
[README](../../../tools/pdf-sanitizer/README.md) — no es dependencia de WordPress). Para
cada uno de los 5: verifica hash del original → abre en modo lectura → retira el
`Filespec`/`EmbeddedFile` alcanzable desde `/Names/EmbeddedFiles` → guarda la copia en
`psindustrial-importer-private/pdf-sanitized/<source_sha256>.pdf` (fuera de `/legacy` y
fuera de Git) → vuelve a hashear el original y confirma que sigue idéntico → corre la
batería de validación → escribe [`sanitization-audit.json`](sanitization-audit.json).

| Archivo (ruta corta) | `.joboptions` retirado | Páginas antes/después | Tamaño antes/después | Todas las validaciones |
|---|---|---:|---|---|
| `639b9801...` | High Quality.joboptions.joboptions | 4/4 | 815.558 → 789.792 B | ✅ |
| `7ab52eec...` | Rampage PDF.joboptions | 4/4 | 953.747 → 942.631 B | ✅ |
| `3af12bf1...` | Press Quality.joboptions.joboptions | 4/4 | 738.042 → 732.214 B | ✅ |
| `7baea774...` | Rampage PDF.joboptions | 4/4 | 1.583.405 → 1.521.295 B | ✅ |
| `7cf4babf...` | Rampage PDF.joboptions | 12/12 | 4.255.039 → 4.244.717 B | ✅ |

**5/5 `SANITIZED_OK`.** Las 9 comprobaciones de validación (páginas preservadas, ausencia
de `/EmbeddedFile`, ausencia de `/OpenAction`/`/AA`/`/AcroForm`/`/Names/JavaScript`, cabecera
PDF válida, no coincide con la expresión regular del importador, texto extraído
equivalente) pasaron en los 5 sin excepción. Ninguna se relajó.

**Los 5 originales bajo `/legacy` se verificaron byte-idénticos** antes y después de correr
la herramienta (hash SHA-256 comparado explícitamente).

## Trazabilidad de una copia sanitizada

Cada entrada de `sanitization-audit.json` conserva como mínimo `source_legacy_path`,
`source_sha256_approved`/`_actual`, `sanitized_sha256`, `sanitization_rule_id`,
`sanitizer_version`, `generated_at`. A nivel de plan, cada entrada del FULL DRY RUN afectada
lleva `pdf_approval_type`, `pdf_approval_rule_id` (o `pdf_approval_classification`) y
`pdf_approval_reason` — visible en el reporte exportado, no sólo en el registro en disco.

**Limitación conocida, documentada, no resuelta en esta fase:** el usuario final no ve esta
metadata en el editor normal (correcto, ya lo cumple el patrón `_psi_*` existente), pero
escribir una meta dedicada (`_psi_pdf_sanitization`) en el *attachment* de WordPress
requiere un pequeño cambio en `Runner::apply()`, que esta fase **no realiza** porque
`Runner.php` está en la lista de archivos que no se deben tocar. Hoy la trazabilidad completa
vive en el plan/reporte y en los dos JSON de esta carpeta; completar la trazabilidad hasta el
propio attachment de WordPress es trabajo futuro, para cuando esta fase se ejecute de verdad.

## Hallazgo reportado, no corregido: revalidación de `Runner::media()` en tiempo de ejecución

> **Resuelto (misma fecha, corrección posterior):** ver
> ["Runtime validation consistency"](#runtime-validation-consistency) al final de este
> documento. Esta sección se conserva sin reescribir como registro histórico de lo que se
> detectó y por qué en ese momento se decidió reportarlo en vez de tocar `Runner.php`.

`Runner::media()` (sin modificar) vuelve a llamar a `Media::file_valid()` **directamente**,
de forma independiente, en el momento de la ejecución real (no en esta fase, que nunca
ejecuta nada). Para el Grupo A esto funciona sin ningún ajuste: los bytes preparados son los
saneados, que pasan la comprobación genuinamente. **Para el Grupo B, esta revalidación
volvería a fallar** (los bytes preparados son los originales, que siguen sin pasar la
expresión regular por sí solos) — el plan diría `CREATE`, pero una ejecución real fallaría
con `MEDIA_CHANGED_OR_UNSAFE`.

Esto es exactamente la clase de problema que la tarea pide reportar en vez de corregir
tocando `Runner`. Se deja documentado aquí, explícitamente, para la fase en que se autorice
una ejecución real de estos objetos: en ese momento hará falta un cambio acotado (que
`Runner::media()` también consulte `PdfApprovals`, o que `Media::file_valid()` acepte un
hash aprobado opcional) antes de que el Grupo B pueda ejecutarse con éxito. **No se ha
tocado `Runner.php` en esta fase.**

## Productos dependientes

Los 11 productos (65, 66, 67, 68, 69, 70, 71, 72, 73, 77, 95) se reevaluaron con
`Planner::build('full')`, no se aprobaron a mano. **Los 11 dejaron REVIEW** porque, una vez
resuelta su única dependencia bloqueante (la ficha técnica), sus demás condiciones R-P01
(grupo canónico unitario, PHP unívoco, categoría propia aprobada, sin conflicto D03/D06) ya
estaban satisfechas desde la fase LOW anterior — no se relajó ni se añadió ninguna condición
nueva de aprobación de producto. Ninguno tiene otro conflicto pendiente.

## Reconciliación — antes / después de LOW / después de PDF

```
BASELINE (fase importador, antes de cualquier regla):
  2.399 fuentes · 15 UNCHANGED · 435 SKIP · 1.949 REVIEW · 0 CREATE/UPDATE/MERGE/ERROR

DESPUÉS DE LAS REGLAS LOW (review-rules/04-full-dry-run-after-low-rules.md):
  2.399 fuentes · 15 UNCHANGED · 1.281 SKIP · 153 CREATE · 950 REVIEW · 0 ERROR

DESPUÉS DE LAS APROBACIONES PDF (esta fase):
  2.399 fuentes · 15 UNCHANGED · 1.281 SKIP · 171 CREATE · 932 REVIEW · 0 ERROR
```

`950 − 932 = 18`. **Coincide exactamente con lo esperado — no fue necesario forzar nada.**
Los 18 (7 medios + 11 productos) dejaron REVIEW; 0 permanecen bloqueados por el PDF.

### Desglose por `source_type`

| source_type | UNCHANGED | SKIP | CREATE (después LOW → después PDF) | REVIEW (después LOW → después PDF) |
|---|---:|---:|---|---|
| media | 8 | 846 | 94 → **101** (+7) | 583 → **576** (−7) |
| category | 2 | 0 | 33 | 3 |
| brand | 1 | 0 | 11 | 0 |
| product | 2 | 0 | 10 → **21** (+11) | 153 → **142** (−11) |
| static_product | 1 | 0 | 0 | 31 |
| page | 1 | 435 | 5 | 176 |
| missing_media | 0 | 0 | 0 | 4 |
| **total** | **15** | **1.281** | **153 → 171** | **950 → 932** |

### Las 18 filas, una por una

Las 7 rutas de medios y los 11 `sql:productos:*` listados en
[03-product-impact.md](03-product-impact.md) — **las 18 pasaron de `REVIEW` a
`action=MIGRATE`/`planned_result=CREATE`**. Ninguna quedó en un estado intermedio. Detalle
completo, fila por fila, con `notes` y campos `pdf_approval_*`, en el reporte exportado
(`docs/implementation/importer-reports/pdf-approvals-tests.json` y el `run_id` del último
FULL DRY RUN, disponible bajo el directorio privado del importador).

## Pruebas

Nueva suite: [`tests/pdf-approvals.php`](../../../wordpress/wp-content/plugins/psindustrial-core/tests/pdf-approvals.php)
— **180 comprobaciones**, todas en verde. Cobertura exacta de lo pedido:

**Grupo A:** hash correcto → sustituto sanitizado; hash incorrecto → bloquea; original
permanece byte-idéntico (verificado por hash); copia sanitizada no contiene el patrón
aprobado (verificado por regex directo sobre los bytes); páginas preservadas (desde el
registro auditable); resultado pasa `Media::file_valid()` real, sin modificar.

**Grupo B:** hash aprobado + clasificación exacta → SAFE; mismo path, hash distinto → NO
SAFE; un PDF sintético con `/OpenAction` JavaScript real, no aprobado → sigue bloqueado por
la comprobación sin modificar; excepción no afecta a una muestra de 15 PDF no relacionados
del catálogo (ninguno recibe `pdf_approval`).

**Suites anteriores, sin cambios, todas en verde:**

```
smoke.php:        todas las comprobaciones OK
importer.php:      {"passed":true,"checks":67,...}
policy.php:        {"passed":true,"checks":6346,...,"review_after":932}
pdf-approvals.php:  {"passed":true,"checks":180,...,"review_after":932}
```

(`policy.php` sube de 6.314 a 6.346 comprobaciones porque sus bucles genéricos de
explicabilidad ahora iteran sobre más entradas resueltas — ninguna aserción se relajó ni se
volvió a escribir; el aumento es puramente el resultado de más entradas cumpliendo las
mismas condiciones ya existentes.)

## Corrección sobre documentación previa

[06-known-issues.md](06-known-issues.md) (fase LOW anterior) afirmó originalmente que los 7
PDF coincidían por `/EmbeddedFile`. La auditoría de esta misma carpeta
([02-embedded-files-analysis.md](02-embedded-files-analysis.md)) ya corrigió esto con
evidencia: **Grupo A (5) = `/EmbeddedFile` real; Grupo B (2) = falso positivo `/JS`**. Este
documento no reescribe esa corrección; la reutiliza como base de la implementación. Se
mantiene el historial: `06-known-issues.md` de la fase LOW queda como estaba (refleja lo que
se sabía en ese momento), y esta carpeta documenta la corrección y su resolución.

## Qué NO se hizo en esta fase

- No se avanzó a ninguna regla MEDIUM o HIGH.
- No se respondió ninguna otra decisión de
  [10-human-decisions.md](../review-analysis/10-human-decisions.md).
- No se ejecutó ninguna importación real (`Runner::batch()` con intención de ejecutar sigue
  rechazando cualquier plan `full`, verificado explícitamente en los tests).
- No se publicó contenido.
- No se incrementó la versión del plugin (sigue en 0.3.0, a la espera de que se acepte y se
  haga commit de toda la fase `review-rules`).
- No se tocó `Runner.php`, `Storage.php`, `Identity.php` ni `Media.php`. *(Esta afirmación
  describía el estado al cierre de esta implementación. `Runner.php` sí se tocó después,
  de forma mínima y explícitamente autorizada, para resolver la inconsistencia DRY RUN vs.
  ejecución real — ver ["Runtime validation consistency"](#runtime-validation-consistency)
  más abajo. `Storage.php`, `Identity.php` y `Media.php` siguen sin ningún cambio.)*

---

## Runtime validation consistency

Corrección posterior a la implementación anterior, misma fecha, alcance estrictamente
acotado a un único problema detectado durante la verificación de esta fase.

### Problema encontrado

El FULL DRY RUN reconocía correctamente los 2 PDF del Grupo B a través de `PdfApprovals`
(vía `Sources::asset()`, en el momento de construir el plan). Pero `Runner::media()`
—código ya existente, que vuelve a validar los bytes de forma **independiente** en el
momento de la ejecución real— llama a `Media::file_valid()` directamente sobre los bytes
preparados. Para el Grupo B esos bytes son los originales (no hay nada que sanear), y
siguen sin pasar la expresión regular por sí solos. Resultado: el plan decía `CREATE`, pero
una ejecución real habría fallado con `MEDIA_CHANGED_OR_UNSAFE`. Detectado y reportado —sin
corregir— en la sección anterior de este mismo documento.

### Solución — modificación mínima, en dos archivos

**`migration/PdfApprovals.php`** (+16 líneas): un método nuevo,
`isApprovedFalsePositive( string $legacyPath, string $sha256 ): bool` — un envoltorio fino,
booleano, sobre el `resolve()` ya existente. Devuelve `true` únicamente cuando
`legacy_path` **y** `sha256` coinciden exactamente con una entrada `group_b_exception` de
`pdf-approvals.json` (`type === 'exception'`); nunca para una aprobación `sanitized` del
Grupo A, que no necesita esta vía porque sus bytes preparados ya pasan
`Media::file_valid()` genuinamente. No añade ninguna lógica de coincidencia nueva: reutiliza
por completo `resolve()`, que sigue siendo la única fuente de verdad.

**`migration/Runner.php`** (+16/-1 líneas): la lógica de validación de `media()` se extrajo
a un método privado nuevo, puro y sin efectos secundarios:

```php
private static function media_is_valid( string $source, string $mime, string $legacyPath ): bool {
 if ( \PSIndustrial\Core\Media::file_valid( $source, $mime ) ) { return true; }
 return 'application/pdf' === $mime && PdfApprovals::isApprovedFalsePositive( $legacyPath, hash_file( 'sha256', $source ) );
}
```

y `media()` pasa a usarlo:

```php
if ( ! hash_equals( $d['sha256'], hash_file( 'sha256', $source ) ) || ! self::media_is_valid( $source, $d['mime'], $d['path'] ?? '' ) ) { throw new \RuntimeException( 'MEDIA_CHANGED_OR_UNSAFE' ); }
```

Exactamente la fórmula pedida: `valid = Media::file_valid(...) OR excepción_explícita(...)`.
La comprobación de deriva de hash (`hash_equals($d['sha256'], ...)`) —que detecta si el
contenido preparado cambió desde que se construyó el plan— **no se tocó** y sigue siendo la
primera condición, incondicional, para cualquier archivo.

### Por qué esto no debilita `Media::file_valid()` globalmente

- La función `Media::file_valid()` **no se modificó ni una línea**. Sigue siendo la primera
  comprobación, sin ningún parámetro nuevo, con el mismo comportamiento exacto para
  cualquier llamador que no sea este.
- La segunda condición sólo se evalúa **si la primera ya falló**, y sólo para
  `mime === 'application/pdf'` — para imágenes, nunca se consulta `PdfApprovals`.
- `isApprovedFalsePositive()` exige coincidencia exacta de **`legacy_path` y `sha256`** vía
  `hash_equals()` contra una lista cerrada de 2 entradas. No hay coincidencia por nombre de
  archivo, por MIME, por prefijo de ruta ni por patrón de contenido.
- Si el hash actual no coincide exactamente (byte alterado, archivo distinto, ruta
  distinta), la función devuelve `false` y `media_is_valid()` cae al bloqueo normal —
  **nunca** `false → true`.
- Se verificó explícitamente con un PDF sintético con una acción `/OpenAction` JavaScript
  real, y con un PDF sintético que reproduce el mismo patrón de falso positivo del Grupo B
  pero en una ruta no aprobada: ambos **siguen bloqueados** por `media_is_valid()`.

### Comportamiento por caso

| Caso | `Media::file_valid()` sola | `media_is_valid()` | Resultado |
|---|---|---|---|
| PDF ordinario (15 muestreados del catálogo) | según corresponda | idéntico a la columna anterior | **sin cambio de comportamiento** |
| Grupo A (bytes preparados = copia saneada) | `true` (genuinamente) | `true` | permitido sin necesitar la excepción |
| Grupo B (bytes preparados = original) | `false` (falso positivo conocido) | `true`, vía excepción exacta por hash | permitido |
| Grupo B con hash distinto | `false` | `false` | bloqueado |
| Mismo path del Grupo B, bytes de otro archivo real | `false` | `false` | bloqueado |
| PDF sintético con el mismo patrón `/JS`, ruta no aprobada | `false` | `false` | bloqueado |
| PDF sintético con `/OpenAction` JavaScript real | `false` | `false` | bloqueado |

### `staged_path`/`staged_sha256` para el Grupo A — verificado, no sólo revisado

Se confirmó con los 5 archivos reales del Grupo A, leyendo directamente los bytes que
`Runner::media()` usaría (`Storage::path($e['data']['package_asset'])`, ya preparados por
`Planner`/`Sources` desde la fase anterior, sin cambios en esta):

- el hash de esos bytes **difiere** del hash del original bajo `/legacy` (el original nunca
  se usa);
- esos bytes **no contienen** ningún rastro del patrón que bloqueó el original;
- pasan `Media::file_valid()` **por sí solos**, sin necesitar la excepción del Grupo B.

### Tests

Suite nueva:
[`tests/runner-media-validation.php`](../../../wordpress/wp-content/plugins/psindustrial-core/tests/runner-media-validation.php)
— **82 comprobaciones**. Accede a `Runner::media_is_valid()` (privado) únicamente mediante
`ReflectionMethod`; nunca llama a `Runner::batch()`, `media_handle_sideload()` ni crea
ningún contenido de WordPress. Cobertura exacta de lo pedido:

**Grupo A:** el original con `/EmbeddedFile` no se usa (hash distinto, patrón ausente);
`staged` saneado es válido por sí solo; hash `staged` correcto → permitido (comprobación de
deriva); `staged` alterado (copia temporal con un byte modificado, nunca el archivo real) →
bloqueado por la comprobación de deriva antes incluso de llegar a `media_is_valid()`.

**Grupo B:** hash exacto aprobado → permitido; mismo path con hash incorrecto → bloqueado;
mismo path con bytes de otro archivo real → bloqueado; PDF sintético con el mismo patrón
`/JS` en una ruta no aprobada → bloqueado; PDF sintético con `/OpenAction` JavaScript real
→ bloqueado.

**PDF normal:** muestra de 15 PDF no relacionados del catálogo — resultado de
`media_is_valid()` idéntico al de `Media::file_valid()` sola en los 15.

**Mime no-PDF:** la vía de excepción nunca se consulta, verificado explícitamente.

**Suites anteriores, sin cambios, todas en verde tras la corrección:**

```
smoke.php:                    todas las comprobaciones OK
importer.php:                 {"passed":true,"checks":67,...}
policy.php:                   {"passed":true,"checks":6346,...,"review_after":932}
pdf-approvals.php:             {"passed":true,"checks":180,...,"review_after":932}
runner-media-validation.php:   {"passed":true,"checks":82,"review_after":932}
```

### FULL DRY RUN — conteo sin cambios

```
REVIEW = 932   (idéntico a antes de esta corrección)
```

Verificado explícitamente en la nueva suite y en las anteriores. Es exactamente el resultado
esperado: esta corrección cambia únicamente qué pasaría en una **ejecución real futura**
(`Runner::media()`), nunca las decisiones que `Planner`/`Policy`/`PdfApprovals` ya tomaban en
el plan. Ningún número del plan se movió.

### Qué sigue sin tocarse

`Storage.php`, `Identity.php`, `Media.php`, `Policy.php`, las reglas LOW, la semántica del
manifest, el bloqueo de importación completa, el límite de subset, la frase de confirmación
y la protección de entorno. No se implementó ninguna regla MEDIUM/HIGH ni se respondió
ninguna otra decisión humana. No se incrementó la versión del plugin. No hubo commit ni
push.
