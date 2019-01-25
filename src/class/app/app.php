<?php
namespace app;

// Clase principal del juego en el que crea un demonio con el servidor corriendo
// continuamente y realiza/crea los juegos por cada grupo de clientes conectados.

// Para iniciarlo hay que pasarle como parametros la IP o hostname del servidor y 
// el puerto por el que se va a comunicar el servidor por TCP. Como parametro opcional
// se le puede pasar tambien el numero de jugadores que tendrá cada sala
class app{

	public $socket;

	private $instances=[];
	private $bussy_peers=[];
	private $sock_time;
	private $wait_time;
	private $time=0;
	private $running=false;

	private $world_width=30;
	private $world_height=30;

	private $players_by_game; // Maximo 9 Jugadores
	private $socket_ip;
	private $socket_port;

	public function __construct($_ip,$_port,$_pl_by_game=2,$_world_width=50,$_world_height=50){
		
		$this->players_by_game=$_pl_by_game;
		$this->socket_ip=$_ip;
		$this->socket_port=$_port;
		$this->world_width=$_world_width;
		$this->world_height=$_world_height;

		if(null===$this->socket_ip){
			throw new \Exception("IP '$this->socket_ip' not exists");
		}
		if(null===$this->socket_port){
			throw new \Exception("PORT '$this->socket_port' not exists");
		}
		if($this->players_by_game>9){
			throw new \Exception("The number of players must be less than 9.");
		}

		$this->sock_time=new \socket\stream_time(0,100000);
		$this->wait_time=($this->sock_time->microseconds/1000000)+$this->sock_time->seconds;
		$this->socket=new \socket\websocket_server($this->socket_port,$this->socket_ip,'tcp',$this->sock_time);
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

				if(count($available_peers) >= $this->players_by_game){
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

	// Genera la instancia de juego con $this->players_by_game jugadores 
	// aleatorios entre todos los disponibles
	private function generate_instances($_available_peers){
		$shuffle=array_rand($_available_peers, $this->players_by_game);
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
			new \app\world($this->world_width,$this->world_height)
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
