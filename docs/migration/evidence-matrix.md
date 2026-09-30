# Matriz de evidencia

Las rutas PHP/SQL de esta matriz son relativas a legacy/public. Los hechos SQL se conservan aunque la relación canónica sea contradictoria. Referencias detalladas por registro: source_of_relationship en product-master.csv; enlaces/lineas propios en page-source-evidence.json; propiedad de medios en media-usage-evidence.csv.

| Conclusión | Evidencia | Nivel / límite |
|---|---|---|
| No hay relación explícita PHP ↔ productos_id | productos sólo almacena datos/IDs; fichas estáticas tienen texto propio | STRONG_INFERENCE para cada correspondencia, nunca FK inventada |
| 136 registros tienen una ficha principal documentalmente inequívoca | product-master unambiguous_php=YES; nombre + texto y/o hashes en product-page-candidates | No son136 entidades únicas; no demuestra existencia HTTP |
| 12 registros tienen aliases y2 nombres problemáticos | alias_candidate_files; IDs3/139 | REVIEW: no escoger URL canónica automáticamente |
| 15 registros no tienen ficha candidata | IDs151–165; SQL y maestros | 14 vacíos y1 Prueba; no excluir sin decisión |
| 54 categoría0 recuperan al menos un contexto | Tabla inferior y enlaces de grids | STRONG_INFERENCE;14 tienen varios contextos |
| SQL puede contradecir navegación | ID3 categoria7; rampa-niveladora.php enlaza ficha Kelley | Categoría original explícita, relación canónica WEAK_INFERENCE |
| ModernSteel no tiene marca única demostrada | productos6/16/20/107; overhead-door.php,wayne-dalton.php,clopay.php; cuerpo menciona Clopay | Contradicción; no resolver por mayoría |
| Título no basta para atribuir marca | lift-master-mod-h.php título BlueGiant frente SQL11, lift-master.php y PDF Lift-Master; blue-giant.php enlaza MOOVI | Conflictos documentados, sin fabricante definitivo |
| Logos no salen de marcas.imagen | Las12 columnas imagen vacías; marcas.php enlaza logos; iconos-marcasv1.php:2 array posicional | CONFIRMED relación logo-enlace estática; orden dinámico puede variar |
| PDF asociados no equivalen a registros ni nombres |159 IDs fichas,72 rutas originales;76 PDF estáticos localizados referenciados; SHA256 |148 rutas asociadas/referenciadas,74 contenidos binarios únicos; no validación técnica del PDF |
| Videos no están en SQL | barreras-estacionamiento-moovi50rm.php:70, icaro-smart.php:43, p45-7-piston-hidraulico-para-puerta-abatible.php:42 | CONFIRMED tres iframe; disponibles online UNKNOWN |
| Un mismo archivo tiene varios IDs | media-reconciliation.csv y media-master.file_ids |1355 registros→820 rutas; conservar puente de IDs |
| Landings no deben convertirse automáticamente en productos | Tabla SEO inferior; textos de gama/compuestos y ausencia de ID propio | STRONG_INFERENCE función editorial; intención SEO/tráfico no demostrados |
| Rewrites no son redirects | .htaccess:2–6 usan QSA,L; sin R | CONFIRMED cinco patrones, no cinco URLs concretas |

## Categorías ausentes en SQL

