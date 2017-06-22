var events 	= require("events");
//const commandExists =require('command-exists');
const child_process = require('child_process');
const exec = child_process.exec;
const spawn = child_process.spawn;
const util = require('util');
const d3 = require("d3-queue");
const log = require('simple-node-logger').createSimpleLogger({timestampFormat:'YYYY-MM-DD HH:mm:ss'});
const fs = require('fs');
const os = require('os');

const TRADFRI_DISCONNECTED = 0;
const TRADFRI_CONNECTED = 1;

const TRADFRI_TYPE_DEVICE = 0;
const TRADFRI_TYPE_GROUP = 1;
const TRADFRI_TYPE_GATEWAY = 2;

const TRADFRI_COLOR_COLD = "f5faf6";
const TRADFRI_COLOR_COLD_X = "24930";
const TRADFRI_COLOR_COLD_Y = "24694";
const TRADFRI_COLOR_NORMAL = "f1e0b5";
const TRADFRI_COLOR_NORMAL_X = "30140";
const TRADFRI_COLOR_NORMAL_Y = "26909";
const TRADFRI_COLOR_WARM = "efd275";
const TRADFRI_COLOR_WARM_X = "33135";
const TRADFRI_COLOR_WARM_Y = "27211";

const execConfig = {
  timeout: 5000
};



