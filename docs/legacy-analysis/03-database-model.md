# Modelo real de base de datos

## Fuentes y límites

`legacy/database/psindustrial_db.sql` (4.909 bytes) contiene seis tablas y sus ALTER, **sin INSERT**. `legacy/public/puerta34_administrador.sql` (583.544 bytes) contiene el mismo dominio con datos: 165 productos, 38 categorías, 12 marcas, 1.355 file, 1 user, 0 permission. Los conteos se obtuvieron leyendo INSERT con un analizador léxico de strings/escapes, sin ejecutar SQL. No se copiaron datos de usuario ni hashes.

Ambos esquemas usan InnoDB/utf8mb3. Dump poblado declara collation general_ci y `productos.fecha DEFAULT current_timestamp()`; esquema local exige fecha sin ese default. Diferencias de ancho int y AUTO_INCREMENT no deben tratarse como filas existentes. No afirmar que el dump poblado es la base actual de producción.

## categorias

Organización jerárquica y presentación de soluciones.

| Columna | Tipo/restricciones DDL local | Propósito |
|---|---|---|
| categorias_id | int NOT NULL | PK autoincremental |
| nombre | varchar(255) NOT NULL | Etiqueta y slug |
| descripcion | text | Utilizada como título de página padre; hidden en BO |
| imagen | varchar(255) DEFAULT NULL | IDs file serializados; UI limita a uno |
| padre_id | int DEFAULT NULL | Relación implícita a categorías; NULL/0 raíz |

Uso: `system/libs/ProjectLibrary/SDO/Core/DAO/Categorias.class.php`, servicios SDO/Application, entidades y controladores FO/BO correspondientes; vistas del mismo dominio.

## file

Originales/variantes y metadatos de recursos.

| Columna | Tipo/restricciones DDL local | Propósito |
|---|---|---|
| id | int UNSIGNED NOT NULL | PK autoincremental |
| original_id | int UNSIGNED DEFAULT NULL | FK a file.id; derivados |
| path | varchar(100) NOT NULL | Ruta relativa; originales bajo system, derivados bajo multimedia |
| filename | varchar(48) NOT NULL | Hash/nombre físico; originales sin extensión |
| name | varchar(100) NOT NULL | Nombre de carga usado en Content-Disposition |
| type | varchar(50) NOT NULL | MIME declarado |
| size | double NOT NULL | Tamaño registrado |
| date | datetime NOT NULL | Fecha de subida |
| description | varchar(255) DEFAULT NULL | Descripción de medio |
| access_type | varchar(255) DEFAULT NULL | Clasificación de acceso del endpoint |
| version | varchar(20) DEFAULT NULL | original/small/medium/large |

Uso: DAO/File, CoreLibrary/DB/DAO/FileDAO, FileManagement, ImageHandler, IO_FileSaver/Viewer y system/file.php; productos/categorías/marcas/usuario referencian IDs.

## marcas

Marca y referencia de logo.

| Columna | Tipo/restricciones DDL local | Propósito |
|---|---|---|
| marcas_id | int NOT NULL | PK autoincremental |
| nombre | varchar(255) NOT NULL | Etiqueta y slug |
| imagen | varchar(255) DEFAULT NULL | Referencia serializada a file; logo frontend v1 usa array fijo |

Uso: `system/libs/ProjectLibrary/SDO/Core/DAO/Marcas.class.php`, servicios SDO/Application, entidades y controladores FO/BO correspondientes; vistas del mismo dominio.

## permission

Permisos nominales asociados a usuario; sin filas en dump poblado.

| Columna | Tipo/restricciones DDL local | Propósito |
|---|---|---|
| permission_id | varchar(255) NOT NULL | PK varchar; permiso globalmente único, no PK compuesta |
| user_id | int UNSIGNED NOT NULL | FK a user.user_id |

Uso: DAO/Permission y ProjectLibrary_Access::loadPermissions. Presencia de clases no prueba que los controladores apliquen permisos, pues la estrategia BO está comentada.

## productos

Contenido del catálogo dinámico.

