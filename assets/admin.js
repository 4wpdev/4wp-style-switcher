( function () {
	'use strict';

	var cfg = window.forwpStyleSwitcherAdmin || {};
	var restBase = ( cfg.restUrl || '' ).replace( /\/$/, '' );
	var state = {
		themeVariations: [],
		allowedSlugs: null,
	};

	function qs( sel, root ) {
		return ( root || document ).querySelector( sel );
	}

	function qsa( sel, root ) {
		return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) );
	}

	function setStatus( message, type ) {
		var el = qs( '#forwp-ss-settings-status' );
		if ( ! el ) {
			return;
		}
		el.textContent = message || '';
		el.className = 'forwp-ss-status forwp-ss-status--global forwp-ss-status--' + ( type || 'pending' ) + ( message ? ' forwp-ss-status--visible' : '' );
	}

	function apiFetch( path, options ) {
		options = options || {};
		var headers = Object.assign(
			{ 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
			options.headers || {}
		);
		return fetch( restBase + path, Object.assign( {}, options, { headers: headers, credentials: 'same-origin' } ) ).then( function ( res ) {
			if ( ! res.ok ) {
				throw new Error( 'HTTP ' + res.status );
			}
			return res.json();
		} );
	}

	function effectiveAllowedSlugs() {
		if ( null === state.allowedSlugs || ! state.allowedSlugs.length ) {
			return state.themeVariations.map( function ( item ) {
				return item.slug;
			} );
		}
		return state.allowedSlugs.slice();
	}

	function themeVariationsFromPayload( payload ) {
		if ( payload.theme_variations && payload.theme_variations.length ) {
			return payload.theme_variations;
		}
		if ( payload.variations && payload.variations.length ) {
			return payload.variations;
		}
		return [];
	}

	function allowedVariationsForSelect() {
		var allowed = effectiveAllowedSlugs();
		return state.themeVariations.filter( function ( item ) {
			return allowed.indexOf( item.slug ) !== -1;
		} );
	}

	function fillVariationSelect( selectId, selectedSlug, includeEmpty ) {
		var select = qs( selectId );
		if ( ! select ) {
			return;
		}

		select.innerHTML = '';
		if ( includeEmpty ) {
			var empty = document.createElement( 'option' );
			empty.value = '';
			empty.textContent = '—';
			select.appendChild( empty );
		}

		allowedVariationsForSelect().forEach( function ( item ) {
			var opt = document.createElement( 'option' );
			opt.value = item.slug;
			opt.textContent = item.title + ' (' + item.slug + ')';
			if ( item.slug === selectedSlug ) {
				opt.selected = true;
			}
			select.appendChild( opt );
		} );
	}

	function fillDefaultVariationSelect( selectedSlug ) {
		fillVariationSelect( '#forwp-ss-default-variation', selectedSlug, true );
	}

	function fillLightDarkSelects( settings ) {
		fillVariationSelect( '#forwp-ss-light-variation', settings.light_variation || '', true );
		fillVariationSelect( '#forwp-ss-dark-variation', settings.dark_variation || '', true );
	}

	function fillAbSelects( ab ) {
		ab = ab || {};
		fillVariationSelect( '#forwp-ss-ab-variation-a', ab.variation_a || '', true );
		fillVariationSelect( '#forwp-ss-ab-variation-b', ab.variation_b || '', true );
	}

	function updateAbSplitLabels( value ) {
		var split = parseInt( value, 10 );
		if ( isNaN( split ) ) {
			split = 50;
		}
		split = Math.max( 0, Math.min( 100, split ) );
		var labelA = qs( '#forwp-ss-ab-split-a-label' );
		var labelB = qs( '#forwp-ss-ab-split-b-label' );
		if ( labelA ) {
			labelA.textContent = 'A: ' + split + '%';
		}
		if ( labelB ) {
			labelB.textContent = 'B: ' + ( 100 - split ) + '%';
		}
	}

	function syncAbRows() {
		var enabled = qs( '#forwp-ss-ab-enabled' );
		var show = enabled && enabled.checked;
		qsa( '.forwp-ss-ab-row' ).forEach( function ( row ) {
			row.style.display = show ? '' : 'none';
		} );
		var stats = qs( '#forwp-ss-ab-stats' );
		if ( stats ) {
			stats.hidden = ! show;
		}
	}

	function formatSplit( value ) {
		return value == null ? '—' : value + '%';
	}

	function renderAbStats( stats, ab ) {
		var body = qs( '#forwp-ss-ab-stats-body' );
		if ( ! body ) {
			return;
		}

		stats = stats || {};
		ab = ab || {};
		var target = ab.traffic_split_a != null ? ab.traffic_split_a : 50;
		var rows = [
			{ key: 'today', label: 'Today' },
			{ key: 'last_7_days', label: 'Last 7 days' },
			{ key: 'all_time', label: 'All time' },
		];

		body.innerHTML = '';
		rows.forEach( function ( row ) {
			var data = stats[ row.key ] || {};
			var tr = document.createElement( 'tr' );
			tr.innerHTML =
				'<td>' + row.label + '</td>' +
				'<td>' + ( data.a != null ? data.a : 0 ) + '</td>' +
				'<td>' + ( data.b != null ? data.b : 0 ) + '</td>' +
				'<td>' + formatSplit( data.split_a ) + '</td>' +
				'<td>' + target + '%</td>';
			body.appendChild( tr );
		} );
	}

	function syncLightDarkRows() {
		var enabled = qs( '#forwp-ss-light-dark-enabled' );
		var show = enabled && enabled.checked;
		qsa( '.forwp-ss-light-dark-row' ).forEach( function ( row ) {
			row.style.display = show ? '' : 'none';
		} );
	}

	function renderAllowedVariations() {
		var list = qs( '#forwp-ss-variations-list' );
		if ( ! list ) {
			return;
		}

		list.innerHTML = '';
		var allowed = effectiveAllowedSlugs();

		if ( ! state.themeVariations.length ) {
			var liEmpty = document.createElement( 'li' );
			liEmpty.textContent = cfg.strings && cfg.strings.noVariations ? cfg.strings.noVariations : 'No style variations found in the active theme.';
			list.appendChild( liEmpty );
			return;
		}

		state.themeVariations.forEach( function ( item ) {
			var li = document.createElement( 'li' );
			var label = document.createElement( 'label' );
			label.className = 'forwp-ss-variation-choice';

			var input = document.createElement( 'input' );
			input.type = 'checkbox';
			input.className = 'forwp-ss-allowed-checkbox';
			input.value = item.slug;
			input.checked = allowed.indexOf( item.slug ) !== -1;

			var text = document.createElement( 'span' );
			text.className = 'forwp-ss-variation-choice__text';
			text.innerHTML = '<strong>' + item.title + '</strong> <code>' + item.slug + '</code>';

			label.appendChild( input );
			label.appendChild( text );
			li.appendChild( label );
			list.appendChild( li );
		} );
	}

	function readAllowedFromCheckboxes() {
		return qsa( '.forwp-ss-allowed-checkbox:checked' ).map( function ( input ) {
			return input.value;
		} );
	}

	function setAllAllowedCheckboxes( checked ) {
		qsa( '.forwp-ss-allowed-checkbox' ).forEach( function ( input ) {
			input.checked = checked;
		} );
	}

	function collectSettingsPayload( options ) {
		options = options || {};
		var visitor = qs( '#forwp-ss-visitor-switcher' );
		var position = qs( '#forwp-ss-switcher-position' );
		var variation = qs( '#forwp-ss-default-variation' );
		var lightDarkEnabled = qs( '#forwp-ss-light-dark-enabled' );
		var lightVariation = qs( '#forwp-ss-light-variation' );
		var darkVariation = qs( '#forwp-ss-dark-variation' );
		var abEnabled = qs( '#forwp-ss-ab-enabled' );
		var abVariationA = qs( '#forwp-ss-ab-variation-a' );
		var abVariationB = qs( '#forwp-ss-ab-variation-b' );
		var abSplit = qs( '#forwp-ss-ab-split' );
		var storageDays = qs( '#forwp-ss-storage-days' );

		var payload = {
			visitor_switcher_enabled: visitor ? visitor.checked : false,
			default_variation: variation ? variation.value : '',
			switcher_position: position ? position.value : 'bottom-right',
			light_dark_mode_enabled: lightDarkEnabled ? lightDarkEnabled.checked : false,
			light_variation: lightVariation ? lightVariation.value : '',
			dark_variation: darkVariation ? darkVariation.value : '',
			visitor_storage_days: storageDays ? parseInt( storageDays.value, 10 ) : 365,
			user_preferences: {
				enabled: false,
			},
		};

		if ( options.includeAllowed ) {
			payload.allowed_variations = readAllowedFromCheckboxes();
		}

		if ( abEnabled || abVariationA || abVariationB || abSplit ) {
			payload.ab_testing = {
				enabled: abEnabled ? abEnabled.checked : false,
				variation_a: abVariationA ? abVariationA.value : '',
				variation_b: abVariationB ? abVariationB.value : '',
				traffic_split_a: abSplit ? parseInt( abSplit.value, 10 ) : 50,
			};
		}

		return payload;
	}

	function applySettings( payload ) {
		var settings = payload.settings || {};
		var visitor = qs( '#forwp-ss-visitor-switcher' );
		var position = qs( '#forwp-ss-switcher-position' );
		var lightDarkEnabled = qs( '#forwp-ss-light-dark-enabled' );
		var abEnabled = qs( '#forwp-ss-ab-enabled' );
		var abSplit = qs( '#forwp-ss-ab-split' );
		var storageDays = qs( '#forwp-ss-storage-days' );
		var userPrefsEnabled = qs( '#forwp-ss-user-prefs-enabled' );

		state.themeVariations = themeVariationsFromPayload( payload );
		state.allowedSlugs = Object.prototype.hasOwnProperty.call( settings, 'allowed_variations' )
			? settings.allowed_variations
			: null;
		if ( Array.isArray( state.allowedSlugs ) && ! state.allowedSlugs.length ) {
			state.allowedSlugs = null;
		}

		if ( visitor ) {
			visitor.checked = !! settings.visitor_switcher_enabled;
		}
		if ( position && settings.switcher_position ) {
			position.value = settings.switcher_position;
		}
		if ( lightDarkEnabled ) {
			lightDarkEnabled.checked = !! settings.light_dark_mode_enabled;
		}

		var ab = payload.ab_testing || settings.ab_testing || {};
		if ( abEnabled ) {
			abEnabled.checked = !! ab.enabled;
		}
		if ( abSplit ) {
			abSplit.value = String( ab.traffic_split_a != null ? ab.traffic_split_a : 50 );
			updateAbSplitLabels( abSplit.value );
		}
		if ( storageDays ) {
			storageDays.value = String( settings.visitor_storage_days != null ? settings.visitor_storage_days : 365 );
		}
		if ( userPrefsEnabled ) {
			var userPrefs = settings.user_preferences || {};
			userPrefsEnabled.checked = !! userPrefs.enabled;
		}

		renderAllowedVariations();
		fillDefaultVariationSelect( settings.default_variation || '' );
		fillLightDarkSelects( settings );
		fillAbSelects( ab );
		syncLightDarkRows();
		syncAbRows();
		renderAbStats( payload.ab_stats || {}, ab );
	}

	function saveSettings( trigger ) {
		setStatus( cfg.strings && cfg.strings.saving ? cfg.strings.saving : 'Saving…', 'pending' );

		var options = {
			includeAllowed: trigger && trigger.id === 'forwp-ss-save-allowed',
		};

		return apiFetch( '/settings', {
			method: 'POST',
			body: JSON.stringify( collectSettingsPayload( options ) ),
		} )
			.then( function ( data ) {
				applySettings( data );
				setStatus( cfg.strings && cfg.strings.saved ? cfg.strings.saved : 'Settings saved.', 'success' );
			} )
			.catch( function () {
				setStatus( cfg.strings && cfg.strings.error ? cfg.strings.error : 'Could not save settings.', 'error' );
			} );
	}

	function activateTab( tabId ) {
		qsa( '.forwp-ss-tab' ).forEach( function ( btn ) {
			var active = btn.getAttribute( 'data-tab' ) === tabId;
			btn.classList.toggle( 'is-active', active );
			btn.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			btn.tabIndex = active ? 0 : -1;
		} );

		qsa( '.forwp-ss-tab-panel [role="tabpanel"]' ).forEach( function ( panel ) {
			var show = panel.id === 'forwp-ss-panel-' + tabId;
			panel.hidden = ! show;
		} );
	}

	function bindTabs() {
		qsa( '.forwp-ss-tab' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				activateTab( btn.getAttribute( 'data-tab' ) );
			} );
		} );
	}

	function init() {
		bindTabs();

		var saveBtn = qs( '#forwp-ss-save-settings' );
		if ( saveBtn ) {
			saveBtn.addEventListener( 'click', function ( event ) {
				saveSettings( event.currentTarget );
			} );
		}

		var saveAllowedBtn = qs( '#forwp-ss-save-allowed' );
		if ( saveAllowedBtn ) {
			saveAllowedBtn.addEventListener( 'click', function ( event ) {
				saveSettings( event.currentTarget );
			} );
		}

		var saveAbBtn = qs( '#forwp-ss-save-ab' );
		if ( saveAbBtn ) {
			saveAbBtn.addEventListener( 'click', function ( event ) {
				saveSettings( event.currentTarget );
			} );
		}

		var selectAllBtn = qs( '#forwp-ss-allowed-select-all' );
		if ( selectAllBtn ) {
			selectAllBtn.addEventListener( 'click', function () {
				setAllAllowedCheckboxes( true );
			} );
		}

		var selectNoneBtn = qs( '#forwp-ss-allowed-select-none' );
		if ( selectNoneBtn ) {
			selectNoneBtn.addEventListener( 'click', function () {
				setAllAllowedCheckboxes( false );
			} );
		}

		var lightDarkEnabled = qs( '#forwp-ss-light-dark-enabled' );
		if ( lightDarkEnabled ) {
			lightDarkEnabled.addEventListener( 'change', syncLightDarkRows );
		}

		var abEnabled = qs( '#forwp-ss-ab-enabled' );
		if ( abEnabled ) {
			abEnabled.addEventListener( 'change', syncAbRows );
		}

		var abSplitInput = qs( '#forwp-ss-ab-split' );
		if ( abSplitInput ) {
			abSplitInput.addEventListener( 'input', function () {
				updateAbSplitLabels( abSplitInput.value );
			} );
		}

		setStatus( '', '' );

		if ( cfg.bootstrap ) {
			applySettings( {
				settings: cfg.bootstrap.settings || {},
				theme_variations: cfg.bootstrap.theme_variations || [],
				ab_testing: cfg.bootstrap.ab_testing || {},
			} );
		}

		apiFetch( '/settings' )
			.then( applySettings )
			.catch( function () {
				setStatus( cfg.strings && cfg.strings.error ? cfg.strings.error : 'Could not load settings.', 'error' );
			} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
