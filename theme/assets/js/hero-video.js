( function () {
	var hero = document.querySelector( '.lohkareet-hero' );
	var video = hero && hero.querySelector( '.lohkareet-hero__video' );

	if ( ! hero || ! video ) {
		return;
	}

	// Seconds of timeline queued per pixel of ordinary document scroll.
	// A 100px wheel notch queues about 0.36s. The clip length comes from
	// the loaded file. It never maps page position to duration.
	var GAIN = 0.0036;
	var EASE = 0.32;
	var MAX_STEP = 0.14;
	var MAX_PENDING = 1.05;
	var DEAD = 0.012;

	// Horizontal swipe scrubbing: pixels of horizontal movement for the full
	// video duration. Lower values increase sensitivity.
	var SWIPE_FULL_DURATION_PX = 300;

	// Movement threshold before the gesture direction is locked.
	var SWIPE_DIRECTION_THRESHOLD = 8;

	var motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var fallback = hero.querySelector( '.lohkareet-hero__fallback' );
	var metadataReady = false;
	var duration = 0;
	var seekFailed = false;
	var visible = false;
	var pending = 0;
	var lastY = window.scrollY;
	var rafId = 0;
	var seekingFrames = 0;

	// Horizontal swipe state.
	var swipePointerId = null;
	var swipeStartX = 0;
	var swipeStartY = 0;
	var swipeStartTime = 0;
	var swipeDirection = null;
	var swipeScrollBlocked = false;

	function isReduced() {
		return motion.matches;
	}

	function clamp( value, min, max ) {
		return Math.min( max, Math.max( min, value ) );
	}

	function isTouchCapable() {
		return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
	}

	function isInteractiveTarget( element ) {
		if ( ! element ) {
			return false;
		}

		var tag = element.tagName;

		if ( tag === 'A' || tag === 'BUTTON' || tag === 'INPUT' ) {
			return true;
		}

		if ( element.closest( 'a, button, [role="button"]' ) ) {
			return true;
		}

		return false;
	}

	function seekSpan() {
		var span = 0;

		try {
			var ranges = video.seekable;

			if ( ! ranges ) {
				return 0;
			}

			for ( var i = 0; i < ranges.length; i++ ) {
				span = Math.max( span, ranges.end( i ) - ranges.start( i ) );
			}
		} catch ( error ) {
			return 0;
		}

		return span;
	}

	function canScrub() {
		return seekSpan() >= 1;
	}

	function setRate( rate ) {
		if ( video.playbackRate === rate ) {
			return;
		}

		try {
			video.playbackRate = rate;
		} catch ( error ) {
			video.playbackRate = 1;
		}
	}

	function stopLoop() {
		if ( rafId ) {
			window.cancelAnimationFrame( rafId );
			rafId = 0;
		}
	}

	function holdStill() {
		stopLoop();
		pending = 0;
		lastY = window.scrollY;
		setRate( 1 );
		video.loop = false;
		video.pause();
	}

	function startPlayback() {
		if ( isReduced() || seekFailed || ! visible ) {
			return;
		}

		video.muted = true;
		video.defaultMuted = true;
		video.playsInline = true;
		video.loop = true;
		setRate( 1 );

		var attempt = video.play();

		if ( attempt && typeof attempt.catch === 'function' ) {
			attempt.catch( function () {
				video.pause();
			} );
		}

		if ( video.readyState >= 2 ) {
			presentFrame();
		}
	}

	function giveUp() {
		seekFailed = true;
		pending = 0;
		stopLoop();
		hero.classList.add( 'is-video-missing' );

		if ( isReduced() ) {
			video.pause();
			return;
		}

		startPlayback();
	}

	function wrapTime( time ) {
		if ( duration <= 0 ) {
			return 0;
		}

		var wrapped = time % duration;

		if ( wrapped < 0 ) {
			wrapped += duration;
		}

		return wrapped;
	}

	function schedule() {
		if ( rafId || seekFailed || isReduced() || ! visible ) {
			return;
		}

		rafId = window.requestAnimationFrame( frame );
	}

	function frame() {
		rafId = 0;

		if ( seekFailed || isReduced() || ! visible ) {
			pending = 0;
			lastY = window.scrollY;
			return;
		}

		var y = window.scrollY;
		var delta = y - lastY;
		lastY = y;

		if ( delta !== 0 && metadataReady ) {
			pending = clamp( pending + delta * GAIN, -MAX_PENDING, MAX_PENDING );
			hero.dataset.scrollDelta = String( delta );
			hero.dataset.pending = String( Math.round( pending * 1000 ) / 1000 );
		}

		if ( Math.abs( pending ) < DEAD ) {
			pending = 0;
			setRate( 1 );
			startPlayback();
			return;
		}

		if ( ! metadataReady ) {
			schedule();
			return;
		}

		if ( video.seeking ) {
			seekingFrames += 1;

			if ( seekingFrames > 180 ) {
				giveUp();
				return;
			}

			schedule();
			return;
		}

		seekingFrames = 0;

		if ( ! canScrub() ) {
			// Some browsers report this file as unseekable and snap currentTime
			// back to the start. Play the queued motion as a speed change instead.
			setRate( clamp( 1 + pending * 8, 0.2, 4.5 ) );
			pending *= 0.94;
			schedule();
			return;
		}

		setRate( 1 );

		var step = clamp( pending * EASE, -MAX_STEP, MAX_STEP );
		pending -= step;

		try {
			video.currentTime = wrapTime( video.currentTime + step );
		} catch ( error ) {
			giveUp();
			return;
		}

		schedule();
	}

	function onScroll() {
		if ( seekFailed || isReduced() || ! visible ) {
			pending = 0;
			lastY = window.scrollY;
			return;
		}

		// After a horizontal swipe ends, the first scroll resets the baseline
		// without jumping the video back to a scroll-derived position.
		if ( swipeScrollBlocked ) {
			swipeScrollBlocked = false;
			pending = 0;
			lastY = window.scrollY;
			return;
		}

		schedule();
	}

	function setVisible( next ) {
		if ( next === visible ) {
			return;
		}

		visible = next;
		pending = 0;
		lastY = window.scrollY;
		stopLoop();

		if ( ! visible || isReduced() ) {
			video.pause();
			return;
		}

		startPlayback();
	}

	function rememberMetadata() {
		metadataReady = isFinite( video.duration ) && video.duration > 0;
		duration = metadataReady ? video.duration : 0;
	}

	if ( typeof motion.addEventListener === 'function' ) {
		motion.addEventListener( 'change', function () {
			if ( isReduced() ) {
				holdStill();
				return;
			}

			if ( visible ) {
				startPlayback();
			}
		} );
	}

	function revealVideo() {
		if ( hero.classList.contains( 'is-video-ready' ) || hero.classList.contains( 'is-video-missing' ) ) {
			return;
		}

		hero.classList.add( 'is-video-ready' );
	}

	function presentFrame() {
		if ( typeof video.requestVideoFrameCallback === 'function' ) {
			video.requestVideoFrameCallback( function () {
				revealVideo();
			} );
			return;
		}

		revealVideo();
	}

	function armVideo() {
		var src = video.getAttribute( 'data-src' );

		if ( ! src || video.getAttribute( 'src' ) ) {
			return;
		}

		video.muted = true;
		video.defaultMuted = true;
		video.playsInline = true;
		video.preload = 'auto';
		video.src = src;
		video.load();
	}

	video.addEventListener( 'loadedmetadata', rememberMetadata );
	video.addEventListener( 'loadeddata', function () {
		if ( ! isReduced() && visible ) {
			startPlayback();
		}

		presentFrame();
	} );
	video.addEventListener( 'error', function () {
		seekFailed = true;
		pending = 0;
		stopLoop();
		hero.classList.remove( 'is-video-ready' );
		hero.classList.add( 'is-video-missing' );
		video.pause();
	} );

	if ( fallback && ! ( fallback.complete && fallback.naturalWidth > 0 ) ) {
		fallback.addEventListener( 'load', armVideo, { once: true } );
		fallback.addEventListener( 'error', armVideo, { once: true } );
	} else {
		armVideo();
	}

	if ( video.readyState >= 1 ) {
		rememberMetadata();
	}

	if ( typeof window.IntersectionObserver === 'function' ) {
		var observer = new window.IntersectionObserver(
			function ( entries ) {
				var entry = entries[ 0 ];
				setVisible( !! entry && entry.isIntersecting && entry.intersectionRatio >= 0.2 );
			},
			{ threshold: [ 0, 0.2, 0.5, 1 ] }
		);
		observer.observe( hero );
	} else {
		setVisible( true );
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );

	// Horizontal swipe scrubbing for touch devices.
	function onPointerDown( e ) {
		if ( ! isTouchCapable() || e.pointerType !== 'touch' ) {
			return;
		}

		if ( isReduced() || seekFailed || ! visible ) {
			return;
		}

		if ( ! metadataReady || duration <= 0 ) {
			return;
		}

		if ( isInteractiveTarget( e.target ) ) {
			return;
		}

		swipePointerId = e.pointerId;
		swipeStartX = e.clientX;
		swipeStartY = e.clientY;
		swipeStartTime = video.currentTime;
		swipeDirection = null;

		hero.setPointerCapture( e.pointerId );
	}

	function onPointerMove( e ) {
		if ( swipePointerId === null || swipePointerId !== e.pointerId ) {
			return;
		}

		if ( isReduced() || seekFailed ) {
			endSwipe( e );
			return;
		}

		var deltaX = e.clientX - swipeStartX;
		var deltaY = e.clientY - swipeStartY;

		// Determine direction if not yet locked.
		if ( swipeDirection === null ) {
			var absX = Math.abs( deltaX );
			var absY = Math.abs( deltaY );

			if ( absX < SWIPE_DIRECTION_THRESHOLD && absY < SWIPE_DIRECTION_THRESHOLD ) {
				return;
			}

			if ( absX > absY ) {
				swipeDirection = 'horizontal';
				// Stop scroll-based updates and clear the queue.
				swipeScrollBlocked = true;
				pending = 0;
				stopLoop();
				video.pause();
			} else {
				swipeDirection = 'vertical';
				endSwipe( e );
				return;
			}
		}

		if ( swipeDirection !== 'horizontal' ) {
			return;
		}

		e.preventDefault();

		// Map horizontal movement to video time.
		var progress = deltaX / SWIPE_FULL_DURATION_PX;
		var newTime = swipeStartTime + progress * duration;

		// Wrap around for looping.
		newTime = wrapTime( newTime );

		if ( ! isFinite( newTime ) || newTime < 0 ) {
			newTime = 0;
		}

		if ( newTime > duration ) {
			newTime = duration;
		}

		try {
			video.currentTime = newTime;
		} catch ( err ) {
			// Ignore seek errors during swipe.
		}
	}

	function onPointerUp( e ) {
		if ( swipePointerId === null || swipePointerId !== e.pointerId ) {
			return;
		}

		endSwipe( e );
	}

	function onPointerCancel( e ) {
		if ( swipePointerId === null || swipePointerId !== e.pointerId ) {
			return;
		}

		endSwipe( e );
	}

	function endSwipe( e ) {
		if ( swipePointerId !== null ) {
			try {
				hero.releasePointerCapture( swipePointerId );
			} catch ( err ) {
				// Ignore if capture was already released.
			}
		}

		swipePointerId = null;
		swipeDirection = null;
		// swipeScrollBlocked stays true until the next scroll event.
	}

	if ( isTouchCapable() && typeof hero.setPointerCapture === 'function' ) {
		hero.style.touchAction = 'pan-y';
		hero.addEventListener( 'pointerdown', onPointerDown );
		hero.addEventListener( 'pointermove', onPointerMove );
		hero.addEventListener( 'pointerup', onPointerUp );
		hero.addEventListener( 'pointercancel', onPointerCancel );
	}

	if ( isReduced() ) {
		holdStill();
	}
}() );
