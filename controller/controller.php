<?php
if(isset($_REQUEST)){
	$ct = new Controller($_REQUEST);
}
class Controller {

	private $_call_method;
	private $_params;
	private $_conveter;

	public function __construct($request){
		require_once  "../converter/YouTubeToMp3Converter.class.php";
		$this->_call_method = 'DownloadVideo';
		$this->_params = $request;
		$this->_conveter = new YouTubeToMp3Converter();
		call_user_func(array($this->_conveter, $this->_call_method), urldecode($this->_params['url']));
	}
}
?>