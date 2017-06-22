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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'tradfri')) {	
#	connection::failed();
	echo 'Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (jeetradfri)';
	log::add ('tradfri', 'debug', "Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (jeetradfri)");
	die();
}

if (isset($_GET['test'])) {
	echo 'OK';
	die();
}

if (isset($_GET['state'])) {
	log::add ('tradfri', 'debug', "Recu changement de status Device");
	$var = urldecode($_GET['state']);
	log::add ('tradfri', 'debug',$var);
	tradfri::updateStatus($var);
	echo 'OK';
}


if (isset($_POST['scan'])) {
	log::add ('tradfri', 'debug', "Recu configuration tradfri");
	log::add ('tradfri', 'debug','scan : '.$_POST['scan']);

	$devices = json_decode(trim($_POST['scan']),true);
	if (empty($devices)) {
		log::add('tradfri','info','Impossible de parser (JSON) le résultat du socket : '.$out);		
	};
	foreach($devices as $key => $device){			
		log::add('tradfri','debug','Device '.$key.' => '.json_encode($device));						
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
	//$var = urldecode($_GET['state']);
	//log::add ('tradfri', 'debug',$var);
	//tradfri::updateStatus($var);
	echo 'OK';
}
