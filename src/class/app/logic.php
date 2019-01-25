<?php 
namespace app;

// Interface para las logicas de la aplicacion
interface logic{
	public function input($_client,$_data);
	
	public function run();
}