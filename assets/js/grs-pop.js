/**
 * GRS Pop - script del sito pubblico.
 *
 * Nessuna dipendenza. Legge i dati da <script type="application/json" id="grs-pop-data">.
 * Memorizza nel browser solo dati tecnici (localStorage "grs_pop", sessionStorage "grs_pop_s").
 * Con la regola sui form (contratto dev/contratto-grs-pop-forms.md) ricorda anche gli ID dei
 * form di GRS Forms inviati e la data (chiave f), mai i dati inseriti.
 *
 * Eventi emessi su document (contratto con GRS Tracking, vedi dev/contratto-grs-pop-tracking.md):
 *   grs-pop:view    { popup_id, popup_name, format, device }
 *   grs-pop:close   { popup_id, popup_name, format, device, reason }
 *   grs-pop:convert { popup_id, popup_name, format, device, type, form_id, event_id, lead_tracked }
 *   grs-pop:reopen  { popup_id, popup_name, format, device } (1.3.0: clic sulla linguetta per riaprire,
 *                   seguito da grs-pop:view con reopen: true)
 *
 * Frequenza "a ogni visita" (1.3.0): la visita finisce dopo N minuti senza attività sulle pagine
 * con GRS Pop. In localStorage: la = ultima attività, gp = pause lunghe [inizio ripresa, durata].
 *
 * @package GRSDigital\Pop
 */
