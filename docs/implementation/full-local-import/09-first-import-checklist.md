# Checklist para la primera ejecución real — revisar juntos antes de autorizar

No ejecutar nada de esto todavía. Esta lista es para cuando decidamos, explícitamente,
autorizar la primera `FULL LOCAL RESOLVED-ONLY`.

## Antes de generar el plan

- [ ] Confirmar que seguimos en `feature/review-resolution`, entorno local,
      `psindustrial_wp_dev`.
- [ ] Revisar si los 339 REVIEW actuales siguen siendo los mismos que documentó
      [20-remaining-review.md](../review-resolution/implementation/20-remaining-review.md) y
      [28-remaining-review-after-q03.md](../review-resolution/implementation/28-remaining-review-after-q03.md)
      — si algo cambió en `docs/migration/` o `docs/implementation/review-resolution/` desde
      entonces, generar primero un FULL DRY RUN nuevo y revisar el delta.

## Generar y revisar el plan

- [ ] Generar un FULL DRY RUN nuevo desde el panel (Herramientas → Migración PS Industrial).
- [ ] Confirmar TOTAL/UNCHANGED/SKIP/CREATE/REVIEW/ERROR — ¿son los esperados, o hay una
      sorpresa que merece pausar?
- [ ] Descargar el reporte completo y revisar, aunque sea por encima, la lista de REVIEW —
      ¿algo ahí debería, en realidad, tener ya una decisión?

## Pre-flight

- [ ] El panel FULL LOCAL RESOLVED-ONLY debe mostrar las 14 verificaciones en verde. Si
      alguna falla, **no continuar** — resolver la causa raíz, generar un plan nuevo.
- [ ] Revisar específicamente `pdf_approvals_current` y `staged_media_assets_valid` — son las
      dos comprobaciones más ligadas a trabajo de una fase anterior (revisión de seguridad de
      PDF) que podría haber quedado desactualizado sin que nadie lo note.

## Antes de confirmar el primer lote

- [ ] Confirmar que el backup automático (`backup-<run>.json` + `backup-uploads-<run>/`) se
      completó — el panel lo indica; si hace falta, verificar directamente en el directorio
      privado (`psindustrial-importer-private/`, fuera del repositorio).
- [ ] Decidir el tamaño de lote razonable para esta sesión (por defecto 50) según cuánto
      tiempo se quiere dedicar a supervisar activamente.
- [ ] Tener a mano cómo consultar el progreso sin ejecutar nada (releer el plan, o el panel
      mismo) por si hace falta pausar entre lotes.

## Durante la ejecución

- [ ] Tras cada lote, revisar si aparece algún `CONFLICT` o `FAILED` inesperado antes de
      continuar con el siguiente.
- [ ] Un `CONFLICT`/`FAILED` de una sola entidad no detiene el lote — pero merece anotarse
      para revisión posterior, no ignorarse.
- [ ] Si el lote se detiene con un error fatal (no de una entidad), **no reintentar a
      ciegas** — identificar la causa (¿entorno, permisos, almacenamiento?) antes de
      continuar.

## Después de completar

- [ ] Confirmar `status=COMPLETE` y que el cursor coincide con el total de entradas.
- [ ] Revisar una muestra de productos/categorías/marcas creados en el admin de WordPress —
      todo debe seguir en borrador/revisión, nada publicado.
- [ ] Confirmar que los 339 (o el número que corresponda) REVIEW originales siguen sin
      ningún post/term creado para ellos.
- [ ] Decidir, como tarea aparte y explícita, cuándo y cómo se revisa/publica el contenido
      creado — esta fase no lo hace ni lo decide.

## Qué NO hace falta comprobar aquí

No hace falta revisar Q01-Q13 de nuevo — ya están implementadas, probadas y documentadas por
separado; esta checklist es sólo sobre la mecánica de ejecución, no sobre las decisiones
editoriales que el plan ya contiene.
