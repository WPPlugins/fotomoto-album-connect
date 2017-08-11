<?php

require_once (WP_PLUGIN_DIR . THIS_PLUGIN_NAME . '/php/helpers.php');

class wpPicasaApi{
	private $xml;
	private $data;
	private $user;
	
	private $params=array(
		'thumbsize'=>160, 
		'imgmax'=> 800 //Yay redundancy, this gets overwritten no matter what...
	);
	
	function __construct($user,$params=array()){
		$this->user = $user;
		$this->_setParams($params);		
	}
	function __get($key){
		return (!isset($this->$key)) ? $this->$key:null;
	}
	function getData(){
		return $this->data;
	}
	
	
	
	/** UTILS **/
	// set addtional params
	private function _setParams($params=array()){
		if(is_array($params)){
			foreach($this->params as $k=>$v){
				if(array_key_exists($k,$params)){
					$this->params[$k]=$params[$k];
				}
			}
		}
	}
	
	private function _postTo($url, $data=array(), $header=array()) {
		
		//check that the url is provided
		if (!isset($url)) {
			return false;
		}
		
		//send the data by curl
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (count($data)>0) {
			//POST METHOD
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} else {
			$header[] = array("application/x-www-form-urlencoded");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		
		$response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
		
		//print_r($info);
		//print $response;
		if($info['http_code'] == 200) {
			return $response;
		} elseif ($info['http_code'] == 400) {
			throw new Exception('Bad request - '.$response);
		} elseif ($info['http_code'] == 401) {
			throw new Exception('Permission Denied - '.$response);
		} else {
			return false;
		}
	}	
	private function _getXml($url, $header=array()) {
		//check that the url is provided
		if (!isset($url)) {
			return false;
		}
		//send the data by curl
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST,0); // do not use POST to get xml feeds. GET only!!!
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //array('Content-type: application/atom+xml','Content-Length: 2000')
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$response = curl_exec($ch);
		if(intval(curl_errno($ch)) == 0){
        	$this->xml = $response;
		}else{
			$this->xml=null;
			$this->error = curl_error($ch);
		}
        $info = curl_getinfo($ch);
        curl_close($ch);
		
		//print_r($info);
		//print $response;
		if($info['http_code'] == 200) {
			return true;
		} elseif ($info['http_code'] == 400) {
			throw new Exception('Bad request - '.$response .' URL: '.$url);
			return false;
		} elseif ($info['http_code'] == 401) {
			throw new Exception('Permission Denied - '.$response);
			return false;
		} else {
			return false;
		}
		return false;
	}

	
	
	/****** 		Public getters 		********/
	function getAlbums(){
		$header = array( 
		    "MIME-Version: 1.0", 
		    "Content-type: text/html", 
		    "Content-transfer-encoding: text" 
		);
		$url='http://picasaweb.google.com/data/feed/api/user/'.$this->user.'?kind=album&thumbsize='.$this->params['thumbsize'].'c';
		$url.='&access=public';
		return $this->_getXml($url,$header);
	}
	function getImages($aid,$authkey=null){
		$header = array( 
		    "MIME-Version: 1.0", 
		    "Content-type: text/html", 
		    "Content-transfer-encoding: text" 
		);
		//http://picasaweb.google.com/data/feed/api/user/userID/albumid/albumID
		$url='http://picasaweb.google.com/data/feed/api/user/'.$this->user.'/albumid/'.$aid.'?kind=photo&imgmax='.$this->params['imgmax'];
		// may be we need to pass key here
		$ch = curl_init($url);
		return $this->_getXml($url,$header);
	}

	/****** 		parse XML 		********/
	function parseAlbumXml($killxml=false){
		$xml = new SimpleXMLElement($this->xml);
		$xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/'); // define namespace media
		$xml->registerXPathNamespace('gphoto', 'http://schemas.google.com/photos/2007'); // define namespace media
		$xml->registerXPathNamespace('georss', 'http://www.georss.org/georss'); // define namespace media
		$xml->registerXPathNamespace('gml', 'http://www.opengis.net/gml'); // define namespace media

		#print_r($xml);
		if(count($xml->entry) > 0){
			foreach($xml->entry as $i=>$oAlbum){
				$aAlbum = array(
					'author'=>array(
						'name'=>(string)$oAlbum->author->name, // Mikhail Kozlov
						'uri'=>(string)$oAlbum->author->uri //http://picasaweb.google.com/kozlov.m.a
					), // will keep this on record in case we decide to go with more than one album
					'id'=> (Array)$oAlbum->xpath('./gphoto:id'), //5516889074505060529
					'name'=>'',//20100902RussiaOddThings
					'authkey'=>'',
					'published'=>strtotime($oAlbum->published), // strtotime(2010-09-11T04:58:08.000Z);
					'updated'=>strtotime($oAlbum->updated),// // strtotime(2010-09-11T04:58:08.000Z);
					'title' =>	(string)$oAlbum->title,//2010-09-02 - Russia - Odd Things
					'thumbnail' => (Array)$oAlbum->xpath('./media:group/media:thumbnail'), // 
					'latlong' => '', //
					'summary' =>addslashes((string) $oAlbum->summary), //Some things in Russia make you wonder
					'rights' => (string)$oAlbum->rights, //public
					'links' => array(
						'text/html'=>'', //http://picasaweb.google.com/kozlov.m.a/20100902RussiaOddThings
						'application/atom+xml'=>'' //http://picasaweb.google.com/data/feed/api/user/kozlov.m.a/albumid/5516889074505060529
					)
				);
				foreach($oAlbum->link as $oLink){
					$a = (Array)$oLink->attributes();
					$a = $a['@attributes'];
					if($a['rel'] == 'alternate' || $a['rel'] == 'self'){
						$aAlbum['links'][$a['type']] = $a['href'];
					}
				}
				unset($oLink);
				$aAlbum['thumbnail'] = (Array)$aAlbum['thumbnail'][0];
				$aAlbum['thumbnail'] = $aAlbum['thumbnail']['@attributes'];
				// $aAlbum['latlong'] = ( $oAlbum->xpath('./georss:where') !== false && $oAlbum->xpath('./georss:where/gml:Point') !== false ) ? (Array)$oAlbum->xpath('./georss:where/gml:Point/gml:pos'):array(); // 
				// $aAlbum['latlong'] = (isset($aAlbum['latlong'][0])) ? explode(' ',(string)$aAlbum['latlong'][0]):array();
				// $aAlbum['latlong'] = (count($aAlbum['latlong']) == 1) ? false:$aAlbum['latlong'];
				$aAlbum['id'] = (string)$aAlbum['id'][0];
				$url = parse_url($aAlbum['links']['text/html']);
				$tmp = explode('/',$url['path']);
				$aAlbum['name']=end($tmp);
				// if we use auth set authkey
				if(!empty($this->_authCode)){
					parse_str($url['query'], $url['query']);
					$aAlbum['authkey']=$url['query']['authkey'];
				}				
				unset($tmp);
				$this->data[$aAlbum['name']]=$aAlbum;
				unset($aAlbum);				
			}
			unset($oAlbum);
		}
		unset($xml);
		if($killxml){
			unset($this->xml);
		}
	}

