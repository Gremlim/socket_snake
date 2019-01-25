<?php
namespace app;

// Clase para el mundo de juego.
class world{

	public $width;
	public $height;
	public $cells=[];
	const c_empty=0;
	
	public function __construct($_w,$_h){
		$this->width=$_w;
		$this->height=$_h;
		$this->build_board();
	}
	// Construye el tablero del mundo segun las celdas y filas indicadas al contruirlo
	// seteando todas las casillas como vacias
	public function build_board(){
		$this->cells=array_fill(0,$this->width*$this->height,self::c_empty);
	}
	// Obtiene informacion de una casilla en especifico
	public function get($_x,$_y){
		return $this->cells[($_y*$this->width)+$_x];
	}
	// Guarda la informacion actual de una casilla
	public function set($_x,$_y,$_v){
		$this->cells[($_y*$this->width)+$_x]=$_v;
	}
	// Comprueba si la celda de las coordenadas que se le pasan existe dentro del juego
	public function is_legal_cell($_x,$_y){
		return $_x>=0 && $_x<$this->width && $_y>=0 && $_y<$this->height;
	}
	// Comprueba si la celda de las coordenadas que se le pasan está vacia en este momento
	public function cell_is_empty($_x,$_y){

		$cell=$this->get($_x,$_y);
		return $cell===self::c_empty;
	}

	// Limpia las casillas con un valor determinado
	public function clear_cells_from_value($_v){
		array_walk($this->cells,function(&$_val,$_index,$_old){
			$_val = $_val===$_old ? 0 : $_val;
		},$_v);
	}
}