'use strict';
jQuery( document ).ready( function( $ ) {
	// show / hide tabs:
	$( '#sistContainer #sistTabs' ).find( 'a' ).click( function() {
		$( '#sistContainer #sistTabs' ).find( 'a' ).removeClass( 'nav-tab-active' );
		$( '#sistContainer .tab-pane' ).removeClass( 'active' );

		var id = $( this ).attr( 'id' ).replace( '-tab', '' );
		$( '#sistContainer #' + id ).addClass( 'active' );
		$( this ).addClass( 'nav-tab-active' );
	} );

	// set active tab on page load:
	var activeTab = window.location.hash.replace( '#tab-', '' );

	// if no tab hash, default to the first tab
	if ( activeTab === '' ) {
		activeTab = $( '#sistContainer .tab-pane' ).attr( 'id' );
	}

	$( '#sistContainer #' + activeTab ).addClass( 'active' );
	$( '#sistContainer #' + activeTab + '-tab' ).addClass( 'nav-tab-active' );

	// pretend the user clicked on the active tab
	$( '#sistContainer .nav-tab-active' ).click();

	// ---------------------------------------------------------------------- //

	// delivery method selection:
	$( '#sistContainer #deliveryMethod' ).change( function() {
		var selected = $( this ).val();
		$( '#sistContainer .delivery-method' ).removeClass( 'active' );
		$( '#sistContainer .' + selected + '.delivery-method' ).addClass( 'active ');
	} );

	// pretend the user selected a value
	$( '#sistContainer #deliveryMethod' ).change();

	// ---------------------------------------------------------------------- //

	$( 'td.url-dest-option' ).click( function() {
		destination_url_type_change( $( this ) );
	} );

	$( '#sistContainer input[type=radio][name=destination_url_type]' ).change( function() {
		destination_url_type_change( $( this ).closest( 'td.url-dest-option' ) );
	} );

	// pretend the user selected a value on page load
	$( '#sistContainer input[type=radio][name=destination_url_type]:checked' ).change();

	function destination_url_type_change( $this ) {
		$( 'td.url-dest-option' ).removeClass( 'active' );
		$this.addClass( 'active' );
		var $radio = $this.find( 'input[type=radio][name=destination_url_type]' );
		$radio.prop( 'checked', true );

		if ( $radio.val() == 'absolute' ) {
			$( '#destinationHost' )
				.prop( 'disabled', false );
			$( '#destinationScheme' )
				.prop( 'disabled', false );
		} else {
			$( '#destinationHost' )
				.val('')
				.prop( 'disabled', true );
			$( '#destinationScheme' )
				.prop( 'disabled', true )
		}

		if ( $radio.val() == 'relative' ) {
			$( '#relativePath' )
				.prop( 'disabled', false );
		} else {
			$( '#relativePath' )
				.val('')
				.prop( 'disabled', true );
		}
	}

	// ---------------------------------------------------------------------- //

	$( '#AddUrlToExclude' ).click( function() {
		var $last_row = $( '.excludable-url-row' ).last();
		var $clone_row = $( '#excludableUrlRowTemplate' ).clone().removeAttr( 'id' );

		var timestamp = new Date().getTime();
		var regex = /excludable\[0\]/g;

		$clone_row.html( $clone_row.html().replace( regex, 'excludable[' + timestamp + ']' ) );
		$clone_row.insertAfter( $last_row );
	} );

	$( '#excludableUrlRows' ).on( 'click', '.remove-excludable-url-row', function() {
		var $row = $( this ).closest( '.excludable-url-row' );
		$row.remove();
	} );

	// ---------------------------------------------------------------------- //

	$( '#basicAuthCredentialsSaved > a' ).click( function(e) {
		e.preventDefault();
		$( '#basicAuthSet' ).addClass( 'hide' );
		$( '#basicAuthUserPass').removeClass( 'hide' ).find( 'input' ).prop( 'disabled', false );
	});
} );
