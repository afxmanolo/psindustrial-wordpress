# Arquitectura del sistema

Análisis estático, 2026-09-17. Evidencias relativas a `legacy/public/`. No se certifica funcionamiento desplegado.

## Dos recorridos que conviven

- Estático: Apache sirve un PHP raíz → `header.php` → cuerpo HTML propio → menús laterales → `footer.php`. Metadatos se definen como `$title` y `$description` o heredan valores genéricos. Ejemplos: `bft.php`, `1500-revolving-door.php`.
- Dinámico: `.htaccess` reescribe a `productos.php`/`categorias.php` → `system/common.php` → controlador `ProjectLibrary_FrontEnd_FO_*` → SDO → aplicación SDO Core → DAO → `DBPEAR` → PEAR DB con mysqli → entidad → Savant → `system/view/*`.
- Portada: `index.php` incluye `headerv1.php`; consulta subcategorías de IDs fijos 1–7, utiliza bloques gráficos fijos y marcas de SQL. No es una portada enteramente administrable.
- Administración: endpoints `system/backoffice/*.php` → verificación de sesión → controladores BO → mismo modelo/DAO → formularios y listados. No genera ni actualiza automáticamente los PHP públicos individuales.

## Bootstrap e infraestructura

`system/common.php:15–95` registra autoload por convención de guiones bajos a directorios; intenta rutas de libs, CoreLibrary y PEAR. Depende de include_path/entorno de ejecución: no se comprobó la configuración del servidor. Inicia sesión en línea 96, configura hora México, oculta errores, amplía memoria a 2 GB y tiempo a 3.600 segundos. Carga explícitamente PEAR DB y clases HTTP_Request2.

`Config.class.php:108–137` distingue localhost/demo/producción; entorno local usa `/macheteria/`; dominio de producción reconocido: `puertasyserviciosindustriales.com`. Genera bases HTTPS, pero eso no demuestra redirect HTTP→HTTPS. Hostnames distintos pueden dejar constantes sin definir. `redirecDomain()` existe pero su invocación está comentada.

`ProjectLibrary/SDO/Core/DB.class.php` extiende DBPEAR y contiene conexiones mysqli por entorno. Hay también wrappers antiguos DB, _DB y drivers PEAR para otros motores; no son prueba de múltiples bases activas.

## Dinamismo y dependencias

Dominio activo: categorías, productos, marcas, archivos y cuentas internas. No hay tablas de pedidos o clientes en los dumps. Calendar, pagos, SOAP, inventarios, sucursales y beneficios aparecen como bibliotecas/vistas heredadas: no confundir su presencia con funcionalidad del negocio.

Frontend: CSS/JS de plantilla comercial, jQuery, Bootstrap y Swiper; panel con AdminLTE, DataTables, CKEditor y widgets. Google Fonts, analytics y reCAPTCHA están referenciados; WhatsApp es un enlace externo. No se identificó una API de negocio esencial, pero sí estas dependencias de terceros.

Ver `class-references.csv` para referencias de clases (resolución estática, no trazado de ejecución) y `dependency-inventory.csv` para includes. No se ejecutó ningún endpoint, envío de correo ni consulta SQL.
