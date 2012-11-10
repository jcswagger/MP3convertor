<?php

	// Config Class
	class Config
	{
		// Protected Fields
		protected $_audioQualities = array(64, 128, 320);

		// Constants
		const _TEMPVIDDIR = '../converter/videos/';
		const _SONGFILEDIR = '../converter/mp3/';
		const _FFMPEG = 'ffmpeg.exe';
		const _LOGSDIR = '../converter/logs/';
		const _VOLUME = '256';  // 256 is normal, 512 is roughly 1.5x louder, 768 is 2x louder, 1024 is 2.5x louder
		const _ENABLE_CONCURRENCY_CONTROL = true;  // Set value to 'true' to prevent possible errors when two users simultaneously download & convert the same video. Note: Enabling this feature will use up more server disk space.
	}

?>
