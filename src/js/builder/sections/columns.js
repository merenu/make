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
			'click .ttfmake-text-columns-add-column-link': 'onAddColumnClick'
		} ),

		initialize: function() {
			make.classes.SectionView.prototype.initialize.apply( this, arguments );
			this.itemViews = new Backbone.Collection();
		},

		afterRender: function() {
			make.classes.SectionView.prototype.afterRender.apply( this, arguments );

			this.listenTo( this.model, 'change:columns-number', this.onColumnCountChanged );
			this.listenTo( this.model.get( 'columns' ), 'add', this.onItemModelAdded );
			this.listenTo( this.model.get( 'columns' ), 'add remove change reset', this.onItemCollectionChanged );
			this.listenTo( this.itemViews, 'add', this.onItemViewAdded );

			var items = this.model.get( 'items' ) || _.times( 3, _.constant( sectionData.defaults['text-item'] ) );
			var itemCollection = this.model.get( 'columns' );

			_.each( items, function( itemAttrs ) {
				var itemModel = make.factory.model( itemAttrs );
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

		onItemCollectionChanged: function() {
			this.model.trigger( 'change' );
		},

		onItemViewAdded: function( itemViewModel, itemViewCollection ) {
			var itemViewIndex = this.itemViews.indexOf( itemViewModel );
			var $itemViewEl = itemViewModel.get( 'view' ).render().$el;

			$( '.ttfmake-text-columns-stage', this.$el ).append( $itemViewEl );

			itemViewModel.get( 'view' ).trigger( 'rendered' );
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

		initialize: function() {
			make.classes.SectionItemView.prototype.initialize.apply( this, arguments );
		},

		afterRender: function() {
			make.classes.SectionItemView.prototype.afterRender.apply( this, arguments );

			var iframe = $( 'iframe', this.$el ).get( 0 );
			make.utils.initFrame( iframe );
			make.utils.setFrameContent( iframe, this.model.get( 'content' ) );
		},
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