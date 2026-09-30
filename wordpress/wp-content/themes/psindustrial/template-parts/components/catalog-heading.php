<?php
defined( 'ABSPATH' ) || exit;
$title = $args['title'] ?? '';
$eyebrow = $args['eyebrow'] ?? __( 'Soluciones', 'psindustrial' );
?>
<header class="catalog-heading"><div class="psi-container">
	<div class="catalog-heading-inner">
		<?php if ( $eyebrow ) : ?><p class="catalog-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
		<h1><?php echo esc_html( $title ); ?></h1>
	</div>
</div></header>
