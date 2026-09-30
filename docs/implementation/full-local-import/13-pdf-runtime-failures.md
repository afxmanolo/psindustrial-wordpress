# Fallos de runtime PDF — diagnóstico completo

Continúa de [12-post-import-dry-run.md](12-post-import-dry-run.md). Cubre el diagnóstico
completo de los 52 `FAILED` y la reclasificación de los 10 `CONFLICT` de la primera
ejecución real (`run_id=2e0c1248-8d56-4d92-a33b-c17e37b2732e`). No se corrige nada en este
documento — ver [14-pdf-runtime-fix.md](14-pdf-runtime-fix.md) para las correcciones.

## Resultado de la clasificación exacta (62 entradas no aplicadas)

```
DIRECT_PDF_A              14   (bug 1: media_changed_replan)
DIRECT_PDF_B               13   (bug 2: media_sideload_failed)
CASCADE_FROM_PDF_A         10   (9 productos + 1 static_product)
CASCADE_FROM_PDF_B         11   (productos)
LEGITIMATE_CATEGORY_CONFLICT 4  (productos que dependen de category:37/38)
LEGITIMATE_SLUG_COLLISION  10   (los 10 CONFLICT originales)
OTHER                       0
```

14+13+10+11+4 = 52 (los `FAILED`). +10 (`CONFLICT`) = 62. **Cero `OTHER`**: no existe un
tercer bug — confirmado por clasificación exhaustiva y programática, no por inspección
manual de una muestra.

## Bug 1 — `MEDIA_CHANGED_REPLAN` (Grupo A)

`Runner::process_entry()` comparaba `$e['data']['sha256']` (el hash **staged**, que
`Planner::build()` sobrescribe con el hash del sustituto saneado para una aprobación Grupo A)
contra el hash del archivo **original** en `/legacy/public`. Para cualquier PDF saneado
ambos hashes difieren por diseño — el saneado reescribe bytes intencionalmente para eliminar
contenido activo. La comprobación por tanto fallaba siempre para los 14 casos Grupo A,
nunca por deriva real.

Efecto: **fail-closed**, nunca fail-open. Ningún PDF no aprobado se aceptó por este bug.

## Bug 2 — `MEDIA_SIDELOAD_FAILED` (Grupo B)

WordPress registra `Media::upload()` en `wp_handle_sideload_prefilter` — un filtro genérico,
siempre activo, para **cualquier** subida/sideload en toda la instalación, sin conocimiento
de las excepciones `PdfApprovals` Grupo B. `Runner::media_is_valid()` aprueba correctamente
el archivo *antes* de intentar el sideload, pero WordPress revalida internamente de forma
independiente y lo rechaza de nuevo, con el mismo falso positivo `/JS` que el revisor humano
ya evaluó y aprobó por hash exacto.

Reproducido de forma aislada llamando directamente a `media_handle_sideload()` sobre
`fichas/Clopay-3720-07.pdf` (aprobación Grupo B confirmada,
`sha256 === staged_sha256`): `WP_Error{code: 'upload_error', message: 'Archivo no
permitido...'}`.

Efecto: igual que el Bug 1, **fail-closed**.

## Cascada `OBJECT_OPERATION_FAILED` (25 productos/fichas)

`process_entry()` valida `$e['dependencies']` antes de llamar a `apply()`: si una
dependencia no tiene `wordpress_id`, lanza `DEPENDENCY_NOT_APPLIED:<clave>`.
`Runner::safe_error()` sólo deja pasar mensajes `/^[A-Z0-9_: .-]+$/D` — la clave
interpolada (minúsculas, `/`) no cumple el patrón, así que el saneador la sustituye por el
mensaje genérico `OBJECT_OPERATION_FAILED` antes de guardarla — funcionando exactamente
según su diseño (nunca filtrar texto dinámico sin controlar). La causa real se reconstruyó
cruzando cada producto fallido contra las claves de dependencia declaradas en su propia
entrada del plan:

- **21 de 25** dependen de un activo Bug 1 o Bug 2 (10 de A, 11 de B) → `CASCADE_FROM_PDF_A`
  / `CASCADE_FROM_PDF_B`.
- **4 de 25** (`sql:productos:1,46,47,48`) dependen de `category:37`/`category:38`, un
  conflicto de slug **legítimo y preexistente**, ajeno a ambos bugs → nunca deben
  reintentarse automáticamente.

## Los 10 `CONFLICT` originales: reclasificados como colisión de slug, no como bug

