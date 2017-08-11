<?php class wpPicasaAccount {
	private $email;
	private $albums;

	function __construct($account_email) {
		$this->email = $account_email;
		$this->albums = array();
	}

	function getAlbums() {
		$args = array();
		$args['imgmax'] = FACs::get_imgmax();
		
		$xml= new wpPicasaApi($this->email, $args);
		$xml->getAlbums();
		$xml->parseAlbumXml(true);
		
		$this->albums = array();
		
		foreach ($xml->getData() as $aData)
		{
			$x= new wpPicasaApi($this->email, $args);
			$x->getImages($aData['id'], $aData['authkey']);
			$x->parseImageXml(true);
			$this->albums[$aData['id']] = new wpPicasaAlbum($aData, $x->getData());
		}

		return $this->albums;
	}

	function syncAllAlbums($settings) {
		$picasa_ablums = $this->getAlbums();
		$query = new WP_Query('post_type=gallery');
		while ($query->have_posts()) {
			$query->the_post();
			$pid = get_the_ID();
			$gid = get_post_meta($pid, 'picasa_gallery_id', true);
			if ($gid)
			{
				$picasa_album = $picasa_ablums[$gid];
				$picasa_album->syncWithPost($pid, $settings);
			}
		}
	}
} ?>
