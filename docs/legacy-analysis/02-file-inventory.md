# Inventario completo de archivos

Análisis 2026-09-17; 3.861 archivos leídos para inventario/hash, 617 PHP clasificados individualmente. El archivo [file-inventory.csv](file-inventory.csv) es el inventario exhaustivo, incluidos binarios, archivos sin extensión, configuraciones y dependencias. No reproduce contenidos secretos.

## Distribución por carpeta

| Carpeta | Archivos | PHP |
|---|---:|---:|
| (raíz) | 201 | 198 |
| PHPMailer | 71 | 64 |
| css | 15 | 0 |
| fichas | 80 | 0 |
| fonts | 22 | 0 |
| images | 340 | 0 |
| js | 31 | 0 |
| multimedia | 564 | 0 |
| sendMail | 1 | 1 |
| system | 2536 | 354 |

## Qué contiene cada área

- Raíz: páginas, fragmentos, formulario, controladores, .htaccess, sitemap.xml y un dump poblado. No hay 404.php ni robots.txt raíz en esta copia.
- system: bootstrap, clases propias, vendors PHP, administración, templates, traducciones y **originales** en files.
- multimedia: versiones públicas generadas de imágenes; no contiene todos los originales.
- images: imágenes fijas, banners, logos, assets y posibles variantes; no asumir que todos corresponden a SQL.
- fichas: PDFs enlazados directamente; existen también PDFs sin extensión en system/files, identificados por tabla file.
- css/js/fonts: plantilla y recursos de frontend. system/backoffice/includes contiene su propia pila de assets/widgets y ejemplos.
- PHPMailer: implementación moderna del envío; sendMail: helpers del formulario; system/libs/PhpMailer contiene otra versión antigua.

## Clasificación

Cada fila del CSV incluye ruta, extensión, bytes, SHA-256, función probable, evidencia, referencias por basename y familia cuando aplica. Categoría/landing y detalle se consideran probables por estructura: no se inventa correspondencia SQL. Funciones múltiples se explican en documentos de flujo.

No hay carpeta includes pública central: fragmentos compartidos están principalmente en raíz y includes administrativos bajo backoffice. El inventario registra todos los PHP, incluidas vistas ajenas al catálogo y ejemplos de terceros. Cero referencias por basename no demuestra archivo sin uso. No se eliminó ni se etiquetó definitivamente como obsoleto un archivo sólo por nombre.

## PHP raíz por función

