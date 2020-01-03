
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

jQuery(document).ready(function(){
	view_mode($('.eqLogicAttr[data-l2key=BBOX_USE_API]').val());
});

function view_mode(mode){
	if(mode == 'api'){
		//$('#mode_select').hide();
		$('#box_passwd').show();
	}
	else{
		//$('#mode_select').show();
		$('#box_passwd').hide();
	}
}

$('body').delegate('.eqLogicAttr[data-l2key=BBOX_USE_API]', 'change', function () {
	view_mode($(this).val());
});

function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="id" ></span>';
	tr += '<span>&nbsp;</span>';
	tr += '<i class="fa fa-arrows-v"></i>';
	tr += '</td>';
	tr += '<td>';
	tr += '<div class="col-sm-8">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name"">';
        tr += '</div>';
	tr += '<td>';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" disabled>';
	tr += '</td>'; 
	tr += '<td>';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" disabled>';
	tr += '</td>'; 
	tr += '<td>';
	if (_cmd.type == 'action') {
		tr += 'Utiliser le résultat de l\'info suivante :';
		tr += '<select class="cmdAttr form-control tooltips input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
		tr += '<option value="">Aucune</option>';
		tr += '</select>';
	}
	else {
		if (_cmd.logicalId == 'connected_devices' || _cmd.logicalId == 'devices_List') {
			tr += 'TV inclu';
		}
		if (_cmd.logicalId == 'rate_down' || _cmd.logicalId == 'rate_up') {
			tr += 'Le maximum est directement fixé par la BBox';
		}
	}
	tr += '</td>';
	tr += '<td>';
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible"/>{{Afficher}}</label>';
	tr += '<span>&nbsp;</span>';
	if (_cmd.logicalId == 'rate_down' || _cmd.logicalId == 'rate_up') {
            tr += '<br/>Min / Max / Unité : <br/>';
            tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 33%; display : inline-block;" disabled>';
            tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 33%; display : inline-block;" disabled>';
            tr += '<input class="cmdAttr form-control tooltips input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width : 25%; display : inline-block;">';
	}
	if (_cmd.type == 'info') {
            tr += '<br/>';
            tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/>{{Historiser}}</label><br/>';
        }
        if (_cmd.logicalId == 'data_received' || _cmd.logicalId == 'data_send' || _cmd.logicalId == 'var_data_received' || _cmd.logicalId == 'var_data_send') {
		tr += 'Unité : <br/>';
		tr += '<input class="cmdAttr form-control tooltips input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width : 35%; display : inline-block;">';
	}
	tr += '</td>';
	tr += '<td>';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
	}
	tr += '</td>';
	tr += '</tr>';
	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
	var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {type: 'info'},
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result);
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
	if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
	if (isset(_cmd.subType)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=subType]').value(init(_cmd.subType));
    }
}