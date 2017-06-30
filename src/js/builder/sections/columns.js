( function( $, _, Backbone, builderSettings, sectionData ) {

	var Model = Backbone.Model.extend( {
		defaults: function() {
			return _.extend( {}, sectionData.defaults.text, {
				id: _.uniqueId( 'text_' ),
			} );
		},

		initialize: function( attrs ) {
			this.set( 'items', attrs.columns );
			this.set( 'columns', new Backbone.Collection() );
		},
	} );

	var ItemModel = Backbone.Model.extend( {
		defaults: function() {
			return _.extend( {}, sectionData.defaults['text-item'], {
				id: _.uniqueId( 'text-item_' ),
			} );
		},
	} );

	var View = make.classes.SectionView.extend( {
		template: wp.template( 'ttfmake-text' ),

		events: _.extend( {}, make.classes.SectionView.prototype.events, {
			'click .ttfmake-text-columns-add-column-link': 'onAddColumnClick',
		} ),

		initialize: function() {
			make.classes.SectionView.prototype.initialize.apply( this, arguments );
			this.itemViews = new Backbone.Collection();
		},

		afterRender: function() {
			make.classes.SectionView.prototype.afterRender.apply( this, arguments );

			this.listenTo( this.model, 'change:columns-number', this.onColumnCountChanged );
			this.listenTo( this.model.get( 'columns' ), 'add', this.onItemModelAdded );
			this.listenTo( this.model.get( 'columns' ), 'remove', this.onItemModelRemoved );
			this.listenTo( this.model.get( 'columns' ), 'reset', this.onItemModelsSorted );
			this.listenTo( this.model.get( 'columns' ), 'add remove change reset', this.onItemCollectionChanged );
			this.listenTo( this.itemViews, 'add', this.onItemViewAdded );
			this.listenTo( this.itemViews, 'remove', this.onItemViewRemoved );
			this.listenTo( this.itemViews, 'reset', this.onItemViewsSorted );

			var items = this.model.get( 'items' ) || _.times( 3, _.constant( sectionData.defaults['text-item'] ) );
			var itemCollection = this.model.get( 'columns' );

			_.each( items, function( itemAttrs ) {
				var itemModel = make.factory.model( itemAttrs );
				itemModel.parentModel = this.model;
				itemCollection.add( itemModel );
			}, this );

			this.initSortables();
			this.on( 'sort-start', this.onItemSortStart, this );
			this.on( 'sort-stop', this.onItemSortStop, this );
		},

		onColumnCountChanged: function( itemModel ) {
			var newColumnCount = this.model.get( 'columns-number' );
			var currentColumnCount = this.model.get( 'columns' ).size();
			var neededColumns = newColumnCount - currentColumnCount;
			var $stage = $( '.ttfmake-text-columns-stage', this.$el );

			if ( neededColumns > 0 ) {
				var items = _.times( neededColumns, _.constant( sectionData.defaults['text-item'] ) );
				var itemCollection = this.model.get( 'columns' );

				_.each( items, function( itemAttrs ) {
					var itemModel = make.factory.model( itemAttrs );
					itemModel.parentModel = this.model;
					itemCollection.add( itemModel );
				}, this );
			}

			$stage.removeClass( function( i, className ) {
				return className.match( /ttfmake-text-columns-[0-9]/g || [] ).join( ' ' );
			});

			$stage.addClass( 'ttfmake-text-columns-' + newColumnCount );
		},

		onItemModelAdded: function( itemModel, itemCollection ) {
			var itemView = make.factory.view( { model: itemModel } );

			if ( itemView ) {
				var itemIndex = itemCollection.indexOf( itemModel );
				var itemViewModel = new Backbone.Model( { id: itemModel.id, view: itemView } );
				this.itemViews.add( itemViewModel, { at: itemIndex } );
			}
		},

		onItemModelRemoved: function( itemModel ) {
			var itemViewModel = this.itemViews.get( itemModel.id );
			this.itemViews.remove( itemViewModel );
		},

		onItemModelsSorted: function( itemCollection ) {
			this.itemViews.reset( _.map( itemCollection.pluck( 'id' ), function( id ) {
				return this.itemViews.get( id );
			}, this ) );
		},

		onItemCollectionChanged: function() {
			this.model.trigger( 'change' );
		},

		onItemViewAdded: function( itemViewModel, itemViewCollection ) {
			var itemViewIndex = this.itemViews.indexOf( itemViewModel );
			var $itemViewEl = itemViewModel.get( 'view' ).render().$el;

			$( '.ttfmake-text-columns-stage', this.$el ).append( $itemViewEl );
			itemViewModel.get( 'view' ).trigger( 'rendered' );
		},

		onItemViewRemoved: function( itemViewModel ) {
			itemViewModel.get( 'view' ).$el.animate( {
				opacity: 'toggle',
				height: 'toggle'
			}, builderSettings.closeSpeed, function() {
				itemViewModel.get( 'view' ).remove();
			} );
		},

		onItemViewsSorted: function( itemViewCollection ) {
			var $stage = $( '.ttfmake-text-columns-stage', this.$el );

			itemViewCollection.forEach( function( itemViewModel ) {
				var $itemViewEl = itemViewModel.get( 'view' ).$el;
				$itemViewEl.detach();
				$stage.append( $itemViewEl );
			}, this );
		},

		initSortables: function() {
			var $sortable = $( '.ttfmake-text-columns-stage', this.$el );

			$sortable.sortable( {
				handle: '.ttfmake-sortable-handle',
				placeholder: 'sortable-placeholder',
				items: '.ttfmake-text-column',
				forcePlaceholderSizeType: true,
				distance: 2,
				zIndex: 99999,
				tolerance: 'pointer',

				start: function ( e, ui ) {
					this.trigger( 'sort-start', e, ui );
				}.bind( this ),

				stop: function ( e, ui ) {
					this.trigger( 'sort-stop', e, ui );
				}.bind( this ),
			} );
		},

		onItemSortStart: function( e, ui ) {
			ui.placeholder.height( ui.item.height() );
			ui.placeholder.css( 'padding', ui.item.css( 'padding' ) );
			ui.placeholder.css( 'margin-bottom', ui.item.css( 'margin-bottom' ) );
		},

		onItemSortStop: function( e, ui ) {
			var $sortable = $( '.ttfmake-text-columns-stage', this.$el );
			var ids = $sortable.sortable( 'toArray', { attribute: 'data-id' } );

			this.model.get( 'columns' ).reset( _.map( ids, function( id ) {
				return this.model.get( 'columns' ).get( id );
			}, this ) );
		},

		onAddColumnClick: function( e ) {
			e.preventDefault();
			this.model.get( 'columns' ).add( sectionData.defaults['text-item'] );
		},
	} );

	var ItemView = make.classes.SectionItemView.extend( {
		template: wp.template( 'ttfmake-text-item' ),

		events: _.extend( {}, make.classes.SectionItemView.prototype.events, {
			'click .ttfmake-text-column-remove': 'onRemoveItemClick',
			'click .edit-content-link': 'onEditItemContentClick',
		} ),

		initialize: function() {
			make.classes.SectionItemView.prototype.initialize.apply( this, arguments );
		},

		afterRender: function() {
			make.classes.SectionItemView.prototype.afterRender.apply( this, arguments );

			this.listenTo( this.model, 'change:content', this.onItemContentChanged );

			var iframe = $( 'iframe', this.$el ).get( 0 );
			make.utils.initFrame( iframe );
			make.utils.setFrameContent( iframe, this.model.get( 'content' ) );
		},

		onEditItemContentClick: function( e ) {
			make.classes.SectionItemView.prototype.onEditItemContentClick.apply( this, arguments );

			var backgroundColor = this.model.parentModel.get( 'background-color' );
			backgroundColor = '' !== backgroundColor ? backgroundColor : 'transparent';
			window.make.overlay.setStyle( { backgroundColor: backgroundColor } );
		},

		onRemoveItemClick: function( e ) {
			e.preventDefault();

			if ( ! confirm( 'Are you sure you want to trash this column permanently?' ) ) {
				return;
			}

			this.model.collection.remove( this.model );
		},

		onItemContentChanged: function() {
			var $iframe = $( 'iframe', this.$el );
			make.utils.setFrameContent( $iframe.get( 0 ), this.model.get( 'content' ) );

			if ( '' !== this.model.get( 'content' ) ) {
				$( '.ttfmake-iframe-content-placeholder', this.$el ).removeClass( 'show' );
				var iframeHeight = Math.min( Math.max( $iframe.contents().innerHeight(), 300 ), 500 );
				$iframe.innerHeight( iframeHeight );
			} else {
				$( '.ttfmake-iframe-content-placeholder', this.$el ).addClass( 'show' );
				$iframe.innerHeight( 300 );
			}
		}
	} );

	make.factory.model = _.wrap( make.factory.model, function( func, attrs ) {
		if ( 'text' === attrs[ 'section-type' ] ) {
			return new Model( attrs );
		}

		if ( 'text-item' === attrs[ 'section-type' ] ) {
			return new ItemModel( attrs );
		}

		return func( attrs );
	} );

	make.factory.view = _.wrap( make.factory.view, function( func, options ) {
		if ( 'text' === options.model.get( 'section-type' ) ) {
			return new View( options );
		}

		if ( 'text-item' === options.model.get( 'section-type' ) ) {
			return new ItemView( options );
		}

		return func( options );
	} );

} ) ( jQuery, _, Backbone, ttfmakeBuilderSettings, ttfMakeSections );