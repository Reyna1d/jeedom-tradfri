<?php
if (!isConnect('admin')) {
    throw new Exception('Error 401 Unauthorized');
}
sendVarToJS('eqType', 'tradfri');
$eqLogics = eqLogic::byType('tradfri');
?>

<script type="text/javascript">

</script>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="Rechercher" style="width: 100%"/></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
					echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction expertModeVisible" id="bt_scantrafri" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
                <center>
                    <i class="fa fa-refresh" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Synchroniser}}</center></span>
            </div>	
            <div class="cursor expertModeVisible" id="bt_tradfriNetwork" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-sitemap" style="font-size : 6em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Réseau Tradfri}}</center></span>
            </div>
        
    

			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;"><center>
				<i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i></center>
				<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
			</div>
		</div>
		
        <legend><i class="fa fa-table"></i>  {{Mes Equipements Tradfri}}</legend>
		<div class="eqLogicThumbnailContainer">
        <?php
			foreach ($eqLogics as $eqLogic) {
                $modelNumber = str_replace(' ', '_',trim($eqLogic->getConfiguration('modelNumber')));                
				$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
				echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
				echo "<center>";
				if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $modelNumber . '.png')) {                    
					echo '<img class="lazy" src="plugins/tradfri/core/config/devices/' . $modelNumber . '.png" height="105" width="95" />';
				} else {
					echo '<img class="lazy" src="plugins/tradfri/doc/images/tradfri_icon.png" height="105" width="95" />';
				}
				echo "</center>";
				echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
				echo '</div>';
			}
			?>
		</div>
		</div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <div class="row">
            <div class="col-sm-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}
                            <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>                            
                        </legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Nom de l'équipement</label>
                            <div class="col-sm-4">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="Nom de l'équipement Tradfri"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Adresse
                            <sup>
						        <i class="fa fa-question-circle tooltips" title="Indiquez l'adresse physique de votre équipement. Sous la forme AAPL OU AA : Ambiance (2 caracteres) et PL : Point Lumineux (2 caracteres)" style="font-size : 1em;color:grey;"></i>
					        </sup>
                            </label>                        
                            <div class="col-sm-4">
                                <input type="text" id="tradfriid" class="eqLogicAttr form-control" data-l1key="logicalId" placeholder="Logical ID"/>
                            </div>
                        </div>
						<div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >Objet parent</label>
                            <div class="col-sm-4">
                                <select class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">Aucun</option>
                                    <?php
                                    foreach (object::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Catégorie</label>
                            <div class="col-sm-9">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>

                            </div>
                        </div>                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Commentaire}}</label>
                            <div class="col-sm-8">
                                <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="commentaire" ></textarea>
                            </div>
                        </div>
                    </fieldset> 
                </form>
            </div>
            <div class="col-sm-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend><i class="fa fa-info-circle"></i>  {{Configuration}}</legend>
                         <div id="div_instruction"></div>
						 <div class="form-group expertModeVisible">
                            <label class="col-sm-3 control-label">{{Création}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr label label-default tooltips" data-l1key="configuration" data-l2key="createtime" title="{{Date de création de l'équipement}}" style="font-size : 1em;"></span>
                            </div>
                         </div>   
                         <div class="form-group expertModeVisible">   
							<label class="col-sm-3 control-label">{{Communication}}</label>
							<div class="col-sm-3">
								<span class="eqLogicAttr label label-default tooltips" data-l1key="status" data-l2key="lastCommunication" title="{{Date de dernière communication}}" style="font-size : 1em;"></span>
							</div>
                        </div>                       
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Fabriquant}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="manufacturer"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Modèle}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="modelNumber"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Firmware}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="firmware"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Serial Number}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="serialNumber"></span>
                            </div>
                        </div>						                        
                    </fieldset> 
                </form>
            </div>
        </div>

        <legend><i class="fa fa-list-alt"></i>  Commandes</legend>
        <div style="margin-bottom:5px;">
            <a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> Ajouter une commande</a>
            <a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> Supprimer</a>
            <a class="btn btn-success btn-sm eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> Sauvegarder</a>                
        </div>   
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 200px;">Nom</th>
                    <th style="width: 130px;" class="expertModeVisible">Type</th>
                    <th class="expertModeVisible">Logical ID (info) ou Commande brute (action)</th>
                    <th>Paramètres</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        

    </div>
</div>

<?php include_file('desktop', 'tradfri', 'js', 'tradfri'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
