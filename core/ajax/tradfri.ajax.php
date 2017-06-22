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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
	
	if (init('action') == 'scanDevices') {		
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'tradfri', 55008));
		$msg = json_encode(['action' => 'scanDevices']);
		socket_write($socket, $msg, strlen($msg));
		
        
		log::add ('tradfri','event','scanDevices');

		$out = $recv = '';		
		$buffersize = 1460;
		do {
			if ($out != '' && $recv != '') break;
			$recv = '';
			$recv = socket_read($socket,$buffersize);			
			if ($recv != '') $out .= $recv;			
		} while (strlen($recv)==$buffersize);
		socket_close($socket);
		log::add ('tradfri','info','recu : '.$out);
/*
		$devices = json_decode(trim($out),true);
		if (empty($devices)) {
			log::add('tradfri','info','Impossible de parser (JSON) le résultat du socket : '.$out);		
		};
		foreach($devices as $key => $device){			
			//tradfri::syncDevice($device);							

			log::add('tradfri','info','Device '.$key.' => '.json_encode($device));						
			$id = $device['id'];	
			$tradfri = eqLogic::byLogicalId($id, 'tradfri');
			if (!is_object($tradfri)) {
				log::add('tradfri', 'debug', 'Aucun équipement trouvé pour : ' . $id . "\n");
				$eqLogic = new eqLogic();
				$eqLogic->setEqType_name('tradfri');
				$eqLogic->setIsEnable(1);
				$eqLogic->setLogicalId($id);
				$eqLogic->setName($device['name']);
				$eqLogic->setConfiguration('url', $device['url']);			
				$eqLogic->setConfiguration('manufacturer', $device['manufacturer']);			
				$eqLogic->setConfiguration('serialNumber', $device['serialNumber']);
				$eqLogic->setConfiguration('firmware', $device['firmware']);	
				log::add('tradfri', 'debug','Generate config type '.$device['tradfri_type']);
				if ($device['tradfri_type']==0){
					$eqLogic->setConfiguration('modelNumber', $device['modelNumber']);
					//$eqLogic->applyModuleConfiguration(str_replace(' ', '_',trim($device['modelNumber'])));
					$template = str_replace(' ', '_',trim($device['modelNumber']));
				} else {
					$eqLogic->setConfiguration('modelNumber', 'group');
					//$eqLogic->applyModuleConfiguration('group');
					$template = 'group';
				};			
				$eqLogic->save();
			}else{
				log::add('tradfri', 'info', 'Equipement déjà existant : ' . $id);
				$template = str_replace(' ', '_',trim($device['modelNumber']));
			};
			//MAJ des commandes
			$tradfri=eqLogic::byLogicalId($id, 'tradfri');
			if (is_object($tradfri)){
				$template = str_replace(' ', '_',trim($tradfri->getConfiguration('modelNumber')));				
				$tradfri->applyModuleConfiguration($template);
			}else{
				log::add('tradfri', 'debug', 'Pas équipement trouvé pour : ' . $id . "\n");
			}
		};
*/
		ajax::success($out);
    }
	
    throw new Exception('Aucune methode correspondante');

    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
