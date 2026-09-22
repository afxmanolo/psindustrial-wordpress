# Pre-flight del reintento — demostración de sólo lectura

Continúa de [15-retry-model.md](15-retry-model.md). **No se ejecutó ningún reintento real.**
Este documento demuestra qué ocurriría, con dos evidencias complementarias: (1) la llamada
real, en vivo, a `Runner::retry_preflight()` contra el `run_id` de la primera ejecución, y
(2) los conteos exactos reconstruidos a partir del registro permanente de
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md), verificados por
[`tests/retry-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/retry-model.php)
(200 comprobaciones).

## 1. Llamada real, en vivo, contra el `run_id` original

```php
Runner::retry_preflight( '2e0c1248-8d56-4d92-a33b-c17e37b2732e' );
```

```json
{
    "run_id": "2e0c1248-8d56-4d92-a33b-c17e37b2732e",
    "ok": false,
    "checks": [
        { "id": "plan_exists", "passed": false, "detail": "No run-2e0c1248-8d56-4d92-a33b-c17e37b2732e.json found." }
    ],
    "blockers": ["plan_exists"],
    "counts": []
}
```

Sin mutación de la base de datos (conteo de `posts`/`terms` idéntico antes/después,
verificado). Este resultado es **correcto y esperado**: el archivo `run-<id>.json` fue
podado por retención rutinaria durante este mismo diagnóstico (ver
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md#incidente-operativo-descubierto-durante-este-diagnóstico-poda-de-run-idjson)).
Demuestra una propiedad de seguridad real e importante: **`retry_preflight()` nunca fabrica
ni asume datos ausentes — se niega honestamente.** Ningún reintento podría ejecutarse contra
este `run_id` específico sin antes generar un plan `full` nuevo y completarlo de nuevo
(fuera del alcance de esta tarea).

## 2. Conteos completos — reconstruidos del registro permanente, verificados por test

Plan `full` reconstruido ahora mismo (`Planner::build('full')`, determinista — mismas 2.399
entradas que el original, confirmado repetidamente en esta sesión):

```
REVIEW      339
SKIP       1534
UNCHANGED   464   (15 del subset + 449 de la primera ejecución real)
CONFLICT     23   (10 colisión de slug original + 13 intento fallido propio, ver abajo)
CREATE       39
-------------------
TOTAL      2399
```

Cruzando estas 62 entradas no-`UNCHANGED`/no-`REVIEW`/no-`SKIP` contra el registro
permanente de la ejecución original (tabla completa en
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md#tabla-completa--62-entradas-no-aplicadas)),
usando el mecanismo real (`Runner::retry_root_cause()`/`retry_eligibility()`, nunca
reinventado ad hoc para este documento):

```
retryable              48
non_retryable_conflict 14   (4 cascada de category:37/38 + 10 colisión de slug)
already_applied        464  (15 subset + 449 primera ejecución -- ninguno se tocaría)
review                 339  (sin cambio)
skip                  1534  (sin cambio)
other_excluded           0
-------------------------------
suma FAILED+CONFLICT originales = 62 = 48 + 14
```

De los 48 retryable: 35 resolverían a `CREATE` limpio (nunca tuvieron *ledger*, el fallo
original ocurrió antes de que `apply()` escribiera uno) y 13 resolverían reconociendo su
propio *ledger* `INTENT` huérfano como intento fallido propio (verificado directamente
contra los 477 archivos `identity-<token>.json` supervivientes — ver
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md#camino-1-intento-fallido-propio-verificado-en-los-13-direct_pdf_b)),
nunca como conflicto editorial nuevo.

## Qué probaría un reintento real, sin ejecutarlo

- Los 449 objetos ya aplicados por la primera ejecución: **0** tocados (predicción
  `UNCHANGED`, `retry_eligibility()` los excluye en el primer paso por no ser `FAILED`).
- Los 339 REVIEW y 1.534 SKIP: **0** tocados (mismo motivo).
- `category:37`/`category:38` y las 8 páginas con colisión de slug: **0** tocados —
  permanecen fuera del `retry_set` por diseño, nunca "limpiados" para mejorar números.
- Los 48 retryable, en orden (causas directas antes que cascadas que dependen de ellas):
  crearían exactamente 14 attachments (Grupo A) + 13 attachments (Grupo B) + 21 objetos
  (productos/ficha estática, 10 dependientes de A + 11 de B) = 48 objetos nuevos, todos en
  `draft`/`_psi_review_state=pending`, nunca publicados.

## Bloqueadores para una ejecución real futura

1. Generar un plan `full` nuevo y completarlo con `IMPORTAR FULL LOCAL RESUELTO` (autorización
   humana separada, no cubierta por esta tarea) — el `run_id` original ya no tiene plan vivo.
2. Sobre ese nuevo `run_id` completo, `retry_preflight()` reportaría en vivo los mismos 48/14
   (o los que correspondan si algo cambió entretanto — cada condición se re-verifica, nunca
   se asume).
3. Autorización humana explícita con la frase `REINTENTAR FALLOS RESUELTOS`, distinta de
   ambas anteriores.
4. Recomendado antes de ese futuro paso: decidir si se exime a los planes `COMPLETE` con
   `backup` de la poda por retención (ver recomendación en
   [13-pdf-runtime-failures.md](13-pdf-runtime-failures.md)), para que este mismo problema no
   se repita entre el pre-flight y la ejecución real.

**Ningún reintento se ejecutó. Ninguna base de datos se mutó más allá de las lecturas
documentadas. Ninguna publicación, staging o producción.**
