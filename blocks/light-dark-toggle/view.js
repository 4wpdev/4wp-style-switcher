( function () {
	'use strict';

	function getStorage() {
		return window.forwpSsVisitorStorage;
	}

	function readToggleConfig( root ) {
		return {
			storageKey: root.getAttribute( 'data-storage-key' ),
			cookieName: root.getAttribute( 'data-cookie-name' ),
			storageDays: root.getAttribute( 'data-storage-days' ),
			activeSlug: root.getAttribute( 'data-active-slug' ) || '',
		};
	}

	function handleToggleClick( event ) {
		var button = event.target.closest( '.forwp-ss-menu-toggle__btn' );
		if ( ! button ) {
			return;
		}

		var root = button.closest( '[data-forwp-ss-menu-toggle]' );
		if ( ! root ) {
			return;
		}

		var storage = getStorage();
		if ( ! storage ) {
			return;
		}

		var slug = button.getAttribute( 'data-slug' );
		if ( ! slug ) {
			return;
		}

		var cfg = readToggleConfig( root );
		if ( slug === cfg.activeSlug ) {
			event.preventDefault();
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		storage.applyPreference(
			{
				storageKey: cfg.storageKey,
				cookieName: cfg.cookieName,
				storageDays: cfg.storageDays,
			},
			slug
		);
	}

	if ( ! window.forwpSsMenuToggleBound ) {
		window.forwpSsMenuToggleBound = true;
		document.addEventListener( 'click', handleToggleClick, true );
	}
} )();
