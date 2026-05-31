<?php
/**
 * Editor script dependencies for the Light / Dark block.
 *
 * @package ForWP\StyleSwitcher
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-i18n',
		'wp-server-side-render',
	),
	'version'      => FORWP_STYLE_SWITCHER_VERSION,
);
