<?php
	function zbTask_autoLoad($class_name){
		$file = zb_task . 'class/' . $class_name . '.class.php';	
		if(is_file($file)){
			require_once $file;
		}
		return false;
	}

	spl_autoload_register('zbTask_autoLoad');

?>