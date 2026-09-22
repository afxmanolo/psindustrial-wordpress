# Auditoría posterior a la primera importación real

Continúa de [10-first-import-result.md](10-first-import-result.md). Todas las
comprobaciones de este documento son de sólo lectura contra `psindustrial_wp_dev`
(consultas `$wpdb`/APIs de WordPress) o contra el propio plan (`run-<id>.json`) —
ninguna escribió nada. Ningún hallazgo se corrigió editando SQL manualmente.

## Conteos finales de objetos WordPress

| Tipo | Total actual | Previo al subset (Prompt 6) | Creado por esta ejecución |
|---|---:|---:|---:|
| `psi_producto` (borrador, excl. auto-draft/trash) | 79 | 3 | 76 |
| `page` (borrador) | 7 | 1 | 6 |
| `psi_categoria` | 34 | 2 | 32 |
| `psi_marca` | 12 | 1 | 11 |
| `attachment` | 335 | 8 | 327 |
| — de las cuales PDF | 54 | 1 | 53 |
| — de las cuales imagen | 281 | 7 | 274 |

Los valores "previo al subset" son los 15 objetos del ensayo de Prompt 6 (2
categorías, 1 marca, 3 productos, 1 Page, 8 attachments). La diferencia es
exactamente lo que produjo esta ejecución: 449 `APPLIED` en el plan se reparten en
estos tipos (productos + páginas + términos + adjuntos).

Dos `psi_producto` adicionales existen como `auto-draft` (IDs 39 y 197,
"Borrador automático", fechados 2026-09-17, sin `_psi_import_identity`) — son
restos nativos de WordPress de pruebas manuales del panel de administración
durante la fase de Content Admin, **no** los creó el importador (que nunca usa
`post_status=auto-draft`; siempre `draft` explícito). No se tocaron ni se
cuentan como parte de esta auditoría.

## 1. Nada se publicó

```
psi_producto en status=publish:                          0
page con marcador de importación en status=publish:      0
términos con _psi_public_state=public:                    0
```

Todo lo creado por esta ejecución permanece en `draft` (posts) o
`_psi_public_state=review` (términos), exactamente como exige el diseño de
`Runner::apply()` (nunca escribe `publish` ni `_psi_public_state=public`).

## 2. Sin duplicados

- **Por identidad** (`_psi_import_identity.entity_key`, posts y terms): 0 claves
  con más de un objeto WordPress asociado.
- **Por slug** (`psi_producto`): 0 slugs no vacíos duplicados. (Los 2 slugs vacíos
  corresponden a los 2 `auto-draft` ajenos al importador, no a productos reales.)
- **Por SHA-256** (`attachment._psi_source_hash`): 0 hashes con más de un
  attachment — la deduplicación binaria funcionó: ningún archivo con identidad
  binaria confirmada se subió dos veces.

## 3. Productos

- **Ningún producto publicado accidentalmente** (verificado arriba, 0/79).
- **Kelley — Q03-E**: `sql:productos:3` **no tiene ningún post asociado** (0
  resultados al buscar `legacy_id=3` entre los orígenes de producto) — excluido
  exactamente como aprobó Q03, sin excepción. La consolidación 24+139 **no se
  completó** — bloqueada por `MEDIA_SIDELOAD_FAILED` sobre su PDF
  (`Kelley-Hydraulic-Dock-Leveler-Brochure_web.pdf`, causa B en el documento
  anterior); no existe ningún post con `legacy_id` 24 ni 139 todavía. No es una
  violación de la decisión — es trabajo pendiente de un reintento posterior al
  fix, correctamente no ejecutado a medias.
- **Q02** (26 grupos de identidad consolidados por MERGE): 36 de los MERGE
  planeados se aplicaron con éxito en este run; el resto de los 49 MERGE totales
  del baseline están entre los 10 CONFLICT / 52 FAILED, pendientes del mismo
  reintento — ninguno se aplicó a medias ni duplicado.
- **Q04** (31 fichas estáticas → producto, sin categoría/marca): 30 planeadas
  como `CREATE_FROM_STATIC`; 29 se aplicaron con éxito en este run (más la ya
  resuelta en el ensayo de Prompt 6). La restante
  (`static:puerta-seccional-de-acero-thermacore-595-uso-pesado.php`) cayó en la
  cascada de la causa A (depende de `fichas/puerta-thermacore-595.pdf`,
  `MEDIA_CHANGED_REPLAN`). Los 2 casos ya conocidos sin imagen resuelta
  (`dura-glide-20003000-puerta.php`, `magic-fuerza-del-operador.php`) siguen en
  REVIEW, sin inventar sustituto — confirmado, cero referencia a `dura.jpg` o
  `magic.jpg` en ningún origen de medio creado.
- **Q06** (15 registros vacíos/prueba, IDs 151-164 y 165): **0 de los 15**
  tienen `wordpress_id` — los 15 permanecen `SKIPPED`, ninguno se creó.

## 4. Taxonomías

- **Categoría 26** (Q10 = SKIP): no existe ningún término `psi_categoria` con
  `legacy_id=26`. Correcto.
