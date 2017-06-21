const Tradfri = require('./tradfri');
var net = require('net');
var request = require('request');
const log = require('simple-node-logger').createSimpleLogger({timestampFormat:'YYYY-MM-DD HH:mm:ss'});

process.argv.forEach(function(val, index, array) {
	switch ( index ) {
		case 2 : urlJeedom = val; break;		
		case 3 : host = val; break;
		case 4 : port = val; break;
		case 5 : key = val; break;
		case 6 : loglevel = val; break;
		case 7 : portSocket = val; break;
	}
});

if (loglevel) log.setLevel(loglevel);

//console.log(urlJeedom);

//Creation du socket
var server = net.createServer(function (socket) {
	socket.on('data', function (data) {
		//logit.log('RECU : '+ data);
		var message = JSON.parse(data);
		log.debug('Tradfri Socket : message = '+JSON.stringify(message));
		switch (message.action) {
			case 'scanDevices' : //Scan de tous les devices présents
				var devices = tradfri.getAllDevices()
				log.debug('Tradfri Socket : reponse = '+JSON.stringify(devices));
				socket.write(JSON.stringify(devices));
				break;

			case 'setValue' : 
				tradfri.setDevice(message.url,message.cde)
				break;

			case 'sendCde' :
				switch (message.cde) {
					case 'on' : 
						tradfri.DeviceON(message.id);
						break;
					case 'off' : 	
						tradfri.DeviceOFF(message.id);	
						break;
					case 'toggle' : 	
						tradfri.DeviceToggle(message.id);
						break;	
					case 'dimmer' : 	
						var val = Math.round((message.level / 100 * 254));
						tradfri.DeviceDIM(message.id, val);
						break;		
					case 'cold' : 	
						tradfri.DeviceTEMP(message.id, 1);
						break;		
					case 'normal' : 	
						tradfri.DeviceTEMP(message.id, 2);
						break;		
					case 'warm' : 	
						tradfri.DeviceTEMP(message.id, 3);
						break;		
					default :
						tradfri.setDevice(message.url,message.cde);
						break;
				}
				break;	
		}		
	});
});
server.listen(portSocket);
server.on('listening',function(){
	log.info('Tradfri Socket démarré '+server.address().address+':'+server.address().port);	
});
server.on('connection', function(socket) { //This is a standard net.Socket
	var remoteAdress = socket.remoteAddress;
	var remotePort = socket.remotePort;
	log.info('Tradfri Socket -> Client connected : ' + remoteAdress +':'+ remotePort);
});
server.on('error', function (err) {
	log.error('Tradfri Socket -> ' + err.code);
});

//Connexion gateway Tradfri
//var tradfri = new Tradfri(host, key, port, loglevel);
var tradfri = new Tradfri.Connect(host, key, port, loglevel);

tradfri.on('init',function(){
	log.info('Initialisation OK');
	//console.log(tradfri.getAllDevices());
});
tradfri.on('state', msg => {
	var device = JSON.parse(msg);
	//str = JSON.stringify(msg, null, 4); 
	//log.debug(str);
	if(device.dimmer){
		device.dimmer = Math.round((device.dimmer / 254 * 100));
	}
	jeeApi = urlJeedom + "&state="+encodeURIComponent(JSON.stringify(device));
	log.info('Tradfri Socket : Changement de status');
	log.debug(jeeApi);
	request(jeeApi, function (error, response, body) {
		if (!error && response.statusCode == 200) {
			log.info('Return OK from Jeedom');
		}else{
			log.error('Error : '+error);				
			log.error('response : '+JSON.stringify(response));
			log.error('body : '+body);
		}
	});

});
tradfri.on('error', msg => {
	str = JSON.stringify(msg, null, 4); 
	log.error(str);
});
