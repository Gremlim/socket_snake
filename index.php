<!doctype HTML5>
<html>
	<head>
		<script type="text/javascript" src="main.js"></script>
		<link rel="stylesheet" type="text/css" href="main.css" media="screen" />
	</head>
	<body>
		<div class="fleft tilesize">
			<button type="button" id="connect" onclick="connect()">Conectar</button>
			<button type="button" id="start" class="hidd" onclick="start()">Buscar Jugadores</button>
			<button type="button" id="disconnect" class="hidd" onclick="disconnect()">Desconectar</button>
		</div>

		<div class="fright tilesize" id="parrilla"></div>
		<br/>
		
		<div id="info-text">&nbsp;</div>
		<br/>
		
		<div id="game-board"></div>

	</body>
</html>