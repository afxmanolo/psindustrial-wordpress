# Medios — análisis de los 1.523 REVIEW

Los medios son **1.523 de 1.949 = 78,1 %** del problema. Es la mayor oportunidad de
reducción y también donde un error automatizado sería más difícil de detectar.

## Composición real

Cruce de `media-master.csv` (1.531 filas; 8 ya aplicadas en el subset) con
`product-media-relations.csv`, `media-usage-evidence.csv` y `missing-media-references.csv`.
El join correcto es por **ruta** (`legacy_path`), no por `file_ids`: `file_ids` está poblado
en 820 filas y produce sólo 91 coincidencias, mientras que la ruta produce 225 y coincide
exactamente con las 225 rutas distintas de `product-media-relations.csv`.

### Por almacenamiento y uso

| Almacenamiento | Uso | Derivado | Filas |
|---|---|---|---:|
| `multimedia/` | sin referencia | derivado | 561 |
| `system/backoffice/` | sin referencia | original | 256 |
| `images/` | referenciado | original | 254 |
| `system/files/` | referenciado | original | 253 |
| `images/` | sin referencia | original | 80 |
| `fichas/` | referenciado | original | 75 |
| `fonts/` | sin referencia | original | 19 |
| `system/` (otros) | sin referencia | original | 7 |
| `system/files/` | sin referencia | original | 5 |
| `fichas/` | sin referencia | original | 4 |
| `fonts/` | referenciado | original | 3 |
| `multimedia/` | sin referencia | original | 3 |
| `https://` | embed externo | — | 3 |

### Por propietario demostrable

| Propiedad | Filas | Evidencia |
|---|---:|---|
| Relación SQL explícita con producto | 225 | `product-media-relations.csv` (150 imagen + 71 PDF + 4 sin duplicar bytes) |
| Imagen de categoría | 28 | `media-usage-evidence.csv usage_type=DATABASE_CATEGORY` |
| Logo de marca | 11 | `media-usage-evidence.csv usage_type=BRAND_LOGO` |
| Referencia pública literal desde páginas | 318 | `usage_type=PUBLIC_SOURCE` |
| Sin propietario demostrable | 941 | derivados, infraestructura y huérfanos |

## Las cuatro familias de medios

### 1. Derivados del CMS legacy — 561 filas

Ficheros bajo `multimedia/` con `original_ids` poblado y flag `DERIVED`. **Los 561 están
marcados `IMAGE_APPARENTLY_UNUSED`: ninguno tiene referencia pública directa.** Son las
variantes de tamaño que generaba el CMS antiguo a partir de un original que se conserva
como fila independiente.

WordPress genera sus propios tamaños derivados al importar el original. Reimportar los
derivados legacy crearía adjuntos duplicados sin función. → `SKIP` (regla R-M02, riesgo LOW).

Salvaguarda incluida en la regla: si algún derivado apareciera con flag `*_USED`, la regla
lo eleva a riesgo HIGH y no se automatiza. Hoy no ocurre en ninguna de las 561 filas.

### 2. Infraestructura — 285 filas

- 256 bajo `system/backoffice/` — assets de la interfaz del CMS antiguo: iconos de TinyMCE,
  skins de iCheck, Bootstrap, temas de calendario. 182 PNG, 54 GIF, 15 JPEG.
- 22 bajo `fonts/` — fuentes de iconos del frontend (FontAwesome, icomoon, themify,
  bootstrap-icons) en `.woff`, `.woff2`, `.ttf`, `.eot`, `.svg`.
- 7 bajo `system/` no clasificables como almacén de contenido.

Ninguno es medio editorial. El tema `psindustrial` aporta sus propios assets; el backoffice
antiguo desaparece con la migración. → `SKIP` (regla R-M03, riesgo LOW).

Matiz sobre las fuentes de iconos: `SKIP` significa que **no entran en la Biblioteca de
medios**. Si el tema nuevo necesita una fuente de iconos, es una decisión de tema, no de
importación de contenido, y se resuelve en el repositorio del tema.

### 3. Medios con propietario explícito — 264 filas

225 con relación SQL a producto, 28 imágenes de categoría, 11 logos de marca. La evidencia
es `CONFIRMED` en los tres casos: relación explícita en el dump más hash SHA-256 validado
contra los bytes locales.

**Estos medios no están bloqueados por su propia evidencia: están bloqueados por la decisión
de su propietario.** Es exactamente el comportamiento que implementa `Planner::build()`
propagando `REVIEW` cuando una dependencia no está aprobada.

Al propagar las decisiones de entidad de este análisis:

| | MIGRATE | KEEP_REVIEW |
|---|---:|---:|
| Medios de producto (225) | 35 | 190 |
| Medios de término (39) | 38 | 1 |

Los 190 se desbloquean solos en cuanto se apruebe el producto propietario. No requieren una
decisión propia.

### 4. Medios referenciados por páginas — 318 filas

`images/` y `fichas/` referenciados literalmente desde PHP estático. 42 pertenecen a páginas
editoriales aprobadas (institucionales, contacto, landings) → `MIGRATE`. Los 276 restantes
pertenecen a páginas cuyo propietario final es un producto o un término todavía no decidido
→ `KEEP_REVIEW`.

### Resto — 95 filas

- 89 sin relación de entidad y sin referencia pública atribuible. **No se proponen para
  `SKIP`.** La documentación es explícita: *ausencia de referencia literal no demuestra
  orfandad* (D09). Se conservan en `KEEP_REVIEW` hasta disponer de evidencia de tráfico.
- 6 con tipo/límite/hash fuera de política (SVG e iconos sueltos fuera de `fonts/`).
- 3 embeds externos de YouTube: no son adjuntos; se representan como metadato de vídeo del
  producto propietario.

## Duplicación binaria

263 grupos SHA-256 cubren 557 rutas REVIEW; hay 1.226 hashes distintos entre 1.523 rutas.

**La duplicación binaria no se usa como criterio de `SKIP` en ninguna regla.** El importador
ya sabe compartir un adjunto entre varias rutas de origen cuando la decisión lo explicita
(el PDF `file676` del subset conserva sus dos rutas en un único adjunto). Quitar rutas por
igualdad de bytes es una decisión de URLs, no de medios, y está fuera de esta fase.

## Resultado

| Acción | Filas | % de los 1.523 |
|---|---:|---:|
| `SKIP` | 846 | 55,5 % |
| `KEEP_REVIEW` | 562 | 36,9 % |
| `MIGRATE` | 115 | 7,6 % |

**846 filas de medios (43,4 % de todo el problema) se resuelven con dos reglas de riesgo
LOW que no dependen de ninguna decisión humana pendiente.** Es la mayor reducción segura
disponible.

De los 562 que siguen en REVIEW, 191 son propagación automática: se resolverán sin
intervención propia en cuanto se decidan productos y términos. Sólo 89 + 6 = 95 filas de
medios necesitan evidencia nueva (D09) o revisión técnica.
