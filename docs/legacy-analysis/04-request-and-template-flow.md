# Recorrido de solicitudes y plantillas

Evidencias relativas a `legacy/public/`; análisis estático.

| Entrada | Resolución demostrada | Salida |
|---|---|---|
| `/` | DirectoryIndex del servidor pendiente; sitemap la trata como portada | probable index.php |
| `/archivo.php` | archivo raíz directo | cuerpo fijo + includes |
| `/productos/{cat}/{marca}/{texto}/` | productos.php?cmd=search&CategoriaId=…&MarcaId=… | ProductosMain.php |
| `/productos/categoria/{id}/{texto}/` | CategoriaId | mismo listado |
| `/productos/marca/{id}/{texto}/` | MarcaId | mismo listado |
| `/{id}/producto/{texto}/` | cmd=loadProductos&productId=… | ProductosView.php |
| `/{id}/categoria/{texto}/` | categorias.php?cmd=search&categoriaId=… | CategoriasMain.php |
| `/system/file.php?id=…&type=…` | carga file, verifica access_type, selecciona IO | inline o attachment con cmd=download |
| `/system/index.php` | Location: ../index.php sin código explícito | normalmente 302; HTTP no medido |
| `/contacto.php` POST | incluye enviaContacto.php antes del formulario | validación, SMTP, respuesta HTML/JS |

Las cinco reglas de `.htaccess` son **rewrites internos** con QSA,L, no redirecciones 301. El texto de slug no se utiliza para localizar la entidad: mandan los IDs. Los patrones exigen slash final; otros casos dependen del servidor. QSA conserva query strings y requiere revisar precedencia de parámetros en futuras pruebas.

Application::getCommand y getParameter leen REQUEST; los IDs de FO pasan is_numeric, no se deben presentar como SQLi confirmada por mera concatenación. En listados de productos se aplica igualdad exacta de categoría y/o marca; no se expanden descendientes (el intento está comentado). Se ordena por productos_id ASC y límite -1, aun cuando comentarios hablan de cinco resultados. Categorías carga hijos por padre_id exacto.

`productos.php:6` ejecuta die. El HTML posterior es inalcanzable y no aporta el title real «Overhead Door» al controlador. No debe extraerse como SEO activo.

ProjectLibrary_Savant configura system/view y traducción TMX. Su constructor alternativo para BO está comentado. Las plantillas administrativas existen en otra carpeta; no se certifica su resolución con esta copia. system/Productos.php, system/Categorias.php y system/Marcas.php son entradas alternativas, no un segundo catálogo distinto demostrado.

IO_FileViewer lee originales relativos a system/files; emite MIME, nombre, longitud y contenido. Si no encuentra archivo imprime texto sin fijar 404 en ese método; rechazos de permiso sí devuelven 404 en el selector IO. Productos/categorías inexistentes no tienen una ruta 404 explícita confirmada en los controladores examinados.
