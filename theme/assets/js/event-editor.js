( function () {
	function bindImagePicker( ids ) {
		var button = document.getElementById( ids.button );
		var remove = document.getElementById( ids.remove );
		var input = document.getElementById( ids.input );
		var preview = document.getElementById( ids.preview );

		if ( ! button || ! input || ! window.wp || ! wp.media ) {
			return;
		}

		var frame;

		function show( attachment ) {
			input.value = String( attachment.id || '' );

			if ( ! preview ) {
				return;
			}

			preview.textContent = '';

			if ( ! attachment.url ) {
				return;
			}

			var image = document.createElement( 'img' );
			var size = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			image.src = size;
			image.alt = attachment.alt || '';
			preview.appendChild( image );
		}

		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			if ( ! frame ) {
				frame = wp.media( {
					title: button.textContent,
					multiple: false,
					library: { type: 'image' },
				} );

				frame.on( 'select', function () {
					var selected = frame.state().get( 'selection' ).first();

					if ( selected ) {
						show( selected.toJSON() );
					}
				} );
			}

			frame.open();
		} );

		if ( remove ) {
			remove.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				input.value = '';

				if ( preview ) {
					preview.textContent = '';
				}
			} );
		}
	}

	bindImagePicker( {
		button: 'lohkareet-artist-image-button',
		remove: 'lohkareet-artist-image-remove',
		input: 'lohkareet_artist_image',
		preview: 'lohkareet-artist-image-preview',
	} );

	bindImagePicker( {
		button: 'lohkareet-social-image-button',
		remove: 'lohkareet-social-image-remove',
		input: 'lohkareet_social_image',
		preview: 'lohkareet-social-image-preview',
	} );
}() );

( function ( wp ) {
	var settings = window.lohkareetEventEditor || {};

	if ( settings.postType !== 'event' || ! wp || ! wp.plugins || ! wp.editor || ! wp.element || ! wp.components || ! wp.data || ! wp.i18n ) {
		return;
	}

	var Slot = wp.editor.PluginPostStatusInfo;

	if ( ! Slot ) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var metaBoxId = settings.metaBoxId || 'lohkareet_event_details';

	function openEventDetails() {
		var panelName = 'meta-box-' + metaBoxId;
		var editorSelect = wp.data.select( 'core/editor' );
		var editorDispatch = wp.data.dispatch( 'core/editor' );
		var preferences = wp.data.dispatch( 'core/preferences' );

		if ( preferences && preferences.set ) {
			preferences.set( 'core/edit-post', 'metaBoxesMainIsOpen', true );
		}

		if ( editorSelect && editorDispatch && editorSelect.isEditorPanelEnabled && ! editorSelect.isEditorPanelEnabled( panelName ) && editorDispatch.toggleEditorPanelEnabled ) {
			editorDispatch.toggleEditorPanelEnabled( panelName );
		}

		var frames = 0;

		function reveal() {
			var box = document.getElementById( metaBoxId );
			var visible = box && ! box.classList.contains( 'is-hidden' ) && box.getClientRects().length > 0;

			if ( visible ) {
				box.scrollIntoView( { block: 'start' } );

				if ( ! box.hasAttribute( 'tabindex' ) ) {
					box.setAttribute( 'tabindex', '-1' );
				}

				box.focus( { preventScroll: true } );
				return;
			}

			frames += 1;

			if ( frames < 30 ) {
				window.requestAnimationFrame( reveal );
			}
		}

		window.requestAnimationFrame( reveal );
	}

	wp.plugins.registerPlugin( 'lohkareet-event-details-jump', {
		render: function EventDetailsShortcut() {
			return el(
				Slot,
				{ className: 'lohkareet-event-details-jump' },
				el(
					wp.components.Button,
					{
						variant: 'secondary',
						onClick: openEventDetails,
					},
					__( 'Go to event details', 'lohkareet' )
				)
			);
		},
	} );

	if ( wp.data.select( 'core/commands' ) ) {
		wp.data.dispatch( 'core/commands' ).registerCommand( {
			name: 'lohkareet/go-to-event-details',
			label: __( 'Go to event details', 'lohkareet' ),
			callback: openEventDetails,
		} );
	}
}( window.wp ) );
