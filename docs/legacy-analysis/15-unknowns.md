# Incertidumbres y evidencia necesaria

| Pregunta | Qué sabemos | Qué falta |
|---|---|---|
| ¿Copia y dump vigentes? | uno sólo DDL, otro poblado | fecha/fuente y comparación controlada |
| ¿Tráfico/indexación? | sitemap/enlaces locales | Search Console, analítica, logs, HTTP |
| ¿Canonicalización de hosting? | htaccess sin regla HTTP/www; función desactivada | Apache/proxy/CDN y HTTP real |
| ¿Backend operativo? | CRUD/vistas existen; Savant busca otra carpeta | configuración y navegación autenticada |
| ¿Roles deseados? | enum Admin/User, código Super, permission vacío | confirmación y pruebas de acceso |
| ¿PHP por producto SQL? | nombres/enlaces/medios, sin FK | matriz revisada; no fusionar por similitud |
| ¿Relaciones cero intencionales? | 54 categoría=0, 110 marca=0 | catálogo/editorial |
| ¿WhatsApp correcto? | dos números en dos pies | decisión del negocio |
| ¿Logo oficial por marca? | array posicional y campo imagen | comparación visual y orden SQL |
| ¿Codificación/slugs? | mojibake y utilidades antiguas | conexión/collation, bytes servidos y URLs |
| ¿PDF y Unicode? | dos href PDF no resuelven literalmente | archivo correcto/NFC/NFD/despliegue |
| ¿404 real? | 404.php ausente | hosting y respuesta real |
| ¿Entrega de correo? | SMTP, éxito independiente del fallo | prueba futura autorizada, DNS/logs |
| ¿Video necesario? | columna vacía, campo hidden, vista sin video | embeds/producción/requisitos |
| ¿Variantes/residuos utilizados? | resp/v1 y bibliotecas extra | logs/referencias; nombre no basta |
| ¿Paridad visual/accesibilidad? | componentes identificados | desktop/móvil, teclado, JS desactivado |
| ¿Binarios íntegros? | 1.355 registros localizados | MIME, apertura y comparación de origen |

Ausencia de referencias literales no demuestra desuso: autoload, enlaces externos, rutas construidas y accesos directos lo impiden. No se inventaron slugs finales ni comportamiento WordPress. URLs derivadas llevan nota de probabilidad y la indexación queda UNKNOWN.

Alcance: inventario recursivo, lectura detallada de recorridos de negocio, extracción por patrones, modelo relacional, lint y documentación. No es auditoría dinámica de producción ni lectura manual línea por línea de todas las bibliotecas/minificados. Auxiliares sin integración demostrada se clasifican con incertidumbre, no como funcionalidades activas.
