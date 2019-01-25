<?php 
namespace socket;

// Clase de tiempo para manejar el socket y cada paso que va a realizar
// Dividido por segundos y microsegundos
class stream_time{
	public $seconds=0;
	public $microseconds=0;
	public function __construct($_sec,$_msec){
		$this->seconds=$_sec;
		$this->microseconds=$_msec;
	}
}