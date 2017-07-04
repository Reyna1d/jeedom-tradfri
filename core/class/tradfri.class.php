<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class tradfri extends eqLogic {
    /*     * *************************Attributs****************************** */
	
	
    /*     * ***********************Methode static*************************** */
/*	public static function syncDevice($device = null) {
		log::add('tradfri','info','Device '.$key.' => '.json_encode($device));						
		$id = $device['id'];	
		$tradfri = eqLogic::byLogicalId($id, 'tradfri');
		if (!is_object($tradfri)) {
			log::add('tradfri', 'info', 'Aucun équipement trouvé pour : ' . $id);
			$eqLogic = new eqLogic();
			$eqLogic->setEqType_name('tradfri');
			$eqLogic->setIsEnable(1);
			$eqLogic->setLogicalId($id);
			$eqLogic->setName($device['name']);
			$eqLogic->setConfiguration('manufacturer', $device['manufacturer']);			
			$eqLogic->setConfiguration('serialNumber', $device['serialNumber']);
			$eqLogic->setConfiguration('firmware', $device['firmware']);	
			log::add('tradfri', 'debug','Generate config type'.$device['tradfri_type']);
			if($device['tradfri_type']==0){
				$eqLogic->setConfiguration('modelNumber', $device['modelNumber']);
				$eqLogic->applyModuleConfiguration(str_replace(' ', '_',trim($device['modelNumber'])));
			}else{
				$eqLogic->setConfiguration('modelNumber', 'group');
				$eqLogic->applyModuleConfiguration('group');
			};			
			$eqLogic->save();
		}else{
			log::add('tradfri', 'info', 'Equipement déjà existant : ' . $id);
		};	
	}
*/
	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'tradfri_dep';
		#$commandexists = realpath(dirname(__FILE__) . '/../../node/node_modules/command-exists');
		$d3 = realpath(dirname(__FILE__) . '/../../node/node_modules/d3-queue');
		$request = realpath(dirname(__FILE__) . '/../../node/node_modules/request');		
		$simplenodelogger = realpath(dirname(__FILE__) . '/../../node/node_modules/simple-node-logger');
		$colors = realpath(dirname(__FILE__) . '/../../node/node_modules/colors');
		$return['progress_file'] = '/tmp/tradfri_dep';
		
		if (is_dir($d3) && is_dir($request) && is_dir($simplenodelogger) && is_dir($colors)) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}


	public static function dependancy_install() {
		log::add('tradfri','info','Installation des dépéndances nodejs');
		$resource_path = realpath(dirname(__FILE__) . '/../../ressources');
		#passthru('/bin/bash ' . $resource_path . '/nodejs3.sh ' . $resource_path . ' > ' . log::getPathToLog('tradfri_dep') . ' 2>&1 &');
		passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('tradfri_dep') . ' 2>&1 &');
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'tradfricmd';
		$return['state'] = 'nok';
		$pid = trim( shell_exec ('ps ax | grep "tradfri/node/deamon.js" | grep -v "grep" | wc -l') );
		if ($pid != '' && $pid != '0') {
			$return['state'] = 'ok';
		}
		
		$return['launchable'] = 'ok';
		if ((config::byKey('socketport', 'tradfri') == '')) {
			$return['launchable'] = 'nok';
      		$return['launchable_message'] = __('Port du socket non configurée', __FILE__);
    	}
    	if ((config::byKey('ipGateway', 'tradfri') == '') && (config::byKey('portGateway', 'tradfri') == '') && (config::byKey('keyGateway', 'tradfri') == '')) {
      		$return['launchable'] = 'nok';
      		$return['launchable_message'] = __('Aucune gateway configurée', __FILE__);
    	}				
		return $return;
	}

	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		log::remove('tradfricmd');

        $ipGateway = config::byKey('ipGateway', 'tradfri', '');
		$portGateway = config::byKey('portGateway', 'tradfri', '');
		$securitykeyGateway = config::byKey('keyGateway', 'tradfri', '');
		$socketport = config::byKey('socketport', 'tradfri', 55008);
		$log_path = log::getPathToLog('tradfricmd');
		
		//$url = network::getNetworkAccess('internal', 'proto:ip') . '/plugins/tradfri/core/php/jeetradfri.php?apikey=' . jeedom::getApiKey('tradfri');
		$url = 'http://127.0.0.1'. '/plugins/tradfri/core/php/jeetradfri.php?apikey=' . jeedom::getApiKey('tradfri');

		tradfri::launch_svc($url, $ipGateway, $portGateway, $securitykeyGateway, $socketport);
	}

	public static function launch_svc($url, $ip, $port, $key, $socketport) {
		$log = log::convertLogLevel(log::getLogLevel('tradfri'));
		$tradfri_path = realpath(dirname(__FILE__) . '/../../node');			
		
		$cmd = 'nice -n 19 nodejs ' . $tradfri_path . '/deamon.js ' . $url . ' ' . $ip . ' ' . $port . ' ' . $key . ' ' . $log . ' ' . $socketport;
		log::add('tradfri', 'debug', 'Lancement du démon tradfri : ' . $cmd);

		$result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('tradfricmd') . ' 2>&1 &');
		if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
			log::add('tradfri', 'error', $result);
			return false;
		}

		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('tradfri', 'error', 'Impossible de lancer le démon tradfri, vérifiez le port', 'unableStartDeamon');
			return false;
		}
		message::removeAll('tradfri', 'unableStartDeamon');
		log::add('tradfri', 'info', 'Démon tradfri lancé');
		return true;
	}


	public static function deamon_stop() {
		exec('kill $(ps aux | grep "tradfri/node/deamon.js" | awk \'{print $2}\')');
		log::add('tradfri', 'info', 'Arrêt du service tradfri');
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] == 'ok') {
			sleep(1);
			exec('kill -9 $(ps aux | grep "tradfri/node/deamon.js" | awk \'{print $2}\')');
		}
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] == 'ok') {
			sleep(1);
			exec('sudo kill -9 $(ps aux | grep "tradfri/node/deamon.js" | awk \'{print $2}\')');
		}
	}

