<?php
/**
 * @package Make
 */
?>

	<textarea class="ttfmake-section-json" name="ttfmake-section-json[{{ data.get('id') }}]" style="display: none;">{{ JSON.stringify( data.toJSON() ) }}</textarea>
</div>