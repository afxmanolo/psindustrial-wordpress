# Estado técnico de migración — cierre operativo

Fecha: 2026-09-21. Checkpoint: `fix: finalize migrated product data and technical sheet semantics` (sobre `e539b6d`). Sólo entorno local `psindustrial_wp_dev`.

**Migration status: OPERATIONALLY COMPLETE**

Cambio de estrategia aprobado por el usuario: el importador queda congelado. Sólo se reabre ante un defecto funcional concreto. Los 52 grupos binarios históricos no bloquean el frontend; no fusionar ni eliminar attachments ni desarrollar deduplicación. Se conserva la [auditoría histórica](importer-reports/final-migration-audit.json), cuyo estado BLOCKED refleja la decisión anterior, ahora sustituida por este cierre operativo.

Deferred:
- editorial REVIEW reconciliation (339 REVIEW y 14 conflictos editoriales)
- final categories
- final brands
- historical duplicate attachment cleanup
- non-blocking editorial test debt (`editorial-decisions`, `q01-content-ownership`; dependientes del estado/contenido)

## Trabajo completado

- Los 11 posts parciales PDF-B se completaron: **11 REPAIRED, 0 CONFLICT, 0 FAILED**; segunda llamada **11 UNCHANGED**. Mismos IDs, ningún post recreado. Comprobación de identidad, run creador, journal OBJECT_CREATED, snapshot inicial, campos/relaciones preexistentes, fuente sellada y aprobación PDF por path/hash. [Resultado](importer-reports/partial-pdf-repair-result.json).
- Se corrigió el override de Sonnet: devolver `true` desde `update_post_metadata` evita la escritura nativa; ahora devuelve `null` después de validar permisos/esquema/PDF. La prueba incluye persistencia real y lectura posterior, no sólo Reflection.
- Backup nuevo `postimport-f14897f9-a2c4-4c9b-942f-937ef390036c`: 12 tablas restauradas y verificadas en una base temporal eliminada al finalizar; 1.088 archivos uploads y 1.022 archivos de evidencia con hashes. No se restauró sobre la base de desarrollo.
- `images/verficha.png`: botón de interfaz confirmado visualmente y en 86 enlaces de 78 PHP. SHA-256 `3f5aeebc29765caddc907536949a7c1802341f1771394b26a5d63c6ab9cfaf0f`; sin otras copias binarias legacy idénticas. [Evidencia de enlaces PDF](importer-reports/verficha-ui-evidence.json).
- Eliminadas asociaciones incorrectas en **60 productos: 58 galerías y 2 imágenes principales**. Attachment 1013 conservado. Cero asociaciones editoriales restantes; **81 PDFs conservados con hashes idénticos**. [Resultado](importer-reports/verficha-ui-cleanup.json).
- Copia canónica del botón en `wordpress/wp-content/themes/psindustrial/assets/images/ver-ficha-tecnica.png`. Todavía no integrada en el frontend.
- Regla exacta path + SHA-256 `UI_ONLY_ASSET`: excluye sólo ese botón de imágenes/PDFs planificados y de futuras importaciones. **Los 1.534 SKIP originales se mantienen; el plan tiene 1.535 al añadir esta corrección explícita.** No se reclasificaron REVIEW.
- El post 1371 sigue `publish`; título, contenido, fechas y estado no cambiaron. Sólo se retiró el botón de su galería, como corrección semántica autorizada. Su divergencia editorial sigue protegida; no se reescribió su snapshot de importación para absorber la edición humana.

## Inventario observado

100 productos migrados (101 productos totales), 34 categorías, 12 marcas, 7 Pages migradas, 359 attachments migrados (362 totales: 81 PDFs y 281 imágenes). Cero duplicados por identidad. Los 339 REVIEW y 14 conflictos editoriales siguen pendientes; marcas/categorías dudosas quedan para el cliente.

## Trabajo diferido y validación

La conciliación final se hará posteriormente con el cliente desde WordPress. No se investigan los duplicados salvo fallo funcional visible. La prioridad inmediata es el single-product y el catálogo; no se modificará código productivo para forzar las dos pruebas editoriales.

Última corrida completa: **24/26 archivos PASS** (25 anteriores y una prueba nueva). Los cuatro fallos antiguos solicitados ya pasan. Fallaron `editorial-decisions.php` y `q01-content-ownership.php`: sus conteos de imágenes incluían el botón UI. El primero se ajustó antes de detectar el bloqueo, pero no se repitió después; el segundo queda pendiente al detener el trabajo. No se declara la suite verde. [Resultados completos](importer-reports/final-suite.json). Sintaxis PHP de archivos modificados y `git diff --check`: correctos.

Comprobación de PDFs recuperados: **14 Grupo A** mantienen bytes saneados; **13 Grupo B** coinciden con aprobaciones exactas path/hash; cero errores. El hash de todos los 81 PDFs coincide con el anterior a la limpieza.

Checkpoint local autorizado; sin push, staging, producción ni cambios en `/legacy` (3.862 archivos idénticos al baseline, comprobados de nuevo al cerrar).
