<?php
namespace logics;

// Logica principal de la aplicacion manteniendo las conexiones de la misma.
class lobby implements \app\logic{
	private $peers=[];
	private $disconnected_pool=[];

	private $client;
	private $msg;
	private $ws;

	public function __construct(\socket\websocket_server $_ws){
		$this->ws=$_ws;
	}

	// Recibe la informacion de un cliente y su mensaje generado.
	public function input($_client,$_msg){
		$this->client=$_client;
		$this->msg=$_msg;
	}

	// Limpia los datos para que los residuos anteriores no afecten a la logica siguiente.
	public function clean(){
		$this->client=null;
		$this->msg=null;
	}

	// Ejecución de la misma logica comprobando la informacion que envian los clientes.
	// Si envian START significa que estan dispuestos para jugar una nueva partida
	// Si envian ENDGAME significa que han terminado y desean volver al lobby para empezar una nueva partida
	// Si se desconectan borra los clientes de la lista de conexiones actuales.
	public function run(){
		$connections=$this->ws->get_connections();
		
		foreach($this->peers as $peer=>$client){
			if(!isset($connections[$peer])){
				$this->disconnect_peer($client);
			}
		}
		
		if(!in_array($this->client,$connections)){
			$this->client=null;
			return;
		}
		
		switch($this->msg){
			case 'START':		
				if(!in_array($this->client,$this->peers)){
					$this->peers[$this->client->peer]=$this->client;
				}
			break;
			case 'ENDGAME':
				$this->disconnect_peer($this->client);
			break;
			default:

			break;
		}
	}

	// Obtiene los peers activos buscando partida.
	public function get_peers(){
		return $this->peers;
	}

	// Devuelve la lista de clientes que se han desconectado
	public function get_disconnected_pool(){
		return $this->disconnected_pool;
	}

	// Limpia la cola de clientes desconectados.
	public function clean_disconnected_pool(){
		$this->disconnected_pool=[];
	}

	// Desconecta un cliente de la aplicacion y lo añade a la cola de clientes desconectados
	private function disconnect_peer(\socket\socket_client $_client){
		if(in_array($_client,$this->peers)){
			unset($this->peers[$_client->peer]);
			$this->disconnected_pool[]=$_client;
		}
	}
}