( function () {
	'use strict';

	var dataEl = document.getElementById( 'grs-pop-data' );
	if ( ! dataEl || ! window.JSON || ! document.querySelector ) {
		return;
	}
	var data;
	try {
		data = JSON.parse( dataEl.textContent || '{}' );
	} catch ( e ) {
		return;
	}
	if ( ! data || ! data.popups || ! data.popups.length ) {
		return;
	}

	var NOW = Date.now();
	var DAY = 86400000;
	var DEVICE_INDEX = { desktop: 0, tablet: 1, mobile: 2 };
	var INTRUSIVE = { modal: true, fullscreen: true };
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ------------------------------------------------------------------ */
	/* Memoria nel browser (sempre in try/catch: può non essere disponibile) */
	/* ------------------------------------------------------------------ */

	function readStore( storage, key ) {
		try {
			var raw = window[ storage ].getItem( key );
			var obj = raw ? JSON.parse( raw ) : null;
			return obj && typeof obj === 'object' ? obj : null;
		} catch ( e ) {
			return null;
		}
	}
	function writeStore( storage, key, value ) {
		try {
			window[ storage ].setItem( key, JSON.stringify( value ) );
		} catch ( e ) {}
	}

	var store = readStore( 'localStorage', 'grs_pop' ) || {};
	var session = readStore( 'sessionStorage', 'grs_pop_s' );
	var newSession = ! session;

	if ( typeof store.v !== 'number' ) {
		store = { v: 1, first: NOW, pv: 0, p: {} };
	}
	store.p = store.p || {};
	if ( ! store.f || typeof store.f !== 'object' ) {
		store.f = {};
	}
	var returning = newSession && store.pv > 0;
	if ( newSession ) {
		session = { ret: returning ? 1 : 0, ref: document.referrer || '', par: {}, p: {} };
		// Parametri della pagina di arrivo (solo quelli usati dalle regole dei popup).
		var qs = new URLSearchParams( window.location.search );
		( data.params || [] ).forEach( function ( name ) {
			if ( qs.has( name ) ) {
				session.par[ name ] = String( qs.get( name ) ).slice( 0, 120 );
			}
		} );
	}
	session.p = session.p || {};
	store.pv = ( store.pv || 0 ) + 1;

	// Attività per la frequenza "a ogni visita": si annotano solo le pause di almeno 5 minuti.
	var MIN_GAP = 5 * 60000;
	function touch( now ) {
		var last = typeof store.la === 'number' ? store.la : 0;
		if ( ! last || now - last >= MIN_GAP ) {
			// Senza attività precedente (primo accesso o memoria della 1.2) la visita è nuova.
			store.gp = ( Array.isArray( store.gp ) ? store.gp : [] ).concat( [ [ now, last ? now - last : 1e15 ] ] ).slice( -20 );
		}
		store.la = now;
	}
	touch( NOW );
	var lastTouch = NOW;
	[ 'click', 'keydown', 'scroll', 'touchstart' ].forEach( function ( type ) {
		window.addEventListener( type, function () {
			var now = Date.now();
			if ( now - lastTouch >= 30000 ) {
				lastTouch = now;
				touch( now );
				save();
			}
		}, { passive: true } );
	} );

	// Inizio della visita in corso per un popup: ultima pausa più lunga dei suoi minuti.
	function visitStart( p ) {
		var gap = ( p.fq.visit_minutes || 30 ) * 60000;
		var list = Array.isArray( store.gp ) ? store.gp : [];
		for ( var i = list.length - 1; i >= 0; i-- ) {
			if ( list[ i ] && list[ i ][ 1 ] >= gap ) {
				return list[ i ][ 0 ];
			}
		}
		return store.first || 0;
	}

	function save() {
		writeStore( 'localStorage', 'grs_pop', store );
		writeStore( 'sessionStorage', 'grs_pop_s', session );
	}
	save();

	function pstate( id ) {
		var key = String( id );
		store.p[ key ] = store.p[ key ] || {};
		return store.p[ key ];
	}

	/* ------------------------------------------------------------------ */
	/* Dispositivo                                                          */
	/* ------------------------------------------------------------------ */

	function device() {
		var w = window.innerWidth || document.documentElement.clientWidth;
		if ( w <= data.bp.m ) {
			return 'mobile';
		}
		if ( w <= data.bp.t ) {
			return 'tablet';
		}
		return 'desktop';
	}
	function devConf( p ) {
		var key = { desktop: 'd', tablet: 't', mobile: 'm' }[ device() ];
		return p.dv[ key ];
	}

	/* ------------------------------------------------------------------ */
	/* Statistiche (inviate a lotti con sendBeacon)                         */
	/* ------------------------------------------------------------------ */

	var queue = [];
	var flushTimer = null;
	function track( p, type ) {
		if ( ! data.stats || data.preview ) {
			return;
		}
		queue.push( { id: p.id, d: DEVICE_INDEX[ device() ], t: type } );
		if ( ! flushTimer ) {
			flushTimer = setTimeout( flush, 3000 );
		}
	}
	function flush() {
		clearTimeout( flushTimer );
		flushTimer = null;
		if ( ! queue.length ) {
			return;
		}
		var body = JSON.stringify( { e: queue.splice( 0, 40 ) } );
		var sent = false;
		try {
			if ( navigator.sendBeacon ) {
				sent = navigator.sendBeacon( data.rest, new Blob( [ body ], { type: 'application/json' } ) );
			}
		} catch ( e ) {}
		if ( ! sent && window.fetch ) {
			try {
				window.fetch( data.rest, { method: 'POST', body: body, keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'application/json' } } );
			} catch ( e ) {}
		}
	}
	document.addEventListener( 'visibilitychange', function () {
		if ( document.visibilityState === 'hidden' ) {
			flush();
		}
	} );
	window.addEventListener( 'pagehide', flush );

	function emit( name, p, extra ) {
		var conf = p.in ? { f: 'inline' } : devConf( p );
		var detail = { popup_id: p.id, popup_name: p.name, format: conf ? conf.f : '', device: device() };
		if ( extra ) {
			for ( var k in extra ) {
				if ( Object.prototype.hasOwnProperty.call( extra, k ) ) {
					detail[ k ] = extra[ k ];
				}
			}
		}
		try {
			document.dispatchEvent( new CustomEvent( name, { detail: detail } ) );
		} catch ( e ) {}
	}

	/* ------------------------------------------------------------------ */
	/* Regole lato browser                                                  */
	/* ------------------------------------------------------------------ */

	var SEARCH = /(^|\.)(google|bing|yahoo|duckduckgo|ecosia|yandex|baidu|qwant|startpage|brave)\./i;
	var SOCIAL = /(^|\.)(facebook|fb|instagram|t|twitter|x|linkedin|lnkd|pinterest|tiktok|youtube|reddit|whatsapp|telegram|threads)\.(com|co|me|in|net|it)$/i;

	function refHost() {
		try {
			return session.ref ? new URL( session.ref ).hostname : '';
		} catch ( e ) {
			return '';
		}
	}

	function siteParts() {
		var d = new Date();
		if ( data.tz && window.Intl && Intl.DateTimeFormat ) {
			try {
				var parts = new Intl.DateTimeFormat( 'en-US', { timeZone: data.tz, weekday: 'short', hour: '2-digit', minute: '2-digit', hour12: false } ).formatToParts( d );
				var out = {};
				parts.forEach( function ( part ) {
					out[ part.type ] = part.value;
				} );
				var days = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
				return { day: days[ out.weekday ], mins: ( parseInt( out.hour, 10 ) % 24 ) * 60 + parseInt( out.minute, 10 ) };
			} catch ( e ) {}
		}
		var local = new Date( d.getTime() + ( data.off || 0 ) * 60000 );
		return { day: local.getUTCDay(), mins: local.getUTCHours() * 60 + local.getUTCMinutes() };
	}

	function hm( value ) {
		var bits = String( value ).split( ':' );
		return parseInt( bits[ 0 ], 10 ) * 60 + parseInt( bits[ 1 ], 10 );
	}

	// Regola "ha / non ha compilato il form": almeno uno dei form scelti, negli ultimi d giorni (0 = sempre).
	function formsPass( p ) {
		var r = p.co && p.co.fm;
		if ( ! r || ! r.ids || ! r.ids.length ) {
			return true;
		}
		var limit = r.d ? Date.now() - r.d * DAY : 0;
		var done = false;
		for ( var i = 0; i < r.ids.length; i++ ) {
			var ts = store.f[ String( r.ids[ i ] ) ];
			if ( typeof ts === 'number' && ts >= limit ) {
				done = true;
				break;
			}
		}
		return r.m === 'done' ? done : ! done;
	}

	function rememberForm( formId ) {
		var id = parseInt( formId, 10 );
		if ( ! data.fw || ! ( id > 0 ) ) {
			return;
		}
		store.f[ String( id ) ] = Date.now();
		Object.keys( store.f ).sort( function ( a, b ) {
			return store.f[ b ] - store.f[ a ];
		} ).slice( 50 ).forEach( function ( key ) {
			delete store.f[ key ];
		} );
		save();
	}

	function conditionsPass( p ) {
		var c = p.co;
		var now = Date.now();
		if ( ! formsPass( p ) ) {
			return false;
		}
		if ( c.from && now < c.from ) {
			return false;
		}
		if ( c.to && now > c.to ) {
			return false;
		}
		if ( ( c.days && c.days.length ) || c.hf || c.ht ) {
			var site = siteParts();
			if ( c.days && c.days.length && c.days.indexOf( site.day ) === -1 ) {
				return false;
			}
			if ( c.hf && c.ht ) {
				var a = hm( c.hf );
				var b = hm( c.ht );
				var inside = a <= b ? ( site.mins >= a && site.mins < b ) : ( site.mins >= a || site.mins < b );
				if ( ! inside ) {
					return false;
				}
			}
		}
		if ( c.pv && store.pv < c.pv ) {
			return false;
		}
		if ( c.vis === 'new' && session.ret ) {
			return false;
		}
		if ( c.vis === 'returning' && ! session.ret ) {
			return false;
		}
		if ( c.ref && c.ref.type && c.ref.type !== 'any' ) {
			var host = refHost();
			var external = host && host !== window.location.hostname;
			switch ( c.ref.type ) {
				case 'search':
					if ( ! external || ! SEARCH.test( host ) ) {
						return false;
					}
					break;
				case 'social':
					if ( ! external || ! SOCIAL.test( host ) ) {
						return false;
					}
					break;
				case 'direct':
					if ( external ) {
						return false;
					}
					break;
				case 'external':
					if ( ! external ) {
						return false;
					}
					break;
				case 'contains':
					if ( ! c.ref.value || session.ref.toLowerCase().indexOf( c.ref.value.toLowerCase() ) === -1 ) {
						return false;
					}
					break;
			}
		}
		if ( c.par && c.par.length ) {
			var qs = new URLSearchParams( window.location.search );
			for ( var i = 0; i < c.par.length; i++ ) {
				var name = c.par[ i ].name;
				var want = c.par[ i ].value;
				var has = qs.has( name ) ? qs.get( name ) : session.par[ name ];
				if ( has === undefined || has === null ) {
					return false;
				}
				if ( want && String( has ).toLowerCase() !== String( want ).toLowerCase() ) {
					return false;
				}
			}
		}
		return true;
	}

	function frequencyPass( p ) {
		var f = p.fq;
		var st = pstate( p.id );
		var now = Date.now();
		if ( st.cv ) {
			if ( f.after_convert === 'never' ) {
				return false;
			}
			if ( f.after_convert === 'days' && now - st.cv < f.after_convert_days * DAY ) {
				return false;
			}
		}
		if ( st.cl && f.after_close_days > 0 && now - st.cl < f.after_close_days * DAY ) {
			return false;
		}
		if ( st.cl && f.after_close_days === 0 ) {
			if ( f.mode === 'visit' ? st.cl >= visitStart( p ) : session.p[ p.id ] && session.p[ p.id ].cl ) {
				return false; // Chiuso in questa visita (o sessione del browser).
			}
		}
		if ( f.max_views && ( st.n || 0 ) >= f.max_views ) {
			return false;
		}
		switch ( f.mode ) {
			case 'once':
				return ! st.n;
			case 'session':
				return ! ( session.p[ p.id ] && session.p[ p.id ].v );
			case 'visit':
				return ! ( st.last && st.last >= visitStart( p ) );
			case 'days':
				return ! st.last || now - st.last >= f.days * DAY;
		}
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Apertura e chiusura                                                  */
	/* ------------------------------------------------------------------ */

	var popups = {};
	var openIntrusive = [];
	var waiting = [];
	var lastFocus = null;

	function el( p ) {
		return document.getElementById( 'grs-pop-' + p.id );
	}

	function applyFormat( p, root ) {
		var conf = devConf( p );
		var cls = root.className.replace( /\sgrs-pop--(fmt|pos)-[a-z-]+/g, '' ).replace( /\sgrs-pop--bar-push/, '' );
		cls += ' grs-pop--fmt-' + conf.f + ' grs-pop--pos-' + conf.p;
		if ( conf.f === 'bar' && p.bar === 'push' ) {
			cls += ' grs-pop--bar-push';
		}
		root.className = cls;
		root.setAttribute( 'aria-modal', INTRUSIVE[ conf.f ] ? 'true' : 'false' );
		if ( conf.f === 'bar' && p.bar === 'push' && conf.p === 'top' && document.body.firstChild !== root ) {
			document.body.insertBefore( root, document.body.firstChild );
		}
		return conf;
	}

	function focusables( root ) {
		return Array.prototype.filter.call(
			root.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])' ),
			function ( node ) {
				return node.offsetWidth > 0 || node.offsetHeight > 0 || node === document.activeElement;
			}
		);
	}

	function lockScroll( on ) {
		document.documentElement.classList.toggle( 'grs-pop-lock', on );
	}

	function updatePush( root, conf, on ) {
		if ( conf.f !== 'bar' ) {
			return;
		}
		if ( conf.p === 'bottom' ) {
			document.body.style.paddingBottom = on ? root.querySelector( '.grs-pop__box' ).offsetHeight + 'px' : '';
		}
	}

	function open( p, opts ) {
		opts = opts || {};
		var root = el( p );
		if ( ! root || p.isOpen ) {
			return false;
		}
		var conf = applyFormat( p, root );
		if ( ! conf.on && ! opts.force ) {
			return false;
		}
		if ( INTRUSIVE[ conf.f ] && openIntrusive.length >= ( data.maxI || 1 ) ) {
			if ( ! opts.force ) {
				if ( waiting.indexOf( p ) === -1 ) {
					waiting.push( p );
				}
				return false;
			}
		}
		if ( conf.f === 'tab' && ! opts.panel ) {
			showTab( p, opts.noCount );
			return true;
		}
		if ( conf.f === 'tab' && ! p.tab ) {
			showTab( p, true );
		}

		p.isOpen = true;
		p.conf = conf;
		if ( INTRUSIVE[ conf.f ] ) {
			openIntrusive.push( p );
			lockScroll( true );
			lastFocus = document.activeElement;
		}
		root.hidden = false;
		// Forza il reflow per far partire l'animazione.
		void root.offsetWidth;
		root.classList.add( 'is-open' );
		if ( p.tab ) {
			p.tab.setAttribute( 'aria-expanded', 'true' );
			p.tab.classList.add( 'is-active' );
		}
		updatePush( root, conf, true );
		syncShift();
		updateReopen( p );
		startCountdowns( p, root );

		if ( INTRUSIVE[ conf.f ] || opts.panel || opts.focus ) {
			// Il focus va al riquadro (role=dialog): Tab porta ai controlli, Esc chiude.
			var box = root.querySelector( '.grs-pop__box' );
			setTimeout( function () {
				box.focus( { preventScroll: true } );
			}, reduceMotion ? 0 : 60 );
		}

		if ( ! opts.noCount ) {
			var st = pstate( p.id );
			st.n = ( st.n || 0 ) + 1;
			st.last = Date.now();
			session.p[ p.id ] = session.p[ p.id ] || {};
			session.p[ p.id ].v = 1;
			save();
			track( p, 'v' );
			emit( 'grs-pop:view', p, opts.reopen ? { reopen: true } : null );
		}

		if ( p.cl.auto_close > 0 ) {
			p.autoTimer = setTimeout( function () {
				close( p, 'auto' );
			}, p.cl.auto_close * 1000 );
		}
		return true;
	}

	function close( p, reason ) {
		var root = el( p );
		if ( ! root || ! p.isOpen ) {
			return;
		}
		p.isOpen = false;
		clearTimeout( p.autoTimer );
		root.classList.remove( 'is-open' );
		var conf = p.conf || devConf( p );
		var finish = function () {
			if ( ! p.isOpen ) {
				root.hidden = true;
			}
		};
		if ( reduceMotion || root.classList.contains( 'grs-pop--anim-none' ) ) {
			finish();
		} else {
			setTimeout( finish, 320 );
		}
		updatePush( root, conf, false );
		syncShift();
		if ( p.tab ) {
			p.tab.setAttribute( 'aria-expanded', 'false' );
			p.tab.classList.remove( 'is-active' );
		}

		var idx = openIntrusive.indexOf( p );
		if ( idx !== -1 ) {
			openIntrusive.splice( idx, 1 );
			if ( ! openIntrusive.length ) {
				lockScroll( false );
				if ( lastFocus && lastFocus.focus ) {
					try {
						lastFocus.focus( { preventScroll: true } );
					} catch ( e ) {}
				}
			}
		} else if ( p.tab && reason !== 'convert' ) {
			p.tab.focus( { preventScroll: true } );
		}

		if ( reason !== 'convert' && reason !== 'silent' ) {
			var st = pstate( p.id );
			// Con la linguetta la chiusura del pannello non nasconde la linguetta.
			if ( ! p.tab ) {
				st.cl = Date.now();
				session.p[ p.id ] = session.p[ p.id ] || {};
				session.p[ p.id ].cl = 1;
			}
			save();
			track( p, 'x' );
			emit( 'grs-pop:close', p, { reason: reason || 'button' } );
		}
		// Linguetta per riaprire; il focus torna lì se il popup era stato riaperto da lei.
		updateReopen( p, p.viaReopen && reason !== 'convert' && reason !== 'silent' );
		p.viaReopen = false;

		// Popup in attesa che si libera lo spazio.
		if ( waiting.length && openIntrusive.length < ( data.maxI || 1 ) ) {
			var next = waiting.shift();
			setTimeout( function () {
				open( next );
			}, 600 );
		}
	}

	function convert( p, type, extra ) {
		var st = pstate( p.id );
		st.cv = Date.now();
		save();
		track( p, 'c' );
		var detail = { type: type };
		if ( extra ) {
			for ( var k in extra ) {
				if ( Object.prototype.hasOwnProperty.call( extra, k ) ) {
					detail[ k ] = extra[ k ];
				}
			}
		}
		emit( 'grs-pop:convert', p, detail );
		updateReopen( p );
	}

	/* Barra in alto: sposta in basso gli elementi fissi del sito ------------ */

	var shift = { p: null, items: [], body: null, html: null, h: -1, ro: null, timer: null };

	function adminBarH() {
		var bar = document.getElementById( 'wpadminbar' );
		if ( ! bar ) {
			return 0;
		}
		return window.getComputedStyle( bar ).position === 'fixed' ? bar.offsetHeight : 0;
	}

	function ownNode( node ) {
		return !! ( node.closest && node.closest( '.grs-pop, .grs-pop-tab, .grs-pop-reopen, #wpadminbar' ) );
	}

	// Elementi fissi o adesivi in cima alla pagina (header, barre di navigazione), fino a 6 livelli.
	function autoTargets() {
		var out = [];
		var seen = 0;
		var vh = window.innerHeight || 800;
		var skip = /^(SCRIPT|STYLE|LINK|META|NOSCRIPT|TEMPLATE|IMG|SVG|BR|VIDEO|IFRAME|CANVAS|PICTURE)$/;
		var walk = function ( parent, depth ) {
			var kids = parent.children;
			for ( var i = 0; i < kids.length && seen < 800; i++ ) {
				var node = kids[ i ];
				seen++;
				if ( skip.test( String( node.tagName ).toUpperCase() ) || ownNode( node ) ) {
					continue;
				}
				var cs = window.getComputedStyle( node );
				if ( cs.display === 'none' ) {
					continue;
				}
				if ( cs.position === 'fixed' || cs.position === 'sticky' ) {
					var top = parseFloat( cs.top );
					if ( ! isNaN( top ) && top >= 0 && top <= 50 && node.offsetHeight > 0 && node.offsetHeight < vh * 0.9 ) {
						out.push( node );
						continue;
					}
				}
				if ( depth < 6 ) {
					walk( node, depth + 1 );
				}
			}
		};
		if ( document.body ) {
			walk( document.body, 1 );
		}
		return out;
	}

	// Elementi da spostare: quelli indicati più, se attivo, quelli trovati da soli.
	function shiftTargets( p ) {
		var list = [];
		if ( p.bo.s ) {
			try {
				Array.prototype.forEach.call( document.querySelectorAll( p.bo.s ), function ( node ) {
					if ( ! ownNode( node ) && list.indexOf( node ) === -1 ) {
						list.push( node );
					}
				} );
			} catch ( e ) {}
		}
		if ( p.bo.a ) {
			autoTargets().forEach( function ( node ) {
				var inside = list.some( function ( other ) {
					return other !== node && other.contains( node ) && window.getComputedStyle( node ).position === 'sticky';
				} );
				if ( ! inside && list.indexOf( node ) === -1 ) {
					list.push( node );
				}
			} );
		}
		return list;
	}

	function numeric( value, fallback ) {
		var n = parseFloat( value );
		return isNaN( n ) ? fallback : n;
	}

	function transitionTop( node, prop ) {
		if ( reduceMotion ) {
			return;
		}
		var cs = window.getComputedStyle( node );
		var props = cs.transitionProperty || 'none';
		var none = props === 'none' || /^0s(,\s*0s)*$/.test( cs.transitionDuration || '0s' );
		// Le transizioni del tema restano: si anima solo se l'elemento non ne ha.
		if ( none ) {
			node.style.transition = prop + ' 0.3s ease';
		}
	}

	function shiftOn( p ) {
		var root = el( p );
		var box = root ? root.querySelector( '.grs-pop__box' ) : null;
		if ( ! box ) {
			return;
		}
		clearTimeout( shift.timer );
		if ( shift.p !== p ) {
			restoreShift();
			shift.p = p;
			shift.h = -1;
			shift.items = shiftTargets( p ).map( function ( node ) {
				var cs = window.getComputedStyle( node );
				var base = numeric( cs.top, node.getBoundingClientRect().top );
				var item = { n: node, top: node.style.getPropertyValue( 'top' ), pri: node.style.getPropertyPriority( 'top' ), tr: node.style.transition };
				transitionTop( node, 'top' );
				node.style.setProperty( 'top', 'calc(' + base + 'px + var(--grs-pop-bar-h, 0px))', item.pri );
				return item;
			} );
			var body = document.body;
			var html = document.documentElement;
			shift.body = { pt: body.style.paddingTop, tr: body.style.transition };
			shift.html = { sp: html.style.scrollPaddingTop };
			var bcs = window.getComputedStyle( body );
			transitionTop( body, 'padding-top' );
			body.style.paddingTop = 'calc(' + numeric( bcs.paddingTop, 0 ) + 'px + var(--grs-pop-bar-h, 0px))';
			html.style.scrollPaddingTop = 'calc(' + numeric( window.getComputedStyle( html ).scrollPaddingTop, 0 ) + 'px + var(--grs-pop-bar-h, 0px))';
			html.classList.add( 'grs-pop-has-bar-top' );
			if ( window.ResizeObserver ) {
				shift.ro = new ResizeObserver( function () {
					if ( shift.p === p && p.isOpen ) {
						setShiftH( box.offsetHeight );
					}
				} );
				shift.ro.observe( box );
			}
		}
		setShiftH( box.offsetHeight );
	}

	function setShiftH( h ) {
		if ( h === shift.h ) {
			return;
		}
		shift.h = h;
		document.documentElement.style.setProperty( '--grs-pop-bar-h', h + 'px' );
	}

	function shiftOff() {
		if ( ! shift.p ) {
			return;
		}
		setShiftH( 0 );
		clearTimeout( shift.timer );
		// Si tolgono gli stili dopo la transizione di ritorno.
		shift.timer = setTimeout( restoreShift, reduceMotion ? 0 : 350 );
	}

	function restoreShift() {
		clearTimeout( shift.timer );
		if ( shift.ro ) {
			shift.ro.disconnect();
			shift.ro = null;
		}
		shift.items.forEach( function ( item ) {
			if ( item.top ) {
				item.n.style.setProperty( 'top', item.top, item.pri );
			} else {
				item.n.style.removeProperty( 'top' );
			}
			item.n.style.transition = item.tr;
		} );
		shift.items = [];
		if ( shift.body ) {
			document.body.style.paddingTop = shift.body.pt;
			document.body.style.transition = shift.body.tr;
			document.documentElement.style.scrollPaddingTop = shift.html.sp;
			shift.body = null;
			shift.html = null;
		}
		var html = document.documentElement;
		html.classList.remove( 'grs-pop-has-bar-top' );
		html.style.removeProperty( '--grs-pop-bar-h' );
		shift.p = null;
		shift.h = -1;
	}

	// La prima barra in alto aperta (fissa, con lo spostamento attivo) decide lo spostamento.
	function syncShift() {
		var active = null;
		Object.keys( popups ).forEach( function ( key ) {
			var q = popups[ key ];
			if ( ! active && q.isOpen && q.bo && q.bar !== 'push' && q.conf && q.conf.f === 'bar' && q.conf.p === 'top' ) {
				active = q;
			}
		} );
		if ( active ) {
			shiftOn( active );
		} else {
			shiftOff();
		}
	}

	/* Linguetta per riaprire dopo la chiusura ---------------------------- */

	function reopenBtn( p ) {
		if ( p.roBtn === undefined ) {
			p.roBtn = document.querySelector( '[data-grs-pop-reopen="' + p.id + '"]' );
		}
		return p.roBtn;
	}

	function reopenPos( p, conf ) {
		if ( p.ro.pos !== 'auto' ) {
			return p.ro.pos;
		}
		if ( conf.f === 'bar' ) {
			return conf.p === 'top' ? 'top-right' : 'bottom-right';
		}
		return conf.p === 'top-left' || conf.p === 'top-right' || conf.p === 'bottom-left' ? conf.p : 'bottom-right';
	}

	function reopenAllowed( p ) {
		if ( ! p.ro || p.isOpen || p.expired || p.in ) {
			return false;
		}
		var conf = devConf( p );
		if ( ! conf || ! conf.on || ( conf.f !== 'bar' && conf.f !== 'slidein' ) ) {
			return false;
		}
		var st = pstate( p.id );
		if ( ! st.cl ) {
			return false;
		}
		if ( st.cv ) {
			if ( p.fq.after_convert === 'never' ) {
				return false;
			}
			if ( p.fq.after_convert === 'days' && Date.now() - st.cv < p.fq.after_convert_days * DAY ) {
				return false;
			}
		}
		return conditionsPass( p );
	}

	// Bordo inferiore dell'header (primo elemento spostato visibile) o distanza impostata.
	function reopenTop( p ) {
		var base = adminBarH();
		if ( p.bo ) {
			var nodes;
			if ( shift.p === p && shift.items.length ) {
				// Barra appena chiusa: gli elementi sono ancora spostati (la ricerca non li troverebbe).
				nodes = shift.items.map( function ( item ) {
					return item.n;
				} );
			} else {
				if ( ! p.anchors ) {
					p.anchors = shiftTargets( p );
				}
				nodes = p.anchors;
			}
			for ( var i = 0; i < nodes.length; i++ ) {
				var node = nodes[ i ];
				var pos = window.getComputedStyle( node ).position;
				if ( pos === 'fixed' || pos === 'sticky' ) {
					var r = node.getBoundingClientRect();
					if ( r.height > 0 && r.bottom > base && r.top <= base + 60 ) {
						return Math.round( r.bottom );
					}
				}
			}
		}
		return base + ( p.ro.top || 0 );
	}

	function placeReopen( p, btn ) {
		var conf = devConf( p );
		var pos = reopenPos( p, conf );
		btn.className = btn.className.replace( /\sgrs-pop-reopen--pos-[a-z-]+/g, '' ) + ' grs-pop-reopen--pos-' + pos;
		btn.style.top = pos.indexOf( 'top' ) === 0 ? reopenTop( p ) + 'px' : '';
	}

	function updateReopen( p, focus ) {
		var btn = p.ro ? reopenBtn( p ) : null;
		if ( ! btn ) {
			return;
		}
		clearTimeout( btn.grsTimer );
		clearTimeout( btn.grsPlace );
		if ( reopenAllowed( p ) ) {
			placeReopen( p, btn );
			if ( ! p.roBound ) {
				p.roBound = true;
				btn.addEventListener( 'click', function () {
					emit( 'grs-pop:reopen', p );
					p.viaReopen = true;
					lastFocus = btn;
					open( p, { force: true, focus: true, reopen: true } );
				} );
			}
			if ( btn.hidden ) {
				btn.hidden = false;
				void btn.offsetWidth;
			}
			btn.classList.add( 'is-visible' );
			if ( p.bo && shift.p === p ) {
				// L'header sta tornando su: posizione definitiva a transizione finita.
				btn.grsPlace = setTimeout( function () {
					placeReopen( p, btn );
				}, reduceMotion ? 0 : 400 );
			}
			if ( focus ) {
				try {
					btn.focus( { preventScroll: true } );
				} catch ( e ) {}
			}
		} else if ( ! btn.hidden ) {
			btn.classList.remove( 'is-visible' );
			btn.grsTimer = setTimeout( function () {
				btn.hidden = true;
			}, reduceMotion ? 0 : 300 );
		}
	}

	function updateAllReopen() {
		Object.keys( popups ).forEach( function ( key ) {
			if ( popups[ key ].ro ) {
				updateReopen( popups[ key ] );
			}
		} );
	}

	// L'header può cambiare altezza scorrendo: la linguetta in alto lo segue.
	var reopenFrame = null;
	window.addEventListener( 'scroll', function () {
		if ( reopenFrame ) {
			return;
		}
		reopenFrame = window.requestAnimationFrame( function () {
			reopenFrame = null;
			Object.keys( popups ).forEach( function ( key ) {
				var q = popups[ key ];
				var btn = q.ro && q.bo ? reopenBtn( q ) : null;
				if ( btn && ! btn.hidden && btn.classList.contains( 'is-visible' ) ) {
					placeReopen( q, btn );
				}
			} );
		} );
	}, { passive: true } );

	/* Linguetta flottante ------------------------------------------------ */

	function showTab( p, noCount ) {
		var tab = document.querySelector( '[data-grs-pop-tab="' + p.id + '"]' );
		if ( ! tab ) {
			return;
		}
		var conf = devConf( p );
		tab.className = tab.className.replace( /\sgrs-pop-tab--pos-[a-z-]+/g, '' ) + ' grs-pop-tab--pos-' + conf.p;
		tab.hidden = false;
		void tab.offsetWidth;
		tab.classList.add( 'is-visible' );
		p.tab = tab;
		if ( ! p.tabBound ) {
			p.tabBound = true;
			tab.addEventListener( 'click', function () {
				if ( p.isOpen ) {
					close( p, 'tab' );
				} else {
					open( p, { panel: true, force: true } );
				}
			} );
			if ( noCount ) {
				return;
			}
			// La linguetta conta come visualizzazione.
			var st = pstate( p.id );
			st.n = ( st.n || 0 ) + 1;
			st.last = Date.now();
			session.p[ p.id ] = session.p[ p.id ] || {};
			session.p[ p.id ].v = 1;
			save();
			track( p, 'v' );
			emit( 'grs-pop:view', p );
		}
	}

	/* Conto alla rovescia ------------------------------------------------ */

	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	function startCountdowns( p, root ) {
		var list = root.querySelectorAll( '[data-grs-pop-countdown]' );
		Array.prototype.forEach.call( list, function ( cd ) {
			if ( cd.grsStarted ) {
				return;
			}
			cd.grsStarted = true;
			var end = parseInt( cd.getAttribute( 'data-end' ) || '0', 10 );
			var minutes = parseInt( cd.getAttribute( 'data-minutes' ) || '0', 10 );
			if ( ! end && minutes ) {
				var st = pstate( p.id );
				if ( ! st.cd ) {
					st.cd = Date.now();
					save();
				}
				end = st.cd + minutes * 60000;
			}
			var tick = function () {
				var left = Math.max( 0, end - Date.now() );
				var s = Math.floor( left / 1000 );
				var vals = { d: Math.floor( s / 86400 ), h: Math.floor( ( s % 86400 ) / 3600 ), m: Math.floor( ( s % 3600 ) / 60 ), s: s % 60 };
				Array.prototype.forEach.call( cd.querySelectorAll( '[data-u]' ), function ( node ) {
					node.textContent = pad( vals[ node.getAttribute( 'data-u' ) ] );
				} );
				var dayUnit = cd.querySelector( '[data-u="d"]' );
				if ( dayUnit && dayUnit.parentNode ) {
					dayUnit.parentNode.hidden = vals.d === 0;
				}
				if ( left <= 0 ) {
					clearInterval( cd.grsTimer );
					var mode = cd.getAttribute( 'data-expire' );
					if ( mode === 'hide_popup' ) {
						if ( p.isOpen ) {
							close( p, 'silent' );
						}
						root.hidden = true;
						if ( p.tab ) {
							p.tab.hidden = true;
						}
						p.expired = true;
						updateReopen( p );
					} else if ( mode === 'hide_section' ) {
						var sec = cd.closest( '.grs-pop__sec' );
						if ( sec ) {
							sec.hidden = true;
						}
					}
				}
			};
			tick();
			cd.grsTimer = setInterval( tick, 1000 );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Trigger                                                              */
	/* ------------------------------------------------------------------ */

	function fire( p ) {
		if ( p.fired || p.expired ) {
			return;
		}
		p.fired = true;
		( p.cleanup || [] ).forEach( function ( fn ) {
			fn();
		} );
		if ( ! formsPass( p ) ) {
			return; // Form inviato nella pagina dopo l'avvio: la regola non è più soddisfatta.
		}
		open( p );
	}

	function listen( p, target, type, fn, options ) {
		target.addEventListener( type, fn, options || { passive: true } );
		p.cleanup.push( function () {
			target.removeEventListener( type, fn, options || { passive: true } );
		} );
	}

	function scrollPercent() {
		var doc = document.documentElement;
		var h = Math.max( doc.scrollHeight, document.body.scrollHeight ) - window.innerHeight;
		return h <= 0 ? 100 : ( window.pageYOffset || doc.scrollTop ) / h * 100;
	}

	function setupTriggers( p ) {
		var t = p.tr;
		var dev = device();
		var any = false;
		p.cleanup = [];

		if ( t.load.on ) {
			any = true;
			var timer = setTimeout( function () {
				fire( p );
			}, t.load.delay * 1000 );
			p.cleanup.push( function () {
				clearTimeout( timer );
			} );
		}
		if ( t.scroll.on ) {
			any = true;
			var onScroll = function () {
				if ( scrollPercent() >= t.scroll.percent ) {
					fire( p );
				}
			};
			listen( p, window, 'scroll', onScroll );
			onScroll();
		}
		if ( t.exit.on ) {
			var finePointer = window.matchMedia && window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;
			if ( dev === 'desktop' || finePointer ) {
				any = true;
				listen( p, document, 'mouseout', function ( e ) {
					if ( ! e.relatedTarget && e.clientY <= 5 ) {
						fire( p );
					}
				} );
			} else if ( t.exit.mobile === 'scroll_up' ) {
				// Su touch: scorrimento rapido verso l'alto dopo aver letto almeno un terzo della pagina.
				any = true;
				var lastY = window.pageYOffset;
				var lastT = Date.now();
				listen( p, window, 'scroll', function () {
					var y = window.pageYOffset;
					var now = Date.now();
					var speed = ( lastY - y ) / Math.max( 1, now - lastT );
					if ( speed > 1.2 && lastY - y > 120 && scrollPercent() > 15 && store.pv >= 1 && now - NOW > 8000 ) {
						fire( p );
					}
					lastY = y;
					lastT = now;
				} );
			}
		}
		if ( t.inactivity.on ) {
			any = true;
			var idle = null;
			var reset = function () {
				clearTimeout( idle );
				idle = setTimeout( function () {
					fire( p );
				}, t.inactivity.seconds * 1000 );
			};
			[ 'mousemove', 'keydown', 'scroll', 'touchstart', 'click' ].forEach( function ( type ) {
				listen( p, window, type, reset );
			} );
			p.cleanup.push( function () {
				clearTimeout( idle );
			} );
			reset();
		}
		if ( t.element.on && t.element.selector && window.IntersectionObserver ) {
			var targets;
			try {
				targets = document.querySelectorAll( t.element.selector );
			} catch ( e ) {
				targets = [];
			}
			if ( targets.length ) {
				any = true;
				var io = new IntersectionObserver( function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting ) {
							fire( p );
						}
					} );
				}, { threshold: 0.3 } );
				Array.prototype.forEach.call( targets, function ( node ) {
					io.observe( node );
				} );
				p.cleanup.push( function () {
					io.disconnect();
				} );
			}
		}
		return any;
	}

	/* ------------------------------------------------------------------ */
	/* Interazioni (delegate su document)                                   */
	/* ------------------------------------------------------------------ */

	function popupFromNode( node ) {
		var root = node.closest ? node.closest( '.grs-pop' ) : null;
		return root ? popups[ root.getAttribute( 'data-grs-pop-id' ) ] : null;
	}

	function openById( id, opener ) {
		var p = popups[ String( id ) ];
		if ( ! p ) {
			return false;
		}
		if ( p.in ) {
			var box = el( p );
			if ( box ) {
				box.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' } );
			}
			return true;
		}
		var conf = devConf( p );
		lastFocus = opener || document.activeElement;
		return open( p, { force: true, panel: conf.f === 'tab', focus: true } );
	}

	function copyText( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			var area = document.createElement( 'textarea' );
			area.value = text;
			area.setAttribute( 'readonly', '' );
			area.style.position = 'fixed';
			area.style.opacity = '0';
			document.body.appendChild( area );
			area.select();
			try {
				document.execCommand( 'copy' );
				resolve();
			} catch ( e ) {
				reject( e );
			}
			document.body.removeChild( area );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		var target = e.target;
		if ( ! target || ! target.closest ) {
			return;
		}

		// Chiusure.
		var closer = target.closest( '[data-grs-pop-close]' );
		if ( closer ) {
			var pc = popupFromNode( closer );
			if ( pc ) {
				var why = closer.getAttribute( 'data-grs-pop-close' );
				if ( why === 'overlay' && ! pc.cl.overlay ) {
					return;
				}
				e.preventDefault();
				if ( pc.in ) {
					var inlineRoot = el( pc );
					if ( inlineRoot ) {
						inlineRoot.hidden = true;
					}
					var sti = pstate( pc.id );
					sti.cl = Date.now();
					save();
					track( pc, 'x' );
					emit( 'grs-pop:close', pc, { reason: why } );
				} else {
					close( pc, why );
				}
				return;
			}
		}

		// Copia del codice sconto.
		var copy = target.closest( '[data-grs-pop-copy]' );
		if ( copy ) {
			e.preventDefault();
			var pcp = popupFromNode( copy );
			var code = copy.getAttribute( 'data-grs-pop-copy' );
			var done = function () {
				var label = copy.textContent;
				copy.textContent = copy.getAttribute( 'data-copied' ) || label;
				copy.classList.add( 'is-copied' );
				var live = copy.parentNode.querySelector( '.grs-pop__sr' );
				if ( live ) {
					live.textContent = copy.textContent;
				}
				setTimeout( function () {
					copy.textContent = label;
					copy.classList.remove( 'is-copied' );
				}, 2500 );
			};
			copyText( code ).then( done, function () {
				var codeEl = copy.parentNode.querySelector( '.grs-pop__code' );
				if ( codeEl && window.getSelection ) {
					var range = document.createRange();
					range.selectNodeContents( codeEl );
					window.getSelection().removeAllRanges();
					window.getSelection().addRange( range );
				}
			} );
			if ( pcp ) {
				track( pcp, 'k' );
				convert( pcp, 'coupon', { code: code } );
			}
			return;
		}

		// Pulsanti che contano come conversione.
		var conv = target.closest( '[data-grs-pop-convert]' );
		if ( conv ) {
			var pv = popupFromNode( conv );
			if ( pv ) {
				track( pv, 'k' );
				convert( pv, 'click', { url: conv.getAttribute( 'href' ) || '' } );
				flush();
			}
		}

		// Apertura da link o pulsanti: #grs-pop-ID, .grs-pop-open-ID, [data-grs-pop-open=ID], selettori personalizzati.
		var opener = target.closest( 'a[href*="#grs-pop-"], [data-grs-pop-open], [class*="grs-pop-open-"]' );
		var id = null;
		if ( opener ) {
			var dataId = opener.getAttribute( 'data-grs-pop-open' );
			var href = opener.getAttribute( 'href' ) || '';
			var m = href.match( /#grs-pop-(\d+)$/ );
			var c = ( opener.getAttribute( 'class' ) || '' ).match( /grs-pop-open-(\d+)/ );
			id = dataId || ( m && m[ 1 ] ) || ( c && c[ 1 ] );
		}
		if ( ! id ) {
			for ( var key in popups ) {
				if ( Object.prototype.hasOwnProperty.call( popups, key ) && popups[ key ].tr.click.selector ) {
					try {
						var hit = target.closest( popups[ key ].tr.click.selector );
						if ( hit && ! hit.closest( '.grs-pop' ) ) {
							opener = hit;
							id = key;
							break;
						}
					} catch ( err ) {}
				}
			}
		}
		if ( id && popups[ String( id ) ] ) {
			var current = popupFromNode( opener );
			if ( current && ! current.in && String( current.id ) !== String( id ) ) {
				close( current, 'silent' );
			}
			if ( openById( id, opener ) ) {
				e.preventDefault();
			}
		}
	} );

	// Esc e trappola del focus nelle finestre che coprono la pagina.
	document.addEventListener( 'keydown', function ( e ) {
		var top = openIntrusive[ openIntrusive.length - 1 ];
		var active = top;
		if ( ! active ) {
			var node = document.activeElement ? popupFromNode( document.activeElement ) : null;
			active = node && node.isOpen ? node : null;
		}
		if ( ! active ) {
			return;
		}
		if ( e.key === 'Escape' || e.key === 'Esc' ) {
			if ( active.cl.esc ) {
				close( active, 'esc' );
			}
			return;
		}
		if ( e.key === 'Tab' && top ) {
			var list = focusables( el( top ) );
			if ( ! list.length ) {
				e.preventDefault();
				return;
			}
			var first = list[ 0 ];
			var last = list[ list.length - 1 ];
			var topBox = el( top ).querySelector( '.grs-pop__box' );
			if ( e.shiftKey && ( document.activeElement === first || document.activeElement === topBox || ! el( top ).contains( document.activeElement ) ) ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	} );

	// Form di GRS Forms inviato da dentro un popup.
	document.addEventListener( 'grs-forms:submitted', function ( e ) {
		var d = e.detail || {};
		rememberForm( d.form_id );
		var forms = document.querySelectorAll( '.grs-pop form[data-grs-form="' + String( d.form_id ).replace( /[^0-9a-z_-]/gi, '' ) + '"]' );
		Array.prototype.forEach.call( forms, function ( form ) {
			var p = popupFromNode( form );
			if ( ! p || ( ! p.in && ! p.isOpen ) ) {
				return;
			}
			convert( p, 'form', { form_id: d.form_id, event_id: d.event_id || '', lead_tracked: d.track !== false } );
			flush();
			if ( ! p.in && p.cl.after_form === 'close' ) {
				setTimeout( function () {
					close( p, 'convert' );
				}, p.cl.after_form_delay * 1000 );
			}
		} );
	} );

	// Cambio di orientamento o dimensione: riallinea il formato dei popup aperti.
	var resizeTimer = null;
	window.addEventListener( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( function () {
			Object.keys( popups ).forEach( function ( key ) {
				var p = popups[ key ];
				p.anchors = null;
				if ( p.isOpen ) {
					var root = el( p );
					var conf = applyFormat( p, root );
					if ( ! conf.on ) {
						close( p, 'silent' );
					} else {
						p.conf = conf;
					}
				}
			} );
			// Cambio di formato (es. barra su desktop, slide-in su smartphone): spostamento da rifare.
			if ( shift.p ) {
				restoreShift();
			}
			syncShift();
			updateAllReopen();
		}, 200 );
	} );

	/* ------------------------------------------------------------------ */
	/* Avvio                                                                */
	/* ------------------------------------------------------------------ */

	function initInline( p ) {
		var root = el( p );
		if ( ! root ) {
			return;
		}
		if ( ! conditionsPass( p ) || ( p.fq.after_convert === 'never' && pstate( p.id ).cv ) || ( pstate( p.id ).cl && Date.now() - pstate( p.id ).cl < Math.max( 1, p.fq.after_close_days ) * DAY ) ) {
			root.hidden = true;
			return;
		}
		startCountdowns( p, root );
		if ( window.IntersectionObserver ) {
			var io = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						io.disconnect();
						track( p, 'v' );
						emit( 'grs-pop:view', p );
					}
				} );
			}, { threshold: 0.5 } );
			io.observe( root );
		}
	}

	data.popups.forEach( function ( p ) {
		popups[ String( p.id ) ] = p;
	} );

	// Anteprima dalla bacheca: apre subito il popup, senza regole né statistiche.
	if ( data.preview && popups[ String( data.preview ) ] ) {
		var pp = popups[ String( data.preview ) ];
		var conf = devConf( pp );
		open( pp, { force: true, panel: conf.f === 'tab', noCount: true } );
	}

	// Ordine: priorità più alta prima.
	data.popups.slice().sort( function ( a, b ) {
		return b.pr - a.pr;
	} ).forEach( function ( p ) {
		if ( String( p.id ) === String( data.preview ) ) {
			return;
		}
		if ( p.in ) {
			initInline( p );
			return;
		}
		if ( p.od ) {
			return; // Solo all'apertura con un clic.
		}
		var conf = devConf( p );
		if ( ! conf || ! conf.on || ! conditionsPass( p ) || ! frequencyPass( p ) ) {
			return;
		}
		if ( ! setupTriggers( p ) ) {
			return; // Nessun trigger automatico: si apre solo con un clic.
		}
	} );

	// Linguette per riaprire i popup già chiusi (anche quando la frequenza ne blocca l'apertura).
	updateAllReopen();

	// Apertura da un indirizzo con #grs-pop-ID.
	var hash = window.location.hash.match( /^#grs-pop-(\d+)$/ );
	if ( hash && popups[ hash[ 1 ] ] ) {
		openById( hash[ 1 ] );
	}

	// API minima per integrazioni: window.grsPop.open( id ), window.grsPop.close( id ).
	window.grsPop = {
		open: function ( id ) {
			return openById( id );
		},
		close: function ( id ) {
			var p = popups[ String( id ) ];
			if ( p ) {
				close( p, 'api' );
			}
		},
	};
} )();
