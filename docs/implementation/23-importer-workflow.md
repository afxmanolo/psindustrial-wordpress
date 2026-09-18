# Flujo operativo y recuperación

1. Entrar como Administrator en Herramientas → Migración PS Industrial. El guard exige local, DB psindustrial_wp_dev y host MySQL loopback. En otro entorno se detiene.
2. Generar DRY RUN del subset o completo. No crea posts/terms/attachments ni escribe options de contenido. Sí escribe archivos privados del plan y copia los binarios seleccionados al paquete privado.
3. Revisar conteos, dependencias, REVIEW, hashes y colisiones. Descargar reporte. Un error global de fuentes/estructura impide generar un plan válido.
4. Para el subset, respaldo privado DB/uploads antes del primer lote. La suite lo hizo automáticamente. Confirmar con IMPORTAR SUBSET EN BORRADOR.
5. Procesar siguiente lote; repetir hasta cursor completo. Máximo25 objetos,5 medios y presupuesto blando de5s por petición; por defecto10 objetos. Una operación individual de WordPress puede exceder5s, por lo que no es un timeout duro ni una garantía del hosting.
6. Revisar resultados y volver a generar un plan. Reejecutar debe producir UNCHANGED/UPDATE sin duplicados y preservar ediciones humanas.
7. Generar FULL DRY RUN y detenerse. La ejecución real scope=full está bloqueada incluso mediante POST directo; máximo25 candidatos mutables en cualquier subset.

No WP-CLI, SSH, cron ni worker requeridos. Las llamadas CLI de tests son únicamente el harness local; el mismo runner y admin-post se probaron por HTTP. El plan completo del corpus actual es pequeño para la preparación local; para paquetes mucho mayores habría que trocear también la preparación, no elevar arbitrariamente timeout/memoria.

## Interrupciones

Lock de archivo no bloqueante + option exclusiva con owner/lease300s. El lock se libera por finally; tras un corte real puede quedar lease hasta su vencimiento. No hay botón que quite a ciegas el lock de otro escritor.

Journal INTENT antes de crear y OBJECT_CREATED con ID/snapshot antes de relaciones. Una interrupción tras crear pero antes de registrar identidad nunca causa reinserción automática: queda CONFLICT. Si no existe ID guardado, revisar el objeto y journal; no borrar/crear por intuición. Cursor se persiste después de cada resultado; una repetición de objeto APPLIED sin checkpoint se detecta como UNCHANGED.

Errores individuales generan FAILED/ERROR y permiten continuar; dependencias no aplicadas bloquean el dependiente. Cambios de fuentes, plan alterado, versión, entorno o concurrencia detienen el lote con error global. Se genera un nuevo dry-run después de resolver la causa, no se altera silenciosamente un plan aprobado.

Rollback creado: Runner::rollback_created(run, 'RETIRAR CREACIONES SIN CAMBIOS'), sólo administrador/local y creaciones comprobadas por journal+hash. No borra publicados ni términos con objetos/hijos; el guard de medios impide borrar referenciados. Actualizaciones y destinos editados requieren snapshot/revisión, no rollback masivo. No se añadió botón destructivo al panel. No se utilizó rollback para borrar el subset conservado.