`Identity::prediction()` devuelve `CONFLICT` por tres caminos estructuralmente distintos, no
uno solo:

1. Sin objeto WordPress, con un *ledger* de intento (`status=INTENT`, sin `wordpress_id`) →
   intento fallido propio, **retryable** una vez corregida su causa.
2. Sin objeto, sin ledger, pero **colisión de slug** contra un objeto WordPress ya existente
   bajo otra `entity_key` → conflicto legítimo, **nunca retryable** automáticamente.
3. Objeto WordPress existente cuyo snapshot actual no coincide con el estado registrado
   ("destino editado") → conflicto legítimo, **nunca retryable** automáticamente.

Verificado directamente (no asumido): los 10 `CONFLICT` originales (`category:37`,
`category:38`, 8 páginas `php:`) son **todos** el camino 2 — cada uno colisiona con un
objeto WordPress real ya existente bajo una `entity_key` distinta:

| Entidad | Slug | Colisiona con |
|---|---|---|
| `category:37` | `operadores-para-puerta-corrediza` | `category:11` |
| `category:38` | `operadores-para-puerta-abatible` | `category:12` |
| las 8 páginas `php:*` | `psi-puertas-y-servicios-industriales` | `php:contacto.php` |

El hallazgo de que 8 páginas distintas colisionan con el mismo slug que `php:contacto.php`
es una observación factual, no diagnosticada más allá de esto — **fuera del alcance de esta
tarea** (limitada a los dos bugs PDF). Se documenta para una revisión editorial futura, sin
tocar nada.

## Camino 1 (intento fallido propio): verificado en los 13 `DIRECT_PDF_B`

Los 13 casos Grupo B **sí** llegan a `apply()` (que escribe su *ledger* en `status=INTENT`
antes de intentar el sideload) antes de fallar dentro de `self::media()` — dejando un
*ledger* huérfano que, al reconstruir el plan, hace que `Identity::prediction()` devuelva
`CONFLICT` por el camino 1, no por fallo directo. Confirmado directamente sobre los 477
archivos `identity-<token>.json` supervivientes: los 13 tienen `status=INTENT`,
`wordpress_id` vacío y `run_id` igual al de esta ejecución. Los 14 casos Grupo A y los 21 de
cascada **nunca** llegan a escribir *ledger* (el fallo ocurre antes, dentro de
`process_entry()`), así que no dejan rastro de intento — en un plan reconstruido aparecen
como `CREATE` limpio, no como `CONFLICT`.

## Incidente operativo descubierto durante este diagnóstico: poda de `run-<id>.json`

Al construir este documento, `Storage::read('run-2e0c1248-8d56-4d92-a33b-c17e37b2732e.json')`
devolvió `null`. Causa confirmada: `Storage::retain_recent_runs()` (invocado en **cada**
`Planner::build()`) conserva sólo los 30 `run-*.json` más recientes por fecha de
modificación, excluyendo únicamente los que tienen `status=RUNNING`. Un plan `COMPLETE` —
aunque represente una ejecución real, respaldada, con objetos WordPress reales creados a
partir de él — no está exento. Las decenas de reconstrucciones de plan (`Planner::build('full')`)
realizadas durante el diagnóstico de estos mismos bugs, sumadas a la suite de regresión
completa (que también reconstruye planes), empujaron el archivo fuera de la ventana de los
30 más recientes.

**Qué se perdió**: únicamente el archivo agregado `run-<id>.json` (plan + resultados de esa
ejecución específica). **Qué sobrevivió intacto**, verificado directamente:

- `backup-2e0c1248-....json` (1.856.954 bytes) y `backup-uploads-2e0c1248-.../` — no coinciden
  con el patrón `run-*.json`.
- Los 477 archivos `identity-<token>.json` (el *ledger* de auditoría por entidad) — tampoco
  coinciden con ese patrón.
- Todos los objetos WordPress reales creados por la ejecución (449).

Ningún dato de la migración en sí se perdió — sólo el snapshot agregado de auditoría de esa
ejecución. La tabla de 62 filas de este documento, una vez confirmada mediante el
mecanismo real (`Runner::retry_root_cause()`/`retry_eligibility()`, no reinventado ad hoc) y
publicada aquí, es ahora el registro permanente y determinante de esa ejecución — inmune a
cualquier poda futura, a diferencia del `run-<id>.json` transitorio.

