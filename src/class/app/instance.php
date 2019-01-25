<?php
namespace app;

// Instancia de juego creada a partir de un array de \socket\socket_client conectados y un \app\world para setear el mundo.
class instance{
	private $world;
	private $state;
	private $data=[];
	private $clients=[];

	public function __construct(array $_clients, \app\world $_world){
		$this->world=$_world;
		$this->clients=$_clients;
		$this->state=new \states\state_loading($this->clients,$this->world);

	}
	// Recoge la informacion enviada por los clientes de esta instancia para que despues
	// cada estado realice la funcion pertinente de con esta informacion
	public function input($_data){
		$this->data=$_data;
	}

	// Ejecuta la instancia realizando las comprobaciones y ejecuciones de sus estados correspondientes
	public function run($_delta){
		// Si no quedan jugadores es hora de cerrar el juego.
		if(!$this->check_players()){
			return false;
		}
		
		// Aqui le insertamos la entrada actual de datos
		$this->state->input($this->data);

		// Ejecutamos el state actual
		$this->state=$this->state->run($_delta);

		// El state realiza la salida hacia los clientes o hacia la instancia en caso necesario
		$this->state->output();

		return true;
	}

	// Devuelve un array con los objetos \socket\socket_client de los jugadores actuales de la instancia.
	public function get_clients(){
		return $this->clients;
	}

	// Desconecta al jugador de la instancia y se setea el estado en recap si solo queda un jugador o menos.
	public function disconnect_player(\socket\socket_client $_client){
		unset($this->clients[$_client->peer]);
		$this->state->disconnect_player($_client);

		if(count($this->clients)<=1){
			$this->state=new \states\state_recap($this->clients,'Player Disconnected');
		}
	}

	// Comprueba si hay al menos un jugador en la instancia.
	private function check_players(){
		foreach($this->clients as $cli){
			if(null!==$cli){
				return true;
			}
		}
		return false;
	}
}