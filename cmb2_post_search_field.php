<?php
/*
Plugin Name: CMB2 Post Search field
Plugin URI: http://webdevstudios.com
Description: Custom field for CMB2 which adds a post-search dialog for searching/attaching other post IDs
Author: WebDevStudios
Author URI: http://webdevstudios.com
Version: 0.2.3
License: GPLv2
*/

function cmb2_post_search_render_field( $field, $escaped_value, $object_id, $object_type, $field_type ) {
	echo $field_type->input( array(
		'data-search' => json_encode( array(
			'posttype'   => $field->args( 'post_type' ),
			'selecttype' => 'radio' == $field->args( 'select_type' ) ? 'radio' : 'checkbox',
			'selectbehavior' => 'replace' == $field->args( 'select_behavior' ) ? 'replace' : 'add',
			'errortxt'   => esc_attr( $field_type->_text( 'error_text', __( 'An error has occurred. Please reload the page and try again.' ) ) ),
			'findtxt'    => esc_attr( $field_type->_text( 'find_text', __( 'Find Posts or Pages' ) ) ),
		) ),
	) );
}
add_action( 'cmb2_render_post_search_text', 'cmb2_post_search_render_field', 10, 5 );

function cmb2_has_post_search_text_field( $fields ) {

	foreach ( $fields as $field ) {
		if ( isset( $field['fields'] ) ) {
			if ( cmb2_has_post_search_text_field( $field['fields'] ) ) {
				return true;
			}
		}
		if ( 'post_search_text' == $field['type'] ) {
			return true;
		}
	}

	return false;
}

function cmb2_post_search_render_js(  $cmb_id, $object_id, $object_type, $cmb ) {
	static $rendered;

	if ( $rendered ) {
		return;
	}

	$fields = $cmb->prop( 'fields' );

	if ( ! is_array( $fields ) ) {
		return;
	}

	$has_post_search_field = cmb2_has_post_search_text_field( $fields );

	if ( ! $has_post_search_field ) {
		return;
	}

	// JS needed for modal
	// wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'wp-backbone' );

	if ( ! is_admin() ) {
		// Will need custom styling!
		// @todo add styles for front-end
		require_once( ABSPATH . 'wp-admin/includes/template.php' );
		do_action( 'cmb2_post_search_field_add_find_posts_div' );
	}

	// markup needed for modal
	add_action( 'admin_footer', 'find_posts_div' );

	// @TODO this should really be in its own JS file.
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		'use strict';

		var SearchView = window.Backbone.View.extend({
			el         : '#find-posts',
			overlaySet : false,
			$overlay   : false,
			$idInput   : false,
			$checked   : false,

			events : {
				'keypress .find-box-search :input' : 'maybeStartSearch',
				'keyup #find-posts-input'  : 'escClose',
				'click #find-posts-submit' : 'selectPost',
				'click #find-posts-search' : 'send',
				'click #find-posts-close'  : 'close',
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

				// WP, why you so dumb? (why isn't text in its own dom node?)
				this.$el.show().find( '#find-posts-head' ).html( this.findtxt + '<div id="find-posts-close"></div>' );

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
				search.$spinner.addClass('is-active');

				$.ajax( ajaxurl, {
					type     : 'POST',
					dataType : 'json',
					data     : {
						ps               : search.$input.val(),
						action           : 'find_posts',
						cmb2_post_search : true,
						post_search_cpt  : search.posttype,
						_ajax_nonce      : $('#find-posts #_ajax_nonce').val()
					}
				}).always( function() {

					search.$spinner.removeClass('is-active');

				}).done( function( response ) {

					if ( ! response.success ) {
						search.$response.text( search.errortxt );
					}

					var data = response.data;

					if ( 'checkbox' === search.selecttype ) {
						data = data.replace( /type="radio"/gi, 'type="checkbox"' );
					}

					search.$response.html( data );

				}).fail( function() {
					search.$response.text( search.errortxt );
				});
			},

			selectPost: function( evt ) {
				evt.preventDefault();

				this.$checked = $( '#find-posts-response input[type="' + this.selecttype + '"]:checked' );

				var checked = this.$checked.map(function() { return this.value; }).get();

				if ( ! checked.length ) {
					this.close();
					return;
				}

				this.handleSelected( checked );
			},

			handleSelected: function( checked ) {
				checked = checked.join( ', ' );

				if ( 'add' === this.selectbehavior ) {
					var existing = this.$idInput.val();
					if ( existing ) {
						checked = existing + ', ' + checked;
					}
				}

				this.$idInput.val( checked );
				this.close();
			}

		});

		window.cmb2_post_search = new SearchView();

		window.cmb2_post_search.closeSearch = function() {
			window.cmb2_post_search.trigger( 'close' );
		};

		window.cmb2_post_search.openSearch = function( evt ) {
			var search = window.cmb2_post_search;

			search.$idInput = $( evt.currentTarget ).parents( '.cmb-type-post-search-text' ).find( '.cmb-td input[type="text"]' );
			// Setup our variables from the field data
			$.extend( search, search.$idInput.data( 'search' ) );

			search.trigger( 'open' );
		};

		window.cmb2_post_search.addSearchButtons = function() {
			var $this = $( this );
			var data = $this.data( 'search' );
			$this.after( '<div title="'+ data.findtxt +'" class="dashicons dashicons-search cmb2-post-search-button"></div>');
		};

		$( '.cmb-type-post-search-text .cmb-td input[type="text"]' ).each( window.cmb2_post_search.addSearchButtons );

		$( '.cmb2-wrap' ).on( 'click', '.cmb-type-post-search-text .cmb-td .dashicons-search', window.cmb2_post_search.openSearch );
		$( 'body' ).on( 'click', '.ui-find-overlay', window.cmb2_post_search.closeSearch );

	});
	</script>
	<style type="text/css" media="screen">
		.cmb2-post-search-button {
			color: #999;
			margin: .3em 0 0 2px;
			cursor: pointer;
		}
	</style>
	<?php

	$rendered = true;
}
add_action( 'cmb2_after_form', 'cmb2_post_search_render_js', 10, 4 );

/**
 * Add the find posts div via a hook so we can relocate it manually
 */
function cmb2_post_search_field_add_find_posts_div() {
	add_action( 'wp_footer', 'find_posts_div' );
}
add_action( 'cmb2_post_search_field_add_find_posts_div', 'cmb2_post_search_field_add_find_posts_div' );

/**
 * Set the post type via pre_get_posts
 * @param  array $query  The query instance
 */
function cmb2_post_search_set_post_type( $query ) {
	$types = $_POST['post_search_cpt'];
	$types = is_array( $types ) ? array_map( 'esc_attr', $types ) : esc_attr( $types );
	$query->set( 'post_type', $types );
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
