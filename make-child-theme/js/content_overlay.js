( function( $, _ ) {
	oneApp.views.overlays.content = Backbone.View.extend( {
		events: {
			'click .ttfmake-overlay-close-update': 'onUpdate',
			'click .ttfmake-overlay-close-discard': 'onDiscard',
			'click .ttfmake-overlay-wrapper': 'onWrapperClick',
		},

		editor: false,
		$editor: false,
		$textarea: false,

		initialize: function() {
			// this.model is the section origin model
			// and is set automatically through
			// the configuration parameter
			this.changeset = new Backbone.Model();
			this.$editor = $( '#wp-make-wrap' );
			this.$textarea = $( '#make' );
			this.editor = tinyMCE.get( 'make' );

			return this.render();
		},

		render: function() {
			this.setElement( document.getElementById( 'ttfmake-tinymce-overlay' ) );

			return this;
		},

		open: function() {
			var $body = $( 'body' );

			// Fill editor with current content
			this.setContent();

			// Show the overlay
			$body.addClass( 'modal-open' );
			this.$el.css( 'display', 'table' );

			if ( 'visual' === this.getMode() ) {
				// Focus on visual editor
				this.editor.focus();
			} else {
				// Focus on code editor
				this.$textarea.focus();
			}
		},

		setContent: function () {
			if ( 'visual' === this.getMode() ) {
				this.editor.setContent( switchEditors.wpautop( this.model.get( 'content' ) ) );
			} else {
				this.$textarea.val( switchEditors.pre_wpautop( this.model.get( 'content' ) ) );
			}
		},

		getContent: function() {
			return 'visual' === this.getMode() ?
				this.editor.getContent(): this.$textarea.val();
		},

		getMode: function() {
			return this.$editor.hasClass( 'tmce-active' ) ? 'visual' : 'text';
		},

		onUpdate: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			this.model.set( { content: this.getContent() } );
			this.remove();
		},

		onDiscard: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			this.remove();
		},

		onWrapperClick: function( e ) {
			if ( $( e.target ).is( '.ttfmake-overlay-wrapper' ) ) {
				e.preventDefault();
				e.stopPropagation();

				this.remove();
			}
		},

		remove: function() {
			// Remove view events
			this.undelegateEvents();
			var $body = $( 'body' );
			$body.removeClass( 'modal-open' );
			this.$el.hide();
		},
	} );
} ) ( jQuery, _ );
