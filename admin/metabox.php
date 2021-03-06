<?php
/**
 * Contextual Related Posts Metabox interface.
 *
 *
 * @package   Contextual_Related_Posts
 * @author    Ajay D'Souza <me@ajaydsouza.com>
 * @license   GPL-2.0+
 * @link      http://ajaydsouza.com
 * @copyright 2009-2015 Ajay D'Souza
 */

/**** If this file is called directly, abort. ****/
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Function to add meta box in Write screens of Post, Page and Custom Post Types.
 *
 * @since	1.9.1
 *
 * @param	text	$post_type
 * @param	object	$post
 */
function crp_add_meta_box( $post_type, $post ) {
	global $crp_settings;

	// If metaboxes are disabled, then exit
    if ( ! $crp_settings['show_metabox'] ) return;

	// If current user isn't an admin and we're restricting metaboxes to admins only, then exit
	if ( ! current_user_can( 'manage_options' ) && $crp_settings['show_metabox_admins'] ) return;

	$args = array(
	   'public'   => true,
	);
	$post_types = get_post_types( $args );

	/**
	 * Filter post types on which the meta box is displayed
	 *
	 * @since	2.2.0
	 *
	 * @param	array	$post_types	Array of post types
	 */
	$post_types = apply_filters( 'crp_meta_box_post_types', $post_types );

	if ( in_array( $post_type, $post_types ) ) {

    	add_meta_box(
    		'crp_metabox',
    		__( 'Contextual Related Posts', CRP_LOCAL_NAME ),
    		'crp_call_meta_box',
    		$post_type,
    		'advanced',
    		'default'
    	);
	}
}
add_action( 'add_meta_boxes', 'crp_add_meta_box' , 10, 2 );


/**
 * Function to call the meta box.
 *
 * @since	1.9.1
 *
 */
function crp_call_meta_box() {
	global $post, $crp_settings;

	/**** Add an nonce field so we can check for it later. ****/
	wp_nonce_field( 'crp_meta_box', 'crp_meta_box_nonce' );

	/**** Get the thumbnail settings. The name of the meta key is defined in thumb_meta parameter of the CRP Settings array ****/
	$crp_thumb_meta = get_post_meta( $post->ID, $crp_settings['thumb_meta'], true );
	$value = ( $crp_thumb_meta ) ? $crp_thumb_meta : '';

	/**** Get related posts specific meta ****/
	$crp_post_meta = get_post_meta( $post->ID, 'crp_post_meta', true );

	if ( isset( $crp_post_meta['crp_disable_here'] ) ) {
		$crp_disable_here = $crp_post_meta['crp_disable_here'];
	} else {
		$crp_disable_here = 0;
	}

?>
	<p>
		<label for="thumb_meta"><?php _e( "Location of thumbnail:", CRP_LOCAL_NAME ); ?></label>
		<input type="text" id="thumb_meta" name="thumb_meta" value="<?php echo esc_attr( $value ) ?>" style="width:100%" />
		<em><?php _e( "Enter the full URL to the image (JPG, PNG or GIF) you'd like to use. This image will be used for the post. It will be resized to the thumbnail size set under Settings &raquo; Related Posts &raquo; Output Options", CRP_LOCAL_NAME ); ?></em>
		<em><?php _e( "The URL above is saved in the meta field:", CRP_LOCAL_NAME ); ?></em> <strong><?php echo $crp_settings['thumb_meta']; ?></strong>
	</p>

	<p>
		<?php if ( function_exists( 'tptn_add_viewed_count' ) ) { ?>
			<em style="color:red"><?php _e( "You have Top 10 WordPress Plugin installed. If you are trying to modify the thumbnail, then you'll need to make the same change in the Top 10 meta box on this page.", CRP_LOCAL_NAME ); ?></em>
		<?php } ?>
	</p>

	<?php
	if ( $crp_thumb_meta ) {
		echo '<img src="' . $value . '" style="max-width:100%" />';
	}
	?>

	<p>
		<label for="crp_disable_here"><?php _e( "Disable Related Posts display:", CRP_LOCAL_NAME ); ?></label>
		<input type="checkbox" id="crp_disable_here" name="crp_disable_here" <?php if ( 1 == $crp_disable_here ) { echo ' checked="checked" '; } ?> />
		<br />
		<em><?php _e( "If this is checked, then Contextual Related Posts will not automatically insert the related posts at the end of post content.", CRP_LOCAL_NAME ); ?></em>
	</p>


	<?php
	/**
	 * Action triggered when displaying Contextual Related Posts meta box
	 *
	 * @since	2.2
	 *
	 * @param	object	$post	Post object
	 */
	do_action( 'crp_call_meta_box', $post );
}


/**
 * Function to save the meta box.
 *
 * @since	1.9.1
 *
 * @param mixed $post_id
 */
function crp_save_meta_box( $post_id ) {
	global $crp_settings;

	$crp_post_meta = array();

    /**** Bail if we're doing an auto save ****/
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    /**** if our nonce isn't there, or we can't verify it, bail ****/
    if ( ! isset( $_POST['crp_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['crp_meta_box_nonce'], 'crp_meta_box' ) ) return;

    /**** if our current user can't edit this post, bail ****/
    if ( ! current_user_can( 'edit_posts' ) ) return;

    /**** Now we can start saving ****/
    if ( isset( $_POST['thumb_meta'] ) ) {
    	$thumb_meta = $_POST['thumb_meta'] == '' ? '' : ( $_POST['thumb_meta'] );
    }

	$crp_thumb_meta = get_post_meta( $post_id, $crp_settings['thumb_meta'], true );

	if ( $crp_thumb_meta && '' != $crp_thumb_meta ) {
		$gotmeta = true;
	} else {
		$gotmeta = false;
	}

	if ( $gotmeta && '' != $thumb_meta ) {
		update_post_meta( $post_id, $crp_settings['thumb_meta'], $thumb_meta );
	} elseif ( ! $gotmeta && '' != $thumb_meta ) {
		add_post_meta( $post_id, $crp_settings['thumb_meta'], $thumb_meta );
	} else {
		delete_post_meta( $post_id, $crp_settings['thumb_meta'] );
	}

	// Disable posts
	if ( isset( $_POST['crp_disable_here'] ) ) {
		$crp_post_meta['crp_disable_here'] = 1;
	} else {
		$crp_post_meta['crp_disable_here'] = 0;
	}

	/**
	 * Filter the CRP Post meta variable which contains post-specific settings
	 *
	 * @since	2.2.0
	 *
	 * @param	array	$crp_post_meta	CRP post-specific settings
	 * @param	int	$post_id	Post ID
	 */
	$crp_post_meta = apply_filters( 'crp_post_meta', $crp_post_meta, $post_id );

    /**** Now we can start saving ****/
	if ( empty( array_filter( $crp_post_meta ) ) ) {	// Checks if all the array items are 0 or empty
		delete_post_meta( $post_id, 'crp_post_meta' );	// Delete the post meta if no options are set
	} else {
		update_post_meta( $post_id, 'crp_post_meta', $crp_post_meta );
	}

	/**
	 * Action triggered when saving Contextual Related Posts meta box settings
	 *
	 * @since	2.2
	 *
	 * @param	int	$post_id	Post ID
	 */
	do_action( 'crp_save_meta_box', $post_id );
}
add_action( 'save_post', 'crp_save_meta_box' );

