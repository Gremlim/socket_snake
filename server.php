<?php 
ini_set('display_errors','1');
ini_set('error_reporting','-1');

require_once('autoload.php');

// DEFAULT CONFIG
$jugadores=3;
$ip='93.93.69.12'; 
$puerto='10555'; 
$w=50;
$h=50;

$usage=<<<U
USAGE: php server.php [-i IP] [-p PORT] [-j PLAYERS] [-s WxH]
	-i	IP from server
	-p	Open port in server to comunicate by TCP
	-j	Number of players for room. MAX: 9
	-s	Size of world. Width x Height

U;

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
			case '-s': list($w,$h)=explode('x',$next); break;
			default:
				if(substr($arg,0,1)==='-'){
					die("ERROR: Invalid parameter '$arg'...".PHP_EOL.$usage);
				}				
			break;
		}
	}

	if(0===$params || $values!==$params){
		die("ERROR: invalid parameters...".PHP_EOL.$usage);
	}
}

try{
	$game= new \app\app($ip,$puerto,$jugadores,$w,$h);
	$game->init();
}catch(\Exception $e){
	echo 'ERROR: '.$e->getMessage().PHP_EOL;
}