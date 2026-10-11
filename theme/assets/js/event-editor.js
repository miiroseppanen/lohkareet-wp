( function () {
	var button = document.getElementById( 'lohkareet-artist-image-button' );
	var remove = document.getElementById( 'lohkareet-artist-image-remove' );
	var input = document.getElementById( 'lohkareet_artist_image' );
	var preview = document.getElementById( 'lohkareet-artist-image-preview' );

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
}() );
