CMB2 Post Search field
======================

Custom field for CMB2 which adds a post-search dialog for searching/attaching other post IDs.

Adds a new text field type (with a button), `post_search_text` that adds a quick post search dialog for saving post IDs to a text input.

##Example

```
//Classic CMB2 declaration
$cmb_task = new_cmb2_box( array(
	'id' => 'metabox',
	'title' => __( 'Post Info' ),
	'object_types' => array( 'post', ), // Post type
	'context' => 'normal',
	'priority' => 'high',
	'show_names' => true,
) );
//Add new field
$cmb_task->add_field( array(
	'name' => __( 'Related post' ),
	'id' => 'related_post',
	'type' => 'post_search_text', //This field type
	//post type also as array
	'post_type' => 'post',
	'select_type' => 'radio' //or checkbox, used in the modal view to select the post type
) );
```

If you're looking for a more general way to attach posts (or other custom post types) with a drag and drop interface, you might consider [CMB2 Attached Posts Field](https://github.com/WebDevStudios/cmb2-attached-posts) instead.
