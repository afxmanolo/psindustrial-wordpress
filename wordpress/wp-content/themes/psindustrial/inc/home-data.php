<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;
// Verified historical Home presentation, not taxonomy or migration data.
function home_data(): array {
	return array(
		'sections' => array(
			array(
				'title' => 'Industriales',
				'slug' => 'industrial',
				'image' => 'industrial-home.jpg',
				'labels' => array(
					'Puertas Seccionales',
					'Operadores para puerta corrediza',
					'Operadores para puerta abatible',
					'Operadores para puertas seccionales y cortinas enrollables',
					'Cortinas enrollables',
					'Puertas rápidas',
					'Tiras plásticas',
				),
				'reverse' => false,
				'narrow' => false,
				'pattern' => false,
			),
			array(
				'title' => 'Comercial',
				'slug' => 'comercial',
				'image' => 'home-comercial.jpg',
				'labels' => array(
					'Fraccionamientos y Condominios',
					'Estacionamientos',
					'Centros comerciales y hoteles',
					'Accesorios y dispositivos de control de acceso y seguridad',
				),
				'reverse' => true,
				'narrow' => false,
				'pattern' => true,
			),
			array(
				'title' => 'Equipos y Accesorios para Anden de Carga',
				'slug' => 'equipos-y-accesorios-para-anden-de-carga',
				'image' => 'equipos-accesorios-anden-carga.jpeg',
				'labels' => array(
					'Rampa Niveladora',
					'Labio de elevación para anden de carga',
					'Retenedores de vehiculos',
					'Sellos para anden',
					'Bumpers, semaforos y cepillos para rampa niveladora',
				),
				'reverse' => false,
				'narrow' => false,
				'pattern' => false,
			),
			array(
				'title' => 'Puertas peatonales de Salida de Emergencia',
				'slug' => 'puertas-peatonales-de-salida-de-emergencia',
				'image' => 'salida-emergencia.png',
				'labels' => array(
					'Puertas peatonales estandar y reforzada',
					'Puertas tipo Louver y holandesa',
				),
				'reverse' => true,
				'narrow' => true,
				'pattern' => true,
			),
			array(
				'title' => 'Puertas peatonales contra Incendio, Contra Explosión y Blindadas',
				'slug' => 'puertas-peatonales-contra-incendio-contra-explosia%c2%b3n-y-blindadas',
				'image' => 'PuertasIncendios.jpeg',
				'labels' => array(
					'Contra incendio',
					'Contra explosión',
					'Blindada',
				),
				'reverse' => false,
				'narrow' => false,
				'pattern' => false,
			),
			array(
				'title' => 'Puertas peatonales para Hospitales',
				'slug' => 'puertas-peatonales-para-hospitales',
				'image' => 'puerta-metalica-hospital.jpg',
				'labels' => array(
					'Puertas peatonales rayos X',
				),
				'reverse' => true,
				'narrow' => true,
				'pattern' => true,
			),
			array(
				'title' => 'Residenciales',
				'slug' => 'residenciales',
				'image' => 'residencial-2.png',
				'labels' => array(
					'Puertas residenciales',
					'Operadores para puerta corrediza',
					'Operadores para puerta abatible',
					'Operadores para puertas ascendentes',
				),
				'reverse' => false,
				'narrow' => false,
				'pattern' => true,
			),
		),
		// 'brands' is no longer a static list here -- see home_brands() in
		// home-presentation.php, sourced live from psi_marca terms. The WebP variants
		// below stay: home_brands() still resolves each term's logo to one of these exact
		// keys (by attachment filename), so the same pre-optimized images keep rendering.
		'assets' => array(
			'banner1.jpg' => array(
				'width' => 1920,
				'height' => 1100,
				'variants' => array(
					array(
						'file' => 'assets/images/home/banner1-640.webp',
						'width' => 640,
						'height' => 367,
					),
					array(
						'file' => 'assets/images/home/banner1-1280.webp',
						'width' => 1279,
						'height' => 733,
					),
					array(
						'file' => 'assets/images/home/banner1-1920.webp',
						'width' => 1920,
						'height' => 1100,
					),
				),
			),
			'banner2.jpg' => array(
				'width' => 1920,
				'height' => 1100,
				'variants' => array(
					array(
						'file' => 'assets/images/home/banner2-640.webp',
						'width' => 640,
						'height' => 367,
					),
					array(
						'file' => 'assets/images/home/banner2-1280.webp',
						'width' => 1279,
						'height' => 733,
					),
					array(
						'file' => 'assets/images/home/banner2-1920.webp',
						'width' => 1920,
						'height' => 1100,
					),
				),
			),
			'banner3.jpeg' => array(
				'width' => 1280,
				'height' => 733,
				'variants' => array(
					array(
						'file' => 'assets/images/home/banner3-640.webp',
						'width' => 639,
						'height' => 366,
					),
					array(
						'file' => 'assets/images/home/banner3-1280.webp',
						'width' => 1280,
						'height' => 733,
					),
				),
			),
			'banner4.jpg' => array(
				'width' => 1920,
				'height' => 1100,
				'variants' => array(
					array(
						'file' => 'assets/images/home/banner4-640.webp',
						'width' => 640,
						'height' => 367,
					),
					array(
						'file' => 'assets/images/home/banner4-1280.webp',
						'width' => 1279,
						'height' => 733,
					),
					array(
						'file' => 'assets/images/home/banner4-1920.webp',
						'width' => 1920,
						'height' => 1100,
					),
				),
			),
			'home-bg-01.jpg' => array(
				'width' => 100,
				'height' => 100,
				'variants' => array(
					array(
						'file' => 'assets/images/home/home-bg-01-100.webp',
						'width' => 100,
						'height' => 100,
					),
				),
			),
			'quienes1.jpg' => array(
				'width' => 539,
				'height' => 739,
				'variants' => array(
					array(
						'file' => 'assets/images/home/quienes1-539.webp',
						'width' => 539,
						'height' => 739,
					),
				),
			),
			'industrial-home.jpg' => array(
				'width' => 670,
				'height' => 670,
				'variants' => array(
					array(
						'file' => 'assets/images/home/industrial-home-670.webp',
						'width' => 670,
						'height' => 670,
					),
				),
			),
			'home-comercial.jpg' => array(
				'width' => 600,
				'height' => 730,
				'variants' => array(
					array(
						'file' => 'assets/images/home/home-comercial-600.webp',
						'width' => 600,
						'height' => 730,
					),
				),
			),
			'equipos-accesorios-anden-carga.jpeg' => array(
				'width' => 670,
				'height' => 670,
				'variants' => array(
					array(
						'file' => 'assets/images/home/equipos-accesorios-anden-carga-670.webp',
						'width' => 670,
						'height' => 670,
					),
				),
			),
			'salida-emergencia.png' => array(
				'width' => 150,
				'height' => 287,
				'variants' => array(
					array(
						'file' => 'assets/images/home/salida-emergencia-150.webp',
						'width' => 150,
						'height' => 287,
					),
				),
			),
			'PuertasIncendios.jpeg' => array(
				'width' => 1600,
				'height' => 1000,
				'variants' => array(
					array(
						'file' => 'assets/images/home/PuertasIncendios-960.webp',
						'width' => 960,
						'height' => 600,
					),
				),
			),
			'puerta-metalica-hospital.jpg' => array(
				'width' => 196,
				'height' => 438,
				'variants' => array(
					array(
						'file' => 'assets/images/home/puerta-metalica-hospital-196.webp',
						'width' => 196,
						'height' => 438,
					),
				),
			),
			'residencial-2.png' => array(
				'width' => 600,
				'height' => 730,
				'variants' => array(
					array(
						'file' => 'assets/images/home/residencial-2-600.webp',
						'width' => 600,
						'height' => 730,
					),
				),
			),
			'tg.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/tg-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'wayne-dayton.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/wayne-dayton-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'clopay.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/clopay-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'blue.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/blue-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'kelley.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/kelley-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'door.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/door-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'rytec.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/rytec-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'infraca.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/infraca-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'glg-porte-industriali.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/glg-porte-industriali-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'dockman.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/dockman-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'lift-master.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/lift-master-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
			'bft.png' => array(
				'width' => 500,
				'height' => 240,
				'variants' => array(
					array(
						'file' => 'assets/images/home/bft-500.webp',
						'width' => 500,
						'height' => 240,
					),
				),
			),
		),
	);
}
