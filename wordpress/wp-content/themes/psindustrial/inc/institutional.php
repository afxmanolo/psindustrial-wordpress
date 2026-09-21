<?php
namespace PSIndustrial\Theme;
defined( 'ABSPATH' ) || exit;
function is_institutional(): bool { return is_page( array( 'nosotros', 'contacto', 'politica-privacidad' ) ); }
function institutional_privacy_url(): string {
 return get_privacy_policy_url() ?: published_page_url( 'politica-privacidad' );
}
