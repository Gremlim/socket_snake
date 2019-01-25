<?php 
namespace states;

// Estado en el que la instancia está en pleno juego actualizando el mundo hasta que haya una colision.
class state_game implements \app\state{

	public $msg=null;
	public $world;
	public $players=[];
	public $players_crashed=[];
	public $clients=[];

	private $elapsed_time=0;

	const secs_to_advance=0.1;
	
	public function __construct(array $_clients, \app\world $_world){
		$this->world=$_world;
		$this->clients=$_clients;
		$this->world->build_board();

		$max_x=$this->world->width-1;
		$max_y=$this->world->height-1;

		$x=1;
		foreach($_clients as $cli){
			switch($x){
				case 1: $player=new \app\player($x,	0,					0,					\app\player::e	);	break;
				case 2: $player=new \app\player($x,	$max_x,				$max_y,				\app\player::w	);	break;
				case 3: $player=new \app\player($x,	$max_x,				0,					\app\player::s	);	break;
				case 4: $player=new \app\player($x,	0,					$max_y,				\app\player::n	);	break;
				case 5: $player=new \app\player($x,	$max_x,				floor($max_y/2),	\app\player::w	);	break;
				case 6: $player=new \app\player($x,	0,					floor($max_y/2),	\app\player::e	);	break;
				case 7: $player=new \app\player($x,	floor($max_x/2),	0,					\app\player::s	);	break;
				case 8: $player=new \app\player($x,	floor($max_x/2),	$max_y,				\app\player::n	);	break;
				case 9: $player=new \app\player($x,	floor($max_x/2),	floor($max_y/2),	\app\player::n	);	break;
				default:
					$this->msg='Too much players';
				break;
			}

			$this->players[$cli->peer]=$player;
			
			$this->world->set(
				$this->players[$cli->peer]->x,
				$this->players[$cli->peer]->y,
				$this->players[$cli->peer]->id
			);
			
			$x++;
		}
	}
	
	// Recoge la introduccion de los jugadores para ver hacia donde se dirigen
	public function input(array $_data) {
		foreach($_data as $peer => $content){
			if(isset($this->players[$peer])){
				$this->players[$peer]->tentative_heading=$content;
			}
		}
	}

	// Avanzan los jugadores y si hay algun "Game Over" se manda al estado recap
	public function run($_delta) {

		$advance_result=true;

		if($this->calculate_time_elapsed($_delta)) {
			$advance_result=$this->advance();
		}

		return $advance_result
			? $this
			: new \states\state_recap($this->clients,$this->msg);
	}

	// Se les devuelve la info del mundo actualizado a los jugadores
	public function output(){
		$info= ['game'=>$this->world->cells,'message'=>$this->msg];

		foreach($this->clients as $peer => &$client) {
			if(null!==$client) {
				$client->write(json_encode($info));
			}
		}
	}

	// Los jugadores avanzan por el mundo y se comprueba si el estado futuro es valido para permanecer ahi.
	// En caso de no ser valido el jugador pierde.
	private function advance(){
		
		foreach($this->players as $peer => $player){
			$player_crash=false;

			// Cambio de direccion del jugador.
			$player->change_direction(\app\player::str_to_direction($player->tentative_heading));

			// Se prevee la proxima casilla del jugador
			$future=$player->next_step();
			
			// Si es una casilla ilegal es que se ha salido del mapa y por lo tanto pierde
			if(!$this->world->is_legal_cell($future['x'],$future['y'])){
				$this->msg= 'Game Over #'.$player->id.': OUT';		
				$player_crash=true;
			}

			// Si se encuentra con una casilla ya ocupada tiene un choque y pierde.
			if(!$this->world->cell_is_empty($future['x'],$future['y'])){
				$this->msg= 'Game Over #'.$player->id.': CRASH';		
				$player_crash=true;
			}
			
			if($player_crash){
				$this->players_crashed[$peer]=$player;
				$this->world->clear_cells_from_value($player->id);
				unset($this->players[$peer]);
				continue;
			}

			if(null!==$this->msg && count($this->players)===1){
				return false;
			}

			// El jugador avanza el paso previsto
			$player->step();

			// Se setea al jugador en el nuevo punto del mapa
			$this->world->set($player->x,$player->y,$player->id);
		}	

		return true;
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