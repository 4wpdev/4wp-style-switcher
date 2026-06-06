<?php
/**
 * Light / Dark menu toggle block markup.
 *
 * @package ForWP\StyleSwitcher
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block content.
 * @var WP_Block             $block      Block instance.
 */

use ForWP\StyleSwitcher\Admin\Settings;
use ForWP\StyleSwitcher\Frontend\Visitor_Storage;
use ForWP\StyleSwitcher\Style_Resolver;

defined( 'ABSPATH' ) || exit;

$forwp_ss_config = Settings::instance()->get_light_dark_config();

if ( null === $forwp_ss_config ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		echo '<div class="forwp-ss-menu-toggle forwp-ss-menu-toggle--placeholder wp-block-forwp-style-switcher-light-dark-toggle">';
		esc_html_e( 'Configure Light / Dark variations under Settings → 4WP Style Switcher.', '4wp-style-switcher' );
		echo '</div>';
	}

	return;
}

$forwp_ss_resolved  = Style_Resolver::resolve();
$forwp_ss_active    = $forwp_ss_resolved['slug'];
$forwp_ss_is_locked = $forwp_ss_resolved['locked'];
$forwp_ss_storage   = Settings::instance()->get_visitor_storage_days();
$forwp_ss_light     = $forwp_ss_config['light'];
$forwp_ss_dark      = $forwp_ss_config['dark'];
$forwp_ss_group_id  = wp_unique_id( 'forwp-ss-menu-toggle-' );

$forwp_ss_is_light_active = ( $forwp_ss_active === $forwp_ss_light['slug'] );
$forwp_ss_is_dark_active  = ( $forwp_ss_active === $forwp_ss_dark['slug'] );

if ( $forwp_ss_is_locked ) {
	$forwp_ss_target_slug = '';
	if ( $forwp_ss_is_light_active ) {
		$forwp_ss_state_class = 'forwp-ss-menu-toggle--show-sun';
	} elseif ( $forwp_ss_is_dark_active ) {
		$forwp_ss_state_class = 'forwp-ss-menu-toggle--show-moon';
	} else {
		$forwp_ss_state_class = 'forwp-ss-menu-toggle--neutral forwp-ss-menu-toggle--show-moon';
	}
	$forwp_ss_aria_label = __( 'Style switching is disabled on this page', '4wp-style-switcher' );
} elseif ( $forwp_ss_is_light_active ) {
	$forwp_ss_target_slug  = $forwp_ss_dark['slug'];
	$forwp_ss_state_class  = 'forwp-ss-menu-toggle--show-moon';
	$forwp_ss_aria_label   = sprintf(
		/* translators: %s: style variation title */
		__( 'Switch to dark: %s', '4wp-style-switcher' ),
		$forwp_ss_dark['title']
	);
} elseif ( $forwp_ss_is_dark_active ) {
	$forwp_ss_target_slug = $forwp_ss_light['slug'];
	$forwp_ss_state_class = 'forwp-ss-menu-toggle--show-sun';
	$forwp_ss_aria_label  = sprintf(
		/* translators: %s: style variation title */
		__( 'Switch to light: %s', '4wp-style-switcher' ),
		$forwp_ss_light['title']
	);
} else {
	$forwp_ss_target_slug = $forwp_ss_dark['slug'];
	$forwp_ss_state_class = 'forwp-ss-menu-toggle--neutral forwp-ss-menu-toggle--show-moon';
	$forwp_ss_aria_label  = sprintf(
		/* translators: %s: style variation title */
		__( 'Switch to dark: %s', '4wp-style-switcher' ),
		$forwp_ss_dark['title']
	);
}

if ( ! is_admin() ) {
	Visitor_Storage::enqueue_assets();
}

$forwp_ss_wrapper_class = trim(
	'forwp-ss-menu-toggle ' . $forwp_ss_state_class . ( $forwp_ss_is_locked ? ' forwp-ss-menu-toggle--disabled' : '' )
);

?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => $forwp_ss_wrapper_class ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-forwp-ss-menu-toggle="true"
	data-light-slug="<?php echo esc_attr( $forwp_ss_light['slug'] ); ?>"
	data-dark-slug="<?php echo esc_attr( $forwp_ss_dark['slug'] ); ?>"
	data-active-slug="<?php echo esc_attr( $forwp_ss_active ); ?>"
	data-storage-key="<?php echo esc_attr( Style_Resolver::VISITOR_COOKIE ); ?>"
	data-cookie-name="<?php echo esc_attr( Style_Resolver::VISITOR_COOKIE ); ?>"
	data-storage-days="<?php echo esc_attr( (string) $forwp_ss_storage ); ?>"
	<?php if ( $forwp_ss_is_locked ) : ?>
	data-forwp-ss-menu-toggle-disabled="true"
	<?php endif; ?>
>
	<button
		type="button"
		class="forwp-ss-menu-toggle__btn"
		<?php if ( ! $forwp_ss_is_locked ) : ?>
		data-slug="<?php echo esc_attr( $forwp_ss_target_slug ); ?>"
		<?php endif; ?>
		aria-label="<?php echo esc_attr( $forwp_ss_aria_label ); ?>"
		id="<?php echo esc_attr( $forwp_ss_group_id . '-toggle' ); ?>"
		<?php if ( $forwp_ss_is_locked ) : ?>
		disabled
		aria-disabled="true"
		<?php endif; ?>
	>
		<span class="forwp-ss-menu-toggle__icon forwp-ss-menu-toggle__icon--sun" aria-hidden="true">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" focusable="false">
				<circle cx="12" cy="12" r="4" fill="currentColor"/>
				<path d="M12 3v2M12 19v2M5.05 5.05l1.41 1.41M17.54 17.54l1.41 1.41M3 12h2M19 12h2M5.05 18.95l1.41-1.41M17.54 6.46l1.41-1.41" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
			</svg>
		</span>
		<span class="forwp-ss-menu-toggle__icon forwp-ss-menu-toggle__icon--moon" aria-hidden="true">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" focusable="false">
				<path d="M12 2.5a9.5 9.5 0 1 0 9.5 9.5A6.5 6.5 0 0 1 12 2.5Z" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round"/>
			</svg>
		</span>
	</button>
</div>
