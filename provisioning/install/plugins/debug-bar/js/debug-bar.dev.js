var wpDebugBar;

( function( $ ) {
	var api;

	wpDebugBar = api = {
		// The element that we will pad to prevent the debug bar
		// from overlapping the bottom of the page.
		body: undefined,

		init: function init() {
			// If we're not in the admin, pad the body.
			api.body = $( document.body );

			api.toggle.init();
			api.tabs();
			api.actions.init();
		},

		isVisible: function isVisible() {
			return api.body.hasClass( 'debug-bar-visible' );
		},

		toggle: {
			init: function init() {
				$( '#wp-admin-bar-debug-bar' ).click( function onClickAdminBarMenu( event ) {
					event.preventDefault();

					// Click on submenu item.
					if ( event.target.hash ) {
						var $menuLink = $( event.target.rel );

						// Open/close debug bar.
						if ( ! api.isVisible() ) {
							api.toggle.visibility();
						} else if ( $menuLink.hasClass( 'current' ) ) {
							$menuLink.removeClass( 'current' );
							api.toggle.visibility();

							return;
						}

						// Deselect other tabs and hide other panels.
						$( '.debug-menu-target' ).hide().trigger( 'debug-bar-hide' );
						$( '.debug-menu-link' ).removeClass( 'current' );

						$menuLink.addClass( 'current' );
						$( event.target.hash ).show().trigger( 'debug-bar-show' );
					} else {
						api.toggle.visibility();
					}
				} );
			},
			visibility: function visibility( show ) {
				show = typeof show == 'undefined' ? ! api.isVisible() : show;

				// Show/hide the debug bar.
				api.body.toggleClass( 'debug-bar-visible', show );

				// Press/unpress the button.
				$( this ).toggleClass( 'active', show );
			}
		},

		tabs: function tabs() {
			var debugMenuLinks = $( '.debug-menu-link' ),
				debugMenuTargets = $( '.debug-menu-target' );

			debugMenuLinks.click( function onClickLink( event ) {
				var $this = $( this );

				event.preventDefault();

				if ( $this.hasClass( 'current' ) ) {
					return;
				}

				// Deselect other tabs and hide other panels.
				debugMenuTargets.hide().trigger( 'debug-bar-hide' );
				debugMenuLinks.removeClass( 'current' );

				// Select the current tab and show the current panel.
				$this.addClass( 'current' );
				// The hashed component of the href is the id that we want to display.
				$( '#' + this.href.substr( this.href.indexOf( '#' ) + 1 ) ).show().trigger( 'debug-bar-show' );
			} );
		},

		actions: {
			init: function init() {
				var actions = $( '#debug-bar-actions' );

				// Close the panel with the esc key if it's open.
				$( document ).keydown( function maybeClosePanel( event ) {
					var key = event.key || event.which || event.keyCode;

					if ( 27 /* esc */ === key && api.isVisible() ) {
						event.preventDefault();
						api.actions.close();
					}
				} );

				$( '.maximize', actions ).click( api.actions.maximize );
				$( '.restore',  actions ).click( api.actions.restore  );
				$( '.close',    actions ).click( api.actions.close    );
			},
			maximize: function maximize() {
				api.body.removeClass( 'debug-bar-partial' );
				api.body.addClass( 'debug-bar-maximized' );
			},
			restore: function restore() {
				api.body.removeClass( 'debug-bar-maximized' );
				api.body.addClass( 'debug-bar-partial' );
			},
			close: function close() {
				api.toggle.visibility( false );
			}
		}
	};

	$( wpDebugBar.init );

} )( jQuery );
