let sock=null;
let w_width;
function get_by_id(_id) {

	let res=document.getElementById(_id);
	if(!res) {
		throw new Error("Element "+_id+" does not exist");
	}
	return res;
}
document.onkeydown = function(evt) {
	if(null!==sock){
		evt = evt || window.event;
		var charCode = evt.keyCode || evt.which;

		switch(charCode){
			case 37: 
				sock.send('W');
			break;
			case 38: 
				sock.send('N');
			break;
			case 39: 
				sock.send('E');
			break;
			case 40: 
				sock.send('S');
			break;
		}
	}
};
function connect(){

	sock=new WebSocket("ws://93.93.69.12:10555");
	sock.onopen = function (event) {
		get_by_id('disconnect').className='';
		get_by_id('start').className='';
		get_by_id('connect').className='hidd';
	};
	sock.onerror = function (event) {
		console.error(event);
	};		

	sock.onmessage = function (event) {
		let data=JSON.parse(event.data);

		if(data.message){
			get_by_id('info-text').innerHTML=data.message;
		}
		
		if(data.players){
			var parrilla=get_by_id('parrilla');
			parrilla.innerHTML='';

			var i;
			for (i = 1; i <= data.players; i++) {
				let div=document.createElement('div');
				div.id='sp'+i;
				div.classList.add('selector');
				div.innerHTML='Jugador '+i+': <div class="legend p'+i+'"></div>';
				parrilla.appendChild(div);
			} 
		}
		if(data.you){
			get_by_id('s'+data.you).classList.add('active');
		}

		let table;

		if(data.world){
			// <table id="game_table">
			// 	<tbody></tbody>
			// </table>

			if(document.getElementById('game_table')){
				get_by_id('game_table').remove();
			}
			table=document.createElement('table');
			table.id='game_table';
			w_width=data.world.width;
			for(let y=0; y < data.world.height; y++){
				table.appendChild(document.createElement('tr'));
				for(let x=0; x < data.world.width; x++) {
					table.rows[y].appendChild(document.createElement('td'));
					table.rows[y].cells[x].innerHTML='&nbsp;';
					table.rows[y].cells[x].className="p0";
				}
			}
			get_by_id('game-board').appendChild(table);
		}

		if(data.game){
			table=get_by_id('game_table');

			get_by_id('info-text').innerHTML='';

			data.game.forEach((element,index) => {
				let y= Math.floor(index/w_width);
				let x= index%w_width;

				table.rows[y].cells[x].className="p"+element;
			});
		}
	};

	
}

function start() {
	sock.send('START');
	get_by_id('start').className='hidd';
}

function disconnect(){
	get_by_id('connect').className='';
	get_by_id('disconnect').className='hidd';
	get_by_id('start').className='hidd';
	
	sock.close(1000,'99');

	sock=null;
}
