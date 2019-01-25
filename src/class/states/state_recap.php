<?php 
namespace states;

// Estado en el que están los jugadores tras el juego. Les devuelve la informacion
// de por que se ha acabado el juego
class state_recap implements \app\state{
	private $msg_info='';
	private $clients;
	private $send=false;

	public function __construct(array $_clients, $_msg){
		$this->clients=$_clients;
		$this->msg_info=$_msg;
	}
	// No recibe parametros de entrada.
	public function input(array $_data){}

	// Siempre se devuelve a si mismo ya que el siguiente estado es destruir la instancia.
	public function run($_delta) {
		return $this;
	}

	// Devuelve la informacion de por que ha concluido la partida
	public function output(){
		
		if(!$this->send){
			
			$info=['message'=>'Final de partida: '.$this->msg_info];
			$this->send=true;

			foreach($this->clients as &$client) {
				if(null!==$client) {
					$client->write(json_encode($info));			
				}
			}
		}

		
	}

	// Desconecta al jugador de la State.
	public function disconnect_player(\socket\socket_client $_client){
		unset($this->clients[$_client->peer]);
	}
}