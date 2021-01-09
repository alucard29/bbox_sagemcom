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

global $listCmdBbox_sagemcom;

$index = 1;

$listCmdBbox_sagemcom = array(
    array(
        'name' => 'Actualiser',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'refresh',
        'description' => 'Actualiser les informations de la BBox',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
        ),
    ),
    array(
        'name' => 'Présence Box',
        'type' => 'info',
        'subType' => 'binary',
        'logicalId' => 'box_state',
        'description' => 'Indique la détection de la BBox',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
    array(
        'name' => 'Redémarrer',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'reboot_box',
        'description' => 'Redémarrage de la box',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'value' => 'box_state',
            'template' => 'bboxState',
        ),
    ),
    array(
        'name' => 'Lumière On',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'lightOn',
        'description' => 'Allumage des lumières de la box',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'value' => 'lightState',
            'template' => 'bboxLightOnOff',
        ),
    ),
    array(
        'name' => 'Lumière Off',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'lightOff',
        'description' => 'Extinction des lumières de la box',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'value' => 'lightState',
            'template' => 'bboxLightOnOff',
        ),
    ),
    array(
        'name' => 'Lumière',
        'type' => 'info',
        'subType' => 'binary',
        'logicalId' => 'lightState',
        'description' => 'Etat des lumières de la box',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
    array(
        'name' => 'Internet',
        'type' => 'info',
        'subType' => 'binary',
        'logicalId' => 'wan_state',
        'description' => 'Indique l\'état de la synchronisation',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'template' => 'bboxInternetOnOff',
        ),
    ),
    array(
        'name' => 'Wifi',
        'type' => 'info',
        'subType' => 'binary',
        'logicalId' => 'wifi_state',
        'description' => 'Indique l\'état du Wifi',
        'group' => 'Wifi',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
    array(
        'name' => 'Wifi On',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'wifi_start',
        'description' => 'Activer le Wifi',
        'group' => 'Wifi',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'template' => 'bboxWifiOnOff',
            'value' => 'wifi_state',
        ),
    ),
    array(
        'name' => 'Wifi Off',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'wifi_stop',
        'description' => 'Desactiver le Wifi',
        'group' => 'Wifi',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'template' => 'bboxWifiOnOff',
            'value' => 'wifi_state',
        ),
    ),
    array(
        'name' => 'TV',
        'type' => 'info',
        'subType' => 'binary',
        'logicalId' => 'tv_state',
        'description' => 'Indique la présence d\'un décodeur TV',
        'group' => 'TV',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'template' => 'bboxTvOnOff',
        ),
    ),
    array(
        'name' => 'VoIP',
        'type' => 'info',
        'subType' => 'binary',
        'logicalId' => 'voip_state',
        'description' => 'Indique l\'état du service VoIP',
        'group' => 'VoIP',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'returnAfter' => 1,
            'template' => 'bboxPhoneOnOff',
        ),
    ),
    array(
        'name' => 'IP Wan',
        'type' => 'info',
        'subType' => 'string',
        'logicalId' => 'public_ip',
        'description' => 'Indique l\'adresse IP publique de la Box',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
        ),
    ),
    array(
        'name' => 'Numéro',
        'type' => 'info',
        'subType' => 'string',
        'logicalId' => 'phone_nb',
        'description' => 'Indique le numéro de téléphone associé au service VoIP',
        'group' => 'VoIP',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
        ),
    ),
    array(
        'name' => 'Temps de fonctionnement',
        'type' => 'info',
        'subType' => 'string',
        'logicalId' => 'uptime',
        'description' => 'Temps depuis le dernier démarrage',
        'group' => 'System',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'returnAfter' => 1,
        ),
    ),
    array(
        'name' => 'Débit descendant',
        'type' => 'info',
        'subType' => 'numeric',
        'unite' => 'bits/s',
        'logicalId' => 'rate_down',
        'description' => 'Débit descendant',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'minValue' => 0,
        ),
    ),
    array(
        'name' => 'Débit ascendant',
        'type' => 'info',
        'subType' => 'numeric',
        'unite' => 'bits/s',
        'logicalId' => 'rate_up',
        'description' => 'Débit ascendant',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'minValue' => 0,
        ),
    ),
    array(
        'name' => 'Reçu',
        'type' => 'info',
        'subType' => 'numeric',
        'unite' => 'Octets',
        'logicalId' => 'data_received',
        'description' => 'Modulo du nombre d\'octects reçus',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
    array(
        'name' => 'Envoyé',
        'type' => 'info',
        'subType' => 'numeric',
        'unite' => 'Octets',
        'logicalId' => 'data_send',
        'description' => 'Modulo du nombre d\'octects envoyés',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
    array(
        'name' => 'Variation Reçu',
        'type' => 'info',
        'subType' => 'numeric',
        'unite' => 'Octets',
        'logicalId' => 'var_data_received',
        'description' => 'Variation du nombre d\'octects reçus depuis la dernière actualisation',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
    array(
        'name' => 'Variation Envoyé',
        'type' => 'info',
        'subType' => 'numeric',
        'unite' => 'Octets',
        'logicalId' => 'var_data_send',
        'description' => 'Variation du nombre d\'octects envoyés depuis la dernière actualisation',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
    array(
        'name' => 'Appels Manqués',
        'type' => 'info',
        'subType' => 'numeric',
        'logicalId' => 'received_calls',
        'description' => 'Nombre d\'appels manqués',
        'group' => 'VoIP',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'returnBefore' => 1,
            'template' => 'bboxMissingCalls',
        ),
    ),
    array(
        'name' => 'Messages vocaux',
        'type' => 'info',
        'subType' => 'numeric',
        'logicalId' => 'message_waiting',
        'description' => 'Nombre de messages vocaux',
        'group' => 'VoIP',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'template' => 'bboxWaitingMessages',
        ),
    ),
    array(
        'name' => 'Appeler',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'phone1_ring',
        'description' => 'Faire sonner le téléphone (test)',
        'group' => 'VoIP',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'returnBefore' => 1,
        ),
    ),
    array(
        'name' => 'Raccrocher',
        'type' => 'action',
        'subType' => 'other',
        'logicalId' => 'phone1_unring',
        'description' => 'terminer le test de sonnerie du téléphone',
        'group' => 'VoIP',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'returnAfter' => 1,
        ),
    ),
    array(
        'name' => 'Périphériques',
        'type' => 'info',
        'subType' => 'numeric',
        'logicalId' => 'connected_devices',
        'description' => 'Nombre de périphériques connectés au réseau',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 1,
            'returnAfter' => 1,
            'template' => 'bboxPeripherical',
        ),
    ),
    array(
        'name' => 'Liste',
        'type' => 'info',
        'subType' => 'string',
        'logicalId' => 'devices_List',
        'description' => 'Liste des périphériques connectés au réseau',
        'group' => 'Statistiques',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
            'template' => 'bboxList',
        ),
    ),
    array(
        'name' => 'Journal des appels',
        'type' => 'info',
        'subType' => 'string',
        'logicalId' => 'calllog',
        'description' => 'liste les appels reçus ou émis',
        'group' => 'VoIP',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
            'template' => 'calllogList',
        ),
    ),
    array(
        'name' => 'Chaîne',
        'type' => 'info',
        'subType' => 'string',
        'logicalId' => 'currentTvChannel',
        'description' => 'Chaîne courante',
        'group' => 'TV',
        'configuration' => array(
            'order' => $index++,
            'visible' => 0,
        ),
    ),
);
?>
