<?php 
namespace states;

// Estado de la instancia para cargar los datos del mundo y de los jugadores en el cliente, con una cuenta atras.
class state_loading implements \app\state{
	private $world;
	private $clients=[];
	private $start_count;
	private $elapsed_time=0;
	
	const init_count=5;
	const secs_to_advance=0.9;

	public function __construct(array $_clients,\app\world $_world){
		$this->world=$_world;
		$this->clients=$_clients;
		$this->start_count=self::init_count;
	}
	
	// No se recoge ningun dato de entradas
	public function input(array $_data){}
	
	// Mientras la cuentra atras no llegue a 0 seguira enviando la misma informacion
	public function run($_delta) {

		if($this->calculate_time_elapsed($_delta)) {
			$this->start_count--;
		}

		return  $this->start_count< 0
			? new \states\state_game($this->clients,$this->world)
			: $this;
	}

	// Salida de la informacion general.
	public function output(){
		$info= [
			'message'=>'El juego comenzará en: '.$this->start_count,
			'players'=>count($this->clients),
			'world'=>[
				'width'=>$this->world->width,
				'height'=>$this->world->height
			]
		];	
		$x=1;
		foreach($this->clients as $client) {
			$info['you']='p'.$x++;
			$client->write(json_encode($info));			
		}
			
	}
	
	// Calcula el tiempo pasado y si ha pasado self::secs_to_advance devuelve true para seguir avanzando
	private function calculate_time_elapsed($_delta){
		
		$this->elapsed_time+=$_delta;
		
		if($this->elapsed_time<self::secs_to_advance){
			return false;
		}

		$this->elapsed_time=0;
		return true;

	}

	// Desconecta al jugador de la State.
	public function disconnect_player(\socket\socket_client $_client){
		unset($this->clients[$_client->peer]);
	}
}