public static function updateStatus($var) {
		$obj = json_decode($var);
		$lastseen = $obj->{'lastseen'};
		$reachable = $obj->{'reachable'};
		$id = $obj->{'id'};
		$onoff = $obj->{'onoff'};
		$dimmer = $obj->{'dimmer'};
		$color = $obj->{'color'};
		log::add('tradfri','debug',"id " . $id . " -> " . $var);
		$device = tradfri::byLogicalId($id, 'tradfri');		
		if (!is_object($device)) {
			log::add('tradfri', 'debug', 'Aucun équipement trouvé pour : ' . $id . "\n");
		}else{			
			if (!$reachable){ #Si pas visible, on considère que la lampe est OFF
				$onoff = 0;
			}
			#$tradfricmd = $device->getCmd('info', 'status');
			#$tradfricmdnum = $device->getCmd('info', 'statusnum');		
			#$lastseencmd = $device->getCmd('info', 'lastseen');		
			#$reachablecmd = $device->getCmd('info', 'reachable');		
			log::add('tradfri','debug',"mise a jour du status : ".$onoff." - ".$dimmer."%"." - ".$lastseen." - ".$reachable." - ".$color);
			#$tradfricmd->event($onoff);
			#$tradfricmdnum->event($dimmer);
			#$lastseencmd->event($lastseen);
			#$reachablecmd->event($reachable);
			$device->checkAndUpdateCmd('status', $onoff);
			$device->checkAndUpdateCmd('statusnum', $dimmer);
			$device->checkAndUpdateCmd('lastseen', $lastseen);
			$device->checkAndUpdateCmd('reachable', $reachable);
			$device->checkAndUpdateCmd('color', $color);
		}
	}