function Tradfri (host, key, port, debuglevel){
//	commandExists('coap-client').catch(() => {
//      throw new Error('[Coap Client] libcoap is not found! Make sure coap-client is available on the command line!');
//    });
	if(os.arch()=='arm'){
		this.pathClientCoap = __dirname+'/bin/coap-client-'+os.platform()+'-32';
	}else{
		this.pathClientCoap = __dirname+'/bin/coap-client-'+os.platform();
	}
	if (fs.existsSync(this.pathClientCoap)) {
		log.info('Client Coap OK');
	}else{
		log.error('Client Coap introuvable ('+this.pathClientCoap+')');
	}
    if (typeof host === 'undefined' || host === null) {
      throw new Error('[Coap Client] You must specify a valid host!');
    }
	if (debuglevel) log.setLevel(debuglevel);

	events.EventEmitter.call(this);	
	this.timer = null;
  	this.host = host;
	this.port = port
  	this.key = key;
	this.devices = {};
	this.username = 'Client_identity';
	this.state = TRADFRI_DISCONNECTED;
	this.TRADFRI_TYPE_DEVICE = TRADFRI_TYPE_DEVICE;
	this.TRADFRI_TYPE_GROUP = TRADFRI_TYPE_GROUP;
	this.TRADFRI_TYPE_GATEWAY = TRADFRI_TYPE_GATEWAY;
	//this.init();
	
//}

//Tradfri.prototype.init = function() {
	this.init = function() {
		var cthis = this;
		var q = d3.queue(1); //File d'attente
		var path = '.well-known/core';
		//var coapCmd = `${this.pathClientCoap} -u '${ this.username }' -k '${ this.key }' -B 5 coaps://${ this.host }:${ this.port }/${ path }`;
		var coapCmd = `${this.pathClientCoap} -k '${ this.key }' -B 5 coaps://${ this.host }:${ this.port }/${ path }`;
		log.debug(coapCmd);
		exec(coapCmd, execConfig, (error, stdout, stderr) => {
			if (error) {
				log.error('Erreur d\'initialisation ');
				log.error(error);
				log.error(stderr);
				setTimeout(function(){
					log.info('Nouvelle tentative d\'initialisation');
					cthis.init();
				},1000);
			}else{	
				log.info('Initialisation OK');	
				var split = stdout.trim().split("\n")
				response = split.pop().split(",");
				response.forEach(function(device) {          
						var obs = false;
						var m = device.match(/^<\/\/([0-9]{5}\/[0-9]{4,6}(\/[0-9]{4,6})*)>;?(.*)/);            
						if(m){				
							//cthis.devices[m[1]] = {};
							if(m[3]){
								var options = m[3].split(';'); //On décompose la réponse
								if(options){
									if (options.indexOf('obs')) obs = true;
								}
								//cthis.devices[m[1]]['options'] = options;                  
							}
						}
						if(m){
							if(m[1].substr(0, 6)=='15004/'){
								q.defer(function(callback){              
									setTimeout(function(){
										cthis.getGroup(m[1],obs,callback);				  	
									},2000);                    
								});
							}					
							if(m[1].substr(0, 6)=='15001/'){
								q.defer(function(callback){              
									setTimeout(function(){
										cthis.getDevice(m[1],obs,callback);				  	
									},2000);                    
					
								});
							};
	/*						if(m[1].substr(0, 11)=='15011/15012'){ //GATEWAY
								q.defer(function(callback){              
									setTimeout(function(){
										cthis.getGateway(m[1],obs,callback);
									},2000);                    
								});
							}					
	*/
						};
				});
				q.awaitAll(function(error) {				
					cthis.state = TRADFRI_CONNECTED;
					log.debug('Gateway IKEA connectée');
					cthis.emit("init", {});
				});
			};
		});
	};

//Tradfri.prototype.getGateway = function(id, obs, callback){
	this.getGateway = function(id, obs, callback){
		var cthis = this;
		const coapCmd = `${this.pathClientCoap} -k '${ this.key }' -B 5 coaps://${ this.host }:${ this.port }/${ id }`;
		log.debug(coapCmd);
		exec(coapCmd, execConfig, (error, stdout, stderr) => {
			var message = stdout.toString();
			var json = null;
			if (error) {
				log.error('Erreur getGroup '+id);
				setTimeout(function(){
					log.info('Nouvelle tentative getGateway'+id);
					cthis.getGateway(id, obs, callback);
				},1000);
			}else{			
				try {
					json = JSON.parse(message);		                
				} catch (e) {                
					log.error('JSON Incorrect');		
				}
				if(json){
					log.info('Get info gateway OK');	
					log.debug(json);


					if(obs) this.observeDevice(id);
				}
				if (callback) callback(null);
			};
		});
	};
	//Tradfri.prototype.getGroup = function(id, obs, callback){
	this.getGroup = function(id, obs, callback){
		var cthis = this;
		const coapCmd = `${this.pathClientCoap} -k '${ this.key }' -B 5 coaps://${ this.host }:${ this.port }/${ id }`;
		log.debug(coapCmd);
		exec(coapCmd, execConfig, (error, stdout, stderr) => {
			var message = stdout.toString();
			var json = null;
			if (error) {
				log.error('Erreur getGroup '+id);
				setTimeout(function(){
					log.info('Nouvelle tentative getGroup'+id);
					cthis.getGroup(id, obs, callback);
				},1000);
			}else{			
				//var split = stdout.trim().split("\n")
				//response = split.pop().split(",");	
				try {
					json = JSON.parse(message);		                
				} catch (e) {                
					log.error('JSON Incorrect');		
				}
				if(json){
					log.info('Get info group '+id+' OK');	
					var instance_id = json['9003']
					cthis.devices[instance_id] = {
						tradfri_type : TRADFRI_TYPE_GROUP,
						id : json['9003'],
						url : id,
						name : json['9001'],
						//scene_ID: json['9039'],
						onoff :  json['5850'],
						dimmer :  json['5851'],
						color :  json['5706'],
						colorX :  json['5709'],
						colorY :  json['5710'],
					};
				};
				//if(obs) this.observeDevice(id);
				if (callback) callback(null);
			};
		});
	};

//Tradfri.prototype.getDevice = function(id, obs, callback){
	this.getDevice = function(id, obs, callback){
		var cthis = this;
		//const coapCmd = `${this.pathClientCoap} -u '${ this.username }' -k '${ this.key }' -B 5 coaps://${ this.host }:${ this.port }/${ id }`;
		const coapCmd = `${this.pathClientCoap} -k '${ this.key }' -B 5 coaps://${ this.host }:${ this.port }/${ id }`;
		log.debug(coapCmd);
		exec(coapCmd, execConfig, (error, stdout, stderr) => {
			if (error) {
				//console.log(stdout, stderr, coapCmd);
				log.error('Erreur getDevice '+id);
				setTimeout(function(){
					log.info('Nouvelle tentative getDevice'+id);
					cthis.getDevice(id, obs, callback);
				},1000);
			} else {
				var message = stdout.toString();
				var json = null;			
				//var split = stdout.trim().split("\n")
				//response = split.pop().split(",");	
				//console.log(response);
				try {
					json = JSON.parse(message);            
				} catch (e) {
					log.error('JSON Incorrect');		
				}
				if(json){
					log.info('Get info device '+id+' OK');					
					var instance_id = json['9003']
					cthis.devices[instance_id] = {
						tradfri_type : TRADFRI_TYPE_DEVICE,
						id : json['9003'],
						url : id,
						lastseen : json['9020'],
						reachable : json['9019'],
						type : json['5750'],								
						name : json['9001'],
						firmware : '',
						manufacturer : '',
						modelNumber : '', 
						serialNumber : '', 

					}
					if(cthis.devices[instance_id].type==2){
						cthis.devices[instance_id].onoff = 0;
						cthis.devices[instance_id].dimmer = 0;
						cthis.devices[instance_id].color = 0;
						cthis.devices[instance_id].colorX = 0;
						cthis.devices[instance_id].colorY = 0;
					}
					if(json['3']){
						if (json['3']['0']) cthis.devices[instance_id].manufacturer = json['3']['0'];
						if (json['3']['1']) cthis.devices[instance_id].modelNumber = json['3']['1'];
						if (json['3']['2']) cthis.devices[instance_id].serialNumber = json['3']['2'];
						if (json['3']['3']) cthis.devices[instance_id].firmware = json['3']['3'];					
					}
					if(json['3311']){
						//console.log(json['3311'][0]['5851']);
						if (json['3311'][0]['5850']) cthis.devices[instance_id].onoff = json['3311'][0]['5850'];
						if (json['3311'][0]['5851']) cthis.devices[instance_id].dimmer = json['3311'][0]['5851'];
						//console.log(cthis.devices[instance_id].dimmer);
						if (json['3311'][0]['5706']) cthis.devices[instance_id].color = json['3311'][0]['5706'];
						if (json['3311'][0]['5709']) cthis.devices[instance_id].colorX = json['3311'][0]['5709'];
						if (json['3311'][0]['5710']) cthis.devices[instance_id].colorY = json['3311'][0]['5710'];
					}
					//console.log(cthis.devices[instance_id].dimmer);
					//console.log(cthis.devices[instance_id]);
				}
				//console.log(devices[id]['options'].indexOf('obs'));
				cthis.emit("state", JSON.stringify(cthis.devices[instance_id])); //On envoie un changement d'etat pour envoyer l'etat initial		
				if(obs) this.observeDevice(id);
				if (callback) callback(null);
			}
		});
	};
//Tradfri.prototype.observeDevice = function(id, callback){
	this.observeDevice = function(id, callback){
		var cthis = this;
		var idDevice = id
		log.info('Observe Device '+idDevice);
		var resourceUrl = `coaps://${this.host }:${ this.port }/${ id }`;

		//On met en place un timer pour relancer l'observe si pas de réponse de sa part après 10s
		//Hack pour corriger le bug de merde de spawn
		var timerData;
		timerData = setTimeout(function(){
			log.info('KILL auto après 10s sans réponse');
			coap_client.kill('SIGHUP');
		},10000)

		var coap_client = spawn(this.pathClientCoap, [
		//'-u', this.username,
		'-k', this.key,
		// '-m', 'get',
		'-s', '120',
		
		//'-O', '6,xx',
		resourceUrl
		]);
		coap_client.stderr.on('data', function(buf) {
			//Erreur
			//log.error('[STR] stderr "%s"', String(buf));				
		});
		coap_client.stdout.on('data', stdout => {
			if (timerData) clearTimeout(timerData);
			var message = stdout.toString();		
			var json = null;		
			if(message=='\n'){
				log.info('Observe Stoped');    
			}else{
				log.info('Data recu '+idDevice+' : '+message);    
				try {
					json = JSON.parse(message);		                
				} catch (e) {                
					log.error('ERROR JSON');		
				}	
			}		
			if(json){			
				var instance_id = json['9003'];
				var lastseen = json['9020'];
				var reachable = json['9019'];
				var onoff = 0;
				var dimmer = 0;
				var color = 0;
				var colorX = 0;
				var colorY = 0;
				var manufacturer = '';
				var model = '';
				var firmware = '';
				//console.log(json)
				if(json['3311']){
					if (json['3311'][0]['5850']) onoff = json['3311'][0]['5850'];
					if (json['3311'][0]['5851']) dimmer = json['3311'][0]['5851'];
					if (json['3311'][0]['5706']) color = json['3311'][0]['5706'];
					if (json['3311'][0]['5709']) colorX = json['3311'][0]['5709'];
					if (json['3311'][0]['5710']) colorY = json['3311'][0]['5710'];
				}
				if(json['3']){
					if (json['3']['0']) manufacturer = json['3']['0'];
					if (json['3']['0']) model = json['3']['0'];
					if (json['3']['0']) firmware = json['3']['3'];
				}
				if(cthis.devices[instance_id]){
					var isStateChange = false;
					//console.log('Device '+instance_id+' trouvé');
					var device = cthis.devices[instance_id];
					//console.log('-----')
					//console.log(device);
					device.manufacturer = manufacturer;
					device.model = model;
					device.firmware = firmware;								
					switch (device.type) {
						case 0: //controleur
							isStateChange = isStateChange||(device.lastseen!=lastseen);
							isStateChange = isStateChange||(device.reachable!=reachable);
							cthis.devices[instance_id].lastseen = onoff;
							cthis.devices[instance_id].reachable = reachable;						
							log.debug('Type 0 (Controleur)');
							log.debug('lastseen : ' + device.lastseen+ ' --> ' + lastseen);
							log.debug('reachable : ' + device.reachable+ ' --> ' + reachable);
							if(isStateChange){
								cthis.devices[instance_id].lastseen = onoff;
								cthis.devices[instance_id].reachable = reachable;
								cthis.emit("state", JSON.stringify(cthis.devices[instance_id]));		
							}
							break;
						case 2: //light	
							isStateChange = isStateChange||(device.lastseen!=lastseen);
							isStateChange = isStateChange||(device.reachable!=reachable);
							isStateChange = isStateChange||(device.onoff!=onoff);
							isStateChange = isStateChange||(device.dimmer!=dimmer);
							isStateChange = isStateChange||(device.color!=color);
							isStateChange = isStateChange||(device.colorX!=colorX);
							isStateChange = isStateChange||(device.colorY!=colorY);
							log.debug('Type 2 (Light)');
							log.debug('lastseen : ' + device.lastseen+ ' --> ' + lastseen);
							log.debug('reachable : ' + device.reachable+ ' --> ' + reachable);
							log.debug('onoff : ' + device.onoff+ ' --> ' + onoff);
							log.debug('dimmer : ' + device.dimmer+ ' --> ' + dimmer);
							log.debug('color : ' + device.color+ ' --> ' + color);
							log.debug('colorX : ' + device.colorX+ ' --> ' + colorX);
							log.debug('colorY : ' + device.colorY+ ' --> ' + colorY);
							//console.log(device);																					
							if(isStateChange){
								cthis.devices[instance_id].lastseen = onoff;
								cthis.devices[instance_id].reachable = reachable;
								cthis.devices[instance_id].onoff = onoff;
								cthis.devices[instance_id].dimmer = dimmer;								
								cthis.devices[instance_id].color = color;	
								cthis.devices[instance_id].colorX = colorX;	
								cthis.devices[instance_id].colorY = colorY;	
								if(cthis.timer) clearTimeout(cthis.timer); //Si un timer est en place, on le kill								
								//on attend 0,25s avant d'envoyer l'état (sans activité) pour eviter de surcharger de message
								cthis.timer = setTimeout(() => {
									cthis.emit("state", JSON.stringify(cthis.devices[instance_id]));		
								}, 250);

							}							
							break;							
					}
				}else{
					log.error('DEVICE NON TROUVE '+instance_id)
					cthis.emit("error", {err:1, msg:'DEVICE NON TROUVE '+instance_id});	
				}
			}
		});
		//coap_client.on('message',(message)=> {
		//	log.info('Observe Message '+idDevice+' : '+message);
		//});
		coap_client.on('close', (code) => {
			log.debug('Observe Close ('+code+') '+idDevice);
			log.debug('Relaunch Observe '+idDevice);
			setTimeout(function(){cthis.observeDevice(idDevice, callback)},500);
		});
		coap_client.on('error', (err) => {
			log.debug('Observe Error ('+err+') '+idDevice);
		});
		coap_client.on('disconnect', () => {
			log.debug('Observe disconnect '+idDevice);
		});
		coap_client.on('exit', (code) => {
			log.debug('Observe Exit ('+code+') '+idDevice);
		});
	};

//Tradfri.prototype.getAllDevices = function(){
	this.getAllDevices = function(){
		log.debug('getAllDevices');
		log.debug('Etat : '+this.state);
		log.debug(this.devices);
		if(this.state == TRADFRI_CONNECTED){
			return this.devices;
		}else{
			return false;
		}
	},

//Tradfri.prototype.setDevice = function(id, data, callback){
	this.setDevice = function(id, data, callback){
		var cthis = this;
		var jsonData = JSON.stringify(data);
		//var coapCmd = `${this.pathClientCoap} -u '${ this.username }' -k '${ this.key }' -B 5 -m PUT -e '${ jsonData }' coaps://${ this.host }:${ this.port }/${ id }`;
		var coapCmd = `${this.pathClientCoap} -k '${ this.key }' -B 5 -m PUT -e '${ jsonData }' coaps://${ this.host }:${ this.port }/${ id }`;
		log.debug(coapCmd);
		setTimeout(function () {
			exec(coapCmd, execConfig, (error, stdout, stderr) => {
				if (error) {
					log.error('Erreur setDevice '+id);			
				}	
				if (callback) callback(null);	
			});    
		}, Math.ceil(100 * Math.random() + 75));
	}
	//Tradfri.prototype.DeviceON = function(id, callback){
	this.DeviceON = function(id, callback){
		log.info('DeviceON '+id);
		var device = this.devices[id];
		if(device){
			var url = device.url;
			var data;
			switch (device.tradfri_type) {
				case TRADFRI_TYPE_GROUP : data = {'5850':1}; break;
				case TRADFRI_TYPE_DEVICE : data = {'3311':[{'5850':1}]}; break;			
			}
					
			this.setDevice(url,data,callback);
		}else{
			log.error('Device introuvable '+id);			
		}
	}
// Tradfri.prototype.DeviceOFF = function(id, callback){
	this.DeviceOFF = function(id, callback){
		log.info('DeviceOFF '+id);
		var device = this.devices[id];
		if(device){
			var url = device.url;
			var data;
			switch (device.tradfri_type) {
				case TRADFRI_TYPE_GROUP : data = {'5850':0}; break;
				case TRADFRI_TYPE_DEVICE : data = {'3311':[{'5850':0}]}; break;			
			}
			this.setDevice(url,data,callback);
		}else{
			log.error('Device introuvable '+id);			
		}	
	}

//	Tradfri.prototype.DeviceToggle = function(id, callback){
	this.DeviceToggle = function(id, callback){
		log.info('DeviceToggle '+id);
		var device = this.devices[id];
		if(device){		
			var url = device.url;
			var data;
			if(device.onoff){
				switch (device.tradfri_type) {
					case TRADFRI_TYPE_GROUP : data = {'5850':0}; break;
					case TRADFRI_TYPE_DEVICE : data = {'3311':[{'5850':0}]}; break;			
				}
			}else{			
				switch (device.tradfri_type) {
					case TRADFRI_TYPE_GROUP : data = {'5850':1}; break;
					case TRADFRI_TYPE_DEVICE : data = {'3311':[{'5850':1}]}; break;			
				}
			}		
			this.setDevice(url,data,callback);
		}else{
			log.error('Device introuvable '+id);
		}
	}
//Tradfri.prototype.DeviceDIM = function(id, level, callback){
	this.DeviceDIM = function(id, level, callback){
		log.info('DeviceDIM '+id+' -> '+level);
		var device = this.devices[id];
		if(device){
			var url = device.url;
			var data;
			switch (device.tradfri_type) {
					case TRADFRI_TYPE_GROUP : data = {'5851':level}; break;
					case TRADFRI_TYPE_DEVICE : data = {'3311':[{'5851':level}]}; break;			
			}
			this.setDevice(url,data,callback);
		}else{
			log.error('Device introuvable '+id);			
		}	
	}
//Tradfri.prototype.DeviceTEMP = function(id, level, callback){
	this.DeviceTEMP = function(id, level, callback){
		log.info('DeviceTEMP '+id+' -> '+level);
		var device = this.devices[id];
		if(device){
			var url = device.url;
			switch (level) {
				case 1:
					var data = {'3311':[{'5706':TRADFRI_COLOR_COLD}]};		
					break;
				case 2:
					var data = {'3311':[{'5706':TRADFRI_COLOR_NORMAL}]};
					break;
				case 3:
					var data = {'3311':[{'5706':TRADFRI_COLOR_WARM}]};
					break;				
			}		
			this.setDevice(url,data,callback);
		}else{
			log.error('Device introuvable '+id);			
		}	
	}

	this.init();

};
// export the class
util.inherits(Tradfri, events.EventEmitter);	
//module.exports = Tradfri;
exports.Connect = Tradfri;