	function parseImageXml($killxml=false){
		$xml = new SimpleXMLElement($this->xml);
		$xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/'); // define namespace media
		$xml->registerXPathNamespace('gphoto', 'http://schemas.google.com/photos/2007'); // define namespace media
		$xml->registerXPathNamespace('georss', 'http://www.georss.org/georss'); // define namespace media
		$xml->registerXPathNamespace('gml', 'http://www.opengis.net/gml'); // define namespace media
		$xml->registerXPathNamespace('exif', 'http://schemas.google.com/photos/exif/2007'); // define namespace media
		if(count($xml->entry) > 0){
			$c=0;
			foreach($xml->entry as $i=>$oImage){
				$c++;
				$aImage = array(
					'id'=> (Array)$oImage->xpath('./gphoto:id'), //5516889074505060529
					'published'=>strtotime($oImage->published), // strtotime(2010-09-11T04:58:08.000Z);
					'updated'=>strtotime($oImage->updated),// // strtotime(2010-09-11T04:58:08.000Z);
					'file' =>(string)$oImage->title,//2010-09-02 - Russia - Odd Things
					'fullpath' =>$oImage->content,//2010-09-02 - Russia - Odd Things
				   	'width'=>(Array)$oImage->xpath('./gphoto:width'), // width of the original in px
				    'height'=>(Array)$oImage->xpath('./gphoto:height'), // height of the original in px 
				    'size'=>(Array)$oImage->xpath('./gphoto:size'), // file size of the original in kb				
					'latlong' => '', //
					'thumbnail' => (Array)$oImage->xpath('./media:group/media:thumbnail'), //
					'summary' =>addslashes((string) $oImage->summary), //Some things in Russia make you wonder
					'rights' => (Array)$oImage->xpath('./gphoto:access'), //public
					'pos'=>$c,
					'show'=>'yes',
					'links' => array(
						'text/html'=>'', //http://picasaweb.google.com/kozlov.m.a/20100902RussiaOddThings
						'application/atom+xml'=>'' //http://picasaweb.google.com/data/feed/api/user/kozlov.m.a/albumid/5516889074505060529
					)
				);
				
				foreach($oImage->link as $oLink){
					$a = (Array)$oLink->attributes();
					$a = $a['@attributes'];
					if($a['rel'] == 'alternate' || $a['rel'] == 'self'){
						$aImage['links'][$a['type']] = $a['href'];
					}
				}
				unset($oLink);
				$aImage['thumbnail'] = (Array)$aImage['thumbnail'][0];
				$aImage['thumbnail'] = $aImage['thumbnail']['@attributes'];
				// some trickery to get image path
				$aImage['fullpath'] = (Array)$aImage['fullpath'];
				$aImage['fullpath'] =str_replace($aImage['file'],'',$aImage['fullpath']['@attributes']['src']);
				// flatten id
				$aImage['id'] = (string)$aImage['id'][0];
				
				// private albums do not seem to have georss.
				$ns = $xml->getDocNamespaces();
				if(array_key_exists('georss',$ns)){
					// lat long as array
					$aImage['latlong'] = (Array)$oImage->xpath('./georss:where/gml:Point/gml:pos');
					$aImage['latlong'] = (isset($aImage['latlong']) && isset($aImage['latlong'][0])) ? explode(' ',(string)$aImage['latlong'][0]):array();
					$aImage['latlong'] = (count($aImage['latlong']) == 1) ? false:$aImage['latlong'];
				}
				// flatten right, size, width, height
				$aImage['size'] = (string)$aImage['size'][0];
				$aImage['rights'] = (string)$aImage['rights'][0];
				$aImage['height'] = (string)$aImage['height'][0];
				$aImage['width'] = (string)$aImage['width'][0];
				unset($tmp);
				$this->data[]=$aImage;
				unset($aImage);				
			}
			unset($oImage);
		}
		unset($xml);
		if($killxml){
			unset($this->xml);
		}
	}
}
?>