/*     * *********************Methode d'instance************************* */

	public function preInsert() {

	}

	public function preSave() {

	}

	public function postSave() {
	
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                                               //
	//                                                      Gestion des Template d'equipement                                                       // 
	//                                                                                                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 	public static function devicesParameters($_device = '') {		
		$path = dirname(__FILE__) . '/../config/devices';
		if (isset($_device) && $_device != '') {
			$files = ls($path, $_device . '.json', false, array('files', 'quiet'));
			if (count($files) == 1) {
				try {
					$content = file_get_contents($path . '/' . $files[0]);
					//log::add('tradfri','debug',$content);
					if (is_json($content)) {						
						//log::add('tradfri','debug','TEST : ' . $_device);
						$deviceConfiguration = json_decode($content, true);
						return $deviceConfiguration;
					}
				} catch (Exception $e) {
					return array();
				}
			}else{
				log::add('tradfri','debug',"Fichier template introuvable : ".$path, $_device . '.json');			
			}
		}
		$files = ls($path, '*.json', false, array('files', 'quiet'));
		$return = array();
		foreach ($files as $file) {
			try {
				$content = file_get_contents($path . '/' . $file);
				if (is_json($content)) {
					$return = array_merge($return, json_decode($content, true));
				}
			} catch (Exception $e) {

			}
		}
		if (isset($_device) && $_device != '') {
			if (isset($return[$_device])) {
				return $return[$_device];
			}
			return array();
		}
		return $return;
	}

	public function applyModuleConfiguration($template) {		
		$device = $this->getConfiguration('name'); 
		//$template = str_replace(' ', '_',trim($template)); 
		log::add('tradfri','debug',"Device : ".$device);			
		log::add('tradfri','debug',"Template : ".$template);			
		//$this->save();		
		if ($template == '') {
		//	$this->save();
			return true;
		}
		$device = self::devicesParameters($template);
		
		if (!is_array($device) || !isset($device['cmd'])) {			
			return true;
		}
		if (isset($device['configuration'])) {
			foreach ($device['configuration'] as $key => $value) {
				$this->setConfiguration($key, $value);
				log::add('tradfri','debug',"Set config ".$key.' '.$value);			
			}
		}
		$cmd_order = 0;
		$link_cmds = array();
		foreach ($device['cmd'] as $command) {
			log::add('tradfri','debug',"Ajout d'une commande ".$command['name']);			
			if (isset($device['cmd']['logicalId'])) {
				continue;
			}
			$cmd = null;
			foreach ($this->getCmd() as $liste_cmd) {
				if (isset($command['name']) && $liste_cmd->getName() == $command['name']) {
					$cmd = $liste_cmd;	
					break;
				}
			}
			try {
				log::add('tradfri','debug',$cmd);	
				if ($cmd == null || !is_object($cmd)) {					
					$cmd = new tradfriCmd();
					$cmd->setOrder($cmd_order);
					$cmd->setEqLogic_id($this->getId());					
				} else {
					//log::add('tradfri','debug',"1b");								
				}			
				
				utils::a2o($cmd, $command);
				if (isset($command['value']) && $command['value']!="") {
					$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$command['value']);
					if(is_object($CmdValue))
						$cmd->setValue('#'.$CmdValue->getId().'#');
					else
						$cmd->setValue(null);
				}
				if (isset($command['configuration']['option']) && $command['configuration']['option']!="") {
					$options=array();
					foreach($command['configuration']['option'] as $option => $cmd){
						$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$cmd);
						if(is_object($CmdValue))
							$options[$option]='#'.$CmdValue->getId().'#';
					}
						$cmd->setConfiguration('option',$options);
				}								
				$cmd->save();	
				$cmd_order++;
				array_push($link_cmds,utils::o2a($cmd));

			} catch (Exception $exc) {
				error_log($exc->getMessage());
			}
		}
		return json_encode(['cmd' => $link_cmds]);
	}

/*     * **********************Getteur Setteur*************************** */
} 	

class tradfriCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

	public function execute($_options = null) {
		if ($this->getType() == 'action') {
			log::add ('tradfri','debug','OPTIONS : '.json_encode($_options));

			$logicalId = $this->getEqlogic()->getLogicalId();			
			$name = $this->getEqlogic()->getName();
			$namecde = $this->getName();
			$url = $this->getEqlogic()->getConfiguration('url');
			$cde = $this->getLogicalId();
			log::add ('tradfri','debug','ID : '.$logicalId.', Name : '.$name.', Name Cde : '.$namecde.', Url : '.$url.', Commande : '.$cde);
			
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
        	socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'tradfri', 55008));
			$msg = json_encode(['action' => 'sendCde', 'level' => $_options['slider'], 'id' => $logicalId, 'cde' => $cde ]);
			socket_write($socket, $msg, strlen($msg));
        }
    }

	public function postSave() {	
	}
    /*     * **********************Getteur Setteur*************************** */

}


?>
