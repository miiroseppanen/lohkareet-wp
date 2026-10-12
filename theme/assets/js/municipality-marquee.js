( function () {
	var rail = document.querySelector( '.lohkareet-footer__logo-rail' );
	var track = rail && rail.querySelector( '.lohkareet-footer__logo-track' );
	var list = track && track.querySelector( '.lohkareet-footer__municipalities:not([aria-hidden="true"])' );

	if ( ! rail || ! track || ! list ) {
		return;
	}

	var narrow = window.matchMedia( '(max-width: 63.99rem)' );
	var motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var speed = 25;

	function distance() {
		return list.getBoundingClientRect().width;
	}

	function sync() {
		if ( ! narrow.matches || motion.matches || rail.classList.contains( 'is-paused' ) ) {
			track.style.animationDuration = '';
			return;
		}

		var width = distance();

		if ( width > 0 ) {
			track.style.animationDuration = ( width / speed ) + 's';
		}
	}

	function release() {
		window.requestAnimationFrame( function () {
			if ( rail.contains( document.activeElement ) ) {
				return;
			}

			rail.classList.remove( 'is-paused' );
			track.style.animation = '';
			track.style.transform = '';
			sync();
		} );
	}

	rail.addEventListener( 'focusin', function ( event ) {
		var link = event.target.closest( 'a' );

		if ( ! link || ! narrow.matches || motion.matches ) {
			return;
		}

		var railBox = rail.getBoundingClientRect();
		var linkBox = link.getBoundingClientRect();
		var visible = linkBox.right > railBox.left + 8 && linkBox.left < railBox.right - 8;

		if ( visible ) {
			return;
		}

		rail.classList.add( 'is-paused' );
		track.style.animation = 'none';
		track.style.transform = 'none';
		window.requestAnimationFrame( function () {
			link.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
		} );
	} );

	rail.addEventListener( 'focusout', release );
	window.addEventListener( 'resize', sync );
	window.addEventListener( 'load', sync );

	if ( narrow.addEventListener ) {
		narrow.addEventListener( 'change', sync );
		motion.addEventListener( 'change', sync );
	}

	sync();
}() );