| Producto ID | Categoría(s) candidata(s) | Confianza | Ficha y evidencia |
|---|---|---|---|
| 1 | 37 / 38 / 39 Operadores para puerta corrediza / Operadores para puerta abatible / Operadores para puertas ascendentes | STRONG_INFERENCE | accesspro-fs1000speed.php; operadores-para-puertas-corredizas-residenciales.php:33 -> accesspro-fs1000speed.php [category 37]; operadores-para-puertas-corredizas-residenciales.php:38 -> accesspro-fs1000speed.php [category 37]; operadores-para-puertas-corredizas-residenciales.php:42 -> accesspro-fs1000speed.php [category 37]; operadores-para-puerta-abatible.php:33 -> accesspro-fs1000speed.php [category 38]; operadores-para-puerta-abatible.php:38 -> accesspro-fs1000speed.php [category 38]; operadores-para-puerta-abatible.php:42 -> accesspro-fs1000speed.php [category 38]; operadores-para-puertas-ascendentes.php:33 -> accesspro-fs1000speed.php [category 39]; operadores-para-puertas-ascendentes.php:38 -> accesspro-fs1000speed.php [category 39]; operadores-para-puertas-ascendentes.php:42 -> accesspro-fs1000speed.php [category 39] |
| 4 | 14 Cortinas enrollables | STRONG_INFERENCE | cortina-serie-625.php; cortinas-enrollables-industriales.php:52 -> cortina-serie-625.php [category 14]; cortinas-enrollables-industriales.php:57 -> cortina-serie-625.php [category 14]; cortinas-enrollables-industriales.php:61 -> cortina-serie-625.php [category 14] |
| 5 | 14 Cortinas enrollables | STRONG_INFERENCE | cortina-en-aluminio-serie-511-521.php; cortinas-enrollables-industriales.php:70 -> cortina-en-aluminio-serie-511-521.php [category 14]; cortinas-enrollables-industriales.php:75 -> cortina-en-aluminio-serie-511-521.php [category 14]; cortinas-enrollables-industriales.php:79 -> cortina-en-aluminio-serie-511-521.php [category 14] |
| 6 | 36 Puertas residenciales | STRONG_INFERENCE | coleccion-modern-steel.php; puertasresidenciales.php:33 -> coleccion-modern-steel.php [category 36]; puertasresidenciales.php:38 -> coleccion-modern-steel.php [category 36]; puertasresidenciales.php:42 -> coleccion-modern-steel.php [category 36] |
| 7 | 14 Cortinas enrollables | STRONG_INFERENCE | cortina-serie-620.php; cortinas-enrollables-industriales.php:124 -> cortina-serie-620.php [category 14]; cortinas-enrollables-industriales.php:129 -> cortina-serie-620.php [category 14]; cortinas-enrollables-industriales.php:133 -> cortina-serie-620.php [category 14] |
| 8 | 8 Puertas Seccionales | STRONG_INFERENCE | puerta-seccional-de-acero-thermacore-serie-592.php; puertas-seccionales-industriales.php:145 -> puerta-seccional-de-acero-thermacore-serie-592.php [category 8]; puertas-seccionales-industriales.php:150 -> puerta-seccional-de-acero-thermacore-serie-592.php [category 8]; puertas-seccionales-industriales.php:154 -> puerta-seccional-de-acero-thermacore-serie-592.php [category 8] |
| 9 | 36 Puertas residenciales | STRONG_INFERENCE | coleccion-classic.php; puertasresidenciales.php:51 -> coleccion-classic.php [category 36]; puertasresidenciales.php:56 -> coleccion-classic.php [category 36]; puertasresidenciales.php:60 -> coleccion-classic.php [category 36] |
| 10 | 14 / 19 Cortinas enrollables / Centros comerciales y hoteles | STRONG_INFERENCE | cortina-serie-600.php; cortinas-enrollables-industriales.php:160 -> cortina-serie-600.php [category 14]; cortinas-enrollables-industriales.php:165 -> cortina-serie-600.php [category 14]; cortinas-enrollables-industriales.php:169 -> cortina-serie-600.php [category 14]; centros-comerciales.php:107 -> cortina-serie-600.php [category 19]; centros-comerciales.php:112 -> cortina-serie-600.php [category 19]; centros-comerciales.php:116 -> cortina-serie-600.php [category 19] |
| 11 | 14 Cortinas enrollables | STRONG_INFERENCE | cortina-serie-630.php; cortinas-enrollables-industriales.php:178 -> cortina-serie-630.php [category 14]; cortinas-enrollables-industriales.php:183 -> cortina-serie-630.php [category 14]; cortinas-enrollables-industriales.php:187 -> cortina-serie-630.php [category 14] |
| 12 | 13 Operadores para puertas seccionales y cortinas enrollables | STRONG_INFERENCE | operador-comercial-rhx.php; operadores-puertas-seccionales-cortinas.php:51 -> operador-comercial-rhx.php [category 13]; operadores-puertas-seccionales-cortinas.php:56 -> operador-comercial-rhx.php [category 13]; operadores-puertas-seccionales-cortinas.php:60 -> operador-comercial-rhx.php [category 13] |
| 13 | 14 Cortinas enrollables | STRONG_INFERENCE | fire-rated-doors-firestar-models-700-700c.php; cortinas-enrollables-industriales.php:286 -> fire-rated-doors-firestar-models-700-700c.php [category 14]; cortinas-enrollables-industriales.php:291 -> fire-rated-doors-firestar-models-700-700c.php [category 14]; cortinas-enrollables-industriales.php:295 -> fire-rated-doors-firestar-models-700-700c.php [category 14] |
| 14 | 8 Puertas Seccionales | STRONG_INFERENCE | puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php; puertas-seccionales-industriales.php:55 -> puertas-seccionales-de-acero-aisladas-thermospan.php [category 8]; puertas-seccionales-industriales.php:60 -> puertas-seccionales-de-acero-aisladas-thermospan.php [category 8]; puertas-seccionales-industriales.php:64 -> puertas-seccionales-de-acero-aisladas-thermospan.php [category 8] |
| 15 | 8 Puertas Seccionales | STRONG_INFERENCE | puertas-seccionales-de-acero-modelo-2415.php; puertas-seccionales-industriales.php:109 -> puertas-seccionales-de-acero-modelo-2415.php [category 8]; puertas-seccionales-industriales.php:114 -> puertas-seccionales-de-acero-modelo-2415.php [category 8]; puertas-seccionales-industriales.php:118 -> puertas-seccionales-de-acero-modelo-2415.php [category 8] |
| 16 | 36 Puertas residenciales | STRONG_INFERENCE | coleccion-modern-steel.php; puertasresidenciales.php:33 -> coleccion-modern-steel.php [category 36]; puertasresidenciales.php:38 -> coleccion-modern-steel.php [category 36]; puertasresidenciales.php:42 -> coleccion-modern-steel.php [category 36] |
| 17 | 8 Puertas Seccionales | STRONG_INFERENCE | energy-series-with-intellicore-37171-3-4.php; puertas-seccionales-industriales.php:37 -> energy-series-with-intellicore-37171-3-4.php [category 8]; puertas-seccionales-industriales.php:42 -> energy-series-with-intellicore-37171-3-4.php [category 8]; puertas-seccionales-industriales.php:46 -> energy-series-with-intellicore-37171-3-4.php [category 8] |
| 18 | 8 Puertas Seccionales | STRONG_INFERENCE | puerta-de-acero-acanalado-modelos-524.php; puertas-seccionales-industriales.php:73 -> puerta-de-acero-acanalado-modelos-524.php [category 8]; puertas-seccionales-industriales.php:78 -> puerta-de-acero-acanalado-modelos-524.php [category 8]; puertas-seccionales-industriales.php:82 -> puerta-de-acero-acanalado-modelos-524.php [category 8] |
| 19 | 8 Puertas Seccionales | STRONG_INFERENCE | energy-series-with-intellicore-3720-3724-3722.php; puertas-seccionales-industriales.php:127 -> energy-series-with-intellicore-3720-3724-3722.php [category 8]; puertas-seccionales-industriales.php:132 -> energy-series-with-intellicore-3720-3724-3722.php [category 8]; puertas-seccionales-industriales.php:136 -> energy-series-with-intellicore-3720-3724-3722.php [category 8] |
| 20 | 36 Puertas residenciales | STRONG_INFERENCE | coleccion-modern-steel.php; puertasresidenciales.php:33 -> coleccion-modern-steel.php [category 36]; puertasresidenciales.php:38 -> coleccion-modern-steel.php [category 36]; puertasresidenciales.php:42 -> coleccion-modern-steel.php [category 36] |
| 21 | 23 Sellos para Anden | STRONG_INFERENCE | shelter-abrigo-estacionario-blue-giant.php; sellos-para-anden.php:33 -> shelter-abrigo-estacionario-blue-giant.php [category 23]; sellos-para-anden.php:38 -> shelter-abrigo-estacionario-blue-giant.php [category 23]; sellos-para-anden.php:42 -> shelter-abrigo-estacionario-blue-giant.php [category 23] |
| 22 | 22 Retenedores de vehiculos | STRONG_INFERENCE | retenedor-de-vehiculos.php; retenedores-de-vehiculos.php:33 -> retenedor-de-vehiculos.php [category 22]; retenedores-de-vehiculos.php:38 -> retenedor-de-vehiculos.php [category 22]; retenedores-de-vehiculos.php:42 -> retenedor-de-vehiculos.php [category 22] |
| 23 | 10 Rampa Niveladora | STRONG_INFERENCE | rampa-niveladora-mecanica-blue-giant.php; rampa-niveladora.php:51 -> rampa-niveladora-mecanica-blue-giant.php [category 10]; rampa-niveladora.php:56 -> rampa-niveladora-mecanica-blue-giant.php [category 10]; rampa-niveladora.php:60 -> rampa-niveladora-mecanica-blue-giant.php [category 10] |
| 24 | 10 Rampa Niveladora | STRONG_INFERENCE | rampas-de-anden-hidraulicas-kelley.php; rampa-niveladora.php:33 -> rampas-de-anden-hidraulicas-kelley.php [category 10]; rampa-niveladora.php:38 -> rampas-de-anden-hidraulicas-kelley.php [category 10]; rampa-niveladora.php:42 -> rampas-de-anden-hidraulicas-kelley.php [category 10] |
| 25 | 22 Retenedores de vehiculos | STRONG_INFERENCE | star4-vehicle-restraint.php; retenedores-de-vehiculos.php:51 -> star4-vehicle-restraint.php [category 22]; retenedores-de-vehiculos.php:56 -> star4-vehicle-restraint.php [category 22]; retenedores-de-vehiculos.php:60 -> star4-vehicle-restraint.php [category 22] |
| 26 | 31 Puerta contra explosion | STRONG_INFERENCE | puerta-contra-explosion.php; puerta-contra-incendio.php:33 -> puerta-contra-explosion.php [category 5]; puerta-contra-incendio.php:38 -> puerta-contra-explosion.php [category 5]; puerta-contra-incendio.php:42 -> puerta-contra-explosion.php [category 5]; contra-exposion.php:33 -> puerta-contra-explosion.php [category 31]; contra-exposion.php:38 -> puerta-contra-explosion.php [category 31]; contra-exposion.php:42 -> puerta-contra-explosion.php [category 31] |
| 27 | 20 Accesorios y dispositivos de control de acceso y seguridad | STRONG_INFERENCE | accesorios.php; receptor-para-control-vehiculos.php:91 -> accesorios.php [category 20]; receptor-para-control-vehiculos.php:96 -> accesorios.php [category 20]; receptor-para-control-vehiculos.php:100 -> accesorios.php [category 20] |
| 28 | 19 / 32 Centros comerciales y hoteles / Puertas blindadas | STRONG_INFERENCE | puertas-blindadas.php; puerta-contra-incendio.php:51 -> puertas-blindadas.php [category 5]; puerta-contra-incendio.php:56 -> puertas-blindadas.php [category 5]; puerta-contra-incendio.php:60 -> puertas-blindadas.php [category 5]; centros-comerciales.php:143 -> puertas-blindadas.php [category 19]; centros-comerciales.php:148 -> puertas-blindadas.php [category 19]; centros-comerciales.php:152 -> puertas-blindadas.php [category 19]; blindadas.php:33 -> puertas-blindadas.php [category 32]; blindadas.php:38 -> puertas-blindadas.php [category 32]; blindadas.php:42 -> puertas-blindadas.php [category 32] |
| 29 | 19 / 33 Centros comerciales y hoteles / Puertas contra incendio | STRONG_INFERENCE | puertas-contra-incendio.php; puertas-salida-emergencia.php:33 -> puertas-contra-incendio.php [category 4]; puertas-salida-emergencia.php:38 -> puertas-contra-incendio.php [category 4]; puertas-salida-emergencia.php:42 -> puertas-contra-incendio.php [category 4]; puerta-contra-incendio.php:69 -> puertas-contra-incendio.php [category 5]; puerta-contra-incendio.php:74 -> puertas-contra-incendio.php [category 5]; puerta-contra-incendio.php:78 -> puertas-contra-incendio.php [category 5]; centros-comerciales.php:161 -> puertas-contra-incendio.php [category 19]; centros-comerciales.php:166 -> puertas-contra-incendio.php [category 19]; centros-comerciales.php:170 -> puertas-contra-incendio.php [category 19]; contra-incendio.php:33 -> puertas-contra-incendio.php [category 33]; contra-incendio.php:38 -> puertas-contra-incendio.php [category 33]; contra-incendio.php:42 -> puertas-contra-incendio.php [category 33] |
| 30 | 34 Puertas contra rayos x | STRONG_INFERENCE | puertas-contra-rayos-x.php; puertas-peatonales-hospitales.php:33 -> puertas-contra-rayos-x.php [category 6]; puertas-peatonales-hospitales.php:38 -> puertas-contra-rayos-x.php [category 6]; puertas-peatonales-hospitales.php:42 -> puertas-contra-rayos-x.php [category 6]; puertas-peatonales-rayos-x.php:33 -> puertas-contra-rayos-x.php [category 34]; puertas-peatonales-rayos-x.php:38 -> puertas-contra-rayos-x.php [category 34]; puertas-peatonales-rayos-x.php:42 -> puertas-contra-rayos-x.php [category 34] |
| 31 | 19 / 27 Centros comerciales y hoteles / Puerta estándar | STRONG_INFERENCE | puerta-estandar.php; puertas-salida-emergencia.php:51 -> puerta-estandar.php [category 4]; puertas-salida-emergencia.php:56 -> puerta-estandar.php [category 4]; puertas-salida-emergencia.php:60 -> puerta-estandar.php [category 4]; centros-comerciales.php:179 -> puerta-estandar.php [category 19]; centros-comerciales.php:184 -> puerta-estandar.php [category 19]; centros-comerciales.php:188 -> puerta-estandar.php [category 19]; puertas-peatonales-estandar-y-reforzada.php:33 -> puerta-estandar.php [category 27]; puertas-peatonales-estandar-y-reforzada.php:38 -> puerta-estandar.php [category 27]; puertas-peatonales-estandar-y-reforzada.php:42 -> puerta-estandar.php [category 27]; puertas-peatonales-estandar-y-reforzada.php:33 -> puerta-estandar.php [category 28]; puertas-peatonales-estandar-y-reforzada.php:38 -> puerta-estandar.php [category 28]; puertas-peatonales-estandar-y-reforzada.php:42 -> puerta-estandar.php [category 28] |
| 32 | 19 / 28 Centros comerciales y hoteles / Puerta estándar reforzada | STRONG_INFERENCE | puerta-estandar-reforzada.php; puertas-salida-emergencia.php:69 -> puerta-estandar-reforzada.php [category 4]; puertas-salida-emergencia.php:74 -> puerta-estandar-reforzada.php [category 4]; puertas-salida-emergencia.php:78 -> puerta-estandar-reforzada.php [category 4]; centros-comerciales.php:197 -> puerta-estandar-reforzada.php [category 19]; centros-comerciales.php:202 -> puerta-estandar-reforzada.php [category 19]; centros-comerciales.php:206 -> puerta-estandar-reforzada.php [category 19]; puertas-peatonales-estandar-y-reforzada.php:51 -> puerta-estandar-reforzada.php [category 27]; puertas-peatonales-estandar-y-reforzada.php:56 -> puerta-estandar-reforzada.php [category 27]; puertas-peatonales-estandar-y-reforzada.php:60 -> puerta-estandar-reforzada.php [category 27]; puertas-peatonales-estandar-y-reforzada.php:51 -> puerta-estandar-reforzada.php [category 28]; puertas-peatonales-estandar-y-reforzada.php:56 -> puerta-estandar-reforzada.php [category 28]; puertas-peatonales-estandar-y-reforzada.php:60 -> puerta-estandar-reforzada.php [category 28] |
| 33 | 19 / 29 Centros comerciales y hoteles / Puerta Holandesa | STRONG_INFERENCE | puerta-holandesa.php; puertas-salida-emergencia.php:87 -> puerta-holandesa.php [category 4]; puertas-salida-emergencia.php:92 -> puerta-holandesa.php [category 4]; puertas-salida-emergencia.php:96 -> puerta-holandesa.php [category 4]; centros-comerciales.php:215 -> puerta-holandesa.php [category 19]; centros-comerciales.php:220 -> puerta-holandesa.php [category 19]; centros-comerciales.php:224 -> puerta-holandesa.php [category 19]; puertas-peatonales-tipo-holandesa.php:33 -> puerta-holandesa.php [category 29]; puertas-peatonales-tipo-holandesa.php:38 -> puerta-holandesa.php [category 29]; puertas-peatonales-tipo-holandesa.php:42 -> puerta-holandesa.php [category 29]; puertas-y-fijos-louver.php:51 -> puerta-holandesa.php [category 30]; puertas-y-fijos-louver.php:56 -> puerta-holandesa.php [category 30]; puertas-y-fijos-louver.php:60 -> puerta-holandesa.php [category 30] |
| 34 | 35 Puerta para hospital | STRONG_INFERENCE | puerta-para-hospital.php; categoria sin listado PHP independiente; puertas-peatonales-hospitales.php:51 -> puerta-para-hospital.php [category 6]; puertas-peatonales-hospitales.php:56 -> puerta-para-hospital.php [category 6]; puertas-peatonales-hospitales.php:60 -> puerta-para-hospital.php [category 6] |
| 35 | 19 / 30 Centros comerciales y hoteles / Puerta y fijos Louver | STRONG_INFERENCE | puerta-y-fijos-louver.php; puertas-salida-emergencia.php:105 -> puerta-y-fijos-louver.php [category 4]; puertas-salida-emergencia.php:110 -> puerta-y-fijos-louver.php [category 4]; puertas-salida-emergencia.php:114 -> puerta-y-fijos-louver.php [category 4]; centros-comerciales.php:233 -> puerta-y-fijos-louver.php [category 19]; centros-comerciales.php:238 -> puerta-y-fijos-louver.php [category 19]; centros-comerciales.php:242 -> puerta-y-fijos-louver.php [category 19]; puertas-y-fijos-louver.php:33 -> puerta-y-fijos-louver.php [category 30]; puertas-y-fijos-louver.php:38 -> puerta-y-fijos-louver.php [category 30]; puertas-y-fijos-louver.php:42 -> puerta-y-fijos-louver.php [category 30] |
| 36 | 17 Puertas rápidas | STRONG_INFERENCE | fast-seal-high-performance-door.php; puertas-rapidas.php:29 -> fast-seal-high-performance-door.php [category 17]; puertas-rapidas.php:34 -> fast-seal-high-performance-door.php [category 17]; puertas-rapidas.php:38 -> fast-seal-high-performance-door.php [category 17] |
| 37 | 17 Puertas rápidas | STRONG_INFERENCE | predadoor-nxt-door.php; puertas-rapidas.php:47 -> predadoor-nxt-door.php [category 17]; puertas-rapidas.php:52 -> predadoor-nxt-door.php [category 17]; puertas-rapidas.php:56 -> predadoor-nxt-door.php [category 17] |
| 38 | 17 Puertas rápidas | STRONG_INFERENCE | rapida-enrollable-industrial.php; puertas-rapidas.php:65 -> rapida-enrollable-industrial.php [category 17]; puertas-rapidas.php:70 -> rapida-enrollable-industrial.php [category 17]; puertas-rapidas.php:74 -> rapida-enrollable-industrial.php [category 17] |
| 39 | 17 Puertas rápidas | STRONG_INFERENCE | rapida-apilable.php; puertas-rapidas.php:83 -> rapida-apilable.php [category 17]; puertas-rapidas.php:88 -> rapida-apilable.php [category 17]; puertas-rapidas.php:92 -> rapida-apilable.php [category 17] |
| 40 | 17 Puertas rápidas | STRONG_INFERENCE | rolli-zip.php; puertas-rapidas.php:123 -> rolli-zip.php [category 17]; puertas-rapidas.php:128 -> rolli-zip.php [category 17]; puertas-rapidas.php:132 -> rolli-zip.php [category 17] |
| 41 | 17 Puertas rápidas | STRONG_INFERENCE | kronos-puertas-rapidas-de-sectores-modulares-autorreparable.php; puertas-rapidas.php:159 -> kronos-puertas-rapidas-de-sectores-modulares-autorreparable.php [category 17]; puertas-rapidas.php:164 -> kronos-puertas-rapidas-de-sectores-modulares-autorreparable.php [category 17]; puertas-rapidas.php:168 -> kronos-puertas-rapidas-de-sectores-modulares-autorreparable.php [category 17] |
| 42 | 14 Cortinas enrollables | STRONG_INFERENCE | vertigo.php; cortinas-enrollables-industriales.php:304 -> vertigo.php [category 14]; cortinas-enrollables-industriales.php:309 -> vertigo.php [category 14]; cortinas-enrollables-industriales.php:313 -> vertigo.php [category 14] |
| 43 | 13 Operadores para puertas seccionales y cortinas enrollables | STRONG_INFERENCE | lift-master-mod-h.php; operadores-puertas-seccionales-cortinas.php:33 -> lift-master-mod-h.php [category 13]; operadores-puertas-seccionales-cortinas.php:38 -> lift-master-mod-h.php [category 13]; operadores-puertas-seccionales-cortinas.php:42 -> lift-master-mod-h.php [category 13] |
| 44 | 9 / 18 / 19 Fraccionamientos y Condominios / Estacionamientos / Centros comerciales y hoteles | STRONG_INFERENCE | barreras-estacionamiento-moovi50rm.php; fraccionamientos-y-condominios.php:107 -> barreras-estacionamiento-moovi50rm.php [category 9]; fraccionamientos-y-condominios.php:112 -> barreras-estacionamiento-moovi50rm.php [category 9]; fraccionamientos-y-condominios.php:116 -> barreras-estacionamiento-moovi50rm.php [category 9]; estacionamientos.php:107 -> barreras-estacionamiento-moovi50rm.php [category 18]; estacionamientos.php:112 -> barreras-estacionamiento-moovi50rm.php [category 18]; estacionamientos.php:116 -> barreras-estacionamiento-moovi50rm.php [category 18]; centros-comerciales.php:323 -> barreras-estacionamiento-moovi50rm.php [category 19]; centros-comerciales.php:328 -> barreras-estacionamiento-moovi50rm.php [category 19]; centros-comerciales.php:332 -> barreras-estacionamiento-moovi50rm.php [category 19] |
| 45 | 13 Operadores para puertas seccionales y cortinas enrollables | STRONG_INFERENCE | serie-x130-motor-para-cortina-oculto.php; operadores-puertas-seccionales-cortinas.php:87 -> serie-x130-motor-para-cortina-oculto.php [category 13]; operadores-puertas-seccionales-cortinas.php:92 -> serie-x130-motor-para-cortina-oculto.php [category 13]; operadores-puertas-seccionales-cortinas.php:96 -> serie-x130-motor-para-cortina-oculto.php [category 13] |
| 46 | 38 Operadores para puerta abatible | STRONG_INFERENCE | phobos-n-piston-electromecanico.php; operadores-para-puerta-abatible.php:51 -> phobos-n-piston-electromecanico.php [category 38]; operadores-para-puerta-abatible.php:56 -> phobos-n-piston-electromecanico.php [category 38]; operadores-para-puerta-abatible.php:60 -> phobos-n-piston-electromecanico.php [category 38] |
| 47 | 12 / 38 Operadores para puerta abatible / Operadores para puerta abatible | STRONG_INFERENCE | p45-7-piston-hidraulico-para-puerta-abatible.php; operadores-puerta-abatible-industrial.php:123 -> p45-7-piston-hidraulico-para-puerta-abatible.php [category 12]; operadores-puerta-abatible-industrial.php:128 -> p45-7-piston-hidraulico-para-puerta-abatible.php [category 12]; operadores-puerta-abatible-industrial.php:132 -> p45-7-piston-hidraulico-para-puerta-abatible.php [category 12]; operadores-para-puerta-abatible.php:87 -> p45-7-piston-hidraulico-para-puerta-abatible.php [category 38]; operadores-para-puerta-abatible.php:92 -> p45-7-piston-hidraulico-para-puerta-abatible.php [category 38]; operadores-para-puerta-abatible.php:96 -> p45-7-piston-hidraulico-para-puerta-abatible.php [category 38] |
| 48 | 12 / 38 Operadores para puerta abatible / Operadores para puerta abatible | STRONG_INFERENCE | lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php; operadores-puerta-abatible-industrial.php:105 -> lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php [category 12]; operadores-puerta-abatible-industrial.php:110 -> lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php [category 12]; operadores-puerta-abatible-industrial.php:114 -> lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php [category 12]; operadores-para-puerta-abatible.php:69 -> lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php [category 38]; operadores-para-puerta-abatible.php:74 -> lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php [category 38]; operadores-para-puerta-abatible.php:78 -> lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php [category 38] |
| 49 | 9 / 11 / 18 Fraccionamientos y Condominios / Operadores para puerta corrediza / Estacionamientos | STRONG_INFERENCE | icaro-smart.php; fraccionamientos-y-condominios.php:125 -> icaro-smart.php [category 9]; fraccionamientos-y-condominios.php:130 -> icaro-smart.php [category 9]; fraccionamientos-y-condominios.php:134 -> icaro-smart.php [category 9]; operadores-para-puerta-corrediza-industrial.php:69 -> icaro-smart.php [category 11]; operadores-para-puerta-corrediza-industrial.php:74 -> icaro-smart.php [category 11]; operadores-para-puerta-corrediza-industrial.php:78 -> icaro-smart.php [category 11]; estacionamientos.php:125 -> icaro-smart.php [category 18]; estacionamientos.php:130 -> icaro-smart.php [category 18]; estacionamientos.php:134 -> icaro-smart.php [category 18] |
| 50 | 8 Puertas Seccionales | STRONG_INFERENCE | puertas-industriales-dockman.php; puertas-seccionales-industriales.php:91 -> puertas-industriales-dockman.php [category 8]; puertas-seccionales-industriales.php:96 -> puertas-industriales-dockman.php [category 8]; puertas-seccionales-industriales.php:100 -> puertas-industriales-dockman.php [category 8] |
| 51 | 21 Labio de elevación para anden de carga | STRONG_INFERENCE | labio-de-elevacion-mecanico-dockman.php; labio-elevacion-anden-carga.php:33 -> labio-de-elevacion-mecanico-dockman.php [category 21]; labio-elevacion-anden-carga.php:38 -> labio-de-elevacion-mecanico-dockman.php [category 21]; labio-elevacion-anden-carga.php:42 -> labio-de-elevacion-mecanico-dockman.php [category 21] |
| 52 | 23 Sellos para Anden | STRONG_INFERENCE | sellos-de-anden.php; sellos-para-anden.php:70 -> sellos-de-anden.php [category 23]; sellos-para-anden.php:75 -> sellos-de-anden.php [category 23]; sellos-para-anden.php:79 -> sellos-de-anden.php [category 23] |
| 53 | 10 Rampa Niveladora | STRONG_INFERENCE | rampas-de-anden-hidraulicas.php; rampa-niveladora.php:69 -> rampas-de-anden-hidraulicas.php [category 10]; rampa-niveladora.php:74 -> rampas-de-anden-hidraulicas.php [category 10]; rampa-niveladora.php:78 -> rampas-de-anden-hidraulicas.php [category 10] |
| 54 | 14 / 19 Cortinas enrollables / Centros comerciales y hoteles | STRONG_INFERENCE | cortina-plana.php; cortinas-enrollables-industriales.php:88 -> cortina-plana.php [category 14]; cortinas-enrollables-industriales.php:93 -> cortina-plana.php [category 14]; cortinas-enrollables-industriales.php:97 -> cortina-plana.php [category 14]; centros-comerciales.php:341 -> cortina-plana.php [category 19]; centros-comerciales.php:346 -> cortina-plana.php [category 19]; centros-comerciales.php:350 -> cortina-plana.php [category 19] |
| 55 | 14 / 19 Cortinas enrollables / Centros comerciales y hoteles | STRONG_INFERENCE | cortina-europea.php; cortinas-enrollables-industriales.php:106 -> cortina-europea.php [category 14]; cortinas-enrollables-industriales.php:111 -> cortina-europea.php [category 14]; cortinas-enrollables-industriales.php:115 -> cortina-europea.php [category 14]; centros-comerciales.php:359 -> cortina-europea.php [category 19]; centros-comerciales.php:364 -> cortina-europea.php [category 19]; centros-comerciales.php:368 -> cortina-europea.php [category 19] |
| 56 | 10 Rampa Niveladora | STRONG_INFERENCE | rampas-de-anden-mecanicas.php; rampa-niveladora.php:87 -> rampas-de-anden-mecanicas.php [category 10]; rampa-niveladora.php:92 -> rampas-de-anden-mecanicas.php [category 10]; rampa-niveladora.php:96 -> rampas-de-anden-mecanicas.php [category 10] |

