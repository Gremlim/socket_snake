<?php
namespace app;

// Clase principal del juego en el que crea un demonio con el servidor corriendo
// continuamente y realiza/crea los juegos por cada grupo de clientes conectados.
class app{

	public $socket;

	private $instances=[];
	private $bussy_peers=[];
	private $sock_time;
	private $wait_time;
	private $time=0;
	private $running=false;

	const world_width=30;
	const world_height=30;

	const players_by_game=3; // Maximo de 9 Jugadores

	public function __construct(){
	
		$this->sock_time=new \socket\stream_time(0,100000);
		$this->wait_time=($this->sock_time->microseconds/1000000)+$this->sock_time->seconds;
		$this->socket=new \socket\websocket_server('10555','93.93.69.12','tcp',$this->sock_time);
		$this->lobby=new \logics\lobby($this->socket);
		
	}
	// Inicia el demonio de la aplicacion manteniendo siempre abierto el socket enviando el log de 
	// fallo a la salida de log del socket dejandolo registrado.
	public function init(){
		$this->running=true;
		
		$prev_microtime=microtime(true);

		while(1){
			if(!$this->running){
				break;
			}
			try{
				
				$current_client_data=$this->socket->poll();			
				$available_peers=$this->execute_lobby($current_client_data);

				if(count($available_peers) >= self::players_by_game){
					$this->generate_instances($available_peers);
				}
				
				$now=microtime(true);
				
				$this->step($current_client_data, ($now-$prev_microtime));	
				
				$prev_microtime=$now;

			}catch(\Exception $e){
				$this->socket->log('SERVER',$e->getMessage());
			}			
		}
	}

	// Marca el flag de ejecucion para que se detenga el 'demonio' en la siguiente iteracion
	public function stop(){
		$this->running=false;
	}

	// Genera la instancia de juego con self::players_by_game jugadores 
	// aleatorios entre todos los disponibles
	private function generate_instances($_available_peers){
		$shuffle=array_rand($_available_peers, self::players_by_game);
		$players=[];
		foreach($shuffle as $peer){
			$players[$peer]=$_available_peers[$peer];
		}
		$this->save_instance($players);
	}

	// Crea y almacena una instancia de juego.
	private function save_instance(array $_clients){

		foreach($_clients as $cli){
			$this->bussy_peers[]=$cli;
		}
		
		$this->instances[]=new \app\instance(
			$_clients,
			new \app\world(self::world_width,self::world_height)
		);
	}

	// Ejecuta la logica del lobby y devuelve los clientes disponibles para un nuevo juego
	private function execute_lobby(array $_client_data){

		$this->lobby->clean();

		foreach($_client_data as $peer => $contents) {
			$this->lobby->input($this->socket->get_connections()[$peer], $contents);
		}
		
		$this->lobby->run();

		return array_filter($this->lobby->get_peers(),function($_item){
			return !in_array($_item,$this->bussy_peers);
		});
	}

	// Cada paso de la aplicacion en la que se le envia a la instancia los datos enviados por los
	// clientes, se ejecuta la logica y se elimina en caso de que no tenga jugadores conectados
	private function step($_client_data, $_delta){

		foreach($this->instances as $k=>$inst){
			$data_input=[];
			$this->players_to_lobby($inst);
			
			// Obtenemos los datos de los clientes de la instancia actual
			foreach($inst->get_clients() as $cli){
				if(null!==$cli && isset($_client_data[$cli->peer])){
					$data_input[$cli->peer]=$_client_data[$cli->peer];
				}
			}

			$inst->input($data_input);
		}

		// Ejecuta la instancia y devuelve el array de las que han pasado la ejecucion, el resto se elimina.
		$this->instances=array_filter($this->instances, function($_instance) use ($_delta){
			return $_instance->run($_delta);
		});
	}

	// Retira a los jugadores desconectados de sus instancias y los pone como disponibles nuevamente si 
	// siguen estando conectados
	private function players_to_lobby($_inst){

		$disconnected=$this->lobby->get_disconnected_pool();
		
		foreach($_inst->get_clients() as $cli){
			if(in_array($cli,$disconnected)){
				$_inst->disconnect_player($cli);

				$key=array_search($cli,$this->bussy_peers);
				
				if(is_numeric($key)){
					unset($this->bussy_peers[$key]);
				}
			}
		}
		$this->lobby->clean_disconnected_pool();
	}

}
