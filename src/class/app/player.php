<?php
namespace app;

// Clase para el jugador dentro del mundo de juego.
class player{
	public $id;
	public $x;
	public $y;
	public $heading;
	public $tentative_heading=null;
	
	const n=1;
	const e=2;
	const s=-1;
	const w=-2;

	public function __construct($_id,$_x,$_y,$_h){
		$this->id=$_id;
		$this->x=$_x;
		$this->y=$_y;
		$this->heading=$_h;
	}

	// Realiza un paso del jugador dejandolo en la siguiente casilla
	public function step(){
		switch($this->heading){
			case self::n: $this->y--; break;
			case self::s: $this->y++; break;
			case self::e: $this->x++; break;
			case self::w: $this->x--; break;
			default: throw new \Exception('Invalid heading: '.$this->heading);
		}
	}

	// Calcula donde estará el jugador en el futuro segun la direccion en la que esté apuntando.
	public function next_step(){
		$x=$this->x;
		$y=$this->y;
		switch($this->heading){
			case self::n: $y=$this->y-1; break;
			case self::s: $y=$this->y+1; break;
			case self::e: $x=$this->x+1; break;
			case self::w: $x=$this->x-1; break;
			default: throw new \Exception('Invalid heading: '.$this->heading);
		}
		return ['x'=>$x,'y'=>$y];
	}

	// Cambia la direccion a la que se dirige el jugador
	public function change_direction($_new){
		if(null===$_new){
			return null;
		}
		if($_new+$this->heading==0){
			return null;
		}
			
		$this->heading=$_new;
		
	}

	// Convierte un string en una direccion valida para este jugador
	public static function str_to_direction($_d){
		if(null===$_d){
			return null;
		}
		switch(strtolower($_d)){
			case 'n': return self::n; 
			case 's': return self::s; 
			case 'e': return self::e; 
			case 'w': return self::w; 
			default : return null;
		}
	}
}