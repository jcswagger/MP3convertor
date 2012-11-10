<?php
	// Execution settings
	ini_set('max_execution_time',0);
	ini_set('display_errors',0);

	// Instantiate converter class
	include 'YouTubeToMp3Converter.class.php';
	$converter = new YouTubeToMp3Converter();

	// On download of MP3
	if (isset($_GET['mp3']))
	{
		$converter->DownloadMP3($_GET['mp3']);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>YouTube-To-Mp3 Converter</title>
	<style type="text/css">
		body
		{
			text-align:center;
			font:13px Verdana,Arial;
			margin-top:50px;
		}

		p
		{
			margin:15px 0;
			font-weight:bold;
		}

		form
		{
			width:450px;
			margin:0 auto;
			padding:15px;
			border:1px solid #ccc;
		}

		form input[type="text"]
		{
			width:385px;
		}

		form p
		{
			margin:10px 0;
			font-weight:normal;
		}

		#progress-bar
		{
			width:200px;
			padding:2px;
			border:2px solid #aaa;
			background:#fff;
			margin:0 auto;
		}

		#progress
		{
			background:#000;
			color:#fff;
			overflow:hidden;
			white-space:nowrap;
			padding:5px 0;
			text-indent:5px;
			width:0%;
		}

		#conversion-status
		{
			width:200px;
			padding:2px;
			margin:0 auto;
			color:#999;
			text-align:center;
		}
	</style>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript">
		var conversionLogLength = 0;

		function updateVideoDownloadProgress(percentage)
		{
			var progress = document.getElementById('progress');
			progress.style.width = progress.innerHTML = parseInt(percentage) + '%';
		}

		function updateConversionProgress(songFile)
		{
			var progress = document.getElementById('progress');
			document.getElementById('conversion-status').innerHTML = "Converting video. . .";
			$.ajax({
				type : "POST",
				url : "ffmpeg_progress.php",
				data : "uniqueId=<?php echo $converter->GetUniqueID(); ?>&logLength=" + conversionLogLength + "&mp3File=" + encodeURI(songFile),
				success : function(retVal, status, xhr) {
					var retVals = retVal.split('|');
					if (retVals[3] == 2)
					{
						progress.style.width = progress.innerHTML = parseInt(retVals[1]) + '%';
						if (parseInt(retVals[1]) < 100)
						{
							conversionLogLength = parseInt(retVals[0]);
							setTimeout(function(){updateConversionProgress(songFile);}, 10);
						}
						else
						{
							$("#preview").css("display", "none");
							var convertSuccessMsg = (retVals[2] == 1) ? '<p>Success!</p><p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?mp3=' + encodeURI(songFile) + '">Download your MP3 file</a>.</p>' : '<p>Error generating MP3 file!</p>';
							$("#conversionSuccess").html(convertSuccessMsg);
							$("#conversionForm").css("display", "block");
						}
					}
					else
					{
						setTimeout(function(){updateConversionProgress(songFile);}, 1);
					}
				},
				error : function(xhr, status, ex) {
					setTimeout(function(){updateConversionProgress(songFile);}, 1);
				}
			});
		}

		window.onload = function()
		{
			if (!document.getElementById('preview'))
			{
				$("#conversionForm").css("display", "block");
			}
		};
	</script>
</head>
<body>
	<h2>YouTube-To-Mp3 Converter</h2>
	<?php
		// On form submission...
		if ($_POST['submit'])
		{
			// Print "please wait" message and preview image
			echo '<div id="preview" style="display:block"><p>...Please wait while I try to convert:</p>';
			echo '<p><img src="http://img.youtube.com/vi/'.$converter->ExtractVideoId(trim($_POST['youtubeURL'])).'/1.jpg" alt="preview image" /></p>';
			echo '<p>'.$converter->ExtractSongTrackName(trim($_POST['youtubeURL']), 'url').'</p>';
			echo '<div id="progress-bar"><div id="progress">0%</div></div>';
			echo '<div id="conversion-status">Downloading video. . .</div></div>';
			flush();

			// Main Program Execution
			if ($converter->DownloadVideo(trim($_POST['youtubeURL'])))
			{
				echo '<div id="conversionSuccess"></div>';
				echo '<script type="text/javascript">var progressBar = document.getElementById("progress"); progressBar.style.width = progressBar.innerHTML = "0%"; updateConversionProgress("'.trim(strstr($converter->GetSongFileName(), '/'), '/').'");</script>';
				flush();
				$converter->GenerateMP3($_POST['quality']);
			}
			else
			{
				echo '<p>Error downloading video!</p>';
			}
		}
	?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="conversionForm" style="display:none">
		<p>Enter a valid YouTube.com video URL:</p>
		<p><input type="text" name="youtubeURL" /></p>
		<p><i>(i.e., "<span style="color:red">http://www.youtube.com/watch?v=HMpmI2F2cMs</span>")</i></p>
		<p style="margin-top:20px">Choose the audio quality (better quality results in larger files):</p>
		<p style="margin-bottom:25px"><input type="radio" value="64" name="quality" />Low &nbsp; <input type="radio" value="128" name="quality" checked="checked" />Medium &nbsp; <input type="radio" value="320" name="quality" />High</p>
		<p><input type="submit" name="submit" value="Create MP3 File" /></p>
	</form>
</body>
</html>