| Columna | Tipo/restricciones DDL local | Propósito |
|---|---|---|
| productos_id | int NOT NULL | PK autoincremental; identidad y ruta |
| nombre | varchar(255) NOT NULL | Nombre visible y origen del slug |
| descripcion | text NOT NULL | HTML de contenido |
| imagenes | varchar(255) NOT NULL | Lista CSV de IDs file, no rutas |
| fichas | varchar(255) DEFAULT NULL | Lista CSV de IDs file |
| video | varchar(255) DEFAULT NULL | Campo oculto en BO; todos vacíos en dump |
| categoria | int DEFAULT NULL | ID categorías; 0 usado como ausencia |
| marca | int DEFAULT NULL | ID marcas; 0 usado como ausencia |
| fecha | date NOT NULL | Fecha de contenido |

Uso: `system/libs/ProjectLibrary/SDO/Core/DAO/Productos.class.php`, servicios SDO/Application, entidades y controladores FO/BO correspondientes; vistas del mismo dominio.

## user

Cuentas internas de administración.

| Columna | Tipo/restricciones DDL local | Propósito |
|---|---|---|
| user_id | int UNSIGNED NOT NULL | PK autoincremental |
| email | varchar(127) NOT NULL | Identificador de login; no se reproducen valores |
| password | varchar(45) NOT NULL | Hash MD5 usado por código; excluido de documentación |
| role | enum('Admin','User') NOT NULL | Enum Admin/User; código menciona Super incompatible |
| first_name | varchar(45) NOT NULL | Identidad administrativa; datos no publicados |
| last_name | varchar(45) NOT NULL | Identidad administrativa; datos no publicados |
| archivo | varchar(255) DEFAULT NULL | Referencia a medio del usuario |

Uso: DAO/User::loadByEmail, BO_Authentification, SDO_Security y CRUD BO/User. Se requiere recreación de cuentas autorizadas, no traslado de passwords.

## Claves y relaciones

Todas las tablas tienen PK según sus IDs, excepto permission cuya PK es **permission_id sola**. file.original_id tiene índice y FK a file.id con ON DELETE/UPDATE CASCADE. permission.user_id tiene índice y FK a user.user_id con cascada. No hay FK para productos.categoria, productos.marca, categorias.padre_id ni listas de medios.

Relaciones implícitas: categoría 1:N producto; marca 1:N producto; categoría padre 1:N hija; propietario de contenido 1:N IDs file serializados; file original 1:N versiones. No hay N:M de productos/categorías o productos/marcas. No hay tabla de slug, redirección, SEO, pedidos o leads.

## Reconciliación de datos

- 54 productos categoría 0, 110 marca 0: 164 campos sin asociación. Se anotan en data-integrity-findings.csv como referencia ausente; **0 es un sentinel**, no una FK positiva rota.
- Todos los IDs positivos de categoría/marca/productos-medios examinados resuelven; padres positivos existen. Árbol con 8 raíces (7 NULL y una 0) y 30 hijas, máximo dos niveles.
- 458 registros file original, 299 small, 299 medium, 299 large. 159 originales son application/pdf. Los 1.355 registros se localizaron por nombre/ruta: originales en system/files y variantes en multimedia. Pueden compartir binario; no confundir filas con archivos únicos.
- 0 productos con video poblado. Hay valores textuales con mojibake en dump; se conserva fuente sin normalizar.
- La comprobación de localización no valida por sí sola MIME, contenido visual o respuesta del endpoint. No se ejecutó una importación.

## Información a conservar o reconsiderar

Migrar productos completos, nombres/descripciones de categorías (la descripción alimenta títulos), padre, marcas, originales y descripciones/nombres/MIME/orden de medios, fechas e IDs de origen. Mantener trazabilidad de versiones y rutas para resolver URLs antiguas; generar nuevos tamaños no autoriza borrar originales.

User: sólo cuentas internas necesarias mediante proceso seguro; no passwords MD5 ni credenciales. Permission: rediseñar capacidades al confirmar reglas reales, no copiar estructura con PK global. Infraestructura de file puede reemplazarse por Media, conservando asociación y compatibilidad. No descartar registros de contenido por ausencia de FK o enlaces.

`database-code-references.csv` enumera archivos/líneas por símbolos de tabla/clase (referencias indirectas incluidas). `productos-catalog.csv`, `categorias-catalog.csv`, `marcas-catalog.csv` y `media-reconciliation.csv` son anexos de análisis, no dumps ni importadores.
