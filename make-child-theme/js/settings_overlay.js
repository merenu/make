( function( $, _, settings ) {

	var sectionPrototype = oneApp.views.section.prototype;

	oneApp.views.section = oneApp.views.section.extend( {
		onViewReady: function(e) {
			sectionPrototype.onViewReady.apply( this, arguments );

			this.model.on( 'change:title', this.updateTitle, this );
			this.model.on( 'change:draft', this.updateDraftMode, this );
		},

		updateTitle: function() {
			if ( this.model.get( 'title' ) ) {
				this.$el.find('.ttfmake-section-header h3').addClass('has-title');
			} else {
				this.$el.find('.ttfmake-section-header h3').removeClass('has-title');
			}

			this.$headerTitle.html( _.escape( this.model.get( 'title' ) ) );
		},

		updateDraftMode: function() {
			var draft = parseInt( this.model.get( 'draft' ), 10 );

			if ( 1 === draft ) {
				this.$el.find('.ttfmake-section-draft-indicator').show();
			} else {
				this.$el.find('.ttfmake-section-draft-indicator').hide();
			}
		},

		openConfigurationOverlay: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var $target = $( e.currentTarget );
			var $section = $target.parents( '.ttfmake-section' );
			var sectionType = $section.data( 'section-type' );
			var sectionSettings = settings[sectionType];

			if ( oneApp.builder.settingsOverlay ) {
				delete oneApp.builder.settingsOverlay;
			}
			oneApp.builder.settingsOverlay = new oneApp.views.overlays.settings( { model: this.model }, sectionSettings );
			oneApp.builder.settingsOverlay.open();
		}
	} );

	var itemPrototype = oneApp.views.item.prototype;

	oneApp.views.item = oneApp.views.item.extend( {
		onViewReady: function(e) {
			itemPrototype.onViewReady.apply( this, arguments );

			this.updateFrame();

			this.model.on( 'change', this.onModelChange, this );
			this.model.on( 'change:content', this.onContentChange, this );
		},

		openConfigurationOverlay: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var $target = $( e.currentTarget );
			// This can be refactored to be read from model itself!!!
			var $item = $target.parents( '[data-section-type]' );
			var itemType = $item.data( 'section-type' );
			var itemSettings = settings[itemType];

			if ( oneApp.builder.settingsOverlay ) {
				delete oneApp.builder.settingsOverlay;
			}
			oneApp.builder.settingsOverlay = new oneApp.views.overlays.settings( { model: this.model }, itemSettings );
			oneApp.builder.settingsOverlay.open();
		},

		onContentEdit: function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $target = $(e.currentTarget);
			var iframeID = ($target.attr('data-iframe')) ? $target.attr('data-iframe') : '';
			var textAreaID = $target.attr('data-textarea');
			var $overlay = oneApp.builder.tinymceOverlay.$el;

			if ( oneApp.builder.contentOverlay ) {
				oneApp.builder.contentOverlay.remove();
			}
			oneApp.builder.contentOverlay = new oneApp.views.overlays.content( { model: this.model } )
			oneApp.builder.contentOverlay.open();
		},

		onModelChange: function() {
			this.$el.trigger( 'model-item-change' );
		},

		onContentChange: function() {
			var content = this.model.get( 'content' );
			this.updateFrame();
		},

		updateFrame: function() {
			var content = switchEditors.wpautop( this.wrapShortcodes( this.model.get( 'content' ) ) );
			var $iframe = $('iframe', this.$el);
			var iframe = $iframe.get( 0 );
			var iframeContent = iframe.contentDocument ? iframe.contentDocument : iframe.contentWindow.document;
			var $iframeHead = $('head', iframeContent);
			var $iframeBody = $('body', iframeContent);

			$iframeHead.html( this.getFrameHeadLinks() );
			$iframeBody.html( content );

			// Firefox hack
			// @link http://stackoverflow.com/a/24686535

			var self = this;
			$iframe.on( 'load', function() {
				$( this ).contents().find( 'head' ).html( link );
				$( this ).contents().find( 'body' ).html( content );
			});
		},

		getFrameHeadLinks: function() {
			var scripts = tinyMCEPreInit.mceInit.make.content_css.split(','),
				link = '';

			// Create the CSS links for the head
			_.each(scripts, function(e) {
				link += '<link type="text/css" rel="stylesheet" href="' + e + '" />';
			});

			return link;
		},

		wrapShortcodes: function(content) {
			// Render captions
			content = content.replace(
				/\[caption.*?\](\<img.*?\/\>)[ ]*(.*?)\[\/caption\]/g,
				'<div><dl class="wp-caption alignnone">'
				+ '<dt class="wp-caption-dt">$1</dt>'
				+ '<dd class="wp-caption-dd">$2</dd></dl></div>'
			);

			return content.replace( /^(<p>)?(\[.*\])(<\/p>)?$/gm, '<div class="shortcode-wrapper">$2</div>' );
		},
	} );

	oneApp.views.overlays = {};

	/**
	 *
	 * Settings overlay class
	 *
	 */
	oneApp.views.overlays.settings = Backbone.View.extend( {
		template: wp.template( 'ttfmake-settings' ),
		className: 'ttfmake-overlay ttfmake-configuration-overlay',

		events: {
			'click .ttfmake-overlay-close-update': 'onUpdate',
			'click .ttfmake-overlay-close-discard': 'onDiscard',
			'click .ttfmake-overlay-wrapper': 'onWrapperClick',
			'setting-updated': 'onSettingUpdated',
		},

		initialize: function( configuration, settings ) {
			// this.model is the section origin model
			// and is set automatically through
			// the configuration parameter
			this.settings = settings;
			this.changeset = new Backbone.Model();
			this.controls = {};

			return this.render();
		},

		render: function() {
			this.$el.html( this.template( { body: '' } ) );

			var $body = $( '.ttfmake-overlay-body', this.$el );

			// Render controls
			_( this.settings ).each( function( setting ) {
				var view = oneApp.views.settings[setting.type];

				if ( view ) {
					var control = new view( setting );
					this.controls[setting.name] = control;
					$body.append( control.render().$el );
				}
			}, this );

			// Wrap controls in divs according to dividers
			$( '.ttfmake-configuration-divider-wrap', this.$el ).each( function() {
				$( this ).nextUntil( '.ttfmake-configuration-divider-wrap' ).wrapAll( '<div />' );
			} );

			// Apply section data from section model
			this.applyValues( this.model.toJSON() );

			return this;
		},

		open: function() {
			var $body = $( 'body' );
			$body.addClass( 'modal-open' );

			// Show the overlay
			$body.append( this.$el );
			this.$el.css( 'display', 'table' );
			$body.on( 'keydown', this.onKeyDown.bind( this ) );

			// Scroll to the open divider
			var $overlay = $( '.ttfmake-overlay-body', this.$el );
			var $dividers = $( '.ttfmake-configuration-divider-wrap', this.$el );

			if ( ! $dividers.length ) {
				return;
			}

			// This can later be removed ...
			$dividers.removeClass( 'open-wrap' );
			// ... together with the `open` class in the settings

			var $divider = $dividers.first();
			if ( this.model.get( 'open-divider' ) ) {
				var name = this.model.get( 'open-divider' );
				$divider = $( '[data-name="' + name + '"]', this.$el ).parent();
			}

			$divider.addClass( 'open-wrap' );
			var offset = $divider.position().top + $overlay.scrollTop() - $divider.outerHeight();
			$overlay.scrollTop( offset );
		},

		applyValues: function( values ) {
			for ( var field in values ) {
				var value = values[field];
				var control = this.controls[field];

				if ( control ) {
					control.setValue( value );
				}
			}
		},

		onSettingUpdated: function( e, setting ) {
			console.log( 'Setting updated: ', setting.name, setting.value );
			this.changeset.set( setting.name, setting.value );

			if ( setting.immediate ) {
				this.model.set( setting.name, setting.value );
			}
		},

		onUpdate: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			this.model.set( this.changeset.toJSON() );
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

		onKeyDown: function( e ) {
			if (27 == e.keyCode) {
				e.preventDefault();
				e.stopPropagation();
				this.remove();
			}
		},

		remove: function() {
			for ( var name in this.controls ) {
				this.controls[name].remove();
			}

			Backbone.View.prototype.remove.apply( this, arguments );

			var $body = $( 'body' );
			$body.removeClass( 'modal-open' );
			$body.off( 'keydown', this.onKeyDown.bind( this ) );
		}
	} );

	/**
	 *
	 * Control base class
	 *
	 */
	oneApp.views.settings.control = Backbone.View.extend( {
		className: 'ttfmake-configuration-overlay-input-wrap',

		initialize: function( setting ) {
			this.setting = setting;
		},

		render: function() {
			var html = this.template( this.setting );

			// Apply user-defined classes
			if ( this.setting.class ) {
				var classes = this.setting.class.split( ' ' );

				classes = _( classes ).map( function( cssClass ) {
					return cssClass + '-wrap';
				} ).join( ' ' );

				this.$el.addClass( classes );
			}

			this.$el.html( html );

			return this;
		},

		setValue: function( value ) {
			// Noop
		},

		getValue: function() {
			// Noop
		},

		enable: function() {
			// Noop
		},

		disable: function() {
			// Noop
		},

		settingUpdated: function() {
			this.$el.trigger( 'setting-updated', { name: this.setting.name, value: this.getValue() } );
		}
	} );

	/**
	 *
	 * Divider control
	 *
	 */
	oneApp.views.settings.divider = oneApp.views.settings.control.extend( {
		template: wp.template( 'ttfmake-settings-divider' ),

		events: {
			'click': 'settingUpdated',
		},

		settingUpdated: function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $divider = this.$el;
			var $dividers = $( '.ttfmake-configuration-divider-wrap' ).not( this.$el );
			var $body = this.$el.parents( '.ttfmake-overlay-body' );

			$dividers.each( function() {
				var $this = $( this );
				$this.next().slideUp( 200, function() {
					$this.removeClass( 'open-wrap' );
				} );
			} );

			$divider.next().slideDown( {
				duration: 200,

				step: function() {
					var offset = $divider.position().top + $body.scrollTop() - $divider.outerHeight();
					$body.scrollTop( offset );
				},

				complete: function() {
					$divider.addClass( 'open-wrap' );
				}
			} );

			// Always sync the current open divider name
			this.$el.trigger( 'setting-updated', { name: 'open-divider', value: this.getValue(), immediate: true } );
		},

		getValue: function() {
			return this.setting.name;
		},
	} );

	/**
	 *
	 * Section title control
	 *
	 */
	oneApp.views.settings.section_title = oneApp.views.settings.control.extend( {
		template: wp.template( 'ttfmake-settings-section_title' ),

		events: {
			'keyup input[type=text]' : 'settingUpdated',
		},

		setValue: function( value ) {
			$( 'input', this.$el ).val( value );
		},

		getValue: function() {
			return $( 'input', this.$el ).val();
		},

		enable: function() {
			$( 'input', this.$el ).prop( 'disabled', false );
		},

		disable: function() {
			$( 'input', this.$el ).prop( 'disabled', true );
		},
	} );

	/**
	 *
	 * Select control
	 *
	 */
	oneApp.views.settings.select = oneApp.views.settings.control.extend( {
		template: wp.template( 'ttfmake-settings-select' ),

		events: {
			'change select' : 'settingUpdated',
		},

		setValue: function( value ) {
			$( 'select', this.$el ).val( value );
		},

		getValue: function() {
			return $( 'select', this.$el ).val();
		},

		enable: function() {
			$( 'select', this.$el ).prop( 'disabled', false );
		},

		disable: function() {
			$( 'select', this.$el ).prop( 'disabled', true );
		},
	} );

	/**
	 *
	 * Checkbox control
	 *
	 */
	oneApp.views.settings.checkbox = oneApp.views.settings.control.extend( {
		template: wp.template( 'ttfmake-settings-checkbox' ),

		events: {
			'change input' : 'settingUpdated',
		},

		setValue: function( value ) {
			value = '' + value;
			$( 'input', this.$el ).prop( 'checked', '1' === value );
		},

		getValue: function() {
			var $input = $( 'input', this.$el );
			return $input.is( ':checked' ) ? 1: 0;
		},

		enable: function() {
			$( 'input', this.$el ).prop( 'disabled', false );
		},

		disable: function() {
			$( 'input', this.$el ).prop( 'disabled', true );
		},
	} );

	/**
	 *
	 * Text control
	 *
	 */
	oneApp.views.settings.text = oneApp.views.settings.control.extend( {
		template: wp.template( 'ttfmake-settings-text' ),

		events: {
			'change input[type=text]' : 'settingUpdated',
		},

		setValue: function( value ) {
			$( 'input', this.$el ).val( value );
		},

		getValue: function() {
			return $( 'input', this.$el ).val();
		},

		enable: function() {
			$( 'input', this.$el ).prop( 'disabled', false );
		},

		disable: function() {
			$( 'input', this.$el ).prop( 'disabled', true );
		},
	} );

	/**
	 *
	 * Color control
	 *
	 */
	oneApp.views.settings.color = oneApp.views.settings.control.extend( {
		template: wp.template( 'ttfmake-settings-color' ),
		widget: false,

		render: function() {
			oneApp.views.settings.control.prototype.render.apply( this, arguments );

			var palettes = _( ttfmakeBuilderData.palettes );
			palettes = palettes.isArray() ? palettes.toArray(): palettes.values();

			this.widget = $( 'input', this.$el ).wpColorPicker( {
				hide: false,
				palettes: palettes,
				defaultColor: this.getValue(),
				change: this.onColorPick.bind( this ),
			} );

			$( 'body' ).off( 'click.wpcolorpicker' );

			return this;
		},

		onColorPick: function( e, widget ) {
			this.$el.trigger( 'setting-updated', { name: this.setting.name, value: widget.color.toString() } );
		},

		setValue: function( value ) {
			this.widget.wpColorPicker( 'color', value );
		},

		getValue: function() {
			return $( '.ttfmake-text-background-color', this.$el ).val();
		},

		remove: function() {
			// this.widget.wpColorPicker( 'destroy' );
		}
	} );

	/**
	 *
	 * Image control
	 *
	 */
	oneApp.views.settings.image = oneApp.views.settings.control.extend( {
		template: wp.template( 'ttfmake-settings-image' ),

		sidebars: {
			default: wp.media.view.Sidebar,
			image: wp.media.view.Sidebar.extend( {
					render: function() {
						this.$el.html( wp.template( 'ttfmake-media-frame-remove-image' ) );
						return this;
					},
				} ),
		},

		currentAttachmentID: false,

		events: {
			'click .ttfmake-media-uploader-placeholder': 'onMediaAdd',
		},

		onMediaAdd: function( e ) {
			wp.media.view.Sidebar = this.sidebars.image;

			if ( window.frame ) {
				window.frame.detach();
			}

			// Create the media frame.
			window.frame = wp.media.frames.frame = wp.media( {
				title: $( e.currentTarget ).data( 'title' ),
				className: 'media-frame ttfmake-builder-uploader',
				multiple: false,
				library: { type: 'image' },
			} );

			frame.on( 'open', this.onFrameOpen.bind( this ) );
			frame.on( 'select', this.onMediaSelected.bind( this ) );
			frame.on( 'close', this.onFrameClose.bind( this ) );

			// Finally, open the modal
			frame.open();
			$( 'body' ).on( 'click', '.ttfmake-media-frame-remove-image', this.onMediaRemoved.bind( this ) );
		},

		onFrameOpen: function() {
			var attachmentID = this.getValue();

			if ( attachmentID ) {
				var selection = window.frame.state().get( 'selection' );
				var attachment = wp.media.attachment( attachmentID );
				selection.add( [ attachment ] );
				window.frame.$el.addClass( 'ttfmake-media-selected' );
			}
		},

		onFrameClose: function() {
			wp.media.view.Sidebar = this.sidebars.default;
		},

		onMediaSelected: function() {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			this.setValue( attachment.id );
			this.$el.trigger( 'setting-updated', { name: this.setting.name, value: attachment.id } );
		},

		onMediaRemoved: function() {
			this.$el.trigger( 'setting-updated', { name: this.setting.name, value: '' } );
			this.setValue( '' );
			frame.close();
		},

		setValue: function( value ) {
			var $el = this.$el;
			var $placeholder = $( '.ttfmake-media-uploader-placeholder', this.$el );

			if ( '' !== value ) {
				var attachment = wp.media.attachment( value );
				var self = this;

				attachment.fetch( {
					success: function( attachmentMeta ) {
						$placeholder.css(
							'background-image',
							'url(' + attachmentMeta.get( 'url' ) + ')'
						);
						$el.addClass( 'ttfmake-has-image-set' );
						self.currentAttachmentID = value;
					}
				} );
			} else {
				this.currentAttachmentID = false;
				$placeholder.css( 'background-image', '' );
				this.$el.removeClass( 'ttfmake-has-image-set' );
			}
		},

		getValue: function() {
			return this.currentAttachmentID;
		},

		remove: function() {
			$( 'body' ).off( 'click', '.ttfmake-media-frame-remove-image', this.onMediaRemoved.bind( this ) );
		},
	} );

} ) ( jQuery, _, settings );
