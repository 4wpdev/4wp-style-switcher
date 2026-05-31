( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.element ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var SelectControl = wp.components.SelectControl;
	var CheckboxControl = wp.components.CheckboxControl;
	var Spinner = wp.components.Spinner;
	var apiFetch = wp.apiFetch;

	var META_STYLE = '_forwp_ss_page_style';
	var META_LOCKED = '_forwp_ss_page_style_locked';

	function PageStylePanel() {
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );

		var meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );

		var editPost = useDispatch( 'core/editor' ).editPost;

		var variationsState = wp.element.useState( [] );
		var variations = variationsState[ 0 ];
		var setVariations = variationsState[ 1 ];

		var loadingState = wp.element.useState( true );
		var loading = loadingState[ 0 ];
		var setLoading = loadingState[ 1 ];

		wp.element.useEffect( function () {
			apiFetch( { path: '/forwp-style-switcher/v1/variations' } )
				.then( function ( res ) {
					setVariations( ( res && res.variations ) || [] );
				} )
				.catch( function () {
					setVariations( [] );
				} )
				.finally( function () {
					setLoading( false );
				} );
		}, [] );

		if ( ! postType || ( postType !== 'page' && postType !== 'post' ) ) {
			return null;
		}

		var options = [ { label: '—', value: '' } ].concat(
			variations.map( function ( item ) {
				return { label: item.title, value: item.slug };
			} )
		);

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'forwp-ss-page-style',
				title: 'Page style',
				className: 'forwp-ss-editor-panel',
			},
			loading
				? createElement( Spinner, null )
				: createElement(
						Fragment,
						null,
						createElement( SelectControl, {
							label: 'Style variation',
							value: meta[ META_STYLE ] || '',
							options: options,
							onChange: function ( value ) {
								editPost( { meta: Object.assign( {}, meta, { [ META_STYLE ]: value } ) } );
							},
						} ),
						createElement( CheckboxControl, {
							label: 'Lock style for visitors',
							help: 'Hides the frontend switcher and ignores visitor preferences on this page.',
							checked: !! meta[ META_LOCKED ],
							onChange: function ( value ) {
								editPost( { meta: Object.assign( {}, meta, { [ META_LOCKED ]: value } ) } );
							},
						} )
				  )
		);
	}

	registerPlugin( 'forwp-style-switcher-page-style', {
		render: PageStylePanel,
		icon: 'art',
	} );
} )( window.wp );
