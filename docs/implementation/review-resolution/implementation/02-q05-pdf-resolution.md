# Q05 — Extensión de la política PDF a los 32 archivos restantes

Implementa Q05 = Opción A: extiende `pdf-approvals.json` con el mismo modelo ya aprobado
(hash/auditoría/saneado), sin relajar `Media::file_valid()` globalmente y sin convertirlo
en "cualquier PDF similar → permitido". Cada uno de los 32 archivos tiene su propia entrada
individual, gateada por `legacy_path` + SHA-256 exactos. Detalle fila por fila en
[q05-pdf-audit.csv](q05-pdf-audit.csv).

## Clasificación final de los 32

| Bucket (triaje previo) | Archivos | Mecanismo | Clasificación |
|---|---:|---|---|
| A — gemelo de saneado ya aprobado | 9 | `group_a_sanitization`, reutiliza el sustituto saneado existente por contenido | — |
| C — mismo perfil que A, contenido nuevo | 1 (`puerta-thermacore-595.pdf`) | `group_a_sanitization`, saneado propio | — |
| B — gemelo de excepción ya aprobada | 2 | `group_b_exception` | `SAFE_PDF_FALSE_POSITIVE` |
| D+E — `/OpenAction` real, navegación interna verificada | 9 (4 hashes × 2 rutas + 1) | `group_b_exception` | `SAFE_PDF_BENIGN_INTERNAL_ACTION` (nueva, más precisa que "falso positivo") |
| F — sin estructura activa, coincidencia de regex casual | 11 | `group_b_exception` | `SAFE_PDF_FALSE_POSITIVE` |

Total: 10 nuevas en `group_a_sanitization` (15 en total con las 5 originales) + 22 nuevas
en `group_b_exception` (24 en total con las 2 originales) = 39 entradas, cubriendo los 7
originales + 32 nuevos.

`SAFE_PDF_BENIGN_INTERNAL_ACTION` / `STRUCTURALLY_VERIFIED_INERT_NAVIGATION` es una
clasificación **nueva**, deliberadamente distinta de `SAFE_PDF_FALSE_POSITIVE`: estos 9
archivos sí tienen una clave `/OpenAction` real (no es una coincidencia casual de bytes),
pero su recorrido completo del grafo de objetos (pikepdf, sólo lectura) confirma que el
destino es siempre un objeto `/Page` dentro del **mismo** documento — nunca `/Launch`,
`/URI`, `/JavaScript`, `/SubmitForm`, `/ImportData`, `/GoToR`, `/GoToE`, `/Sound`, `/Movie`,
`/Rendition` ni `/Hide` en ningún lugar del archivo. Mismo mecanismo de aprobación
(`group_b_exception`, gateado por hash+ruta exactos), etiqueta más honesta.

## Resultado de resolución (no de conteo forzado)

De los 32 archivos:

- **32/32** tienen su bloqueo de **tipo/seguridad** resuelto: `PdfApprovals::resolve()`
  devuelve una aprobación válida para los 32 (verificado en
  [tests/q05-pdf-resolution.php](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/q05-pdf-resolution.php)).
- **4** quedan completamente desbloqueados (`action: MIGRATE`) porque además son
  dependencia de un producto que Q02 fusionó con éxito: `Clopay-3720-07.pdf` (productos
  19/61), `CMDC-0524SP-14-1.pdf` (18/58), `rolling-steel-doors-610-620-brochure.pdf`
  (7/91), `StrongArm_HVR303_Brochure_Spanish.pdf` (22/144).
- **1** (`system/files/images/productos/800c352920...`, el PDF exclusivo del producto 165)
  tiene su bloqueo de seguridad resuelto pero su destino final es `SKIP` — decidido por
  Q06, no por Q05. Ver más abajo.
