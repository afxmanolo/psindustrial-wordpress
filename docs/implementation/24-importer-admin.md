# Pantalla administrativa

Ruta: Herramientas → **Migración PS Industrial** (`tools.php?page=psi-migration`). Requiere manage_options y psi_manage_migration; Gestor de contenidos no puede acceder ni invocar acciones. Cada POST requiere nonce propio y método POST.

Muestra el selector de alcance, DRY RUN predeterminado, estado/progreso, runID, conteos por tipo/resultado, fuentes y SHA-256, resultados recientes y descarga del reporte íntegro. El formulario de ejecución sólo existe para subset, con texto de confirmación explícita por lote. No importa en activación/carga normal del plugin ni al abrir la pantalla.

La tabla muestra hasta50 entradas/resultados; el archivo descargable incluye todos los REVIEW/errores. No se omiten del reporte por paginación. Continuar se hace con el mismo enlace run; no depende de sesión PHP ni proceso persistente. Se exige nueva sesión/nonce si caduca la autenticación.

No assets administrativos globales, librerías JS, AJAX propio ni formularios de subida arbitraria. No admite nombres de paths desde el navegador. Exportación autenticada con no-cache y JSON, sin filas SQL, cuerpos de contenido, credenciales ni backups.

## Checklist

- [ ] Administrator abre la pantalla y ve DRY RUN como opción inicial.
- [ ] Generar subset, revisar plan, descargar JSON.
- [ ] Confirmación vacía no ejecuta.
- [ ] Continuar un lote incrementa cursor; repetir con mismo run al terminar no vuelve a crear.
- [ ] Revisar Productos en borrador, Categorías/Marcas en review y Medios.
- [ ] Gestor no puede abrir/ejecutar/exportar migración por URL directa.
- [ ] FULL DRY RUN ofrece reporte y no ofrece ejecución.

El acceso/nonce/generación/ejecución de lote se verificaron por HTTP autenticado en local. No se requiere plugin adicional.
