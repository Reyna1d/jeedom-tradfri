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

