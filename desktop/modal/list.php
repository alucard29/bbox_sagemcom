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
$list_obj = $bbox_obj->getCmd('info','devices_List'); 
$listId=$list_obj->getId();
$listLogicalId=$list_obj->getLogicalId();
$data=$list_obj->execCmd(null, 2);
$list = json_decode($data);

$indent=1;

$dataInHtml = '<table class="bbox_table"><tr><th class="dataListe">Nom</th><th class="dataListe">@ IP</th></tr>';
foreach ($list as $key => $value) {
    $dataInHtml .= '<tr id="machine' . $indent . '" class="dataListe">';
    $dataInHtml .= '<td class="dataListe">';
    if ($value[1]=="") {
        $dataInHtml .= $value[2];
    } else {
        $dataInHtml .= $value[1];
    }
    $dataInHtml .= '</td>';
    $dataInHtml .= '<td class="dataListe">' . $value[0] . '</td></tr>';
    $indent++;
}
$dataInHtml.= '</table>';

echo <<<MON_HTML
 
<html>
<head>
</head>
<body>
   <style>
    table.bbox_table {
        border-collapse: collapse;
    }

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
<div style="min-width:50px;min-height:50px;" class="cmd tooltips cmd-widget container-fluid" data-type="info" data-subtype="other">
    <center>
        <span class="action" id="dataListe">${dataInHtml}</span>
    </center>
    <script>
    </script>
</div>
</body>
</html>
 
MON_HTML;

?>