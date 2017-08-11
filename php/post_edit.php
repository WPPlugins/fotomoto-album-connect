<?php

require_once (WP_PLUGIN_DIR . THIS_PLUGIN_NAME . '/php/helpers.php');

// Post Edit meta Box =============================================================================

function fac_meta_box() {
	global $post;
	
	//variables
	$pid = fotomoto_picasa_post_id();
	$picasa_gallery_id = get_post_meta($pid, 'picasa_gallery_id', true);
	$picasa_gallery_name = get_post_meta($pid, 'picasa_gallery_name', true);
	

	
	
	$no_imgs_but_associated_with_album = $picasa_gallery_id && !get_children(array('post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image'));
	$c_titl = FACs::$connected_title;
	
	//three cases. 
	
	//id but no attachments, attempt sync again or import new album 
	
	if($no_imgs_but_associated_with_album){
		echo "<p class='error' style='line-height: 1.5em'><span>". $c_titl . " has no images but remote link remains.</span><a style='margin-left: 5px' href='#' class='button-secondary' onclick=\"jQuery.fac.interact.disconnectAlbum('$picasa_gallery_id')\">Remove link</a></p>";
	}
	
	if ($picasa_gallery_id)
	{
		echo '<label for="' . $meta_box['name'] . '">' . $meta_box['title'] . '</label>';
		
		$sync_in_prog = get_post_meta($pid, 'sync_in_progress', true);
		
		//If no attachments.
		
		if (!$picasa_gallery_name)
			$picasa_gallery_name = 'NO '. strtoupper(FACs::$connected_title) .' NAME';
		
			echo '<p>'. FACs::$connected_title . ': <b>' . $picasa_gallery_name . '</b></p><br/>' . FAHelper::button("Sync", array(
				'id' => 'picasa_sync_link', 
				'onclick' => "jQuery.fac.syncAlbumModal('$picasa_gallery_id')",
				'alt' => 'Attach images from a remote album to this post.'
				) );
		
		if ($meta_box['description'])
			echo '<p>' . $meta_box['description'] . '</p>';
	}
	
	if(!$picasa_gallery_id || $no_imgs_but_associated_with_album)
	{
		echo FAHelper::button('Import ' . $c_titl, array('id' => 'import-connected-btn'), $no_imgs_but_associated_with_album ? '' : 'p'); //NOTE: import connected is not found in helpers.php as constsant since modal.js references it directly
	}
	
	echo '<p id="picasa_sync_msg" style="display: none;"></p>';
	
}
