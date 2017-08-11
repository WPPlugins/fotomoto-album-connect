<?php class wpPicasaAlbum {
	private $album_id;
	
	private $album_data;
	private $image_data;

	private $import_date;
	private $import_dir;

	function __construct($aData, $iData) {
		$this->album_id = $aData['id'];
		$this->album_data = $aData;
		$this->image_data = $iData;
	}

	function getImageCount() {
		return count($this->image_data);
	}

	function getTitle() {
		return $this->album_data['title'];
	}

	function getName() {
		return $this->album_data['name'];
	}

	function setImportDate($date_value) {
		$this->import_date = $date_value;
	}

	function prepareImportDir() {
		if (!$this->import_date)
			$this->import_date = date('Y-m-d-G-i-s');
		
		$upload_dir = wp_upload_dir();

		$base_import_dir = $upload_dir['basedir'] . '/' . 'PicasaImports';
		if (!is_dir($base_import_dir))
			mkdir($base_import_dir);
		
		$this->import_dir = $base_import_dir . '/' . $this->getName() . '-' . $this->import_date;
		if (!is_dir($this->import_dir))
			mkdir($this->import_dir);
		
		return $this->import_dir;
	}

	function attachToPost($pid, $settings) {
		$this->prepareImportDir();
		
		//Ajax information for status updates
		update_post_meta($pid, FACs::$meta_img_cnt, strval(count($this->image_data))); //keep track of img counts for status updates
		FACs::add_syncing_pid($pid, $this->album_id);
		
		foreach ($this->image_data as $image)
		{
			$image_path = $this->import_dir . '/' . preg_replace('/[^\w\.]/', '_', $image['file']);
			
			$ci = curl_init();
			curl_setopt($ci, CURLOPT_URL, $image['fullpath']);
			curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
			$image_data = curl_exec($ci);
			curl_close($ci);

			file_put_contents($image_path, $image_data);

			$wp_filetype = wp_check_filetype(basename($image_path), null);
			
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($image_path)),
				'post_content' => '',
				'post_status' => 'inherit'
			);

			if ($settings['sync_photo_description'])
				$attachment['post_content'] = $image['summary'];
			
			$attach_id = wp_insert_attachment($attachment, $image_path, $pid);
			$attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
			wp_update_attachment_metadata($attach_id, $attach_data);
		}
		
		FACs::remove_syncing_pid($pid);
	}
	
	function syncWithPost($pid, $settings) {
		if ($settings['sync_album_description'])
			wp_update_post(array( 'ID' => $pid, 'post_content' => $this->album_data['summary'] ));
		
		if ($this->album_id == get_post_meta($pid, 'picasa_gallery_id', true))
		{
			$this->repeatelyDeletePostAttachments($pid);
			$this->setImportDate(get_post_meta($pid, 'picasa_gallery_import_date', true));
			$this->attachToPost($pid, array(
				'sync_photo_description' => $settings['sync_photo_description']
			));
		}
	}

	// WTF? I'm not sure why WordPress can't delete all attachments
	// from a post in one go and I have to run this multiple times.
	function repeatelyDeletePostAttachments($pid) {
		$attachments = get_posts(array('post_type' => 'attachment', 'post_parent' => $pid));
		if ($attachments)
		{
			foreach ($attachments as $attachment)
			{
				wp_delete_attachment($attachment->ID, true);
			}
		}

		$attachments = get_posts(array('post_type' => 'attachment', 'post_parent' => $pid));
		if ($attachments && count($attachments) > 0)
			$this->repeatelyDeletePostAttachments($pid);
	}
} ?>
