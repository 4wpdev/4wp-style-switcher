( function () {
	'use strict';

	var c = window.forwpSsVisitorStorageConfig;
	if ( ! c || ! window.localStorage ) {
		return;
	}

	try {
		var key = c.storageKey || c.cookieName;
		var name = c.cookieName || key;
		var days = parseInt( c.storageDays, 10 ) || 365;
		var expiresAt = Date.now() + days * 86400000;
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		var match = document.cookie.match(
			new RegExp( '(?:^|; )' + name.replace( /([.$?*|{}()[\]\\/+^])/g, '\\$1' ) + '=([^;]*)' )
		);
		var cookieSlug = match ? decodeURIComponent( match[1] ) : '';
		var raw = localStorage.getItem( key );
		var lsData = raw ? JSON.parse( raw ) : null;
		var lsSlug = '';

		if ( lsData && lsData.slug ) {
			if ( ! lsData.expires || Date.now() <= lsData.expires ) {
				lsSlug = lsData.slug;
			} else {
				localStorage.removeItem( key );
			}
		}

		if ( cookieSlug ) {
			if ( cookieSlug !== lsSlug ) {
				localStorage.setItem(
					key,
					JSON.stringify( {
						slug: cookieSlug,
						expires: expiresAt,
					} )
				);
			}
			return;
		}

		if ( lsSlug ) {
			document.cookie =
				name + '=' + encodeURIComponent( lsSlug ) + '; path=/; SameSite=Lax' + secure;
		}
	} catch ( e ) {
		// Ignore storage errors; cookie/query flow still works.
	}
} )();
