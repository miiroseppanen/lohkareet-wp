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
