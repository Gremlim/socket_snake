<?php 
ini_set('display_errors','1');
ini_set('error_reporting','-1');

require_once('autoload.php');

// DEFAULT CONFIG
$jugadores=3;
$ip='93.93.69.12'; 
$puerto='10555'; 

$usage='php server.php [-i IP] [-p PORT] [-j PLAYERS]'.PHP_EOL;

if($argc>1){
	$params=0;
	$values=0;
	foreach($argv as $key => $arg){
		
		if(0===$key){
			continue;
		}

		if(substr($arg,0,1)==='-'){
			$params++;
		}else{
			$values++;
		}

		$next=isset($argv[$key+1]) ? $argv[$key+1] : null;

		switch($arg){
			case '-i': $ip=$next; break;
			case '-p': $puerto=$next; break;
			case '-j': $jugadores=$next; break;
			default:
				if(substr($arg,0,1)==='-'){
					die($usage);
				}				
			break;
		}
	}

	if(0===$params || $values!==$params){
		die($usage);
	}
}

try{
	$game= new \app\app($ip,$puerto,$jugadores);
	$game->init();
}catch(\Exception $e){
	echo 'ERROR: '.$e->getMessage().PHP_EOL;
}