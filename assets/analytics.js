( function () {
	'use strict';

	var cfg = window.forwpStyleSwitcherAnalyticsConfig || {};
	var EVENT_NAME = 'forwp-ss-analytics';

	function push( event, payload ) {
		var detail = Object.assign(
			{
				event: event,
				plugin: cfg.eventName || 'forwp_style_switcher',
				timestamp: Date.now(),
			},
			payload || {}
		);

		/**
		 * CustomEvent for GTM / custom listeners.
		 */
		document.dispatchEvent(
			new CustomEvent( EVENT_NAME, {
				bubbles: true,
				detail: detail,
			} )
		);

		if ( window.dataLayer && Array.isArray( window.dataLayer ) ) {
			window.dataLayer.push(
				Object.assign(
					{
						event: 'forwp_style_switcher_' + event,
					},
					detail
				)
			);
		}

		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'event', 'forwp_style_switcher_' + event, detail );
		}
	}

	window.forwpStyleSwitcherAnalytics = {
		push: push,
		config: cfg,
	};

	/**
	 * Example future events:
	 * - ab_assigned { cohort, variation }
	 * - variation_switched { from, to, source }
	 * - variation_applied { slug, source }
	 */
} )();
