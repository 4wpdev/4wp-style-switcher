( function () {
	'use strict';

	function getCfg( override ) {
		return Object.assign( {}, window.forwpSsVisitorStorageConfig || {}, override || {} );
	}

	function storageKey( cfg ) {
		return cfg.storageKey || cfg.cookieName || 'forwp_ss_style';
	}

	function cookieName( cfg ) {
		return cfg.cookieName || cfg.storageKey || 'forwp_ss_style';
	}

	function storageDays( cfg ) {
		var days = parseInt( cfg.storageDays, 10 );
		return isNaN( days ) || days < 1 ? 365 : days;
	}

	function setCookie( name, value, days ) {
		var expires = '';
		if ( days ) {
			var date = new Date();
			date.setTime( date.getTime() + days * 24 * 60 * 60 * 1000 );
			expires = '; expires=' + date.toUTCString();
		}
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = name + '=' + encodeURIComponent( value ) + expires + '; path=/; SameSite=Lax' + secure;
	}

	function readFromLocalStorage( cfg ) {
		if ( ! window.localStorage ) {
			return '';
		}

		try {
			var raw = localStorage.getItem( storageKey( cfg ) );
			if ( ! raw ) {
				return '';
			}

			var data = JSON.parse( raw );
			if ( ! data || ! data.slug ) {
				return '';
			}

			if ( data.expires && Date.now() > data.expires ) {
				localStorage.removeItem( storageKey( cfg ) );
				return '';
			}

			return data.slug;
		} catch ( e ) {
			return '';
		}
	}

	function savePreference( cfg, slug ) {
		cfg = getCfg( cfg );
		var days = storageDays( cfg );
		var key = storageKey( cfg );
		var name = cookieName( cfg );

		if ( window.localStorage ) {
			try {
				localStorage.setItem(
					key,
					JSON.stringify( {
						slug: slug,
						expires: Date.now() + days * 24 * 60 * 60 * 1000,
					} )
				);
			} catch ( e ) {
				// Ignore quota errors; cookie still works for this session.
			}
		}

		setCookie( name, slug, days );
	}

	function applyPreference( cfg, slug ) {
		cfg = getCfg( cfg );
		savePreference( cfg, slug );

		var name = cookieName( cfg );
		try {
			var url = new URL( window.location.href );
			url.searchParams.set( name, slug );
			window.location.assign( url.toString() );
		} catch ( e ) {
			window.location.href =
				window.location.pathname +
				'?' +
				encodeURIComponent( name ) +
				'=' +
				encodeURIComponent( slug );
		}
	}

	function cleanStyleQueryFromUrl( cfg ) {
		cfg = getCfg( cfg );
		var name = cookieName( cfg );

		try {
			var url = new URL( window.location.href );
			if ( ! url.searchParams.has( name ) ) {
				return;
			}

			url.searchParams.delete( name );
			var clean = url.pathname + ( url.searchParams.toString() ? '?' + url.searchParams.toString() : '' ) + url.hash;
			window.history.replaceState( null, '', clean );
		} catch ( e ) {
			// Ignore URL API errors.
		}
	}

	function syncFromLocalStorage( cfg ) {
		cfg = getCfg( cfg );
		var slug = readFromLocalStorage( cfg );
		if ( ! slug ) {
			return '';
		}

		setCookie( cookieName( cfg ), slug, storageDays( cfg ) );
		return slug;
	}

	window.forwpSsVisitorStorage = {
		getCfg: getCfg,
		readFromLocalStorage: readFromLocalStorage,
		savePreference: savePreference,
		applyPreference: applyPreference,
		syncFromLocalStorage: syncFromLocalStorage,
		cleanStyleQueryFromUrl: cleanStyleQueryFromUrl,
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			cleanStyleQueryFromUrl();
		} );
	} else {
		cleanStyleQueryFromUrl();
	}
} )();
