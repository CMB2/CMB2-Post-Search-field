<?php
/*
Plugin Name: CMB2 Post Search field
Plugin URI: http://webdevstudios.com
Description: Custom field for CMB2 which adds a post-search dialog for searching/attaching other post IDs
Author: WebDevStudios
Author URI: http://webdevstudios.com
Version: 0.2.0
License: GPLv2
*/

function cmb2_post_search_render_field( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type ) {

	$select_type = $field->args( 'select_type' );

	echo $field_type->input( array(
		'data-posttype'   => $field->args( 'post_type' ),
		'data-selecttype' => 'radio' == $select_type ? 'radio' : 'checkbox',
	) );
}
add_action( 'cmb2_render_post_search_text', 'cmb2_post_search_render_field', 10, 5 );

function cmb2_post_search_render_js(  $cmb_id, $object_id, $object_type, $cmb ) {
	static $rendered;

	if ( $rendered ) {
		return;
	}

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

		var l10n = {
			'error' : '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>',
			'find' : '<?php _e( 'Find Posts or Pages' ); ?>'
		};


		var searchView = window.Backbone.View.extend({
			el         : '#find-posts',
			overlaySet : false,
			$overlay   : false,
			$idInput   : false,
			$checked   : false,

			events : {
				'keypress .find-box-search :input' : 'maybeStartSearch',
				'keyup #find-posts-input'   : 'escClose',
				'click #find-posts-submit'  : 'selectPost',
				'click #find-posts-search'  : 'send',
				'click #find-posts-close'   : 'close',
			},

			initialize: function() {
				this.$spinner  = this.$el.find( '.find-box-search .spinner' );
				this.$input    = this.$el.find( '#find-posts-input' );
				this.$response = this.$el.find( '#find-posts-response' );
				this.$overlay  = $( '.ui-find-overlay' );

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

				if ( ! this.$overlay.length ) {
					$( 'body' ).append( '<div class="ui-find-overlay"></div>' );
					this.$overlay  = $( '.ui-find-overlay' );
				}

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

			send: function() {

				var search = this;
				search.$spinner.show();

				$.ajax( ajaxurl, {
					type     : 'POST',
					dataType : 'json',
					data     : {
						ps               : search.$input.val(),
						action           : 'find_posts',
						cmb2_post_search : true,
						post_search_cpt  : search.postType,
						_ajax_nonce      : $('#_ajax_nonce').val()
					}
				}).always( function() {

					search.$spinner.hide();

				}).done( function( response ) {

					if ( ! response.success ) {
						search.$response.text( l10n.error );
					}

					var data = response.data;

					if ( 'checkbox' === window.cmb2_post_search.selectType ) {
						data = data.replace( /type="radio"/gi, 'type="checkbox"' );
					}

					search.$response.html( data );

				}).fail( function() {
					search.$response.text( l10n.error );
				});
			},

			selectPost: function( evt ) {
				evt.preventDefault();

				var inputType = 'checkbox';

				if ( 'radio' === window.cmb2_post_search.selectType ) {
					inputType = 'radio';
				}

				this.$checked = $( '#find-posts-response input[type="'+inputType+'"]:checked' );
				var checked   = this.$checked.map(function() { return this.value; }).get();

				if ( ! checked.length ) {
					this.close();
					return;
				}

				this.handleSelected( checked );
			},

			handleSelected: function( checked ) {
				var existing = this.$idInput.val();
				existing = existing ? existing + ', ' : '';
				this.$idInput.val( existing + checked.join( ', ' ) );

				this.close();
			}

		});

		window.cmb2_post_search = new searchView();

		$( '.cmb-type-post-search-text .cmb-td input[type="text"]' ).after( '<div title="'+ l10n.find +'" style="color: #999;margin: .3em 0 0 2px; cursor: pointer;" class="dashicons dashicons-search"></div>');

		$( '.cmb-type-post-search-text .cmb-td .dashicons-search' ).on( 'click', openSearch );

		function openSearch( evt ) {
			window.cmb2_post_search.$idInput = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td input[type="text"]' );
			window.cmb2_post_search.postType = window.cmb2_post_search.$idInput.data( 'posttype' );
			window.cmb2_post_search.selectType = window.cmb2_post_search.$idInput.data( 'selecttype' );
			window.cmb2_post_search.trigger( 'open' );
		}


	});
	</script>
	<?php

	$rendered = true;
}
add_action( 'cmb2_after_form', 'cmb2_post_search_render_js', 10, 4 );

function cmb2_post_search_add_post_search_div( $cmb_id, $object_id, $object_type, $cmb ) {
	static $rendered;

	if ( $rendered ) {
		return;
	}

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

	require_once(ABSPATH . 'wp-admin/includes/template.php');

	// markup needed for modal
	find_posts_div();

	$rendered = true;
}
add_action( 'cmb2_after_form', 'cmb2_post_search_add_post_search_div', 11, 4 );

/**
 * Set the post type via pre_get_posts
 * @param  array $query  The posts query
 */
function cmb2_post_search_set_post_type( $query ) {

	$query->set( 'post_type', esc_attr( $_POST['post_search_cpt'] ) );
}

/**
 * Check to see if we have a post type set and, if so, add the
 * pre_get_posts action to set the queried post type
 */
function cmb2_post_search_wp_ajax_find_posts() {
	if (
		defined( 'DOING_AJAX' )
		&& DOING_AJAX
		&& isset( $_POST['cmb2_post_search'], $_POST['action'], $_POST['post_search_cpt'] )
		&& 'find_posts' == $_POST['action']
		&& ! empty( $_POST['post_search_cpt'] )
	) {

		add_action( 'pre_get_posts', 'cmb2_post_search_set_post_type' );
	}
}
add_action( 'admin_init', 'cmb2_post_search_wp_ajax_find_posts' );