**No se intentó reconstruir ni escribir un `run-2e0c1248-....json` de reemplazo** — habría
sido exactamente el tipo de "editar manualmente identities/manifest" que este proyecto
prohíbe explícitamente, sin la garantía de integridad real que da el sello `plan_hash`
original. `Runner::retry_preflight()`, al ejecutarse honestamente contra este `run_id` ahora,
reporta correctamente `ok=false` con el bloqueador `plan_exists` — nunca fabrica un
resultado. Ver [16-retry-preflight.md](16-retry-preflight.md).

**Recomendación para una fase futura, no implementada aquí** (fuera del alcance de dos
bugs PDF): eximir de poda, además de `status=RUNNING`, cualquier plan `COMPLETE` que tenga
`backup` registrado — mismo principio de exención ya aplicado a `RUNNING`, extendido a
"representa una mutación real ya ejecutada", no sólo "está en curso ahora mismo".

## Tabla completa — 62 entradas no aplicadas

Fuente: copia fiel de `plan['results']` (`FAILED`/`CONFLICT`) tomada durante la auditoría
posterior a la importación, antes de la poda descrita arriba; causa raíz y elegibilidad
recalculadas después de aplicar las correcciones, con el mecanismo real
(`Runner::retry_root_cause()`/`retry_eligibility()`), contra un plan reconstruido
(`Planner::build('full')`, determinista — mismas 2.399 entradas, mismos hashes).

| source_key | original_result | root_cause | retryable | expected_after_fix |
|---|---|---|:---:|---|
| asset:fichas/Clopay-3720-07.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/CMDC-0524SP-14-1.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/CMDC-3717-3718-11.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/commercial-aluminum-door-systems-brochure.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/Cortina-serie-610-620.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/Kelley-Hydraulic-Dock-Leveler-Brochure_web.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/lisos.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/moovi50rm.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/puerta-424.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-430.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-seccional-418.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-seccional-422.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-seccional-426.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-seccional-432.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-thermacore-593.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-thermacore-594.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/puerta-thermacore-595.pdf | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:fichas/rhx-commercial-operator-brochure.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/rolling-steel-doors-610-620-brochure.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:fichas/StrongArm_HVR303_Brochure_Spanish.pdf | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:system/files/images/productos/3af12bf12f61eda51a19d1e8af3cefc05878f484 | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:system/files/images/productos/3e620ed84c43c5de234ae437f58f7083e93f6b8d | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| asset:system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0 | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:system/files/images/productos/7ab52eec612af22d23a62f849479371ebf1c2f22 | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:system/files/images/productos/7baea774c5fc4abe3735d40fdf16e84f1e3521ef | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:system/files/images/productos/7cf4babfab2712e41d6fd1c050f5c31b604c97ae | FAILED | DIRECT_PDF_A | ✅ | CREATE |
| asset:system/files/images/productos/98b07de6dca4ad402a37385c485fb6999d5c3323 | FAILED | DIRECT_PDF_B | ✅ | CREATE |
| sql:productos:1 | FAILED | LEGITIMATE_CATEGORY_CONFLICT | ❌ | CONFLICT (sin cambio) |
| sql:productos:5 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:6 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:7 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:17 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:18 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:19 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:22 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:24 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE (Kelley 24+139, Q03) |
| sql:productos:44 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:46 | FAILED | LEGITIMATE_CATEGORY_CONFLICT | ❌ | CONFLICT (sin cambio) |
| sql:productos:47 | FAILED | LEGITIMATE_CATEGORY_CONFLICT | ❌ | CONFLICT (sin cambio) |
| sql:productos:48 | FAILED | LEGITIMATE_CATEGORY_CONFLICT | ❌ | CONFLICT (sin cambio) |
| sql:productos:65 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:66 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:67 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:68 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:69 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:70 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:71 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:72 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:73 | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| sql:productos:77 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| sql:productos:95 | FAILED | CASCADE_FROM_PDF_B | ✅ | CREATE |
| static:puerta-seccional-de-acero-thermacore-595-uso-pesado.php | FAILED | CASCADE_FROM_PDF_A | ✅ | CREATE |
| category:37 | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| category:38 | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:cortinas-enrollables-de-aluminio.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:index-estatico.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:index-resp.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:index.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:politica-privacidad.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:puertas-de-garaje-aisladas.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:puertas-enrollables-de-garage.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |
| php:tiras-plasticas-hawaianas.php | CONFLICT | LEGITIMATE_SLUG_COLLISION | ❌ | CONFLICT (sin cambio) |

**48 retryable, 14 no retryable (4 cascada de categoría + 10 colisión de slug), 0 `OTHER`.**

Continúa en [14-pdf-runtime-fix.md](14-pdf-runtime-fix.md).
