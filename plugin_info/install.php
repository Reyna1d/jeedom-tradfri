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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function tradfri_install() {
	log::add('tradfri', 'debug', 'Installation du Plugin Tradfri IKEA');
}

function tradfri_update() {
	log::add('tradfri', 'debug', 'Update du Plugin Tradfri IKEA');
	$resource_path = realpath(dirname(__FILE__) . '/../ressources');
	passthru('/bin/bash ' . $resource_path . '/maj.sh ' . $resource_path . ' > ' . log::getPathToLog('tradfri') . ' 2>&1 &');
}

function tradfriremove() {
	log::add('tradfri', 'debug', 'Suppression du Plugin Tradfri IKEA');
}
?>