## Marcas ausentes en SQL

Se combinaron ficha reconciliada, páginas de marca, título, texto propio y registros pares. El texto de HVR303 confirma Blue Giant; el texto de Thermospan introduce Clopay y Sellos Nacionales introduce Solmmer, generando conflictos adicionales. Son atribuciones del sitio, no certificaciones del fabricante.

| Producto ID | Marca candidata / alternativas | Confianza | Evidencia auditada |
|---|---|---|---|
| 2 | Clopay | STRONG_INFERENCE | energy-series-with-intellicore-37171-3-4.php; pares SQL 2 / 17;  |
| 3 | Kelley | STRONG_INFERENCE | rampas-de-anden-hidraulicas-kelley.php; pares SQL 3 / 24 / 139; CONFLICT: SQL categoria 7 Residenciales; pagina/medios Kelley y grid rampa-niveladora apuntan categoria 10; no corregido; Correspondencia no inequivoca: nombre no coincide exactamente |
| 57 | 2 / 3 | WEAK_INFERENCE | puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php; pares SQL 14 / 57; CONFLICT: marcas candidatas 2,3; asignacion SQL no equivale a fabricante verificado; Multiples PHP relacionados; alias_candidate_files no implica redirect aprobado |
| 58 | Clopay | STRONG_INFERENCE | puerta-de-acero-acanalado-modelos-524.php; pares SQL 18 / 58; Multiples PHP relacionados; alias_candidate_files no implica redirect aprobado |
| 59 | Dockman | STRONG_INFERENCE | puertas-industriales-dockman.php; pares SQL 50 / 59;  |
| 60 | Wayne Dalton | STRONG_INFERENCE | puertas-seccionales-de-acero-modelo-2415.php; pares SQL 15 / 60;  |
| 61 | Clopay | STRONG_INFERENCE | energy-series-with-intellicore-3720-3724-3722.php; pares SQL 19 / 61;  |
| 62 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-thermacore-serie-592.php; pares SQL 8 / 62;  |
| 63 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-sin-aislamiento-416-uso-muy-pesado.php; pares SQL 63;  |
| 64 | Overhead Door | STRONG_INFERENCE | puertas-seccionales-de-acero-sin-aislamiento-420-uso-pesado.php; pares SQL 64;  |
| 65 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-sin-aislamiento-424-uso-pesado.php; pares SQL 65;  |
| 66 | Overhead Door | STRONG_INFERENCE | puert-seccional-de-acero-sin-aislamiento-430-uso-medio.php; pares SQL 66;  |
| 67 | Overhead Door | STRONG_INFERENCE | puertas-seccionales-de-aluminio-521.php; pares SQL 67;  |
| 68 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-perfiles-418.php; pares SQL 68;  |
| 69 | Overhead Door | STRONG_INFERENCE | puerta-seccionabl-acero-perfiles-422-uso-pesado.php; pares SQL 69;  |
| 70 | Overhead Door | STRONG_INFERENCE | puertas-seccionales-de-acero-perfiles-426.php; pares SQL 70;  |
| 71 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-perfiles-432-uso-medio.php; pares SQL 71;  |
| 72 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-thermacore-593-uso-medio.php; pares SQL 72;  |
| 73 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-thermacore-594-uso-medio.php; pares SQL 73;  |
| 74 | Overhead Door | STRONG_INFERENCE | puertas-seccionales-de-acero-thermacore-598-uso-liviano.php; pares SQL 74;  |
| 75 | Overhead Door | STRONG_INFERENCE | puerta-seccional-de-acero-thermacore-599-uso-muy-pesado.php; pares SQL 75;  |
| 76 | BFT | STRONG_INFERENCE | icaro-smart.php; pares SQL 49 / 76 / 124 / 126;  |
| 77 | Overhead Door | STRONG_INFERENCE | operador-para-perfilados-comerciales-jst.php; pares SQL 77;  |
| 78 | Overhead Door | STRONG_INFERENCE | operador-para-perfilados-comerciales-sel.php; pares SQL 78 / 84;  |
| 79 | BFT | STRONG_INFERENCE | lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php; pares SQL 48 / 79 / 112;  |
| 80 | BFT | STRONG_INFERENCE | p45-7-piston-hidraulico-para-puerta-abatible.php; pares SQL 47 / 80 / 113;  |
| 81 | 4 / 11 | WEAK_INFERENCE | lift-master-mod-h.php; pares SQL 43 / 81; CONFLICT: marcas candidatas 4,11; asignacion SQL no equivale a fabricante verificado; Multiples PHP relacionados; alias_candidate_files no implica redirect aprobado |
| 82 | Overhead Door | STRONG_INFERENCE | operador-comercial-rhx.php; pares SQL 12 / 82;  |
| 83 | BFT | STRONG_INFERENCE | serie-x130-motor-para-cortina-oculto.php; pares SQL 45 / 83;  |
| 84 | Overhead Door | STRONG_INFERENCE | operador-para-perfilados-comerciales-sel.php; pares SQL 78 / 84;  |
| 85 | Overhead Door | STRONG_INFERENCE | operadores-para-puertas-de-mostrador-cdo.php; pares SQL 85;  |
| 86 | Overhead Door | STRONG_INFERENCE | operador-para-cortina-enrrollable-rsx.php; pares SQL 86;  |
| 87 | Overhead Door | STRONG_INFERENCE | cortina-serie-625.php; pares SQL 4 / 87;  |
| 88 | Overhead Door | STRONG_INFERENCE | cortina-en-aluminio-serie-511-521.php; pares SQL 5 / 88;  |
| 89 | Dockman | STRONG_INFERENCE | cortina-plana.php; pares SQL 54 / 89 / 136;  |
| 90 | Dockman | STRONG_INFERENCE | cortina-europea.php; pares SQL 55 / 90 / 137;  |
| 91 | Overhead Door | STRONG_INFERENCE | cortina-serie-620.php; pares SQL 7 / 91; Multiples PHP relacionados; alias_candidate_files no implica redirect aprobado |
| 92 | Overhead Door | STRONG_INFERENCE | cortina-serie-790-cw.php; pares SQL 92;  |
| 93 | Overhead Door | STRONG_INFERENCE | cortina-serie-600.php; pares SQL 10 / 93 / 127;  |
| 94 | Overhead Door | STRONG_INFERENCE | cortina-serie-630.php; pares SQL 11 / 94;  |
| 95 | Overhead Door | STRONG_INFERENCE | cortina-serie-610.php; pares SQL 95;  |
| 96 | Overhead Door | STRONG_INFERENCE | cortinas-ventiladas-685.php; pares SQL 96 / 128;  |
| 97 | Overhead Door | STRONG_INFERENCE | cortina-de-seguridad-670.php; pares SQL 97;  |
| 98 | Overhead Door | STRONG_INFERENCE | cortina-de-seguridad-671.php; pares SQL 98;  |
| 99 | Wayne Dalton | STRONG_INFERENCE | fire-rated-doors-firestar-models-700-700c.php; pares SQL 13 / 99;  |
| 100 | GLG Porter Industriali | STRONG_INFERENCE | vertigo.php; pares SQL 42 / 100;  |
| 101 | Rytec | STRONG_INFERENCE | fast-seal-high-performance-door.php; pares SQL 36 / 101;  |
| 102 | Rytec | STRONG_INFERENCE | predadoor-nxt-door.php; pares SQL 37 / 102;  |
| 103 | Infraca Quality Doors | STRONG_INFERENCE | rapida-enrollable-industrial.php; pares SQL 38 / 103;  |
| 104 | Infraca Quality Doors | STRONG_INFERENCE | rapida-apilable.php; pares SQL 39 / 104;  |
| 105 | GLG Porter Industriali | STRONG_INFERENCE | rolli-zip.php; pares SQL 40 / 105;  |
| 106 | GLG Porter Industriali | STRONG_INFERENCE | kronos-puertas-rapidas-de-sectores-modulares-autorreparable.php; pares SQL 41 / 106;  |
| 107 | 1 / 2 / 3 | WEAK_INFERENCE | coleccion-modern-steel.php; pares SQL 6 / 16 / 20 / 107; CONFLICT: marcas candidatas 1,2,3; asignacion SQL no equivale a fabricante verificado |
| 108 | Overhead Door | STRONG_INFERENCE | coleccion-classic.php; pares SQL 9 / 108;  |
| 109 | Overhead Door | STRONG_INFERENCE | accesspro-fs1000speed.php; pares SQL 1 / 109 / 110 / 114; Multiples PHP relacionados; alias_candidate_files no implica redirect aprobado |
| 110 | Overhead Door | STRONG_INFERENCE | accesspro-fs1000speed.php; pares SQL 1 / 109 / 110 / 114; Multiples PHP relacionados; alias_candidate_files no implica redirect aprobado |
| 111 | BFT | STRONG_INFERENCE | phobos-n-piston-electromecanico.php; pares SQL 46 / 111;  |
| 112 | BFT | STRONG_INFERENCE | lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php; pares SQL 48 / 79 / 112;  |
| 113 | BFT | STRONG_INFERENCE | p45-7-piston-hidraulico-para-puerta-abatible.php; pares SQL 47 / 80 / 113;  |
| 114 | Overhead Door | STRONG_INFERENCE | accesspro-fs1000speed.php; pares SQL 1 / 109 / 110 / 114; Multiples PHP relacionados; alias_candidate_files no implica redirect aprobado |
| 115 | Doorlock | STRONG_INFERENCE | puertas-contra-rayos-x.php; pares SQL 30 / 115;  |
| 116 | Doorlock | STRONG_INFERENCE | puertas-contra-incendio.php; pares SQL 29 / 116 / 130;  |
| 117 | Doorlock | STRONG_INFERENCE | puerta-contra-explosion.php; pares SQL 26 / 117;  |
| 118 | Doorlock | STRONG_INFERENCE | puertas-blindadas.php; pares SQL 28 / 118 / 129;  |
| 119 | Doorlock | STRONG_INFERENCE | puerta-estandar.php; pares SQL 31 / 119 / 131;  |
| 120 | Doorlock | STRONG_INFERENCE | puerta-estandar-reforzada.php; pares SQL 32 / 120 / 132;  |
| 121 | Doorlock | STRONG_INFERENCE | puerta-y-fijos-louver.php; pares SQL 35 / 121 / 134;  |
| 122 | Doorlock | STRONG_INFERENCE | puerta-holandesa.php; pares SQL 33 / 122 / 133; Tambien enlazado desde grid Louver; no prueba que sea producto Louver |
| 123 | 4 / 12 | WEAK_INFERENCE | barreras-estacionamiento-moovi50rm.php; pares SQL 44 / 123 / 125 / 135; CONFLICT: marcas candidatas 4,12; asignacion SQL no equivale a fabricante verificado |
| 124 | BFT | STRONG_INFERENCE | icaro-smart.php; pares SQL 49 / 76 / 124 / 126;  |
| 125 | 4 / 12 | WEAK_INFERENCE | barreras-estacionamiento-moovi50rm.php; pares SQL 44 / 123 / 125 / 135; CONFLICT: marcas candidatas 4,12; asignacion SQL no equivale a fabricante verificado |
| 126 | BFT | STRONG_INFERENCE | icaro-smart.php; pares SQL 49 / 76 / 124 / 126;  |
| 127 | Overhead Door | STRONG_INFERENCE | cortina-serie-600.php; pares SQL 10 / 93 / 127;  |
| 128 | Overhead Door | STRONG_INFERENCE | cortinas-ventiladas-685.php; pares SQL 96 / 128;  |
| 129 | Doorlock | STRONG_INFERENCE | puertas-blindadas.php; pares SQL 28 / 118 / 129;  |
| 130 | Doorlock | STRONG_INFERENCE | puertas-contra-incendio.php; pares SQL 29 / 116 / 130;  |
| 131 | Doorlock | STRONG_INFERENCE | puerta-estandar.php; pares SQL 31 / 119 / 131;  |
| 132 | Doorlock | STRONG_INFERENCE | puerta-estandar-reforzada.php; pares SQL 32 / 120 / 132;  |
| 133 | Doorlock | STRONG_INFERENCE | puerta-holandesa.php; pares SQL 33 / 122 / 133; Tambien enlazado desde grid Louver; no prueba que sea producto Louver |
| 134 | Doorlock | STRONG_INFERENCE | puerta-y-fijos-louver.php; pares SQL 35 / 121 / 134;  |
| 135 | 4 / 12 | WEAK_INFERENCE | barreras-estacionamiento-moovi50rm.php; pares SQL 44 / 123 / 125 / 135; CONFLICT: marcas candidatas 4,12; asignacion SQL no equivale a fabricante verificado |
| 136 | Dockman | STRONG_INFERENCE | cortina-plana.php; pares SQL 54 / 89 / 136;  |
| 137 | Dockman | STRONG_INFERENCE | cortina-europea.php; pares SQL 55 / 90 / 137;  |
| 138 | Doorlock | STRONG_INFERENCE | accesorios.php; pares SQL 27 / 138;  |
| 139 | 5 | WEAK_INFERENCE | rampas-de-anden-hidraulicas-kelley.php; pares SQL 3 / 24 / 139; Nombre compuesto incluye Kelley y Blue Giant; equivalencia principal por hash+descripcion, identidad pendiente; Correspondencia no inequivoca: nombre no coincide exactamente |
| 140 | Blue Giant | STRONG_INFERENCE | rampa-niveladora-mecanica-blue-giant.php; pares SQL 23 / 140;  |
| 141 | Dockman | STRONG_INFERENCE | rampas-de-anden-hidraulicas.php; pares SQL 53 / 141;  |
| 142 | Dockman | STRONG_INFERENCE | rampas-de-anden-mecanicas.php; pares SQL 56 / 142;  |
| 143 | Dockman | STRONG_INFERENCE | labio-de-elevacion-mecanico-dockman.php; pares SQL 51 / 143;  |
| 144 | Blue Giant | STRONG_INFERENCE | retenedor-de-vehiculos.php; pares SQL 22 / 144;  |
| 145 | Kelley | STRONG_INFERENCE | star4-vehicle-restraint.php; pares SQL 25 / 145;  |
| 146 | Blue Giant | STRONG_INFERENCE | retenedor-vehiculos-hvr303.php; pares SQL 146; Texto propio atribuye explicitamente HVR303 StrongArm a Blue Giant; retenedor-vehiculos-hvr303.php:29,34 |
| 147 | Blue Giant | STRONG_INFERENCE | shelter-abrigo-estacionario-blue-giant.php; pares SQL 21 / 147;  |
| 148 | Dockman | STRONG_INFERENCE | sellos-para-anden-shelter-de-soporte-flexible.php; pares SQL 148;  |
| 149 | Dockman | STRONG_INFERENCE | sellos-de-anden.php; pares SQL 52 / 149;  |
| 150 | 10 | WEAK_INFERENCE | sellos-nacionales.php; pares SQL 150; CONFLICT: title Dockman pero texto propio y PDF dicen Solmmer, marca no presente en tabla marcas |
| 151 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 152 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 153 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 154 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 155 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 156 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 157 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 158 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 159 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 160 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 161 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 162 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 163 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |
| 164 | UNKNOWN | UNKNOWN | ; pares SQL ; Registro sin nombre ni descripcion; categoria25; requiere decision editorial |

