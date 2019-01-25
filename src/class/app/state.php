<?php 

namespace app;

// Interface para los estados que necesita que al menos todos tengan una
// funcion publica llamada run() y otra llamada output()
interface state{

	// Es necesario pasarle un array con los datos de los clientes. 
	// El array debe contener como indices los peer de los clientes
	public function input(array $_data);

	// Es necesario pasarle el tiempo calculado en microsegundos del bucle
	// actual para que calcule la ejecucion segun un tiempo determinado
	// Devuelve siempre una instancia de state, ya sea la actual o una nueva
	public function run($_delta);

	// Salida de datos ya sea a traves de los clientes actuales de la instancia
	// o atraves de la misma funcion para usarla dentro de la instancia
	public function output();

	// Desconecta al jugador de la State.
	public function disconnect_player(\socket\socket_client $_client);
}