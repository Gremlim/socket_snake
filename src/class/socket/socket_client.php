<?php
namespace socket;

// Clase de los clientes del websocket
class socket_client{
	
	public $peer;

	private $resource;

	// Si el recurso es distinto de null devuelve un socket_client si no devuelve un null.
	public static function from_resource($_resource){
		if(false===$_resource){
			return null;
		}

		return new socket_client($_resource);
	}

	// Devuelve la resource del socket.
	public function get_resource(){
		return $this->resource;
	}

	// Escribe una respuesta al cliente codificada con hybi10
	public function write($_data){
		$data=\socket\hybi10::encode($_data);
		if($this->resource){
			fwrite($this->resource, $data);
		}
	}

	// Desconecta del socket al cliente.
	public function disconnect(){
		$peer = stream_socket_get_name($this->resource, true);
		fclose($this->resource);
	}

	// Realiza el saludo entre el socket y el cliente
	public function handshake($_hs) {
		
		$response=<<<R
HTTP/1.1 101 Switching Protocols
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Accept: {$_hs}


R;
		fwrite($this->resource, $response);
	}

	// Lee la informacion introducida por el cliente en el socket
	public function read() {

		$message=stream_get_contents($this->resource, 1);
		$metadata=stream_get_meta_data($this->resource);

		while($metadata['unread_bytes']) {
			$message.=stream_get_contents($this->resource, $metadata['unread_bytes']);
			$metadata=stream_get_meta_data($this->resource);
		}

		return \socket\hybi10::decode($message);

	}
	
	private function __construct($_resource){
		$this->resource=$_resource;
		$this->peer= stream_socket_get_name($this->resource, true);
	}

}

