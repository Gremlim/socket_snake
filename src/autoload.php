<?php
spl_autoload_register(function($_classname){
	$filename=__DIR__.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.strtolower(str_replace("\\", DIRECTORY_SEPARATOR, $_classname)).'.php';
	if(is_file($filename)){
		require_once($filename);
	}
});