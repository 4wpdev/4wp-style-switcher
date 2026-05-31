( function () {
	'use strict';

	var cfg = window.forwpStyleSwitcher || {};
	var root = document.querySelector( '[data-forwp-ss-switcher]' );
	var storage = window.forwpSsVisitorStorage;

	if ( ! root || ! cfg.variations || ! cfg.variations.length || ! storage ) {
		return;
	}

	function applyVariation( slug ) {
		storage.applyPreference( cfg.storage || cfg, slug );
	}

	function buildSelectSwitcher( inner ) {
		var label = document.createElement( 'span' );
		label.className = 'forwp-ss-switcher__label';
		label.textContent = ( cfg.strings && cfg.strings.label ) || 'Site style';

		var select = document.createElement( 'select' );
		select.className = 'forwp-ss-switcher__select';
		select.setAttribute( 'aria-label', label.textContent );

		cfg.variations.forEach( function ( item ) {
			var opt = document.createElement( 'option' );
			opt.value = item.slug;
			opt.textContent = item.title;
			if ( item.slug === cfg.active ) {
				opt.selected = true;
			}
			select.appendChild( opt );
		} );

		select.addEventListener( 'change', function () {
			applyVariation( select.value );
		} );

		inner.appendChild( label );
		inner.appendChild( select );
	}

	var inner = document.createElement( 'div' );
	inner.className = 'forwp-ss-switcher__inner';
	buildSelectSwitcher( inner );
	root.appendChild( inner );
	root.hidden = false;
} )();
