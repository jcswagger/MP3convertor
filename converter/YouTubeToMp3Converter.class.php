<?php

	// Conversion Class
	include 'config.class.php';
	class YouTubeToMp3Converter extends Config
	{
		// Private Fields
		private $_songFileName = '';
		private $_flvUrls = array();
		private $_tempVidFileName;
		private $_uniqueID = '';
		private $_vidSrcTypes = array('source_code', 'url');
		private $_percentVidDownloaded = 0;

		#region Public Methods
		function __construct()
		{
			$this->_uniqueID = time() . "_" . uniqid('', true);
		}

		function DownloadVideo($youTubeUrl)
		{
			$file_contents = file_get_contents($youTubeUrl);
			if ($file_contents !== false)
			{
				$this->SetSongFileName($file_contents);
				$this->SetFlvUrls($file_contents);
				if ($this->GetSongFileName() != '' && count($this->GetFlvUrls()) > 0)
				{
					return $this->SaveVideo($this->GetFlvUrls());
				}
			}
			return false;
		}

		function GenerateMP3($audioQuality)
		{
			echo "<pre>SCRIPT_NAME->".print_r($_SERVER['SCRIPT_NAME'],true)."</pre>";
			$qualities = $this->GetAudioQualities();
			$quality = (in_array($audioQuality, $qualities)) ? $audioQuality : $qualities[1];
			$exec_string = parent::_FFMPEG.' -i '.$this->GetTempVidFileName().' -vol '.parent::_VOLUME.' -y -acodec libmp3lame -ab '.$quality.'k '.$this->GetSongFileName() . ' 2> logs/' . $this->_uniqueID . '.txt';
			$ffmpegExecUrl = preg_replace('/(([^\/]+?)(\.php))$/', "exec_ffmpeg.php", "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
			$postData = "cmd=".urlencode($exec_string);
			echo "<pre>ffmpegExecUrl-> ".print_r($ffmpegExecUrl,true)."</pre>";
			echo "<pre>exec_string-> ".print_r($exec_string,true)."</pre>";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $ffmpegExecUrl);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_exec($ch);
			curl_close($ch);
		}

		function DownloadMP3($file)
		{
			$filepath = parent::_SONGFILEDIR . urldecode($file);
			$filename = urldecode($file);
			if (parent::_ENABLE_CONCURRENCY_CONTROL)
			{
				$filename = preg_replace('/((_uuid-)(\w{13})(\.mp3))$/', "$4", $filename);
			}
			if (is_file($filepath))
			{
				header('Content-Type: audio/mpeg3');
				header('Content-Length: ' . filesize($filepath));
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				ob_clean();
				flush();
				readfile($filepath);
				die();
			}
			else
			{
				$redirect = explode("?", $_SERVER['REQUEST_URI']);
				header('Location: ' . $redirect[0]);
			}
		}

		function ExtractSongTrackName($vidSrc, $srcType)
		{
			$name = '';
			$vidSrcTypes = $this->GetVidSrcTypes();
			if (in_array($srcType, $vidSrcTypes))
			{
				$vidSrc = ($srcType == $vidSrcTypes[1]) ? file_get_contents($vidSrc) : $vidSrc;
				if ($vidSrc !== false && preg_match ('/eow-title/',$vidSrc))
				{
					$name = end(explode('eow-title',$vidSrc));
					$name = current(explode('">',$name));
					$name = end(explode('title="', $name));
					$name = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $name);
					$name = (!empty($name)) ? html_entity_decode($name) : 'unknown_'.time();
				}
			}
			return $name;
		}

		function ExtractVideoId($youTubeUrl)
		{
			$v = '';
			$urlQueryStr = parse_url(trim($youTubeUrl), PHP_URL_QUERY);
			if ($urlQueryStr !== false && !empty($urlQueryStr))
			{
				parse_str($urlQueryStr);
			}
			return $v;
		}

		function UpdateVideoDownloadProgress($downloadSize, $downloaded, $uploadSize, $uploaded)
		{
			echo "<pre>UpdateVideoDownloadProgress-> ".var_dump($downloadSize, $downloaded, $uploadSize, $uploaded)."</pre>";
			// $percent = round($downloaded/$downloadSize, 2) * 100;
			// if ($percent > $this->_percentVidDownloaded)
			// {
			// 	$this->_percentVidDownloaded++;
			// 	echo '<script type="text/javascript">updateVideoDownloadProgress("'. $percent .'");</script>';
			// 	ob_end_flush();
			// 	ob_flush();
			// 	flush();
			// }
		}
		#endregion

		#region Private "Helper" Methods
		private function SaveVideo(array $urls)
		{

			$success = false;
			$vidCount = -1;
			while (!$success && ++$vidCount < count($urls))
			{
				echo "<pre>url-> ".print_r($urls[$vidCount],true)."</pre>";
				$this->_percentVidDownloaded = 0;
				$this->SetTempVidFileName();
				$file = fopen($this->GetTempVidFileName(), 'w');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_FILE, $file);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_URL, $urls[$vidCount]);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_NOPROGRESS, false);
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, 'UpdateVideoDownloadProgress'));
				curl_setopt($ch, CURLOPT_BUFFERSIZE, 4096000);
				curl_exec($ch);
				curl_close($ch);
				fclose($file);
				if (is_file($this->GetTempVidFileName()))
				{
					if (!filesize($this->GetTempVidFileName()) || filesize($this->GetTempVidFileName()) < 10000)
					{
						unlink($this->GetTempVidFileName());
					}
					else
					{
						$success = true;
					}
				}
			}
			$this->GenerateMP3(64);
			return $success;
		}
		#endregion

		#region Properties
		public function GetSongFileName()
		{
			return $this->_songFileName;
		}
		private function SetSongFileName($file_contents)
		{
			$vidSrcTypes = $this->GetVidSrcTypes();
			$trackName = $this->ExtractSongTrackName($file_contents, $vidSrcTypes[0]);
			if (!empty($trackName))
			{
				$fname = parent::_SONGFILEDIR . preg_replace('/_{2,}/','_',preg_replace('/ /','_',preg_replace('/[^A-Za-z0-9 _-]/','',$trackName)));
				$fname .= (parent::_ENABLE_CONCURRENCY_CONTROL) ? uniqid('_uuid-') : '';
				$this->_songFileName = $fname . '.mp3';
			}
		}

		public function GetFlvUrls()
		{
			return $this->_flvUrls;
		}
		private function SetFlvUrls($file_contents)
		{
			$vidUrls = array();
			$vidSrcTypes = $this->GetVidSrcTypes();
			if (preg_match('/(yt\.playerConfig =)([^\r\n]+)/', $file_contents, $matches) == 1)
			{
				$jsonObj = json_decode(trim($matches[2], ';'));
				if (isset($jsonObj->args->url_encoded_fmt_stream_map))
				{
					$urls = urldecode(urldecode($jsonObj->args->url_encoded_fmt_stream_map));
					$urlsArr = preg_split('/(itag=)(\d+)(&url=)/', $urls, -1, PREG_SPLIT_NO_EMPTY);
					foreach ($urlsArr as $url)
					{
						if (preg_match('/quality=small/',$url) != 1)
						{
							$url = preg_replace('/sig=/', "signature=", $url);
							$url = trim($url, ',');
							$url .= '&title=' . urlencode($this->ExtractSongTrackName($file_contents, $vidSrcTypes[0]));
							$url = preg_replace_callback('/(&type=)(.+?)(&)/', function($match){return $match[1].urlencode($match[2]).$match[3];}, $url);
							$vidUrls[] = $url;
						}
					}
					$vidUrls = array_reverse($vidUrls);
					//die(print_r($vidUrls));
				}
			}
			$this->_flvUrls = $vidUrls;
		}

		public function GetAudioQualities()
		{
			return $this->_audioQualities;
		}

		private function GetTempVidFileName()
		{
			return $this->_tempVidFileName;
		}
		private function SetTempVidFileName()
		{
			$this->_tempVidFileName = parent::_TEMPVIDDIR . $this->_uniqueID .'.flv';
		}

		public function GetVidSrcTypes()
		{
			return $this->_vidSrcTypes;
		}

		public function GetUniqueID()
		{
			return $this->_uniqueID;
		}
		#endregion
	}

?>