## Páginas editoriales/SEO candidatas

| Archivo | Encabezados propios | Evidencia / límite |
|---|---|---|
| cortinas-enrollables-de-aluminio.php | Cortinas enrollables de aluminio / Persianas Térmicas | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| operadores-puerta-abatible.php | Operadores para puertas abatibles / AccessPRO FS1000SPEED / Q- Silent Max Eagle 3/4 Hp | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| operadores-puerta-corrediza-residencial.php | Operadores para puertas corredizas residenciales / AccessPRO FS1000SPEED / Q- Silent Max Eagle 3/4 Hp / Ditec NeoS y NeoS+ | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| operadores-puertas-ascendentes.php | Operadores para puertas automáticas ascendentes / AccessPRO FS1000SPEED / Q- Silent Max Eagle 3/4 Hp / DOD – Automatización compacto y potente para portones seccionales, correderos y enrollables | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| puertas-de-garaje-aisladas.php | Puertas de garaje aisladas / Construcción de 3 capas, 2 capas y 1 capa | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| puertas-enrollables-de-garage.php | Puertas enrollables de garage | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| puertas-rapidas-enrollables.php | Puertas rápidas enrollables / Puerta de enrollamiento rápido con contrapesos | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| puertas-residenciales.php | Puertas Residenciales / Colección Modern Steel™ / PUERTAS ENROLLABLES DE GARAJE / Colección Classic™ – Serie Premium | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |
| tiras-plasticas-hawaianas.php | Tiras Plasticas (Hawaianas) / Puertas de tiras plasticas flexibles industriales | Texto propio de gama/compuesto; conservar URL; no exige entidad SQL nueva |

