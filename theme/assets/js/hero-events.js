( function () {
	var root = document.querySelector( '.lohkareet-hero__cycle' );
	var events = root ? root.querySelectorAll( '.lohkareet-hero__event' ) : [];

	if ( ! root || events.length < 2 ) {
		return;
	}

	var motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var steps = [ 300, 1100, 1900, 5500, 6200 ];
	var index = 0;
	var step = 0;
	var elapsed = 0;
	var startedAt = 0;
	var timer = 0;
	var paused = false;
	var running = false;

	function held() {
		return root.contains( document.activeElement ) || root.parentNode.matches( ':hover' );
	}

	function current() {
		return events[ index ];
	}

	function show( next ) {
		var i;

		index = next;

		for ( i = 0; i < events.length; i++ ) {
			var on = i === index;

			events[ i ].classList.toggle( 'is-current', on );
			events[ i ].classList.remove( 'is-date', 'is-artist', 'is-place' );

			if ( on ) {
				events[ i ].removeAttribute( 'tabindex' );
				events[ i ].removeAttribute( 'aria-hidden' );
			} else {
				events[ i ].setAttribute( 'tabindex', '-1' );
				events[ i ].setAttribute( 'aria-hidden', 'true' );
			}
		}
	}

	function apply() {
		var el = current();
		var shown = step > 0 && step < 4;

		el.classList.toggle( 'is-date', shown );
		el.classList.toggle( 'is-artist', shown && step > 1 );
		el.classList.toggle( 'is-place', shown && step > 2 );
	}

	function clearTimer() {
		window.clearTimeout( timer );
		timer = 0;
	}

	function arm() {
		var wait;

		if ( ! running || paused || step >= steps.length ) {
			return;
		}

		wait = Math.max( 0, steps[ step ] - elapsed );
		startedAt = performance.now();
		clearTimer();
		timer = window.setTimeout( advance, wait );
	}

	function advance() {
		if ( ! running ) {
			return;
		}

		if ( held() ) {
			elapsed = steps[ step ];
			paused = true;
			return;
		}

		elapsed = steps[ step ];
		step += 1;

		if ( step >= steps.length ) {
			show( ( index + 1 ) % events.length );
			step = 0;
			elapsed = 0;
			arm();
			return;
		}

		apply();
		arm();
	}

	function pause() {
		if ( ! running || paused ) {
			return;
		}

		paused = true;
		elapsed += performance.now() - startedAt;
		clearTimer();
	}

	function resume() {
		if ( ! running || ! paused || held() ) {
			return;
		}

		paused = false;
		arm();
	}

	function stop() {
		running = false;
		paused = false;
		clearTimer();
		root.classList.remove( 'is-live' );
		root.classList.add( 'is-static' );
		show( 0 );
	}

	function start() {
		if ( motion.matches ) {
			stop();
			return;
		}

		running = true;
		paused = false;
		root.classList.remove( 'is-static' );
		root.classList.add( 'is-live' );
		show( 0 );
		step = 0;
		elapsed = 0;
		arm();
	}

	root.parentNode.addEventListener( 'pointerenter', pause );
	root.parentNode.addEventListener( 'pointerleave', resume );
	root.addEventListener( 'focusin', pause );
	root.addEventListener( 'focusout', function () {
		window.setTimeout( resume, 0 );
	} );

	if ( typeof motion.addEventListener === 'function' ) {
		motion.addEventListener( 'change', start );
	}

	start();
}() );
