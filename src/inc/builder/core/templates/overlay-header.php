<div class="<?php echo $ttfmake_overlay_class; ?>" id="<?php echo $ttfmake_overlay_id; ?>">
	<div class="ttfmake-overlay-wrapper">
		<div class="ttfmake-overlay-dialog">
			<div class="ttfmake-overlay-header">
				<div class="ttfmake-overlay-window-head">
					<div class="ttfmake-overlay-title"><?php if ( ! empty( $ttfmake_overlay_title ) ) : echo $ttfmake_overlay_title; else : _e( 'Configuration', 'make' ); endif; ?></div>
					<button type="button" class="media-modal-close ttfmake-overlay-close-discard">
						<span class="media-modal-icon">
					</button>
				</div>
			</div>
			<div class="ttfmake-overlay-body">