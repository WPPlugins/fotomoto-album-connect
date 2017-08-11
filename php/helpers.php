<?
class FACs{ //Static class holding constants for Fotomoto Album Connect
	static $plugin_title = "Fotomoto Album Connect";
	static $plugin_id = "fotomoto_album_connect";
	static $shortened_plugin_title = "Album Connect";
	static $connected_title = "Album"; //The import connected album/gallery name to be used in UI
	static $connected_title_plural = "Albums";
	static $post_type = "post"; //Type of post type that this plugin works with
	static $import_connect = "import_connected"; //Script name and possible html element constant
	static $script = "fac_script_js";
	
	static $curr_sync_pids = "fac_posts_currently_syncing"; //array of post currently being synced with their remote album, with remote id's reference the post ids (might have conflicts when two of same remote id are being synced but simply return the first for now)
	
	
	//Gallery Meta Settings
	static $meta_img_cnt = 'fac_gallery_image_count';
	
	static $ns = "fac_"; //Namespace for this plugin (Functions and Classes)
	
	//Settings
	static $settings_slug = "fotomoto_album_connect_settings";
	
	//Options
	static $options_id = "fotomoto_album_connect_options";
	
	static function plugin_dir(){
		return WP_PLUGIN_DIR . '/' . self::$plugin_id . '/'; 
	}
	
	static function get_options(){
		return get_option(self::$options_id);
	}
	
	static function get_option($id){
		$sets = self::get_options();
		return $sets[$id];
	}
	
	static function remove_syncing_pid($pid){
		$opts = self::get_options();
		$arr = $opts[self::$curr_sync_pids];
		$toRemove = null;
		
		if($arr){
			
			foreach( $arr as $key => $val){
				if($val == $pid){
					$toRemove = $key;
					break;
				}
			}
			
			if($toRemove)
				$arr[$toRemove] = null;
			
			$opts[self::$curr_sync_pids] = $arr;
			self::update_options($opts);	 
		}
	}
	
	static function get_syncing_pid($rid){
		$opts = self::get_options();
		$arr = $opts[self::$curr_sync_pids];
		
		if($arr){
			return $arr[$rid];
		}
		
		return null;
	}
	
	static function add_syncing_pid($pid, $rid){
		$opts = self::get_options();
		$arr = $opts[self::$curr_sync_pids];
		if(!$arr){
			$arr = array();
		}
		$arr[$rid] = $pid;
		
		$opts[self::$curr_sync_pids] = $arr;
		
		self::update_options($opts);
	}
	
	static function update_options($arr){
		update_option(self::$options_id, $arr);
	}
	
	static function reset_options(){
		self::update_options(self::def_opts());
	}
	
	/** Return default options array **/
	static function def_opts() {
		$args = array();
		$args[self::$pica_email] = "";
		$args[self::$pica_img_import_res] = 800;
		return $args;
	}
	
	//Picasa
	static $pica_img_import_res = "picasa_image_import_resolution";
	static $pica_email = "picasa_email";
	static $pica_img_resolutions = array(94, 110, 128, 200, 220, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600);
	
	/** Return resolution to be used to import images from picasa **/
	static function get_imgmax(){
		return self::get_option(self::$pica_img_import_res);
	}
	
	//Post Edit
	
	static $imp_con_btn = "import_connected"; //Import Connected Button (name/id)
	
	
	//Ajax
	
	static $sync_action = "fac_sync_album";
	static $get_connectables_action = "fac_get_connectables";
}

class FAHelper{
	
	public static $def_btn_classes = array("button-secondary");

	static function generateSelect($name = '', $options = array(), $sel_val) {
		$html = '<select name="'.$name.'">';
		foreach ($options as $ind => $option) {
			$html .= '<option ';
			if ($option == $sel_val)
				$html .= "selected='selected' ";
			$html .= 'value="'. $option .'">';
			$html .= $option;
			$html .= "</option>";
		}
		$html .= '</select>';
		return $html;
	}
	
 	static function button($text, $args = array(), $wrap_tag = ''){
			$class = $args['class'] ? $args['class'] : implode(self::$def_btn_classes, " ");
			$out =  '<a  class="' . $class . '" style="cursor: pointer;" ';
			foreach($args as $attr => $val){
				$out .= " $attr=\"$val\"";
			}
			$out .= ">$text</a>";
			if($wrap_tag != ''){
				$out = "<$wrap_tag>$out</$wrap_tag>";
			}
			return $out;
	}
	
}

?>