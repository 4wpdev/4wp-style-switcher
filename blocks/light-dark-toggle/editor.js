( function ( wp ) {
	'use strict';

	var blocks = wp.blocks;
	var blockEditor = wp.blockEditor;
	var element = wp.element;
	var serverSideRender = wp.serverSideRender;
	var i18n = wp.i18n;

	if ( ! blocks || ! blockEditor || ! element ) {
		return;
	}

	var registerBlockType = blocks.registerBlockType;
	var useBlockProps = blockEditor.useBlockProps;
	var createElement = element.createElement;
	var __ = i18n.__;

	registerBlockType( 'forwp-style-switcher/light-dark-toggle', {
		edit: function Edit() {
			var blockProps = useBlockProps( {
				className: 'forwp-ss-menu-toggle forwp-ss-menu-toggle--editor',
			} );

			if ( serverSideRender ) {
				return createElement(
					'div',
					blockProps,
					createElement( serverSideRender, {
						block: 'forwp-style-switcher/light-dark-toggle',
					} )
				);
			}

			return createElement(
				'div',
				blockProps,
				createElement(
					'span',
					{ className: 'forwp-ss-menu-toggle__btn forwp-ss-menu-toggle--show-moon' },
					createElement( 'span', { className: 'forwp-ss-menu-toggle__icon forwp-ss-menu-toggle__icon--moon', 'aria-hidden': true }, '☽' )
				),
				createElement(
					'p',
					{ className: 'forwp-ss-menu-toggle__editor-note' },
					__( 'Light / Dark toggle (moon on light, sun on dark)', '4wp-style-switcher' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
