<?php
namespace socket;

// Excepciones del websocket_server
class websocket_server_exception extends \Exception{
	public function __construct($_msg){
		parent::__construct($_msg);
	}
}

// Clase del servidor websocket.
class websocket_server{

	private $hostname;
	private $protocol;
	private $port;
	private $stream_time;
	private $errno=0;
	private $errmsg='';
	private $connections = [];		
	private $server = null;

	public function __construct($_port='555',$_hostname='127.0.0.1',$_protocol='tcp',$_stream_time=null){
		
		$this->stream_time=null!==$_stream_time ? $_stream_time : new \socket\stream_time(0,50);
		$this->hostname=$_hostname;
		$this->port=$_port;
		$this->protocol=$_protocol;

		$this->server=stream_socket_server($this->protocol."://".$this->hostname.":".$this->port, $this->errno, $this->errmsg);
		if(false===$this->server){
			throw new websocket_server_exception("No se pudo crear el server por: {$this->errmsg}");
		}
	}

	// Se realiza cada iteracion del servidor para recoger y leer la información de los clientes conectados
	// guardando las nuevas conexiones y desconectando las obsoletas. Devuelve un array con la informacion 
	// recogida que hayan enviado los clientes, si han enviado alguna, si no genera un array vacio
	public function poll(){	

		$current_data=[];

		$read =[$this->server];

		foreach($this->connections as $peer=>$client){
			$read[$peer]=$client->get_resource();
		}

		$write=[];
		$except=[];

		$quantity=stream_select($read, $write, $except, $this->stream_time->seconds,$this->stream_time->microseconds);
		
		if (!$quantity) {
			return $current_data;
		}

		foreach ($read as $peer=>$c) {

			if($c==$this->server) {
				$this->look_connect();
				continue;
			}

			$client=$this->connections[$peer];
			
			if (feof($c)) {
				$this->disconnect_client($client);
				continue;
			}	
				
			$contents = $client->read();

			if(3===ord($contents)){
				$this->disconnect_client($client);
				continue;
			}
				
			$this->log($peer,'Send: '.trim($contents));

			$current_data[$client->peer]=$contents;
		}

		return $current_data;
	}

	// Devuelve las conexiones actuales del websocket
	public function get_connections(){
		return $this->connections;
	}
	
	// Guarda en log lo que se le pase por parametro. 
	// En este caso lo imprime por pantalla.
	public function log($_peer,$_msg){
		$time=date('Y-m-d H:i:s');
		echo "[$time] $_peer - $_msg".PHP_EOL;
	}

	// Desconecta un cliente del websocket y lo elimina de la lista de conexiones
	private function disconnect_client($_client){
		$peer=$_client->peer;
		$_client->disconnect();

		unset($this->connections[$peer]);
		$this->log($peer,'Connection closed');
	}

	// Comprueba si hay una nueva conexion y si es valida. Si es así la guarda
	// en la lista de conexiones actuales del websocket
	private function look_connect(){

		$c=\socket\socket_client::from_resource(@stream_socket_accept($this->server, empty($this->connections) ? -1 : 0));
		
		if(null===$c) {	
			$this->log('','Error to create client');	
			return;
		}

		$peer=$c->peer;
		$hash=$this->create_hash_key($c->read());
		$c->handshake($hash);
		$this->log($peer,'Connected');
		$this->connections[$peer] = $c;
	}

	// Crea el hash para el handshake con el cliente para ver si es correcto
	private function create_hash_key($_msg) {

		//TODO: do it better
		$hash='';
		$cut=explode("\n",$_msg);
		foreach($cut as $line){
			if(substr_count($line,'Sec-WebSocket-Key')){
				$data=explode(':',$line);
				$hash=trim($data[1]);
				break;
			}
		}
		
		$hash.='258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		return base64_encode(sha1($hash,true));
	}

	
}