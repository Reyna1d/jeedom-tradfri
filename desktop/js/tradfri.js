
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

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});


$('body').delegate('.cmdAttr[data-l1key=type]','change',function(){
	var tr = $(this).closest('tr');
	if ( $(this).value() =="info") {
//		tr.find('.cmdAttr[data-l1key=configuration][data-l2key=DureeCmd]').show()
	} else  if ( $(this).value() =="action") {
//		tr.find('.cmdAttr[data-l1key=configuration][data-l2key=DureeCmd]').hide()
	}
});

$('.eqLogicAttr[data-action=add]').on('click', function () {
    debugger;
    if($('.eqLogicAttr[data-l2key=device]').val()!=""){
        //$('.eqLogicAction[data-action=save]').trigger('click');        
        $.ajax({
            type: 'POST',   
            url: 'plugins/tradfri/core/ajax/tradfri.ajax.php',
            data: {
                action: 'AppliTemplate',
                id:$('.eqLogicAttr[data-l1key=id]').val(),
                template:$('.eqLogicAttr[data-l2key=device]').val()
            },
            dataType: 'json',
            global: true,
            error: function(request, status, error) {},
            success: function(data) {   
                //debugger;
                var json = json_decode(data.result)
                for (var i in json.cmd) {
                    addCmdToTable(json.cmd[i]);
                };
          //      $('.eqLogicAction[data-action=save]').trigger('click');                
          //      window.location.reload();
            }
        });
    }
});

$('#bt_scantrafri').on('click', function () {
    $.ajax({
        type: 'POST',   
        url: 'plugins/tradfri/core/ajax/tradfri.ajax.php',
        data: {
            action: 'scanDevices',            
        },
        dataType: 'json',
        global: true,
        error: function(request, status, error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function(data) {   
            //Le scan viens de se terminer
            debugger;
            switch (data) {
                case 'scan_impossible': //Scan impossible
                    $('#div_alert').showAlert({message: '{{L\'initialisation de la passerelle Tradfri est en cours. Veuillez réessayer plus tard.}}', level: 'warning'});
                case 'reload': //On refresh la page
                    window.location.reload();        
                    break;
            }
        }
    });
});

$('#bt_tradfriNetwork').on('click', function () {
    $('#md_modal').dialog({title: "{{Réseaux tradfri}}"});
    $('#md_modal').load('index.php?v=d&plugin=tradfri&modal=network').dialog('open');
});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td style="width:200px;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</td>';
    tr += '<td class="expertModeVisible" style="width:130px;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td class="expertModeVisible">'
    tr += '  <input class="cmdAttr form-control input-sm" data-l1key="logicalId"  value="0">';
    tr += '  <div class="RetourEtat form-horizontal">';
	tr += '    <label class="col-sm-3 control-label">{{Retour d\'état}}</label>';
	tr += '    <div class="input-group">';
	tr += '      <input class="cmdAttr form-control input-sm" data-l1key="value">';
	tr += '      <span class="input-group-btn">';
	tr += '        <a class="btn btn-success btn-sm bt_selectCmdExpression" id="value">';
	tr += '          <i class="fa fa-list-alt"></i>';
    tr += '        </a>';
    tr += '      </span>';
    tr += '    </div>';
    tr += '  </div>';
    tr += '<div class="option">';
//	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="DureeCmd" placeholder="{{Durée de la commande (en seconde)}}">';
//    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateValue" placeholder="{{Valeur retour d\'état}}" style="margin-top : 5px;">';
//    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateTime" placeholder="{{Durée avant retour d\'état (min)}}" style="margin-top : 5px;">';
    tr += '</td>';
    tr += '<td>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" data-size="mini" />{{Historiser}}</label><br>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" data-size="mini" checked/>{{Afficher}}</label><br>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-size="mini" data-l2key="invertBinary" />{{Inverser}}</label> ';
//    tr += '<input style="width : 150px;" class="tooltips cmdAttr form-control input-sm" data-l1key="cache" data-l2key="lifetime" placeholder="Lifetime cache">';
    tr += '</td>';    
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> Tester </a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {type: 'info'},
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result);
            tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
}

 
$('body').on('change', '.cmdAttr[data-l1key=type]',function() {
    switch ($(this).val()){
        case "info":
            $(this).closest('.cmd').find('.RetourEtat').hide();
            
        break;
        case "action":		
            $(this).closest('.cmd').find('.RetourEtat').show();
            
        break;
    }
});	
$('body').on( 'click','.bt_selectCmdExpression', function() {
    var el=$(this).closest('.input-group').find('.cmdAttr');
    $(this).value()
    jeedom.cmd.getSelectModal({cmd: {type: 'info'},eqLogic: {eqType_name : ''}}, function (result) {
        var value=el.val();
        if(value != '')
            value= value+'|';
        value=value+result.human;
        el.val(value);
    });  
});  
