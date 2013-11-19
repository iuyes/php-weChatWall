<?php
if(!empty($head['script'])){
	foreach ($head['script'] as $scriptS){
		echo "<script type='text/javascript' src='".$scriptS."' ></script>";
	}
}
?>
</body>
</html>