## Jerarquía y diferencias

38 filas SQL:7 raíces NULL,1 raíz0 (26),30 hijas. Se halló correspondencia visual candidata para36;25 y26 no tienen una página única. La correspondencia no es una biyección:27/28 comparten un grid;35 se representa mediante una ficha;33 comparte nombre con26.

Las páginas raíz industrial/comercial/equipos/residencial enlazan subgrids. Los enlaces transversales no cambian padre_id: centros comerciales muestra puertas de salida/emergencia e incendio; salida de emergencia enlaza incendio; el grid Louver también enlaza Holandesa. Se conservan como contextos de navegación y no como nuevas relaciones padre-hijo.

Los H1 de cortinas-enrollables-industriales.php y puertas-rapidas.php dicen Residenciales; el encabezado de operadores-puerta-abatible-industrial.php dice corredizas. Frente a estas copias, la ubicación en el grid padre aporta evidencia más consistente, pero no autoriza corregir contenido en esta fase.

| ID | Nombre | Padre SQL | Página candidata | Evidencia enlace desde padre |
|---|---|---|---|---|
| 1 | Industrial |  | industrial.php | Sin enlace padre exacto; ver notas del maestro |
| 2 | Comercial |  | comercial.php | Sin enlace padre exacto; ver notas del maestro |
| 3 | Equipos y Accesorios para Anden de Carga |  | equipos-y-accesorios-para-anden-de-carga.php | Sin enlace padre exacto; ver notas del maestro |
| 4 | Puertas peatonales de Salida de Emergencia |  | puertas-salida-emergencia.php | Sin enlace padre exacto; ver notas del maestro |
| 5 | Puertas peatonales contra Incendio, Contra ExplosiÃ³n y Blindadas |  | puerta-contra-incendio.php | Sin enlace padre exacto; ver notas del maestro |
| 6 | Puertas peatonales para Hospitales |  | puertas-peatonales-hospitales.php | Sin enlace padre exacto; ver notas del maestro |
| 7 | Residenciales |  | residencial.php | Sin enlace padre exacto; ver notas del maestro |
| 8 | Puertas Seccionales | 1 | puertas-seccionales-industriales.php | industrial.php:33 / industrial.php:38 / industrial.php:42 |
| 9 | Fraccionamientos y Condominios | 2 | fraccionamientos-y-condominios.php | comercial.php:33 / comercial.php:38 / comercial.php:42 |
| 10 | Rampa Niveladora | 3 | rampa-niveladora.php | equipos-y-accesorios-para-anden-de-carga.php:37 / equipos-y-accesorios-para-anden-de-carga.php:42 / equipos-y-accesorios-para-anden-de-carga.php:46 |
| 11 | Operadores para puerta corrediza | 1 | operadores-para-puerta-corrediza-industrial.php | industrial.php:51 / industrial.php:56 / industrial.php:60 |
| 12 | Operadores para puerta abatible | 1 | operadores-puerta-abatible-industrial.php | industrial.php:69 / industrial.php:74 / industrial.php:78 |
| 13 | Operadores para puertas seccionales y cortinas enrollables | 1 | operadores-puertas-seccionales-cortinas.php | industrial.php:87 / industrial.php:92 / industrial.php:96 |
| 14 | Cortinas enrollables | 1 | cortinas-enrollables-industriales.php | industrial.php:105 / industrial.php:110 / industrial.php:114 |
| 16 | Tiras plÃ¡sticas | 1 | tiras-plasticas.php | industrial.php:141 / industrial.php:146 / industrial.php:150 |
| 17 | Puertas rÃ¡pidas | 1 | puertas-rapidas.php | industrial.php:123 / industrial.php:128 / industrial.php:132 |
| 18 | Estacionamientos | 2 | estacionamientos.php | comercial.php:51 / comercial.php:56 / comercial.php:60 |
| 19 | Centros comerciales y hoteles | 2 | centros-comerciales.php | comercial.php:69 / comercial.php:74 / comercial.php:78 |
| 20 | Accesorios y dispositivos de control de acceso y seguridad | 2 | receptor-para-control-vehiculos.php | comercial.php:87 / comercial.php:92 / comercial.php:96 |
| 21 | Labio de elevaciÃ³n para anden de carga | 3 | labio-elevacion-anden-carga.php | equipos-y-accesorios-para-anden-de-carga.php:55 / equipos-y-accesorios-para-anden-de-carga.php:60 / equipos-y-accesorios-para-anden-de-carga.php:64 |
| 22 | Retenedores de vehiculos | 3 | retenedores-de-vehiculos.php | equipos-y-accesorios-para-anden-de-carga.php:73 / equipos-y-accesorios-para-anden-de-carga.php:78 / equipos-y-accesorios-para-anden-de-carga.php:82 |
| 23 | Sellos para Anden | 3 | sellos-para-anden.php | equipos-y-accesorios-para-anden-de-carga.php:91 / equipos-y-accesorios-para-anden-de-carga.php:96 / equipos-y-accesorios-para-anden-de-carga.php:100 |
| 24 | Semaforos y seÃ±alizaciones para anden | 3 | semaforos-senalizaciones-para-anden.php | equipos-y-accesorios-para-anden-de-carga.php:110 / equipos-y-accesorios-para-anden-de-carga.php:115 / equipos-y-accesorios-para-anden-de-carga.php:119 |
| 25 | Bumpers, semaforos y cepillos para rampa niveladora | 3 | UNKNOWN | Sin enlace padre exacto; ver notas del maestro |
| 26 | Puertas contra incendio | 0 | UNKNOWN | Sin enlace padre exacto; ver notas del maestro |
| 27 | Puerta estÃ¡ndar | 4 | puertas-peatonales-estandar-y-reforzada.php | Sin enlace padre exacto; ver notas del maestro |
| 28 | Puerta estÃ¡ndar reforzada | 4 | puertas-peatonales-estandar-y-reforzada.php | Sin enlace padre exacto; ver notas del maestro |
| 29 | Puerta Holandesa | 4 | puertas-peatonales-tipo-holandesa.php | Sin enlace padre exacto; ver notas del maestro |
| 30 | Puerta y fijos Louver | 4 | puertas-y-fijos-louver.php | Sin enlace padre exacto; ver notas del maestro |
| 31 | Puerta contra explosion | 5 | contra-exposion.php | Sin enlace padre exacto; ver notas del maestro |
| 32 | Puertas blindadas | 5 | blindadas.php | Sin enlace padre exacto; ver notas del maestro |
| 33 | Puertas contra incendio | 5 | contra-incendio.php | Sin enlace padre exacto; ver notas del maestro |
| 34 | Puertas contra rayos x | 6 | puertas-peatonales-rayos-x.php | Sin enlace padre exacto; ver notas del maestro |
| 35 | Puerta para hospital | 6 | puerta-para-hospital.php | puertas-peatonales-hospitales.php:51 / puertas-peatonales-hospitales.php:56 / puertas-peatonales-hospitales.php:60 |
| 36 | Puertas residenciales | 7 | puertasresidenciales.php | residencial.php:33 / residencial.php:38 / residencial.php:42 |
| 37 | Operadores para puerta corrediza | 7 | operadores-para-puertas-corredizas-residenciales.php | residencial.php:51 / residencial.php:56 / residencial.php:60 |
| 38 | Operadores para puerta abatible | 7 | operadores-para-puerta-abatible.php | residencial.php:69 / residencial.php:74 / residencial.php:78 |
| 39 | Operadores para puertas ascendentes | 7 | operadores-para-puertas-ascendentes.php | residencial.php:87 / residencial.php:92 / residencial.php:96 |