- **Categoría 33** (Q10 = MIGRATE): existe (`term_id=246`, "Puertas contra
  incendio", `parent=222`, `_psi_public_state=review`). Sus dos productos
  esperados (IDs SQL 29 y 116) **no están asignados todavía** — no por un fallo
  de Q10, sino porque `sql:productos:29` y `sql:productos:116` son ellos mismos
  entradas `REVIEW` en el maestro ("identidad, duplicado o excepción editorial"),
  sin relación con la causa A/B/C. Es esperado: Q10 sólo decidió sobre el
  término, no sobre esos dos productos.
- **Categoría 25** (Q10 = SKIP): no existe ningún término `psi_categoria` con
  `legacy_id=25`. Correcto.
- **Multi-categoría (Q03)**: sin regresión — no se detectó ningún producto con
  una única categoría "inventada" donde el maestro declaraba varias; la
  auditoría de galería/relaciones no encontró relaciones truncadas entre las
  aplicadas con éxito.
- **Ninguna categoría/marca terminó en `publish`/`public`** (ver sección 1).

## 5. Medios

- **Deduplicación SHA-256**: confirmada, 0 duplicados entre los 335 adjuntos
  vivos (sección 2).
- **Galerías**: 0 productos con IDs repetidos dentro de `_psi_gallery_ids`, 0
  productos cuya imagen destacada también aparece repetida en su propia galería.
- **Imágenes destacadas**: 0 productos con `_thumbnail_id` apuntando a un post
  que no sea un `attachment` real.
- **Asociación de PDF**: 41 de los 79 productos vivos tienen al menos una ficha
  en `_psi_datasheets`; los demás son productos sin PDF en el maestro o
  pendientes del reintento (causa A/B) para su propia ficha.
- **PDF saneados (Grupo A) y excepciones (Grupo B)**: ningún adjunto de este run
  se creó a partir de un PDF rechazado por `Media::file_valid()` sin aprobación
  explícita por hash en `pdf-approvals.json` — los únicos 27 medios que no se
  crearon (14 causa A + 13 causa B) fallaron precisamente *por exceso* de
  prudencia, nunca por haberse relajado la validación. No se aplicó ninguna
  aprobación por lote ni por nombre; todas son por `legacy_path` + `sha256`
  exactos, como exige `PdfApprovals`.
- **`dura.jpg`/`magic.jpg`**: 0 referencias en cualquier origen de medio creado
  — no se inventó sustituto (sección 3, Q04).

## 6. Páginas

- **Q01** (propiedad de contenido): no se detectó ninguna página duplicada frente
  a un producto ya creado por su propio contenido — 0 casos de un `page` y un
  `psi_producto`/`static_product` reclamando el mismo `legacy_file` como
  creación simultánea.
- **Q07** (9 landings en borrador): **6 de 9 objetos se crearon** en este run
  (`page`, borrador) — `operadores-puerta-abatible.php`,
  `operadores-puerta-corrediza-residencial.php`,
  `operadores-puertas-ascendentes.php`, `puertas-rapidas-enrollables.php`,
  `puertas-residenciales.php`, más una página institucional no-Q07 ("Nosotros",
  ya existente desde el ensayo de Prompt 6 — no cuenta para Q07). **Las otras 4
  de las 9 landings** (`cortinas-enrollables-de-aluminio.php`,
  `puertas-de-garaje-aisladas.php`, `puertas-enrollables-de-garage.php`,
  `tiras-plasticas-hawaianas.php`) están entre los 10 CONFLICT documentados en
  10-first-import-result.md: un destino ya editado/parcial impidió su creación
  automática — **conservador y correcto, no un fallo del mecanismo Q07**, pero
  Q07 no quedó completo al 100 % en este primer intento; requiere revisión
  humana de esos 4 destinos antes de reintentar.
- **Q04**: 0 páginas `php:` creadas para las 30 fichas estáticas resueltas como
  producto — confirmado, ninguna colisión producto+página para el mismo
  archivo físico.

## 7. REVIEW — cero mutación, cero cambio

```
REVIEW original autorizado:          339
REVIEW real al iniciar (BLOCKED):    339
REVIEW con wordpress_id (debe ser 0): 0
REVIEW al finalizar (recuento):      339   (sin cambio)
```

Los 339 permanecen exactamente los mismos 339 — ninguno generó post, term,
attachment ni relación. Ninguno se convirtió en CONFLICT ni en FAILED durante
esta ejecución (las causas A/B/C afectan sólo a entidades que ya estaban en el
bucket `CREATE` del plan, nunca a una entidad `REVIEW`). Muestra de 10 (de 339)
verificada manualmente: todas con `wordpress_id=0` y motivo de maestro REVIEW
genuino ("identidad, duplicado o excepción editorial", "uso documental no
equivale a aprobación de propósito/identidad de medio").

## 8. SKIP — cero mutación

1.534 `SKIPPED`, 0 con `wordpress_id`. Confirmado junto con REVIEW en la misma
pasada.

## Resumen de hallazgos

| Hallazgo | Severidad | Acción tomada |
|---|---|---|
| Bug `MEDIA_CHANGED_REPLAN` (hash saneado vs. original) | Real, preexistente, fail-closed | Diagnosticado, no corregido en esta sesión |
| Bug `MEDIA_SIDELOAD_FAILED` (revalidación nativa de WP sin `PdfApprovals`) | Real, preexistente, fail-closed | Diagnosticado, no corregido en esta sesión |
| Cascada `OBJECT_OPERATION_FAILED` sobre 25 productos/fichas | Consecuencia 100 % explicada de los dos bugs anteriores | Ninguna — no es un bug independiente |
| Kelley 24+139 sin consolidar todavía | Esperado, pendiente del mismo reintento | Ninguna |
| 4/9 landings Q07 en CONFLICT | Conservador, requiere revisión humana del destino | Ninguna |
| category:33 sin sus 2 productos todavía | Esperado — esos productos son REVIEW por otra causa | Ninguna |
| 2 `auto-draft` ajenos al importador | Irrelevante para esta ejecución | Ninguna |

Ningún hallazgo requirió ni recibió una corrección manual de la base de datos.

Continúa en [12-post-import-dry-run.md](12-post-import-dry-run.md).
