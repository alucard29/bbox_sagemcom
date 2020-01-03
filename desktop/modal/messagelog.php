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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$id = init('id');
$object = cmd::byId($id);
$bbox_id = $object->getEqLogic_id();
$bbox_obj = bbox_sagemcom::byId($bbox_id);

$useAPI = $bbox_obj->getConfiguration('BBOX_USE_API');
if ($useAPI != 'api') {
    throw new Exception('{{Fonction uniquement accessible aux BBoxs utilisants l\'API}}');
}

$messagelog_obj = $bbox_obj->getCmd('info','messagelog'); 
$messagelogId=$messagelog_obj->getId();
$messagelogLogicalId=$messagelog_obj->getLogicalId();
$data=$messagelog_obj->execCmd(null, 2);
$messagelog = json_decode($data);
?>

<div class="eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <style>
    td.dataListe {
        border: 1px solid white;
        padding: 5px;
    }

    th.dataListe {
        border: 1px solid white;
        background:rgba(0,0,0,0.2);
        text-align:center;
    }

    tr.dataListe:nth-child(odd){
        background:rgba(0,0,0,0.1);
    }

    </style>
    <table class="table table-condensed table-bordered" id="table_<?php echo $messagelogId?>">
        <thead>
                <tr>
                    <th class="dataListe"></th>
                    <th class="dataListe">{{N°}}</th>
                    <th class="dataListe">{{Durée}}</th>
                    <th class="dataListe">{{Date}}</th>
                    <th class="dataListe"></th>
                </tr>
        </thead>
        <tbody>
            <?php
                foreach ($messagelog as $key => $value) {
                    $data_table .= '<tr id="log' . $value[5] . '" class="dataListe"';
                    if ($value[0]=='Read') {
                        $data_table .= '>';
                    } else {
                        $data_table .= 'style="font-weight: bold;">';
                    }
                    $data_table .= '<td class="dataListe">';
                    if ($value[0]!='Read') {
                        $data_table .= '<a class="downloadMessage" data-id="'.$value[5].'" data-link="'.$value[4].'" href="'.$value[4].'"><i class="fa fa-download"></i></a>';
                    }
                    $data_table .= '</td>';
                    $data_table .= '<td class="dataListe">' . $value[1] . '</td>';
                    $data_table .= '<td class="dataListe">' . $value[2] . '</td>';
                    $data_table .= '<td class="dataListe">' . $value[3] . '</td>';
                    $data_table .= '<td class="dataListe"><a class="deleteMessage" data-id="'.$value[5].'"><i class="fa fa-trash"></i></a></td></tr>';
                }
                echo $data_table;
            ?>

        </tbody>
    </table>
    <script>
    $('.deleteMessage').on('click', function () {
        var tr = $(this).closest('tr');
        var messageId = $(this).data('id');
 	$.ajax({
		type: 'POST',
		url: 'plugins/bbox_sagemcom/core/ajax/bbox_sagemcom.ajax.php',
		data: {
			action: 'deleteMessage',
                        eqLogicId: <?php echo $bbox_id?>,
                        messageId: messageId
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error, $('#div_eventEditAlert'));
		},
		success: function (data) {
                        tr.remove();
		}
	});				
	})
    
    $('.downloadMessage').on('click', function () {
        var messageId = $(this).data('id');
        var messageLink = $(this).data('link');
 	$.ajax({
		type: 'POST',
		url: 'plugins/bbox_sagemcom/core/ajax/bbox_sagemcom.ajax.php',
		data: {
			action: 'downloadMessage',
                        eqLogicId: <?php echo $bbox_id?>,
                        messageId: messageId,
                        messageLink: messageLink
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error, $('#div_eventEditAlert'));
		},
		success: function (data) {
                    location.reload(); 
		}
	});				
	})
        
        
    </script>
</div>


