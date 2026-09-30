# Páginas — análisis de los 181 REVIEW

Fuente: `content-master.csv` (617 filas: 435 `LEGACY_INTERNAL` ya en SKIP, 1 aplicada en el
subset, 181 en REVIEW).

## Distribución por clasificación

| Clasificación | Filas | `preserve_url` | Confianza |
|---|---:|---|---|
| `PRODUCT_PAGE` | 114 | YES | STRONG_INFERENCE |
| `CATEGORY_PAGE` | 37 | YES | CONFIRMED |
| `BRAND_PAGE` | 13 | YES | CONFIRMED |
| `SEO_LANDING` | 9 | YES | STRONG_INFERENCE |
| `INSTITUTIONAL_PAGE` | 4 | YES | CONFIRMED |
| `UTILITY` | 3 | YES | CONFIRMED |
| `CONTACT_PAGE` | 1 | YES | CONFIRMED |

Las 181 tienen `preserve_url = YES` y `migration_priority = HIGH`.

## Agrupación pedida

| Grupo solicitado | Filas | Corresponde a |
|---|---:|---|
| institucional | 4 | `INSTITUTIONAL_PAGE` |
| servicio | 0 | no existe esa clasificación en el maestro |
| SEO landing | 9 | `SEO_LANDING` |
| category-like | 37 | `CATEGORY_PAGE` |
| brand-like | 13 | `BRAND_PAGE` |
| utility | 3 | `UTILITY` |
| duplicate | 164 | `PRODUCT_PAGE` + `CATEGORY_PAGE` + `BRAND_PAGE`, ver abajo |
| obsolete_candidate | 0 | ninguna fila tiene evidencia de obsolescencia |
| unknown | 0 | las 181 están clasificadas |

No se declara ninguna página `obsolete_candidate`. La obsolescencia exigiría datos de tráfico
o enlaces externos, que no existen en el repositorio (D09). Clasificar una página como
obsoleta por ausencia de enlaces internos repetiría el error que la documentación prohíbe.

## El problema real: propiedad, no valor

Las 164 filas `PRODUCT_PAGE` / `CATEGORY_PAGE` / `BRAND_PAGE` **no están en REVIEW porque su
contenido sea dudoso, sino porque el mismo artefacto legacy ya está enumerado como otra
entidad**:

- 82 `PRODUCT_PAGE` tienen `related_product_ids` → su contenido pertenece a un `psi_producto`.
- 32 `PRODUCT_PAGE` sin `related_product_ids` → son exactamente las 32 fichas estáticas
  ([05](05-static-product-analysis.md)).
- 37 `CATEGORY_PAGE` → archivo de término `psi_categoria`.
- 13 `BRAND_PAGE` → archivo de término `psi_marca`.

La arquitectura ya resolvió esta cuestión y el informe del full dry-run lo dice
explícitamente: *no se crea una Page adicional por cada ficha o archivo*. Crear 164 Pages
además de los productos y términos duplicaría el catálogo y generaría competencia de
contenido entre dos URLs.

→ `SKIP` como Page (regla R-G01, riesgo MEDIUM).

**`SKIP` aquí significa «no crear una Page»; no significa perder la URL ni el contenido.**
El contenido lo aporta la entidad propietaria; la URL se trata en la fase de URLs/SEO, donde
`preserve_url = YES` sigue vigente para las 164. El riesgo se marca MEDIUM, no LOW,
precisamente porque exige que esa fase posterior exista: si se aplicara `SKIP` y nunca se
implementara el mapa de URLs, se perderían 164 rutas públicas.

## Páginas que sí son Pages — 14 filas

`INSTITUTIONAL_PAGE` (4) + `CONTACT_PAGE` (1) + `SEO_LANDING` (9). Coinciden con las 14 filas
`EDITORIAL_CONTENT` de `proposed_wordpress_type`, lo que confirma la lectura desde un segundo
campo independiente.

No tienen otro propietario posible: no hay producto ni término que pueda absorberlas.

→ `MIGRATE` como Page en borrador (regla R-G02).

Riesgo diferenciado:

- Institucionales y contacto: **LOW** (5 filas). Identidad y propósito `CONFIRMED`.
- Landings SEO: **MEDIUM** (9 filas). `classification_confidence = STRONG_INFERENCE`; la
  finalidad SEO es inferencia y algunas solapan productos. Migrar como borrador es seguro;
  publicarlas no lo es todavía.

Ninguna se propone para publicación. El destino es `draft` con estado de revisión pendiente,
igual que el resto del modelo editorial ya implementado.

## Utilitarias — 3 filas

Páginas de función, no de contenido. Su destino depende de si la función se reimplementa en
WordPress, se descarta o se conserva. Es la decisión D12 (uso real de funciones heredadas).

→ `KEEP_REVIEW` (regla R-G03).

## Resultado

| Acción | Filas |
|---|---:|
| `SKIP` (no crear Page; contenido y URL a cargo de otra entidad) | 164 |
| `MIGRATE` (Page en borrador) | 14 |
| `KEEP_REVIEW` | 3 |

## Condiciones para pasar a publicación

Ninguna página se autoriza a publicar en esta fase. Las condiciones que habría que cumplir
después, por orden:

1. El adaptador SEO y la capa de URLs existen y el mapa de redirects está aprobado.
2. La extracción estática se ha revisado manualmente: títulos, tablas, enlaces internos
   pendientes de remapeo (limitación 4 de `29-importer-known-issues.md`).
3. El contenido tiene H1 y cuerpo aprobados por el modelo editorial ya implementado.
4. Para las landings, el negocio confirma que la URL debe seguir siendo primaria.
