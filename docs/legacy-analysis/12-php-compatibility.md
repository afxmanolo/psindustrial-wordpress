# Compatibilidad PHP (referencia)

Se ejecutó sólo `php -n -l` sobre 617 PHP usando CLI **PHP 8.4.24** local. No ejecuta archivos ni resuelve includes, conecta DB o envía correo. Resultado: **613 pasan sintaxis, 4 fallan**; 29 de los que pasan emiten diagnósticos. Evidencia en php84-lint.csv. Pasar lint no demuestra compatibilidad de ejecución.

| Archivo bajo system/libs que falla | Diagnóstico |
|---|---|
| CoreLibrary/PaymentGateway/USAePay.class.php:45 | parámetro discount repetido |
| CoreLibrary/PayPalPro.class.php:908 | sintaxis inesperada |
| Nusoap/nusoap0.7.3.php:7460 | sintaxis inesperada |
| ProjectLibrary/Savant_php2.class.php:86 | __toString con argumentos |

No se demostró que estas cuatro variantes participen en rutas principales. No concluir que producción PHP 7.4 está caída por su presencia.

Búsqueda estática: each (3), mysql_* (55), ereg/eregi/split (61), utf8_encode/decode (16), parámetros tipados posiblemente nullable implícitos (91). Son ocurrencias de patrones, no errores confirmados; ver php-compatibility-signatures.csv. Parte reside en drivers/bibliotecas sin uso demostrado.

PHP 8.0 eliminó each/create_function y offsets con llaves; endureció firmas heredadas y llamadas no estáticas. [Guía oficial 8.0](https://www.php.net/manual/en/migration80.incompatible.php). No aparecieron llamadas activas create_function ni offsets con el patrón sencillo utilizado; no es garantía de ausencia absoluta.

PHP 8.2 depreca utf8_encode/decode y propiedades dinámicas fuera de excepciones. Asignaciones de entidades/Savant necesitan revisar herencia/métodos mágicos antes de clasificar cada caso. Referencias: [utf8_encode](https://www.php.net/utf8-encode), [propiedades dinámicas](https://wiki.php.net/rfc/deprecate_dynamic_properties).

PHP 8.4 depreca parámetros tipados implícitamente nullable; existen avisos reales del lint en bibliotecas. [Guía 8.4](https://www.php.net/manual/en/migration84.deprecated.php). Pasar null a parámetros internos no nullable puede emitir avisos desde 8.1: [guía 8.1](https://www.php.net/manual/en/migration81.deprecated.php).

Util_String::divideInColumns conserva split, anterior a PHP 7; PEAR incluye drivers mysql antiguos pero **la conexión de dominio selecciona mysqli**. No confundir código disponible con ejecución. Hay llamadas estáticas a métodos declarados de instancia en servicios y firmas antiguas; requieren comprobación de ejecución.

Dependencias: Savant3, Zend Translate, PEAR DB, HTTP_Request2, Nusoap y PHPMailer antiguo en system; PHPMailer 6.9.1 en contacto. Rutas absolutas SMTP, include_path, locale, GD, XML y mysqli dependen del entorno. Hay parámetros opcionales antes de requeridos y constructores antiguos.

WordPress futuro tendrá objetivo PHP 8.4 según proyecto; no se instaló ni seleccionó versión. No se moderniza ni porta este CMS: estos hallazgos evitan reutilizar piezas incompatibles y preparan lectura/exportación aislada futura.
