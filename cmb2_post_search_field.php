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
	// wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'wp-backbone' );

	// markup needed for modal
	add_action( 'admin_footer', 'find_posts_div' );

	?>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		'use strict';

		window.cmb2_post_search_field_l10n = window.cmb2_post_search_field_l10n || {
			'error' : '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>'
		}


		var searchView = window.Backbone.View.extend({
			el: '#find-posts',
			overlaySet: false,
			$overlay : false,
			$idInput : false,

			events : {
				'keypress .find-box-search :input' : 'maybeStartSearch',
				'keyup #find-posts-input'   : 'escClose',
				'click #find-posts-submit'  : 'selectPost',
				'click #find-posts-search'  : 'send',
				'click #find-posts-close'   : 'close',
				// Enable whole row to be clicked
				'click .find-box-inside tr' : 'clickRow',
			},

			initialize: function() {
				this.$spinner  = this.$el.find( '.find-box-search .spinner' );
				this.$input    = this.$el.find( '#find-posts-input' );
				this.$response = this.$el.find( '#find-posts-response' );
				this.$overlay  = $( '.ui-find-overlay' );
				console.log( 'this.$overlay', this.$overlay.length );


				this.listenTo( this, 'open', this.open );
				this.listenTo( this, 'close', this.close );
			},

			escClose: function( evt ) {
				if ( evt.which && 27 === evt.which ) {
					this.close();
				}
			},

			close: function() {
				this.$overlay.hide();
				this.$el.hide();
			},

			open: function() {
				this.$response.html('');

				this.$el.show();

				this.$input.focus();

				this.$overlay.show();

				// Pull some results up by default
				this.send();

				return false;
			},

			maybeStartSearch: function( evt ) {
				if ( 13 == evt.which ) {
					this.send();
					return false;
				}
			},

			clickRow: function( evt ) {
				$( evt.currentTarget ).find( '.found-radio input' ).prop( 'checked', true );
			},

			send: function() {

				var _this = this;
				_this.$spinner.show();

				$.ajax( ajaxurl, {
					type     : 'POST',
					dataType : 'json',
					data     : {
						ps: _this.$input.val(),
						action: 'find_posts',
						_ajax_nonce: $('#_ajax_nonce').val()
					}
				}).always( function() {

					_this.$spinner.hide();

				}).done( function( response ) {

					if ( ! response.success ) {
						_this.$response.text( cmb2_post_search_field_l10n.error );
					}

					var data = response.data.replace( '"radio"', '"checkbox"' );


					console.log( 'data', data.search( 'type="radio"' ) );
					_this.$response.html( data );

				}).fail( function() {
					_this.$response.text( cmb2_post_search_field_l10n.error );
				});
			},

			selectPost: function( evt ) {
				evt.preventDefault();

				var $checked = $( '#find-posts-response input[type="radio"]:checked' );

				if ( ! $checked.length ) {
					this.close();
					return;
				}

				var selected = $checked.val();
				var existing = this.$idInput.val();
				existing = existing ? existing + ', ' : '';
				this.$idInput.val( existing + selected );

				this.close();
			},

		});

		var search = new searchView();


		// function closeSearch() {
		// 	if ( window.findPosts ) {
		// 		window.findPosts.close();
		// 	}
		// }

		// function doSearch() {
		// 	if ( window.findPosts ) {
		// 		window.findPosts.send();
		// 	}
		// }

		// function overrideSearchHandler( evt ) {
		// 	evt.preventDefault();

		// 	var $checked = $( '#find-posts-response input[type="radio"]:checked' );

		// 	if ( ! $checked.length ) {
		// 		closeSearch();
		// 		return;
		// 	}

		// 	var selected = $checked.val();
		// 	var existing = $input.val();
		// 	existing = existing ? existing + ', ' : '';
		// 	$input.val( existing + selected );
		// 	closeSearch();
		// }



		// // JS needed for modal
		// $.getScript( '<?php echo admin_url( "/js/media.js" ); ?>' );

		// $( '#find-posts' )
		// 	.on( 'keypress', '.find-box-search :input', function( evt ) {
		// 		if ( 13 == evt.which ) {
		// 			doSearch();
		// 			return false;
		// 		}
		// 	})
		// 	.on( 'click', '#find-posts-search', doSearch )
		// 	.on( 'click', '#find-posts-close', closeSearch )
		// 	.on( 'click', '#find-posts-submit', overrideSearchHandler )
		// 	// Enable whole row to be clicked
		// 	.on( 'click', 'tr', function() {
		// 		$( this ).find( '.found-radio input' ).prop( 'checked', true );
		// 	});


		$( '.cmb-type-post-search-text .cmb-td input[type="text"]' ).after( '<div title="<?php _e( 'Find Posts or Pages' ); ?>" style="color: #999;margin: .3em 0 0 2px; cursor: pointer;" class="dashicons dashicons-search"></div>');
		$( '.cmb-type-post-search-text .cmb-td .dashicons-search' ).on( 'click', openSearch );

		function openSearch( evt ) {
			search.$idInput = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td input[type="text"]' );
			search.trigger( 'open' );
		}




	});
	</script>
	<?php
}
add_action( 'cmb2_after_form', 'cmb2_post_search_render_js', 10, 4 );