- **27** siguen en `REVIEW`, **no por seguridad**: carecen de una decisión de propietario
  (ningún producto/página/término aprobado los reclama todavía). Se confirma con el campo
  `media_validation = VALID_BYTES_REQUIRES_APPROVAL` en el plan — esa etiqueta sólo
  aparece cuando los bytes ya pasarían la validación pero no existe ninguna decisión que
  los reclame. Ejemplo notable: `fichas/lisos.pdf` y su gemelo SQL, cuyos únicos
  propietarios documentados (productos 6/16/20/107) son el registro de conflicto de marca
  D03 (`Policy::BRAND_CONFLICT_PRODUCTS`), fuera del alcance de esta fase.

**No se esperaba ni se forzó que las 41 filas originalmente atribuidas a Q05 (por el
análisis de fase 8) desaparecieran de REVIEW.** Q05 resuelve el bloqueo de seguridad; el
bloqueo de propiedad, cuando existe, sigue abierto por su propia causa y se resolverá
cuando se responda Q01 (o la decisión de marca/categoría correspondiente).

## Reutilización sin regeneración

Los 11 archivos "gemelo" (bucket A ×9 + B ×2) comparten SHA-256 con un archivo ya aprobado
en una fase anterior, alcanzado por una ruta distinta — típicamente la ruta estática de
página (`fichas/*.pdf`) frente a la ruta derivada de SQL
(`system/files/images/productos/<hash>`) del **mismo** producto. `PdfApprovals::resolve()`
exige coincidencia exacta de `legacy_path` **y** hash (nunca sólo hash), así que cada
gemelo necesita su propia entrada — pero el saneado real (`sanitize.py`) sólo se ejecuta
**una vez por hash único**; los gemelos reutilizan el mismo binario saneado por contenido,
nunca se regenera ni se duplica.

## Bug de saneado encontrado y corregido

Al extender `group_a_sanitization` con gemelos que comparten `source_sha256`, la primera
ejecución de `sanitize.py` reveló un problema real: cada entrada procesaba su propio
archivo de origen de forma independiente y **sobrescribía** el mismo archivo de salida
direccionado por contenido (`<source_sha256>.pdf`). `pikepdf.save()` no garantiza bytes
idénticos entre ejecuciones separadas sobre el mismo contenido lógico (el orden interno de
objetos puede variar) — así que la **última** entrada en procesar un hash dado dejaba el
archivo con un valor, mientras el registro de auditoría de las entradas **anteriores** con
ese mismo hash seguía citando un `sanitized_sha256` que ya no coincidía con el archivo real
en disco.

Consecuencia observada: `PdfApprovals::resolve()` para `system/files/images/productos/639b9801...`
(la entrada original del Grupo A) devolvía `null` — no por ningún fallo de aprobación, sino
porque su registro de auditoría había quedado obsoleto en cuanto se procesó
`fichas/puerta-424.pdf` (mismo hash, entrada posterior). El sistema falló de forma segura
(rechazó en vez de aceptar algo incorrecto), pero silenciosamente.

Corrección aplicada a `tools/pdf-sanitizer/sanitize.py` (única modificación de ese archivo
en esta fase): se sanea **una sola vez por `source_sha256` único** — la primera entrada con
un hash dado hace el trabajo real (abrir, limpiar `/EmbeddedFile`, guardar, validar); toda
entrada posterior con el mismo hash **reutiliza ese resultado** sin volver a abrir/guardar,
after verificando independientemente que su propio archivo de origen tiene exactamente ese
hash (defensa en profundidad: una declaración de "gemelo" incorrecta en `pdf-approvals.json`
seguiría detectándose). Verificado tras el arreglo: las 15 entradas de
`sanitization-audit.json` tienen `sanitized_sha256` idéntico al hash real del archivo que
citan — cero discrepancias.

## Trazabilidad

[q05-pdf-audit.csv](q05-pdf-audit.csv) — una fila por archivo: ruta, SHA-256, bucket,
tipo de aprobación, clasificación, productos PMR, acción final de la entidad, motivo si
sigue en REVIEW. [pdf-approvals.json](../../../pdf-security-review/pdf-approvals.json) —
las 39 entradas completas con su justificación individual.
