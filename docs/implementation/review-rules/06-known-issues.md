# Límites y hallazgos de esta fase

> **Corrección 2026-09-18 (fase posterior, misma fecha):** el título de la sección 1
> siguiente y su primer párrafo dicen que los 7 PDF coinciden por `/EmbeddedFile`. La
> auditoría dedicada (`docs/implementation/pdf-security-review/`) demostró que eso sólo es
> cierto para **5 de los 7** (Grupo A). Los otros **2** (Grupo B: productos 77 y 95)
> coinciden por `/JS`, y esa coincidencia es un falso positivo de 3 bytes dentro de un
> stream binario comprimido — no hay ningún objeto `/EmbeddedFile` en esos 2 archivos. El
> texto original de esta sección se conserva sin reescribir como registro histórico de lo
> que se sabía al cierre de la fase LOW; ver
> [`../pdf-security-review/02-embedded-files-analysis.md`](../pdf-security-review/02-embedded-files-analysis.md)
> para el análisis correcto y completo, y
> [`../pdf-security-review/07-implementation-result.md`](../pdf-security-review/07-implementation-result.md)
> para cómo se resolvieron los 7 (ambos grupos ya aprobados e implementados).

## 1. Hallazgo real: 7 PDF de ficha técnica con referencia `/EmbeddedFile`

**Esto no es un defecto de esta fase — es nueva evidencia que las reglas LOW revelaron al
validar contra bytes reales**, exactamente el tipo de hallazgo que el encargo pide documentar
(punto 16: "documenta únicamente si una regla LOW revela nueva evidencia").

### Qué se encontró

`Media::file_valid()` (código existente, sin modificar) rechaza cualquier PDF cuyo contenido
contenga el patrón `/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b`. Al proponer R-M01
para las fichas técnicas de 7 productos y validarlas contra el archivo real (SHA-256
coincide con `media-master.csv` en los 7 casos; el rechazo es por contenido, no por
integridad de bytes), el importador detectó `/EmbeddedFile` en los 7 y los mantuvo en
REVIEW. La reversión se propagó correctamente, por dependencia, a los 11 productos que
usan esas 7 fichas (algunas se comparten entre dos productos).

| Ruta (bajo `legacy/public/`) | Tamaño | Productos afectados |
|---|---:|---|
| `system/files/images/productos/639b9801fe440dcd2179c23cca14401710c5e7e0` | 815.558 B | 65 (Puerta seccional 424), 66 (430) |
| `system/files/images/productos/7ab52eec612af22d23a62f849479371ebf1c2f22` | 953.747 B | 68 (perfiles 418), 69 (perfiles 422) |
| `system/files/images/productos/3af12bf12f61eda51a19d1e8af3cefc05878f484` | 738.042 B | 70 (perfiles 426), 71 (perfiles 432) |
| `system/files/images/productos/7baea774c5fc4abe3735d40fdf16e84f1e3521ef` | 1.583.405 B | 72 (Thermacore 593), 73 (Thermacore 594) |
| `system/files/images/productos/7cf4babfab2712e41d6fd1c050f5c31b604c97ae` | — | 67 (aluminio 521) |
| `system/files/images/productos/3e620ed84c43c5de234ae437f58f7083e93f6b8d` | 1.205.977 B | 77 (Operador RHX) |
| `system/files/images/productos/98b07de6dca4ad402a37385c485fb6999d5c3323` | 1.613.543 B | 95 (Cortina serie 610) |

Los 7 archivos: empiezan correctamente con `%PDF-`, `finfo` los detecta como
`application/pdf`, y su SHA-256 coincide exactamente con el registrado. El único motivo de
rechazo es el patrón `/EmbeddedFile`.

### Qué significa `/EmbeddedFile` — y qué no se afirma aquí

`/EmbeddedFile` es el nombre de subtipo que el formato PDF usa para un flujo de archivo
adjunto dentro del propio PDF (por ejemplo, un perfil de color, una fuente incrustada como
adjunto en vez de subconjunto normal, o un archivo realmente adjunto). El regex de
`Media::file_valid()` es deliberadamente conservador: no distingue un adjunto benigno de uno
malicioso, y **no se ha inspeccionado el contenido de estos 7 PDF para determinar cuál es
el caso**. No se afirma que sean peligrosos; tampoco se afirma que sean inofensivos.

### Qué se hizo y qué no se hizo

- **No se relajó ni se bypassó la comprobación.** Es exactamente el comportamiento correcto
  y conservador que el proyecto exige (`Storage::guard()`, "no relajes ninguna protección").
