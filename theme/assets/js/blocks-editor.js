( function ( wp ) {
	var blocks = wp.blocks;
	var el = wp.element.createElement;
	var serverSideRender = wp.serverSideRender && ( wp.serverSideRender.default || wp.serverSideRender );
	var InspectorControls = wp.blockEditor.InspectorControls;
	var TextControl = wp.components.TextControl;
	var __ = wp.i18n.__;

	function register( name, settings ) {
		var existing = blocks.getBlockType( name );

		if ( existing ) {
			blocks.unregisterBlockType( name );
			settings = Object.assign( {}, existing, settings, {
				attributes: Object.assign( {}, existing.attributes || {}, settings.attributes || {} ),
			} );
		}

		blocks.registerBlockType( name, settings );
	}

	function preview( name, props ) {
		if ( ! serverSideRender ) {
			return null;
		}

		return el( serverSideRender, {
			block: name,
			attributes: props.attributes,
		} );
	}

	register( 'lohkareet/zoneatlas-map', {
		title: __( 'Lohkareet map', 'lohkareet' ),
		category: 'lohkareet',
		edit: function ( props ) {
			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el( TextControl, {
						label: __( 'Map URL', 'lohkareet' ),
						help: __( 'Leave empty to use the map for the current language.', 'lohkareet' ),
						value: props.attributes.url || '',
						onChange: function ( value ) {
							props.setAttributes( { url: value } );
						},
					} ),
					el( TextControl, {
						label: __( 'Map name', 'lohkareet' ),
						help: __( 'Name announced to screen readers.', 'lohkareet' ),
						value: props.attributes.title || '',
						onChange: function ( value ) {
							props.setAttributes( { title: value } );
						},
					} )
				),
				preview( 'lohkareet/zoneatlas-map', props )
			);
		},
		save: function () {
			return null;
		},
	} );

	function simple( name, title ) {
		register( name, {
			title: title,
			category: 'lohkareet',
			edit: function ( props ) {
				return preview( name, props );
			},
			save: function () {
				return null;
			},
		} );
	}

	simple( 'lohkareet/next-event', __( 'Next event', 'lohkareet' ) );
	simple( 'lohkareet/event-list', __( 'Event list', 'lohkareet' ) );
	simple( 'lohkareet/event-details', __( 'Event details', 'lohkareet' ) );
	simple( 'lohkareet/language-switcher', __( 'Language switcher', 'lohkareet' ) );
}( window.wp ) );
