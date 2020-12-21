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

function bbox_sagemcom_update() {
	//log::add('bbox', 'error', 'Merci de re-sauvegarder votre equipement (Plugins -> Monitoring -> BBox Sagemcom)');
        log::add('bbox', 'error', 'Attention ! dernière mise à jour avant la suppression du mode par défaut. Merci de m\'envoyer un mp si vous utilisez le mode "défaut".');
}

?>