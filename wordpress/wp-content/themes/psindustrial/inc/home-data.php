<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;
// Verified historical Home presentation, not taxonomy or migration data.
function home_data(): array {
	return array(
		// Title, parent/child relationships and "Ver todos" destination all come from the
		// real psi_categoria hierarchy now (home_category_sections()) -- legacy_id (1-7,
		// CategoriesMigration's own MENU_ORDER roots) is the only thing needed to find which
		// term. What's left here is presentation the taxonomy has no opinion on: which theme
		// image, and the reverse/narrow/pattern layout flags -- verified historical Home
		// composition, never category data.
		'sections' => array(
			array( 'legacy_id' => 1, 'image' => 'industrial-home.jpg', 'reverse' => false, 'narrow' => false, 'pattern' => false ),
			array( 'legacy_id' => 2, 'image' => 'home-comercial.jpg', 'reverse' => true, 'narrow' => false, 'pattern' => true ),
			array( 'legacy_id' => 3, 'image' => 'equipos-accesorios-anden-carga.jpeg', 'reverse' => false, 'narrow' => false, 'pattern' => false ),
			array( 'legacy_id' => 4, 'image' => 'salida-emergencia.png', 'reverse' => true, 'narrow' => true, 'pattern' => true ),
			array( 'legacy_id' => 5, 'image' => 'PuertasIncendios.jpeg', 'reverse' => false, 'narrow' => false, 'pattern' => false ),
			array( 'legacy_id' => 6, 'image' => 'puerta-metalica-hospital.jpg', 'reverse' => true, 'narrow' => true, 'pattern' => true ),
			array( 'legacy_id' => 7, 'image' => 'residencial-2.png', 'reverse' => false, 'narrow' => false, 'pattern' => true ),
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
