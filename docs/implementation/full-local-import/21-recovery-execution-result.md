# Ejecución real del recovery — resultado

Continúa de [19-recovery-preflight.md](19-recovery-preflight.md). Primera y única
ejecución real de `Runner::batch_recovery()` contra el recovery plan
`9c492018-cc99-4548-ac37-aed5d73688c1` (padre `2e0c1248-8d56-4d92-a33b-c17e37b2732e`),
autorizada explícitamente. Entorno local, `psindustrial_wp_dev` exclusivamente.

## Pre-flight inmediatamente antes de mutar

16/16 checks, 0 bloqueadores, `retryable=48` — sin cambios desde la construcción del plan.
Backup post-import (`postimport-41111c84-...`) confirmado presente con hashes correctos.
Confirmado: proceder.

## Ejecución por lotes sellados (sin reconstruir el conjunto)

```
Lote 1 (índices 0-19):  cursor=20/48  aplicadas=20  conflicto=0
Lote 2 (índices 20-39): cursor=40/48  aplicadas=12  conflicto=8
Lote 3 (índices 40-47): cursor=48/48  aplicadas=5   conflicto=3   STATUS=COMPLETE
```

**Total: 37 APPLIED, 0 UNCHANGED, 11 CONFLICT, 0 FAILED, 0 fatal.** La lista sellada de 48
entradas nunca se recalculó entre lotes.

## Los 11 CONFLICT — causa raíz exacta, encontrada durante esta misma ejecución

Los 11 son, sin excepción, los 11 productos `CASCADE_FROM_PDF_B` (ninguno de los 10
`CASCADE_FROM_PDF_A` falló). Patrón idéntico en los 11: `apply()` llegó hasta
`OBJECT_CREATED` (post real creado, `draft`, categoría/marca/miniatura/galería
correctamente asignadas) y falló específicamente al escribir `_psi_datasheets`.

**Causa exacta**: `includes/Fields.php`'s `Fields::guard()` (enganchado a
`update_post_metadata`) valida `_psi_datasheets` vía `Media::valid()` →
`Media::file_valid()` — el mismo escaneo crudo de patrón `/JS` que bloqueaba
originalmente el *sideload* de Grupo B, **sin ningún conocimiento de `PdfApprovals`**.
Confirmado directamente: `Media::valid(1333, 'pdf')` devuelve `false` para un PDF Grupo B
ya creado y válido. `Identity::set()` interpreta el rechazo como
`METADATA_WRITE_REJECTED:_psi_datasheets` — mensaje que `safe_error()` enmascara a
`OBJECT_OPERATION_FAILED` porque el nombre del campo contiene minúsculas.

Es la **misma** brecha arquitectónica que `MEDIA_SIDELOAD_FAILED` (Bug 2, fase anterior),
en un punto de código **distinto** (el guard de escritura de postmeta, no el hook de
subida). Nunca se había manifestado antes porque, hasta corregir el Bug 2, ningún PDF
Grupo B llegaba a crearse y por tanto nunca se intentaba asociar vía `_psi_datasheets`.
**No se corrigió en esta ejecución** — instrucción explícita de no resolver problemas
nuevos durante el recovery; queda documentado para una corrección futura separada.

| source_key | wordpress_id | motivo exacto |
|---|---:|---|
| sql:productos:5 | 1360 | `_psi_datasheets` rechazado por `Fields::guard()`/`Media::valid()` (PDF Grupo B `commercial-aluminum-door-systems-brochure.pdf` sin conocimiento de `PdfApprovals`) |
| sql:productos:6 | 1361 | ídem, PDF Grupo B `lisos.pdf` |
| sql:productos:7 | 1367 | ídem, PDF Grupo B `rolling-steel-doors-610-620-brochure.pdf` |
| sql:productos:17 | 1354 | ídem, PDF Grupo B `CMDC-3717-3718-11.pdf` |
| sql:productos:18 | 1355 | ídem, PDF Grupo B `CMDC-0524SP-14-1.pdf` |
| sql:productos:19 | 1356 | ídem, PDF Grupo B `Clopay-3720-07.pdf` |
| sql:productos:22 | 1357 | ídem, PDF Grupo B `StrongArm_HVR303_Brochure_Spanish.pdf` |
| sql:productos:24 | 1358 | ídem, PDF Grupo B `Kelley-Hydraulic-Dock-Leveler-Brochure_web.pdf` (Kelley 24+139) |
| sql:productos:44 | 1359 | ídem, PDF Grupo B `moovi50rm.pdf` |
| sql:productos:77 | 1372 | ídem, PDF Grupo B `rhx-commercial-operator-brochure.pdf` |
| sql:productos:95 | 1373 | ídem, PDF Grupo B `Cortina-serie-610-620.pdf` |

Los 11 posts permanecen `draft`, con `_psi_import_state` vacío (nunca alcanzaron
`APPLIED`), `_psi_datasheets=[]`. `Identity::prediction()` los clasifica ahora como
`CONFLICT` — no `UNCHANGED` ni `CREATE` — porque el objeto existe pero está incompleto;
confirmado que un recovery/reintento futuro **no los reoferta como creación limpia** (ver
auditoría de idempotencia en [22-post-recovery-audit.md](22-post-recovery-audit.md)).

## Lo que NO se tocó (confirmado, no asumido)

- Los 449 objetos ya aplicados por la primera ejecución: 0 en la intersección entre sus
  `entity_key` y los tocados por este recovery.
- 339 REVIEW, 1.534 SKIP: fuera del conjunto sellado por diseño — nunca se recorrieron.
- Los 10 conflictos de colisión de slug + los 4 productos de cascada de
  `category:37`/`category:38`: fuera del conjunto sellado, sin tocar.
- Publicación: `psi_producto` en `publish` = 0.

Continúa en [22-post-recovery-audit.md](22-post-recovery-audit.md).
