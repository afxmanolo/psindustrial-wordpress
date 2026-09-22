# Por qué REVIEW (y SKIP) nunca mutan nada

## El mecanismo, no una promesa

`process_entry()` — el núcleo compartido por ambas rutas de ejecución — empieza así:

```php
if ( in_array( $e['action'], array( 'SKIP','REVIEW' ), true ) ) {
 $result['status'] = 'SKIP' === $e['action'] ? 'SKIPPED' : 'BLOCKED';
 $result['result'] = $e['action'];
 return $result;
}
```

Esto es un **return temprano**, antes de cualquier llamada a `self::apply()`,
`Identity::prediction()`, `wp_insert_post()`, `wp_insert_term()` o `media_handle_sideload()`.
Una fila `REVIEW`/`SKIP` nunca llega a ese código — no es una comprobación adicional que
podría fallar, es la ausencia total del camino que crea objetos.

## Nunca una conversión implícita

La acción propia de la entidad (`REVIEW`, o `SKIP`) se copia literalmente a
`result['result']` — nunca se reescribe a `SKIP` cuando en realidad era `REVIEW`, ni a
`CREATE`. El plan en disco, tras ejecutar un lote, sigue mostrando exactamente qué acción
tenía cada entidad originalmente (`entries[n]['action']`) junto a qué pasó al procesarla
(`results[n]['result']`) — ambos coinciden siempre para REVIEW/SKIP.

## Verificado, no sólo argumentado

- Un plan sintético con una fila `REVIEW` intercalada entre dos filas `MIGRATE`: tras
  ejecutar el lote completo, `Identity::find()` sobre la entidad REVIEW devuelve `0` (ningún
  post/term/attachment existe para ella), mientras las dos filas MIGRATE vecinas sí se
  crearon con normalidad en el mismo lote.
- Una fila `SKIP` aislada: mismo resultado, `Identity::find()` devuelve `0`.
- Contra el plan real completo: 1.534 filas SKIP y 339 REVIEW — **ninguna se tocó en esta
  fase**, porque no se ejecutó nada contra el plan real (sólo pre-flight, de sólo lectura).
  La garantía para cuando sí se ejecute algún día es la misma que aquí se prueba con
  fixtures: cada una de esas 1.873 filas pasará por el mismo `return` temprano.

## Lo que el pre-flight añade, sin sustituir esta garantía

`preflight_full_local()`'s comprobación `review_counted` no es lo que impide que REVIEW mute
— es una confirmación *informativa*, previa, de cuántas filas quedarán preservadas, para que
quien autorice la ejecución vea la cifra antes de confirmar. La garantía real vive en
`process_entry()`, no en el pre-flight.
