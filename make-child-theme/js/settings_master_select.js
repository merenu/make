( function( $, _, masterSections ) {

	var overlayClass = oneApp.views.overlays.settings;

	oneApp.views.overlays.settings = overlayClass.extend( {
		initialize: function() {
			overlayClass.prototype.initialize.apply( this, arguments );

			if ( this.model.get( 'master' ) ) {
				this.controls.master_id.enable();
			}

			// Watch for changes on the master checkbox
			this.changeset.on( 'change:master', this.onMasterChange, this );
			// Watch for changes on the master id reference
			this.changeset.on( 'change:master_id', this.onMasterIdChange, this );
		},

		onMasterChange: function() {
			var isMaster = this.changeset.get( 'master' );

			if ( ! isMaster ) {
				// If this section isn't marked as master,
				// reset any current master_id value
				// and disable the master_id control
				this.changeset.set( 'master_id', '' );
				this.controls.master_id.disable();
			} else {
				// Enable the master_id control if
				// this section is marked as master
				this.controls.master_id.enable();
			}
		},

		onMasterIdChange: function() {
			var masterId = this.changeset.get( 'master_id' );

			if ( '' !== masterId ) {
				var master = masterSections[ masterId ];
				this.changeset.set( master );
				this.applyValues( master );
			} else {
				var originalAttributes = this.model.toJSON();
				originalAttributes = _( originalAttributes ).omit( 'id', 'sid', 'master', 'master_id' );
				this.changeset.set( originalAttributes );
				this.applyValues( originalAttributes );
			}
		},

		onUpdate: function( e ) {
			overlayClass.prototype.onUpdate.apply( this, arguments );

			var masterId = this.model.get( 'master_id' );

			if ( '' !== masterId ) {
				var attributes = this.model.toJSON();
				attributes = _( attributes ).omit( 'id', 'sid', 'master', 'master_id' );
				// Sync original master definition
				// to current state on the client
				masterSections[ masterId ] = attributes;

				// Select all instances of the current master
				// for which changes are being applied...
				var instances = oneApp.builder.sections.filter( function( section ) {
					return section.get( 'master_id' ) === this.model.get( 'master_id' );
				}, this );

				// ...and sync those changes on every instance
				_( instances ).each( function( section ) {
					section.set( section.parse( attributes ) );
				}, this );

				// Finally re-render all views holding instances of this master
				var instanceViews = oneApp.builder.sectionViews.filter( function( sectionView ) {
					return sectionView.get( 'view' ).model.get( 'master_id' ) === this.model.get( 'master_id' );
				}, this );

				_( instanceViews ).each( function( sectionView ) {
					var sectionIndex = oneApp.builder.sectionViews.indexOf( sectionView );
					oneApp.builder.removeSectionView( sectionView );
					oneApp.builder.addSectionView( sectionView.get( 'view' ).model, sectionIndex );
				}, this );
			}
		},
	} );

} ) ( jQuery, _, masterSections );
