( function () {
	var hero = document.querySelector( '.lohkareet-hero' );
	var video = hero && hero.querySelector( '.lohkareet-hero__video' );

	if ( ! hero || ! video ) {
		return;
	}

	// Seconds of timeline queued per pixel of ordinary document scroll.
	// A 100px wheel notch queues about 0.36s of the five-second clip.
	// It never maps page position to duration.
	var GAIN = 0.0036;
	var EASE = 0.32;
	var MAX_STEP = 0.14;
	var MAX_PENDING = 1.05;
	var DEAD = 0.012;

	var motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var metadataReady = false;
	var duration = 0;
	var seekFailed = false;
	var visible = false;
	var pending = 0;
	var lastY = window.scrollY;
	var rafId = 0;
	var seekingFrames = 0;

	function isReduced() {
		return motion.matches;
	}

	function clamp( value, min, max ) {
		return Math.min( max, Math.max( min, value ) );
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

	video.addEventListener( 'loadedmetadata', rememberMetadata );
	video.addEventListener( 'error', giveUp );

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

	if ( isReduced() ) {
		holdStill();
	}
}() );
