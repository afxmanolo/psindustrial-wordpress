# Fuentes y precedencia

Referencias: [reconciliación](../migration/00-migration-readiness-summary.md), [evidencia](../migration/evidence-matrix.md), [decisiones humanas](../migration/manual-decisions-required.md), [arquitectura](../architecture/15-migration-architecture.md).

| Dato | Fuente autoritativa de esta fase | Uso |
|---|---|---|
| Productos SQL candidatos | product-master.csv | Identidad candidata, confianza y estado; no165 entidades aprobadas |
| Fichas sin SQL | static-product-supplement.csv |32 fuentes independientes; no adjudicar fabricante |
| Fusiones | canonical-candidate-groups.csv y decisiones editoriales |77 grupos candidatos, sin merge comercial automático |
| Categorías | category-master.csv | Padre propuesto y evidencia;25/26/33 pendientes |
| Marcas | brand-master.csv | Nombre/logo explícito y referencias, sin adjudicar marcas débiles |
| Pages y landings | content-master.csv | Clasificación; no CPT adicional; producto/categoría/marca PHP no se duplica como Page |
| Medios | media-master.csv + media-usage-evidence.csv | Tipo/hash/uso; SHOULD/MUST no equivalen a aprobación de importación |
| Relaciones | product-media-relations.csv y columnas reconciliadas | ID file/ruta/orden; no usar orden alfabético como galería |
| Ausencias | missing-media-references.csv |4 referencias REVIEW, incluida discrepancia Unicode; no normalización silenciosa |
| URL/SEO | url-master.csv + PHP + page-source-evidence.json | Evidencia conservada, sin activar rutas ni metadata pública nueva |
| Datos SQL originales | legacy/public/puerta34_administrador.sql | Lectura de INSERT literales productos/categorias/marcas/file, hashes y fecha; nunca ejecutar dump |
| Dump de database | legacy/database/psindustrial_db.sql | Disponible, esquema sin catálogo poblado; no sustituye al dump poblado |

Precedencia: decisión editorial explícita → evidencia visible coherente → SQL no contradictorio → inferencia revisada. La autorización del Prompt6 permite un ensayo en borradores; no resuelve decisiones comerciales. La selección está en [subset-decisions.json](importer-reports/subset-decisions.json), scope LOCAL_DRAFT_REHEARSAL_ONLY. El resto conserva REVIEW, salvo código interno que no representa contenido editorial.

## Extracción

CSV con BOM, campos multilínea/comillas y cantidad de columnas validada. SQL se tokeniza como literales de INSERT; no conexiones, eval, include ni consultas contra legacy. No se extraen user/permission/configuraciones/credenciales. El dump original sólo se lee.

El cuerpo se extrae de HTML estático mediante token_get_all y DOM con LIBXML_NONET; PHP ejecutable queda fuera. Se conservan párrafos, H2/H3, listas y tablas permitidas; se excluyen forms, navegación, scripts y wrappers visuales. No reconstruye includes dinámicos. Nombres/relaciones vienen del maestro/decisión, no de cualquier H1 copiado. Title/description literales y encabezados se conservan como evidencia privada. Los enlaces aún necesitan remapeo y revisión: sólo hay borradores.

Las fotos del subset siguen orden explícito de la ficha; se excluye images/verficha.png como foto comercial. No se une automáticamente galería SQL y frontend. SQL descripcion de categoría se conserva si existe; no se inventan descripciones vacías.

## Paquete

Plan JSON normalizado y los8 binarios permitidos se congelan fuera del webroot. Los paquetes de ejecución no contienen código PHP ni dump SQL; los dumps siguen siendo entradas del preparador. En local se vuelven a comprobar los hashes de las entradas antes de cada lote. El destino actual exige la estructura local existente para esa comprobación; su adaptación al canal FTP privado se describe como pendiente, sin acciones remotas.
