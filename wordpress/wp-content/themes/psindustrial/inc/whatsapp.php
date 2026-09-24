<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;

/**
 * Global floating WhatsApp button (legacy/public/footer.php's own .whatsapp element, present
 * on every legacy page through the shared footer include). A single wp_footer hook, not a
 * template part copied into every template, so it reaches every page type the same way
 * legacy's shared footer include did -- verified every theme template routes through
 * get_footer() (front-page.php, page-*.php, single-psi_producto.php, archive-psi_producto.php/
 * taxonomy-psi_categoria.php/taxonomy-psi_marca.php via template-parts/catalog.php,
 * archive.php, page.php, index.php, 404.php).
 *
 * The only data source is Settings::whatsapp_url(), which already encapsulates both the
 * enabled+valid-number gate and the safe URL construction (digit-only normalization,
 * rawurlencode() for the message) -- never a second copy of that logic here, never a
 * hardcoded number or message.
 */
add_action( 'wp_footer', static function(): void {
	$url = \PSIndustrial\Core\Settings::whatsapp_url();
	if ( ! $url ) { return; }
	?>
	<a class="psi-whatsapp-button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Contactar por WhatsApp', 'psindustrial' ); ?>">
		<svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true" focusable="false"><path fill="#fff" d="M12.01 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.38 5.07L2 22l5.07-1.35A9.94 9.94 0 0 0 12.01 22C17.53 22 22 17.52 22 12S17.53 2 12.01 2Zm0 18.17c-1.62 0-3.13-.44-4.43-1.2l-.32-.19-3.13.83.84-3.05-.2-.32A8.15 8.15 0 0 1 3.84 12c0-4.51 3.67-8.17 8.17-8.17S20.18 7.49 20.18 12s-3.67 8.17-8.17 8.17Zm4.47-6.12c-.24-.12-1.44-.71-1.67-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.53.06-.24-.12-1.03-.38-1.96-1.21-.72-.65-1.21-1.44-1.35-1.68-.14-.24-.02-.37.11-.5.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.32-.75-1.81-.2-.48-.4-.41-.55-.42-.14-.01-.3-.01-.46-.01a.9.9 0 0 0-.65.3c-.22.24-.86.84-.86 2.04s.88 2.37 1 2.53c.12.16 1.73 2.65 4.2 3.71.59.25 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.44-.59 1.64-1.16.2-.57.2-1.05.14-1.16-.06-.11-.22-.17-.46-.29Z"/></svg>
	</a>
	<?php
} );
