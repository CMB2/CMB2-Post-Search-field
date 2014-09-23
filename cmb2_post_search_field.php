<?php
/*
Plugin Name: CMB2 Post Search field
Plugin URI: http://webdevstudios.com
Description: Custom field for CMB2 which adds a post-search dialog for searching/attaching other post IDs
Author: WebDevStudios
Author URI: http://webdevstudios.com
Version: 1.0.0
License: GPLv2
*/

function cmb2_post_search_render_field( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type ) {
	echo $field_type->text();
}
add_action( 'cmb2_render_post_search_text', 'cmb2_post_search_render_field', 10, 5 );

function cmb2_post_search_render_js(  $cmb_id, $object_id, $object_type, $cmb ) {

	$fields = $cmb->prop( 'fields' );

	if ( ! is_array( $fields ) ) {
		return;
	}

	$not_found = true;
	foreach ( $fields as $field ) {
		if ( 'post_search_text' == $field['type'] ) {
			$not_found = false;
			break;
		}
	}

	if ( $not_found ) {
		return;
	}

	// JS needed for modal
	wp_enqueue_media();

	// markup needed for modal
	add_action( 'admin_footer', 'find_posts_div' );

	?>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		'use strict';

		window.attachMediaBoxL10n = window.attachMediaBoxL10n || {
			'error' : '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>'
		}

		var $input = $( '.cmb-type-post-search-text .cmb-td input[type="text"]' ).after( '<div title="<?php _e( 'Find Posts or Pages' ); ?>" style="color: #999;margin: .3em 0 0 2px; cursor: pointer;" class="dashicons dashicons-search"></div>');
		var $search = $( '.cmb-type-post-search-text .cmb-td .dashicons-search' ).on( 'click', openSearch );

		function openSearch() {
			if ( window.findPosts ) {
				window.findPosts.open();
			}
		}

		function closeSearch() {
			if ( window.findPosts ) {
				window.findPosts.close();
			}
		}

		function doSearch() {
			if ( window.findPosts ) {
				window.findPosts.send();
			}
		}

		function overrideSearchHandler( evt ) {
			evt.preventDefault();

			var $checked = $( '#find-posts-response input[type="radio"]:checked' );

			if ( ! $checked.length ) {
				closeSearch();
				return;
			}

			var selected = $checked.val();
			var existing = $input.val();
			existing = existing ? existing + ', ' : '';
			$input.val( existing + selected );
			closeSearch();
		}

		// JS needed for modal
		$.getScript( '<?php echo admin_url( "/js/media.js" ); ?>' );

		$( '#find-posts' )
			.on( 'keypress', '.find-box-search :input', function( evt ) {
				if ( 13 == evt.which ) {
					doSearch();
					return false;
				}
			})
			.on( 'click', '#find-posts-search', doSearch )
			.on( 'click', '#find-posts-close', closeSearch )
			.on( 'click', '#find-posts-submit', overrideSearchHandler );

		// Enable whole row to be clicked
		$( '.find-box-inside' ).on( 'click', 'tr', function() {
			$( this ).find( '.found-radio input' ).prop( 'checked', true );
		});


	});
	</script>
	<?php
}
add_action( 'cmb2_after_form', 'cmb2_post_search_render_js', 10, 4 );
