( function () {
	var header = document.querySelector( 'header.wp-block-template-part' );
	var toggle = header && header.querySelector( '.lohkareet-header__toggle' );
	var panel = document.getElementById( 'lohkareet-menu' );

	if ( ! header || ! toggle || ! panel ) {
		return;
	}

	var label = toggle.querySelector( '.lohkareet-header__toggle-text' );
	var openLabel = toggle.getAttribute( 'data-label-open' ) || 'Menu';
	var closeLabel = toggle.getAttribute( 'data-label-close' ) || 'Close';
	var desktop = window.matchMedia( '(min-width: 60.01rem)' );
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var switcher = header.querySelector( '.lohkareet-language-switcher' );
	var langTrigger = switcher && switcher.querySelector( '.lohkareet-language-switcher__trigger' );
	var closeTimer = 0;

	function isOpen() {
		return toggle.getAttribute( 'aria-expanded' ) === 'true';
	}

	function isLangOpen() {
		return !! ( langTrigger && langTrigger.getAttribute( 'aria-expanded' ) === 'true' );
	}

	function prefersReduced() {
		return reduceMotion.matches;
	}

	function focusable() {
		var nodes = header.querySelectorAll( 'a[href], button:not([disabled])' );
		var list = [];

		for ( var i = 0; i < nodes.length; i++ ) {
			if ( ! nodes[ i ].getClientRects().length ) {
				continue;
			}

			if ( window.getComputedStyle( nodes[ i ] ).visibility === 'hidden' ) {
				continue;
			}

			list.push( nodes[ i ] );
		}

		return list;
	}

	function finishClose() {
		closeTimer = 0;
		header.classList.remove( 'is-menu-open', 'is-menu-closing' );
		document.documentElement.classList.remove( 'is-nav-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );

		if ( label ) {
			label.textContent = openLabel;
		}

		toggle.focus();
	}

	function setOpen( open ) {
		if ( open ) {
			if ( closeTimer ) {
				window.clearTimeout( closeTimer );
				closeTimer = 0;
			}

			header.classList.remove( 'is-menu-closing' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			header.classList.add( 'is-menu-open' );
			document.documentElement.classList.add( 'is-nav-open' );

			if ( label ) {
				label.textContent = closeLabel;
			}

			var first = panel.querySelector( 'a[href]' );

			if ( first ) {
				first.focus();
			}

			return;
		}

		if ( header.classList.contains( 'is-menu-closing' ) ) {
			return;
		}

		if ( ! isOpen() && ! header.classList.contains( 'is-menu-open' ) ) {
			return;
		}

		if ( prefersReduced() ) {
			finishClose();
			return;
		}

		header.classList.add( 'is-menu-closing' );
		closeTimer = window.setTimeout( finishClose, 300 );
	}

	function setLangOpen( open ) {
		if ( ! langTrigger || ! switcher ) {
			return;
		}

		langTrigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		switcher.classList.toggle( 'is-open', open );
	}

	toggle.addEventListener( 'click', function () {
		setOpen( ! isOpen() );
	} );

	if ( langTrigger ) {
		langTrigger.addEventListener( 'click', function () {
			setLangOpen( ! isLangOpen() );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! isLangOpen() || ! switcher ) {
			return;
		}

		if ( ! switcher.contains( event.target ) ) {
			setLangOpen( false );
		}
	} );

	if ( switcher ) {
		switcher.addEventListener( 'focusout', function ( event ) {
			if ( ! isLangOpen() ) {
				return;
			}

			if ( event.relatedTarget && ! switcher.contains( event.relatedTarget ) ) {
				setLangOpen( false );
			}
		} );
	}

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && isLangOpen() && ! isOpen() ) {
			event.preventDefault();
			setLangOpen( false );

			if ( langTrigger ) {
				langTrigger.focus();
			}

			return;
		}

		if ( ! isOpen() ) {
			return;
		}

		if ( event.key === 'Escape' ) {
			event.preventDefault();
			setOpen( false );
			return;
		}

		if ( event.key !== 'Tab' ) {
			return;
		}

		var list = focusable();

		if ( ! list.length ) {
			return;
		}

		var first = list[ 0 ];
		var last = list[ list.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	function closeIfDesktop( event ) {
		if ( ! event.matches ) {
			setLangOpen( false );
		}

		if ( ! event.matches || ( ! isOpen() && ! header.classList.contains( 'is-menu-open' ) ) ) {
			return;
		}

		if ( closeTimer ) {
			window.clearTimeout( closeTimer );
			closeTimer = 0;
		}

		toggle.setAttribute( 'aria-expanded', 'false' );
		header.classList.remove( 'is-menu-open', 'is-menu-closing' );
		document.documentElement.classList.remove( 'is-nav-open' );

		if ( label ) {
			label.textContent = openLabel;
		}
	}

	if ( typeof desktop.addEventListener === 'function' ) {
		desktop.addEventListener( 'change', closeIfDesktop );
	}

	var hero = document.querySelector( '.lohkareet-hero, .lohkareet-page-hero' );

	if ( ! hero || typeof window.IntersectionObserver !== 'function' ) {
		header.classList.add( 'is-past-hero' );
	} else {
		var heroWatch = new IntersectionObserver(
			function ( entries ) {
				var visible = entries[ 0 ] && entries[ 0 ].isIntersecting;
				header.classList.toggle( 'is-over-hero', visible );
				header.classList.toggle( 'is-past-hero', ! visible );
			},
			{ threshold: 0 }
		);

		heroWatch.observe( hero );
	}

	var footer = document.querySelector( 'footer.wp-block-template-part' );

	if ( ! footer || typeof window.IntersectionObserver !== 'function' ) {
		return;
	}

	var footerWatch = null;

	function watchFooter() {
		if ( footerWatch ) {
			footerWatch.disconnect();
		}

		var band = 88;

		if ( document.body.classList.contains( 'admin-bar' ) ) {
			band += window.matchMedia( '(max-width: 782px)' ).matches ? 46 : 32;
		}

		var inset = Math.max( 0, window.innerHeight - band );

		footerWatch = new IntersectionObserver(
			function ( entries ) {
				header.classList.toggle( 'is-over-footer', !!( entries[ 0 ] && entries[ 0 ].isIntersecting ) );
			},
			{
				rootMargin: '0px 0px -' + inset + 'px 0px',
				threshold: 0,
			}
		);

		footerWatch.observe( footer );
	}

	watchFooter();
	window.addEventListener( 'resize', watchFooter );
}() );
