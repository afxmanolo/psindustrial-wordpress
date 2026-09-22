# Compatibilidad de URLs legacy

Fecha: 2026-09-21. Rama `feature/review-resolution`. Cruce evidencia-first de
`content-master.csv` (617 filas) contra `q01-q04-url-mapping.csv` (196 filas, la auditoría
previa Q01/Q04) y la identidad real actualmente en WordPress (`_psi_import_identity`,
`_psi_source_keys`). Sin invención de destinos: toda fila sin evidencia queda `KEEP_PENDING`.

## Resultado — [redirect-map.csv](redirect-map.csv), 617 filas

| Resolución | Filas | Significado |
|---|---:|---|
| `INTENTIONALLY_GONE` | 435 | `LEGACY_INTERNAL`: infraestructura del CMS viejo, nunca fue contenido público |
| `KEEP_PENDING` | 176 | Destino demostrado o no; en ambos casos el objeto/término destino aún no es público — nunca se redirige a contenido no publicado |
| `EXACT` | 3 | `nosotros.php`→Nosotros, `contacto.php`→Contacto, `index.php`→raíz del sitio |
| `REDIRECT_301` | 3 | `index-estatico.php`/`index-resp.php`→raíz (mismo title/H1 que index.php, variante histórica), y el único producto actualmente publicado |

De las 176 `KEEP_PENDING`: 131 tienen un destino real ya identificado (objeto propio o
target editorial Q01/Q02/Q04) que activará su redirect automáticamente en cuanto ese
producto/categoría/marca/Page se publique — sin cambiar código. Las 45 restantes no tienen
ningún propietario aprobado todavía (43 `KEEP_REVIEW` originales de la auditoría Q01/Q04 + 3
`UTILITY` ligadas a Q12), y 4 son conflictos de slug preexistentes y protegidos (uno de los
14 conflictos editoriales nunca tocados en esta rama).

## Jerarquía de evidencia aplicada

1. **Identidad real de WordPress** (`_psi_import_identity.entity_key` en el objeto/término
   vivo) — la única fuente para `EXACT`.
2. **Mapeo editorial explícito** documentado fuera del importador: Contacto (ID 1481) y
   Privacidad (ID 1482) se construyeron a mano en `feat: rebuild legacy institutional
   frontend` (ver `docs/frontend/institutional-frontend.md`) — su borrador huérfano del
   importador (`contacto.php` → ID 1185, draft) se descarta a propósito a favor del objeto
   realmente publicado.
3. **Mapeo editorial Q01/Q02/Q04** (`q01-q04-url-mapping.csv`, `status=RESOLVED_NO_PAGE`) —
   la página legacy reexpresa contenido ya propiedad de otra entidad.
4. **Evidencia de contenido** (no de nombre): `index-estatico.php`/`index-resp.php` comparten
   title y H1 exactos con `index.php` en `content-master.csv`, con nota propia "variante
   histórica aparente" — nunca se dedujo por similitud de archivo.

Nunca se usó similitud de nombre como prueba. Nunca se publicó ni creó ningún objeto para
construir este mapa.

## Capa de redirect — `psindustrial-core`

`includes/LegacyUrls.php` + `data/legacy-url-map.php` (generado desde `redirect-map.csv`,
131 reglas con destino demostrado). Vive en el plugin, no en el theme ni en `.htaccess`,
siguiendo la separación presentación/negocio del proyecto.

Comportamiento:

- Sólo actúa cuando WordPress ya determinó `is_404()` — una URL moderna real nunca pasa por
  esta capa.
- Resuelve el destino **en vivo** en cada request (estado de publicación actual del
  objeto/término), nunca desde una foto fija: en cuanto algo pasa a público, su redirect
  empieza a funcionar sin desplegar código nuevo.
- Si el destino existe pero no es público: sin redirect, 404 normal — nunca un redirect
  "suave" a Home.
- 301 permanente. Preserva query string. No puede haber cadena ni loop: todo destino vivo es
  una URL canónica limpia (nunca otra ruta `.php`), verificado para las 131 reglas en
  `tests/legacy-url-redirects.php`.
- Nunca intercepta `wp-admin`, REST (`REST_REQUEST`) ni WP-CLI.

Regenerar la tabla cuando cambien decisiones editoriales: releer `redirect-map.csv` (o
reconstruirlo si `content-master.csv`/`q01-q04-url-mapping.csv` cambian) y regenerar
`data/legacy-url-map.php` con el mismo cruce de evidencia descrito arriba.

## Qué no se hizo

No se publicó ningún producto/categoría/marca/Page para activar un redirect. No se tocó
ninguna de las 339 REVIEW, ni las 43 `KEEP_REVIEW` de la auditoría Q01/Q04, ni los 14
conflictos editoriales (10 de slug + 4 de categoría). No se creó ningún archivo `.php`
físico. No se modificó `/legacy`.
