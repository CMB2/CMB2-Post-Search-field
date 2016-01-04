CMB2 Post Search field
======================

Custom field for CMB2 which adds a post-search dialog for searching/attaching other post IDs.

Adds a new text field type (with a button), `post_search_text` that adds a quick post search dialog for saving post IDs to a text input.

## Example

```php
// Classic CMB2 declaration
$cmb = new_cmb2_box( array(
	'id'           => 'prefix-metabox-id',
	'title'        => __( 'Post Info' ),
	'object_types' => array( 'post', ), // Post type
) );

// Add new field
$cmb->add_field( array(
	'name'        => __( 'Related post' ),
	'id'          => 'prefix_related_post',
	'type'        => 'post_search_text', // This field type
	// post type also as array
	'post_type'   => 'post',
	// Default is 'checkbox', used in the modal view to select the post type
	'select_type' => 'radio',
	// Will replace any selection with selection from modal. Default is 'add'
	'select_behavior' => 'replace',
) );
```

## Screenshots

1. Field display  
![Field display](https://raw.githubusercontent.com/WebDevStudios/CMB2-Post-Search-field/master/post-search-field.png)

2. Search Modal  
![Search Modal](https://raw.githubusercontent.com/WebDevStudios/CMB2-Post-Search-field/master/post-search-dialog.png)

----

If you're looking for a more general way to attach posts (or other custom post types) with a drag and drop interface, you might consider [CMB2 Attached Posts Field](https://github.com/WebDevStudios/cmb2-attached-posts) instead.
