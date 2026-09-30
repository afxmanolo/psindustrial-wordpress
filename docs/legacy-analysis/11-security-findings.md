# Hallazgos preliminares de seguridad

Auditoría estática, sin explotación, requests al hosting ni correcciones. Prioridad depende de exposición real. Rutas relativas a legacy/public/system/libs salvo indicación.

| ID / prioridad | Evidencia | Hallazgo y alcance |
|---|---|---|
| S01 crítica potencial | dump puerta34_administrador.sql en public; htaccess sin bloqueo SQL | Posible descarga si se despliega así. Presencia local confirmada; exposición remota UNKNOWN. No se copian registros user. |
| S02 alta | SDO/Core/DB, CoreLibrary/DB, public/sendMail/sendMail.php | SE DETECTÓ UNA CREDENCIAL QUE DEBE ROTARSE/EXTERNALIZARSE |
| S03 alta | BO/Productos:48–49 → Entity/Search:58–59 → DB/DAO/AdvancedDAO:20 | Parámetro o llega a ORDER BY sin lista permitida: superficie SQL injection. Depende de alcance de endpoint/auth; no se explotó. |
| S04 alta | public/contacto:85–92; sendMail::getValue | XSS reflejado potencial por POST sin escape en atributo/textarea. |
| S05 alta | BO/Productos:265 → system/view/ProductosView:47 | HTML se guarda/imprime sin filtrado: XSS almacenado posible con acceso de edición. Conservar HTML legítimo con sanitización futura. |
| S06 alta | BO::checkPermission comentado; Application::redirect | Auth no equivale a autorización. Redirect no termina ejecución; revisar continuación de endpoint. Rol desde entity sin capability específica demostrada. |
| S07 alta | save/delete leen REQUEST; enlaces cmd=delete | No se localizaron nonces/CSRF en CRUD. Posibles acciones inducidas con sesión. |
| S08 alta | BO/Authentification:72 | MD5 sin sal; no portable como política de contraseñas. Hashes excluidos. |
| S09 media/alta | public/enviaContacto:49–52 | reCAPTCHA sólo no-vacío: protección anti-spam insuficiente. |
| S10 media | Validator/Test/FileUpload:20–28 | Usa type/name del cliente. Hay allowlists y GD; no se demuestra inspección robusta PDF, cuotas o control uniforme de tamaño. |
| S11 media | common:96; UserSession:28–38 | Sin regeneración de ID en login observada ni borrado completo en logout; flags dependen del entorno. |
| S12 media | FileApplication/FileViewer y multimedia | access_type en endpoint no protege necesariamente URLs directas de binarios. |
| S13 media | ErrorInfo SMTP; common oculta errores | Posible filtración de diagnóstico y baja observabilidad; log operativo no verificado. |

**Matices:** FO filtra IDs con is_numeric; loadByEmail usa escapeString; GenericDAO usa addslashes para IDs y escapeString para otros valores. No se afirma que toda concatenación sea explotable. Sí hay flujo inseguro concreto en ordenación BO. No se realizaron pruebas de ataque.

Includes: autoload construye rutas desde clases; Application::redirect(url,true) puede incluir. Templates/paths principales son literales en los controladores vistos. No se demostró input remoto para inclusión arbitraria: LFI/RFI no confirmado.

Uploads: FileSaver usa sha1_file y nombres sin extensión para originales, move_uploaded_file y derivados GD. Reduce algunos escenarios por extensión, pero no certifica seguridad de contenido o exposición. PDFs y otros tipos requieren validación independiente.

No copiar infraestructura de autenticación ni secretos al sistema nuevo. Revisar cada riesgo en staging controlado y definir capacidades de contenido/usuarios/medios. No se corrigió nada en esta fase.