- **No se sustituyó el PDF por otro.** No hay autorización para tocar `/legacy`.
- Los 11 productos y las 7 rutas permanecen en REVIEW, con la razón exacta (`MEDIA_VALIDATION_OR_HASH`
  y la cadena "Dependencia no aprobada") visible en `notes` y en
  [low-rule-decisions.csv](low-rule-decisions.csv).

### Qué se necesitaría para resolverlo (no ejecutado aquí)

Inspección humana de los 7 PDF (por ejemplo, confirmar con una herramienta de análisis PDF
qué contiene el objeto `/EmbeddedFile`: perfil de color ICC, fuente, o un adjunto real) y una
decisión explícita sobre si se autoriza una excepción puntual y auditada, o si se sustituyen
por versiones regeneradas sin el adjunto. Ninguna opción se implementa en esta fase.

## 2. Bug encontrado y corregido durante la implementación: colisión de `binary_aliases`

Ver detalle completo en la sección "Hallazgo y corrección durante la implementación" de
[01-implemented-rules.md](01-implemented-rules.md). Resumen: la propia protección `SOURCE_KEY_HAS_TWO_OWNERS` de `Planner` (sin modificar) detuvo
el primer intento de FULL DRY RUN con política activa, porque `Policy` no conocía que
`asset:fichas/puerta-420.pdf` (decisión manual) ya declaraba
`system/files/images/productos/9856...` como alias binario. Corregido pasando las decisiones
manuales a `Policy::decisions()` y excluyendo cualquier ruta ya reservada como alias. Test de
regresión específico en `tests/policy.php`.

## 3. Asimetría de `policy_rule_id` entre medios y productos revertidos (documentación, no bug)

En el plan generado, los 11 productos revertidos (65…95) **sí conservan** `policy_rule_id =
'R-P01'` en su entrada final (con `action=REVIEW`), porque la reversión ocurre más tarde, en
la fase de dependencias del `Planner` — después de que la política ya se hubiera registrado.
Los 7 medios revertidos, en cambio, **no** conservan `policy_rule_id`, porque su reversión
ocurre inmediatamente, dentro de la misma extracción donde se habría fijado. Quien audite
directamente el JSON del plan (en vez de `low-rule-decisions.csv`) debe saber que un
`policy_rule_id` presente junto a `action=REVIEW` significa "la regla lo propuso pero una
validación posterior lo bloqueó" — información útil, no un error — y que su ausencia en una
fila REVIEW de tipo `media` no descarta que la política la haya intentado.
[`low-rule-decisions.csv`](low-rule-decisions.csv) es la fuente completa y autoritativa
porque se construye desde la propuesta de `Policy`, no desde este campo derivado.

## 4. No se implementó deduplicación automática de bytes idénticos entre rutas aprobadas por LOW

263 grupos de hashes idénticos existen entre las filas de medios (ver
[08-duplicate-merge-analysis.md](../review-analysis/08-duplicate-merge-analysis.md) de la
fase anterior). Si dos rutas con bytes idénticos son aprobadas de forma independiente por
reglas LOW distintas (por ejemplo, la misma imagen usada por dos productos), cada una crea su
propio adjunto en WordPress — no se fusionan mediante `binary_aliases` automáticamente.
Elegir qué ruta es la "canónica" es una decisión con matices editoriales (qué imagen queda
destacada) que esta fase, deliberadamente conservadora, no toma. El mecanismo de
`binary_aliases` (ya usado por la decisión manual del subset) sigue disponible para cuando se
autorice esa política.

## 5. Alcance no cubierto (recordatorio, no pendiente de esta fase)

Ninguna regla MEDIUM o HIGH se implementó. Ninguna pregunta de
[10-human-decisions.md](../review-analysis/10-human-decisions.md) se respondió. `MERGE`
permanece en 0. Ningún `static_product` se convirtió en producto. Nada se publicó.

## 6. Resuelto en una fase posterior: inconsistencia DRY RUN vs. ejecución real (Grupo B)

*(Añadido en una corrección posterior a esta fase; no fue un hallazgo original de este
documento — se registra aquí porque el título de esta lista es el punto natural de
consulta.)* La fase de aprobaciones PDF-A/PDF-B detectó que `Runner::media()` revalidaba de
forma independiente en ejecución real, lo que habría bloqueado el Grupo B pese a la
aprobación del plan. **Ya está resuelto** — ver la sección
["Runtime validation consistency"](../pdf-security-review/07-implementation-result.md#runtime-validation-consistency)
en `pdf-security-review/07-implementation-result.md` para el problema completo, la
corrección (`PdfApprovals::isApprovedFalsePositive()` + `Runner::media_is_valid()`) y las 82
pruebas que lo verifican.
