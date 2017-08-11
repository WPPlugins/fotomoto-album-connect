<?php
/*
Plugin Name: Fotomoto Album Connect
Plugin URI: http://www.fotomoto.com
Description: Allows images to be imported from remote image albums sources (e.g. Picasa) and associated with posts as attachments.
Version: 0.1.0
Author: Fotomoto
Author URI: http://www.fotomoto.com/
*/

/*  Copyright 2011  Fotomoto  (email : support@fotomoto.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define(THIS_PLUGIN_NAME, '/fotomoto_album_connect');

require_once (WP_PLUGIN_DIR . THIS_PLUGIN_NAME . '/php/helpers.php');
require_once (FACs::plugin_dir() . 'php/wpPicasaApi.php');
require_once (FACs::plugin_dir() . 'php/wpPicasaAlbum.php');
require_once (FACs::plugin_dir() . 'php/wpPicasaAccount.php');

require_once (FACs::plugin_dir() . 'php/settings.php');
require_once (FACs::plugin_dir() . 'php/post_edit.php');

define("FOTOMOTO_ALBUM_CONNECT_OPTIONS", FACs::$options_id);


global $wpdb;
$wpdb->{FOTOMOTO_ALBUM_CONNECT_OPTIONS} = $wpdb->prefix . FOTOMOTO_ALBUM_CONNECT_OPTIONS;

$fotomoto_picasa_options = FACs::get_options();

function activate_fotomoto_picasa() {  
	global $wpdb;
	//add_option(FACs::$options_id, FACs::def_opts());
	$table_name = $wpdb->prefix . FOTOMOTO_ALBUM_CONNECT_OPTIONS;
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
			`meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`meta_key` varchar(255) DEFAULT NULL,
			`meta_value` longtext,
			PRIMARY KEY (`meta_id`)
		);";
		$wpdb->query($sql);
	}
	
	FACs::update_options(FACs::def_opts()); //initial default options
}

function deactive_fotomoto_picasa() {
	global $wpdb;
	delete_option(FACs::$options_id);
	$table_name = $wpdb->prefix . FOTOMOTO_ALBUM_CONNECT_OPTIONS;
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
}

register_activation_hook(__FILE__, 'activate_fotomoto_picasa');
register_deactivation_hook(__FILE__, 'deactive_fotomoto_picasa');

// Menu Registrations =============================================================================

function fac_admin_menu() {
	global $wpdb;
	$settings = FACs::get_options();

	//Add Settings Page to Admin Menu
	$t = FACs::$shortened_plugin_title;
	add_options_page($t, $t, 'manage_options', FACs::$settings_slug, FACS::$ns.'settings');
	
	if (is_admin())
	{
		add_action( 'admin_enqueue_scripts', 'fac_modal_scripts', 10, 1 );
		add_meta_box('fotomoto-picasa-sync', FACs::$plugin_title, FACs::$ns.'meta_box', FACs::$post_type , 'normal', 'high');
	}
}
add_action('admin_menu', FACs::$ns.'admin_menu');

function fac_modal_scripts( $hook){
	
	if($hook == 'post.php' || $hook == 'post-new.php'){
		wp_enqueue_script(FACs::$script, plugins_url('js/script.js', __FILE__), array('jquery-ui-dialog'));
		
	}
}


// Picasa Import Page =============================================================================

function fotomoto_picasa_galleries() {
	global $wpdb;
	$settings = FACs::get_options();

	global $current_user;
	get_currentuserinfo();
	
	$picasa_loaded = true;

	try
	{
		$picasa_account = new wpPicasaAccount($settings["picasa_email"]);
		$picasa_ablums = $picasa_account->getAlbums();
		$picasa_loaded = true;
	}
	catch (Exception $e)
	{
		$picasa_loaded = false;
	}
	
	if (!$picasa_loaded)
	{
	echo '<div id="setting-error-settings_updated" class="updated settings-error">';
	echo '<p><strong>Error Loading Picasa Galleries</strong>. Please make sure your Picasa Email is correctly set.</p>';
	echo '</div>';
	return;
	}

	if ($_POST['pi_action'] == 'import')
	{
		$picasa_album = $picasa_ablums[$_POST['gallery_id']];

		$post = array(
			'post_status' => 'draft', 
			'post_type' => $_POST['gallery_type'],
			'post_title' => $picasa_album->getTitle(),
			'post_name' => $picasa_album->getName(),
			'post_author' => $current_user->ID
		);

		$pid = wp_insert_post($post);

		$import_date = date('Y-m-d-G-i-s');

  	update_post_meta($pid, 'picasa_gallery_id', $_POST['gallery_id']);
  	update_post_meta($pid, 'picasa_gallery_name', $picasa_album->getTitle());
  	update_post_meta($pid, 'picasa_gallery_import_date', $import_date);

  	$picasa_album->setImportDate($import_date);
  	$picasa_album->attachToPost($pid, array(
  		'sync_photo_description' => $settings['picasa_sync_photo_description'] == '1'
	  ));
  	
  	echo '<div id="setting-error-settings_updated" class="updated settings-error">';
  	echo '<p>';
  	echo '<strong>Gallery Imported:</strong> ' . $picasa_album->getTitle() . '.';
  	echo ' <a href="' . get_edit_post_link($pid) . '">Edit</a>';
  	echo ' | <a href="' . get_permalink($pid) . '">Preview</a>';
  	echo '</p>';
  	echo '</div>';
	}
	else if ($_POST['pi_action'] == 'sync')
	{
		$picasa_account->syncAllAlbums(array(
			'sync_album_description' => $settings['picasa_sync_album_description'] == '1',
			'sync_photo_description' => $settings['picasa_sync_photo_description'] == '1'
		));

		echo '<div id="setting-error-settings_updated" class="updated settings-error">';
    	echo '<p>';
    	echo '<strong>Sync Complete</strong>';
    	echo '</p>';
    	echo '</div>';
	}
	
	echo '
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br></div>
		<h2>Fotomoto Picasa Galleries</h2>
		<form action="" method="post" class="themeform">
			<input type="hidden" id="pi_action" name="pi_action" value="import" />
			<table class="form-table">
				<tbody>
					<tr>
						<th>Import From Gallery</th>
						<td>
							<select name="gallery_id">';
							foreach($picasa_ablums as $aid => $picasa_ablum) {
								echo '<option value="' . $aid . '">' . $picasa_ablum->getTitle() . ' (' . $picasa_ablum->getImageCount() . ' images)</option>';
							}
							echo '
							</select>
						</td>
					</tr>
					<tr>
						<th>Import As Gallery Type</th>
						<td>
							<select name="gallery_type">';
							$synced_post_types_ary = explode(',', $settings['synced_post_types']);
							foreach($synced_post_types_ary as $gid => $gallery_type) {
								echo '<option value="' . trim($gallery_type) . '">' . trim($gallery_type) . '</option>';
							}
							echo '
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button-primary" value="Import Gallery" />
				or
				<input type="submit" name="reset-default" class="button-primary" value="Sync Existing Galleries" onclick="jQuery(\'#pi_action\').val(\'sync\');" />
			</p>
		</form>
	</div>';
}

//Ajax

add_action('wp_ajax_' . FACs::$sync_action , FACs::$sync_action);
add_action('wp_ajax_' . FACs::$get_connectables_action, FACs::$get_connectables_action);
add_action('wp_ajax_fac_diassociate_album', 'fac_diassociate_album');
add_action('wp_ajax_fac_remote_album_image_count', 'fac_remote_album_image_count');
add_action('wp_ajax_fac_local_album_image_count', 'fac_local_album_image_count');
add_action("wp_ajax_fac_syncing_post_id", 'fac_syncing_post_id');

function fac_syncing_post_id(){
	$rid = $_POST['rid']; //id of remote album being imported/synced
	echo FACs::get_syncing_pid($rid);	
	//echo print_r(FACs::get_option(FACs::$curr_sync_pids));	
	die();
}

function fac_local_album_image_count(){
	$pid = $_POST['pid'];
	echo count(get_children('post_type=attachment&post_mime_type=image&orderby=menu_order&post_parent=' . $pid));
	die();
}


function fac_remote_album_image_count(){
	$pid = $_POST['pid'];
	if($pid){
		echo intval(get_post_meta($pid, FACs::$meta_img_cnt, true));
	}else{
		$rid = $_POST['rid'];
		$picasa_account = new wpPicasaAccount( FACs::get_option(FACs::$pica_email) );
		$picasa_ablums = $picasa_account->getAlbums();
		$album = $picasa_ablums[$rid];
		echo $album->getImageCount();
	}
	
	die();
}

function fac_diassociate_album(){
	$pid = $_POST['pid'];

	delete_post_meta($pid, 'picasa_gallery_id');
	delete_post_meta($pid, 'picasa_gallery_name');
	delete_post_meta($pid, 'picasa_gallery_import_date');
	delete_post_meta($pid, FACs::$meta_img_cnt);
	echo "done.";
	die();
}

function fac_sync_album() {

	$pid = $_POST['pid'];
	$gid = $_POST['gid'];
	
	$pid = intval($pid);

	$picasa_account = new wpPicasaAccount( FACs::get_option(FACs::$pica_email) );
	$picasa_ablums = $picasa_account->getAlbums();
	$picasa_album = $picasa_ablums[$gid];
	
	

	$args= array(
		'sync_album_description' => $_POST['al_desc'] == 'on',
		'sync_photo_description' => $_POST['ph_desc'] == 'on'
	);
	
	$redirect_user = false;
	
	if($pid == 0){ //on post-new page. make a new post, then redirect user 
			$post = array(
				'post_status' => 'draft', 
				'post_type' => 'post',
				'post_title' => $picasa_album->getTitle(),
				'post_name' => $picasa_album->getName(),
				'post_author' => $current_user->ID
			);

			$pid = wp_insert_post($post);
			$redirect_user = true;
	}
	
	$is_new = $gid != get_post_meta($pid, 'picasa_gallery_id', true);

	if ($is_new)
	{
	  	update_post_meta($pid, 'picasa_gallery_id', $gid);
	  	update_post_meta($pid, 'picasa_gallery_name', $picasa_album->getTitle());
	  	update_post_meta($pid, 'picasa_gallery_import_date', $import_date); 	
	}
	
	$picasa_album->syncWithPost($pid, $args);
	
	if($redirect_user){
		echo "url:" . get_edit_post_link($pid);
	}else{
		echo "done";
	}
	
	die(); // this is required to return a proper result
}

function fac_get_connectables(){
	global $wpdb;

	try
	{
		$picasa_account = new wpPicasaAccount(FACs::get_option(FACs::$pica_email));
		$albums = $picasa_account->getAlbums();
		$picasa_loaded = true;
	}
	catch (Exception $e)
	{
		$picasa_loaded = false;
	}
	
	if (!$picasa_loaded)
	{
		return;
	}
	
	foreach($albums as $aid => $picasa_ablum) {
		echo '<option value="' . $aid . '">' . $picasa_ablum->getTitle() . ' (' . $picasa_ablum->getImageCount() . ' images)</option>';
	}
	
	die(); // this is required to return a proper result
}

// Automatic Sync =================================================================================

function fotomoto_picasa_cron() {
	return array(
		'hour' => array(
			'interval' => 3600,
			'display' => 'Picasa sync every hour'
		),
		'day' => array(
			'interval' => 86400,
			'display' => 'Picasa sync every day'
		),
		'week' => array(
			'interval' => 604800,
			'display' => 'Picasa sync every week'
		)
	);
}
add_filter('cron_schedules', 'fotomoto_picasa_cron');

if ($fotomoto_picasa_options['picasa_sync_interval'] == 'hour' && !wp_next_scheduled('fotomoto_picasa_sync_event'))
	wp_schedule_event(time(), 'fotomoto_picasa_sync_hour', 'fotomoto_picasa_sync_event');
else if ($fotomoto_picasa_options['picasa_sync_interval'] == 'day' && !wp_next_scheduled('fotomoto_picasa_sync_event'))
	wp_schedule_event(time(), 'fotomoto_picasa_sync_day', 'fotomoto_picasa_sync_event');
else if ($fotomoto_picasa_options['picasa_sync_interval'] == 'week' && !wp_next_scheduled('fotomoto_picasa_sync_event'))
	wp_schedule_event(time(), 'fotomoto_picasa_sync_week', 'fotomoto_picasa_sync_event');

function fotomoto_picasa_auto_sync() {
	global $wpdb;
	$settings = get_option('fotomoto_picasa_options');
	
	if ($settings["picasa_email"])
	{
		$picasa_account = new wpPicasaAccount($settings["picasa_email"]);
		$picasa_account->syncAllAlbums(array(
			'sync_album_description' => $settings['picasa_sync_album_description'] == '1',
			'sync_photo_description' => $settings['picasa_sync_photo_description'] == '1'
		));
	}
}

add_action('fotomoto_picasa_sync_event', 'fotomoto_picasa_auto_sync');

// Utilities ======================================================================================

function fotomoto_picasa_post_id() {
	if (isset($_GET['post']))
		$post_id = (int)$_GET['post'];
	elseif (isset($_POST['post_ID']))
		$post_id = (int)$_POST['post_ID'];
	else
		$post_id = 0;
	return $post_id;
}

?>
