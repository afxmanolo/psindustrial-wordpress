<?php
defined( 'ABSPATH' ) || exit;
$videos = get_post_meta( get_the_ID(), '_psi_videos', true );
foreach ( is_array( $videos ) ? $videos : array() as $video ) :
	if ( 'youtube' !== ( $video['provider'] ?? '' ) || ! preg_match( '/^[A-Za-z0-9_-]{11}$/D', $video['video_id'] ?? '' ) ) { continue; }
	$title = ( $video['title'] ?? '' ) ?: __( 'Video del producto', 'psindustrial' );
	?>
	<section class="product-video"><h2><?php echo esc_html( $title ); ?></h2>
		<iframe src="<?php echo esc_url( 'https://www.youtube-nocookie.com/embed/' . $video['video_id'] ); ?>" title="<?php echo esc_attr( $title ); ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
	</section>
<?php endforeach; ?>
