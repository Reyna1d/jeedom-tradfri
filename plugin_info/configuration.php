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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <legend><i class="fa fa-list-alt"></i> {{Général}}</legend>
		    <div class="form-group">
				<label class="col-sm-4 control-label">{{IP Gateway}}</label>
				<div class="col-sm-2">
                    <input class="configKey form-control" data-l1key="ipGateway" value='192.168.0.1' />
                </div>
			</div>            
            <div class="form-group">
				<label class="col-sm-4 control-label">{{Port Gateway}}</label>
				<div class="col-sm-2">
                    <input class="configKey form-control" data-l1key="portGateway" value='5684' />
                </div>
			</div>            
            <div class="form-group">
				<label class="col-sm-4 control-label">{{Security Key Gateway}}</label>
				<div class="col-sm-2">
                    <input class="configKey form-control" data-l1key="keyGateway" value='' />
                </div>
			</div>            
        </div>
		
        <div class="form-group">
            <label class="col-sm-4 control-label">{{Port socket interne (modification dangereuse}}</label>
            <div class="col-sm-2">
                <input class="configKey form-control" data-l1key="socketport" value='55008' />
            </div>
        </div>
    </fieldset>
</form>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
    <input type="hidden" name="cmd" value="_s-xclick">
    <input type="hidden" name="hosted_button_id" value="HN3KF9SQTNQK4">
    <input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
</form>


<script>
    $('#bt_logTradfriMessage').on('click', function () {
        $('#md_modal').dialog({title: "{{Log des messages Tradfri}}"});
        $('#md_modal').load('index.php?v=d&plugin=tradfri&modal=show.log').dialog('open');
    });
</script>