| Archivo | Función probable | Familia |
|---|---|---|
| 1500-revolving-door.php | producto (probable por estructura) | detalle-estatico |
| accesorios.php | producto (probable) | detalle-estatico |
| accesspro-fs1000speed.php | producto (probable) | detalle-estatico |
| automatismos-para-cancelas-cubic.php | producto (probable por estructura) | detalle-estatico |
| back-seccionales.php | include / fragmento |  |
| barreras-estacionamiento-moovi50rm.php | producto (probable) | detalle-estatico |
| bft-resp.php | categoría / landing SEO (probable) | listado-estatico |
| bft.php | marca | listado-marca |
| bix-al-antena-externa-433-mhz.php | producto (probable por estructura) | detalle-estatico |
| blindadas.php | categoría / landing SEO (probable) | listado-estatico |
| blue-giant.php | marca | listado-marca |
| categorias.php | endpoint público / controlador | controlador-categorias |
| centros-comerciales.php | categoría / landing SEO (probable) | listado-estatico |
| clopay.php | marca | listado-marca |
| coleccion-classic.php | producto (probable) | detalle-estatico |
| coleccion-modern-steel.php | producto (probable) | detalle-estatico |
| com-programadores-digitales-y-rotativos.php | producto (probable por estructura) | detalle-estatico |
| comercial.php | categoría / landing SEO (probable) | listado-estatico |
| contacto.php | página institucional / formulario | contacto |
| contra-exposion.php | categoría / landing SEO (probable) | listado-estatico |
| contra-incendio.php | categoría / landing SEO (probable) | listado-estatico |
| cortina-de-seguridad-670.php | producto (probable) | detalle-estatico |
| cortina-de-seguridad-671.php | producto (probable) | detalle-estatico |
| cortina-en-aluminio-serie-511-521.php | producto (probable) | detalle-estatico |
| cortina-europea.php | producto (probable) | detalle-estatico |
| cortina-plana.php | producto (probable) | detalle-estatico |
| cortina-serie-600.php | producto (probable) | detalle-estatico |
| cortina-serie-610.php | producto (probable) | detalle-estatico |
| cortina-serie-620.php | producto (probable) | detalle-estatico |
| cortina-serie-625.php | producto (probable) | detalle-estatico |
| cortina-serie-630.php | producto (probable) | detalle-estatico |
| cortina-serie-790-cw.php | producto (probable) | detalle-estatico |
| cortinas-enrollables-de-aluminio.php | producto (probable por estructura) | detalle-estatico |
| cortinas-enrollables-industriales.php | categoría / landing SEO (probable) | listado-estatico |
| cortinas-ventiladas-685.php | producto (probable) | detalle-estatico |
| cross-3e.php | producto (probable por estructura) | detalle-estatico |
| detector-de-masas-max-eagle.php | producto (probable por estructura) | detalle-estatico |
| ditec-neos-y-neos.php | producto (probable por estructura) | detalle-estatico |
| dockman.php | marca | listado-marca |
| dod-automatizacion-compacto-y-potente-para-portones-seccionales-correderos-y-enrollables.php | producto (probable por estructura) | detalle-estatico |
| doorlock.php | marca | listado-marca |
| dura-glide-20003000-puerta.php | producto (probable por estructura) | detalle-estatico |
| energy-series-with-intellicore-37171-3-4.php | producto (probable) | detalle-estatico |
| energy-series-with-intellicore-3720-3724-3722.php | producto (probable) | detalle-estatico |
| enviaContacto.php | formulario / procesamiento |  |
| equipos-y-accesorios-para-anden-de-carga.php | categoría / landing SEO (probable) | listado-estatico |
| estacionamientos.php | categoría / landing SEO (probable) | listado-estatico |
| fast-seal-high-performance-door.php | producto (probable) | detalle-estatico |
| fire-rated-doors-firestar-models-700-700c.php | producto (probable) | detalle-estatico |
| footer.php | include / fragmento |  |
| footerv1.php | include / fragmento |  |
| fotoceldas.php | producto (probable por estructura) | detalle-estatico |
| fraccionamientos-y-condominios.php | categoría / landing SEO (probable) | listado-estatico |
| glg-porte-industriali.php | marca | listado-marca |
| gol-transmisores-de-cuatro-canales.php | producto (probable por estructura) | detalle-estatico |
| header.php | include / fragmento |  |
| headerv1.php | include / fragmento |  |
| icaro-smart.php | producto (probable) | detalle-estatico |
| iconos-marcas.php | include / fragmento |  |
| iconos-marcasv1.php | include / fragmento |  |
| index-estatico.php | página pública | inicio |
| index-resp.php | página pública | inicio |
| index.php | página pública | inicio |
| industrial.php | categoría / landing SEO (probable) | listado-estatico |
| infraca-quality-doors.php | marca | listado-marca |
| kelley.php | marca | listado-marca |
| kronos-puertas-rapidas-de-sectores-modulares-autorreparable.php | producto (probable) | detalle-estatico |
| lab-4-fotocelulas-de-rayos-infrarrojos.php | producto (probable por estructura) | detalle-estatico |
| lab-9-detectores-de-volumen-de-metal-loop.php | producto (probable por estructura) | detalle-estatico |
| labio-de-elevacion-mecanico-dockman.php | producto (probable) | detalle-estatico |
| labio-elevacion-anden-carga.php | categoría / landing SEO (probable) | listado-estatico |
| lan-4-sistema-de-teclado-con-combinacion-digital.php | producto (probable por estructura) | detalle-estatico |
| lan-7-sistema-de-mando-de-proximidad-de-tarjetas-con-tecnologia-transponder.php | producto (probable por estructura) | detalle-estatico |
| lift-master-mod-h.php | producto (probable) | detalle-estatico |
| lift-master.php | marca | listado-marca |
| liftmaster-modh.php | producto (probable por estructura) | detalle-estatico |
| light-communication-system-lcs.php | producto (probable por estructura) | detalle-estatico |
| links-menu-marcas.php | include / fragmento |  |
| links-menu-marcasv1.php | include / fragmento |  |
| links-menu.php | include / fragmento |  |
| links-menuv1.php | include / fragmento |  |
| lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php | producto (probable) | detalle-estatico |
| luxo-robusto-e-incansable-para-servicios-intensivos-para-hojas-de-hasta-5-m.php | producto (probable por estructura) | detalle-estatico |
| magic-fuerza-del-operador.php | producto (probable por estructura) | detalle-estatico |
| marcas.php | directorio estático de marcas | marcas-estaticas |
| marcasv1.php | directorio dinámico de marcas | marcas-dinamicas |
| menu-marcas.php | include / fragmento |  |
| menu-marcasv1.php | include / fragmento |  |
| menu-seccionales.php | include / fragmento |  |
| menu-soluciones.php | include / fragmento |  |
| menu-solucionesv1.php | include / fragmento |  |
| nosotros.php | página institucional | nosotros |
| operador-comercial-rhx.php | producto (probable) | detalle-estatico |
| operador-para-cortina-enrrollable-rsx.php | producto (probable) | detalle-estatico |
| operador-para-perfilados-comerciales-jst.php | producto (probable) | detalle-estatico |
| operador-para-perfilados-comerciales-sel.php | producto (probable) | detalle-estatico |
| operadores-para-puerta-abatible.php | categoría / landing SEO (probable) | listado-estatico |
| operadores-para-puerta-corrediza-industrial.php | categoría / landing SEO (probable) | listado-estatico |
| operadores-para-puertas-ascendentes.php | categoría / landing SEO (probable) | listado-estatico |
| operadores-para-puertas-corredizas-residenciales.php | categoría / landing SEO (probable) | listado-estatico |
| operadores-para-puertas-de-mostrador-cdo.php | producto (probable) | detalle-estatico |
| operadores-puerta-abatible-industrial.php | categoría / landing SEO (probable) | listado-estatico |
| operadores-puerta-abatible.php | producto (probable) | detalle-estatico |
| operadores-puerta-corrediza-residencial.php | producto (probable) | detalle-estatico |
| operadores-puertas-ascendentes.php | producto (probable) | detalle-estatico |
| operadores-puertas-seccionales-cortinas.php | categoría / landing SEO (probable) | listado-estatico |
| overhead-door.php | marca | listado-marca |
| p45-7-piston-hidraulico-para-puerta-abatible.php | producto (probable) | detalle-estatico |
| pas-sensores-volumetricos.php | producto (probable por estructura) | detalle-estatico |
| phobos-n-piston-electromecanico.php | producto (probable) | detalle-estatico |
| politica-privacidad.php | página institucional | politica-privacidad |
| predadoor-nxt-door.php | producto (probable) | detalle-estatico |
| productos.php | categoría / landing SEO (probable) | controlador-productos |
| puert-seccional-de-acero-sin-aislamiento-430-uso-medio.php | producto (probable) | detalle-estatico |
| puerta-contra-explosion.php | producto (probable) | detalle-estatico |
| puerta-contra-incendio.php | categoría / landing SEO (probable) | listado-estatico |
| puerta-de-acero-acanalado-modelos-524.php | producto (probable) | detalle-estatico |
| puerta-de-acero-acanalado.php | producto (probable por estructura) | detalle-estatico |
| puerta-estandar-reforzada.php | producto (probable) | detalle-estatico |
| puerta-estandar.php | producto (probable) | detalle-estatico |
| puerta-holandesa.php | producto (probable) | detalle-estatico |
| puerta-para-hospital.php | producto (probable por estructura) | detalle-estatico |
| puerta-seccionabl-acero-perfiles-422-uso-pesado.php | producto (probable) | detalle-estatico |
| puerta-seccional-de-acero-perfiles-418.php | producto (probable) | detalle-estatico |
| puerta-seccional-de-acero-perfiles-432-uso-medio.php | producto (probable) | detalle-estatico |
| puerta-seccional-de-acero-sin-aislamiento-416-uso-muy-pesado.php | producto (probable por estructura) | detalle-estatico |
| puerta-seccional-de-acero-sin-aislamiento-424-uso-pesado.php | producto (probable) | detalle-estatico |
| puerta-seccional-de-acero-thermacore-593-uso-medio.php | producto (probable) | detalle-estatico |
| puerta-seccional-de-acero-thermacore-594-uso-medio.php | producto (probable) | detalle-estatico |
| puerta-seccional-de-acero-thermacore-595-uso-pesado.php | producto (probable) | detalle-estatico |
| puerta-seccional-de-acero-thermacore-599-uso-muy-pesado.php | producto (probable por estructura) | detalle-estatico |
| puerta-seccional-de-acero-thermacore-serie-592.php | producto (probable) | detalle-estatico |
| puerta-y-fijos-louver.php | producto (probable por estructura) | detalle-estatico |
| puertas-blindadas.php | producto (probable) | detalle-estatico |
| puertas-contra-incendio.php | producto (probable) | detalle-estatico |
| puertas-contra-rayos-x.php | producto (probable) | detalle-estatico |
| puertas-de-garaje-aisladas.php | producto (probable por estructura) | detalle-estatico |
| puertas-enrollables-de-garage.php | producto (probable por estructura) | detalle-estatico |
| puertas-industriales-dockman.php | producto (probable) | detalle-estatico |
| puertas-peatonales-estandar-y-reforzada.php | categoría / landing SEO (probable) | listado-estatico |
| puertas-peatonales-hospitales.php | categoría / landing SEO (probable) | listado-estatico |
| puertas-peatonales-rayos-x.php | categoría / landing SEO (probable) | listado-estatico |
| puertas-peatonales-tipo-holandesa.php | categoría / landing SEO (probable) | listado-estatico |
| puertas-rapidas-enrollables.php | producto (probable por estructura) | detalle-estatico |
| puertas-rapidas.php | categoría / landing SEO (probable) | listado-estatico |
| puertas-residenciales.php | producto (probable por estructura) | detalle-estatico |
| puertas-salida-emergencia.php | categoría / landing SEO (probable) | listado-estatico |
| puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php | producto (probable) | detalle-estatico |
| puertas-seccionales-de-acero-aisladas-thermospan.php | producto (probable) | detalle-estatico |
| puertas-seccionales-de-acero-modelo-2415.php | producto (probable) | detalle-estatico |
| puertas-seccionales-de-acero-perfiles-426.php | producto (probable) | detalle-estatico |
| puertas-seccionales-de-acero-sin-aislamiento-420-uso-pesado.php | producto (probable) | detalle-estatico |
| puertas-seccionales-de-acero-thermacore-598-uso-liviano.php | producto (probable por estructura) | detalle-estatico |
| puertas-seccionales-de-aluminio-521.php | producto (probable) | detalle-estatico |
| puertas-seccionales-industriales.php | categoría / landing SEO (probable) | listado-estatico |
| puertas-y-fijos-louver.php | categoría / landing SEO (probable) | listado-estatico |
| puertasresidenciales.php | categoría / landing SEO (probable) | listado-estatico |
| q-silent-max-eagle-3-4-hp.php | producto (probable) | detalle-estatico |
| qik-barreras-automaticas-de-hasta-58-m.php | producto (probable por estructura) | detalle-estatico |
| rampa-niveladora-mecanica-blue-giant.php | producto (probable) | detalle-estatico |
| rampa-niveladora.php | categoría / landing SEO (probable) | listado-estatico |
| rampas-de-anden-hidraulicas-kelley.php | producto (probable) | detalle-estatico |
| rampas-de-anden-hidraulicas.php | producto (probable) | detalle-estatico |
| rampas-de-anden-mecanicas.php | producto (probable) | detalle-estatico |
| rapida-apilable.php | producto (probable) | detalle-estatico |
| rapida-enrollable-industrial.php | producto (probable) | detalle-estatico |
| receptor-para-control-vehiculos.php | categoría / landing SEO (probable) | listado-estatico |
| residencial.php | categoría / landing SEO (probable) | listado-estatico |
| retenedor-de-vehiculos.php | producto (probable) | detalle-estatico |
| retenedor-vehiculos-hvr303.php | producto (probable por estructura) | detalle-estatico |
| retenedores-de-vehiculos-automatico.php | producto (probable) | detalle-estatico |
| retenedores-de-vehiculos.php | categoría / landing SEO (probable) | listado-estatico |
| rolli-zip.php | producto (probable) | detalle-estatico |
| rytec.php | marca | listado-marca |
| sector-puertas-rapidas-enrollables-de-sectores-intercambiables-y-contrapesos.php | producto (probable por estructura) | detalle-estatico |
| sector-reset.php | producto (probable por estructura) | detalle-estatico |
| sellos-de-anden.php | producto (probable) | detalle-estatico |
| sellos-nacionales.php | producto (probable) | detalle-estatico |
| sellos-para-anden-shelter-de-soporte-flexible.php | producto (probable por estructura) | detalle-estatico |
| sellos-para-anden.php | categoría / landing SEO (probable) | listado-estatico |
| semaforos-senalizaciones-para-anden.php | categoría / landing SEO (probable) | listado-estatico |
| serie-620.php | producto (probable por estructura) | detalle-estatico |
| serie-x130-motor-para-cortina-oculto.php | producto (probable) | detalle-estatico |
| sg-onixx-600.php | producto (probable por estructura) | detalle-estatico |
| shelter-abrigo-estacionario-blue-giant.php | producto (probable) | detalle-estatico |
| smart-puertas-rapidas-enrollables-de-sectores-intercambiables-con-motor-externo.php | producto (probable por estructura) | detalle-estatico |
| soluciones.php | página pública / soluciones | soluciones |
| sprint-automatizaciones-para-puertas-peatonales-pequenas-dimensiones.php | producto (probable por estructura) | detalle-estatico |
| star1000-receptor-comercial.php | producto (probable por estructura) | detalle-estatico |
| star4-vehicle-restraint.php | producto (probable) | detalle-estatico |
| tiras-plasticas-hawaianas.php | producto (probable por estructura) | detalle-estatico |
| tiras-plasticas.php | categoría / landing SEO (probable) | listado-estatico |
| traffic.php | producto (probable por estructura) | detalle-estatico |
| upper-up8e-barreras-automaticas-de-hasta-8-m.php | producto (probable por estructura) | detalle-estatico |
| vertigo.php | producto (probable) | detalle-estatico |
| wayne-dalton.php | marca | listado-marca |
| wel-aotmatizacion-de-puertas-peatonales-pequena-y-robusta.php | producto (probable por estructura) | detalle-estatico |
| xel-5-selector-de-llave.php | producto (probable por estructura) | detalle-estatico |

## Anexos de evidencia

- dependency-inventory.csv: includes y expresiones de rutas; líneas de fuente con comentarios sustituidos por saltos, no ejecución.
- class-references.csv: referencias de clase y archivos candidatos por declaración; no alcanza invocaciones dinámicas ni prueba reachability.
- duplicate-files.csv: grupos idénticos SHA-256.
- template-fingerprints.csv: huellas estructurales del frontend raíz.
- media-reconciliation.csv: resolución física de cada file; originales y derivados separados.
- php84-lint.csv: sintaxis PHP sin ejecutar aplicación.
