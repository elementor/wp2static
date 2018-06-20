/**
 * Dockable.
 **/
(function($){
	$.widget("db.dockable", $.ui.mouse, {
		options: {
			handle: false,
			axis: 'y',
			resize: function() {},
			resized: function() {}
		},
		_create: function() {
			if ( this.options.axis == 'x' ) {
				this.page = 'pageX';
				this.dimension = 'width';
			} else {
				this.page = 'pageY';
				this.dimension = 'height';
			}

			if ( ! this.options.handle )
				return;

			this.handle = $( this.options.handle );

			this._mouseInit();
		},
		_handoff: function() {
			return {
				element: this.element,
				handle: this.handle,
				axis: this.options.axis
			};
		},
		_mouseStart: function(event) {
			this._trigger( "start", event, this._handoff() );
			this.d0 = this.element[this.dimension]() + event[this.page];
		},
		_mouseDrag: function(event) {
			var resize = this._trigger( "resize", event, this._handoff() );

			// If the resize event returns false, we don't resize.
			if ( resize === false )
				return;

			this.element[this.dimension]( this.d0 - event[this.page] );
			this._trigger( "resized", event, this._handoff() );
		},
		_mouseCapture: function(event) {
			return !this.options.disabled && event.target == this.handle[0];
		},
		_mouseStop: function(event) {
			this._trigger( "stop", event, this._handoff() );
		}
	});
})(jQuery);