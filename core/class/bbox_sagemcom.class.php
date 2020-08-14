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

// Revision	Date			Comment
// v0.0.1 		02/04/2015		First release
// v0.0.2		10/04/2015		Comment in [request_mechanism]
//                                              corrected http:// added at the
//                                              beginning of the URL
//						Specific reboot function added
//						Write function return corrected
// v0.0.3 		28/06/2015		All postUpdate parameters moved
//                                              in "if" statement
//						xDSL/Cable or Cable function
//						added
// v0.1.0 		30/06/2015		Bandwidth informations are now
//                                              computed from Layer3 Service
//                                              only (more precise)
// v0.1.1 		01/07/2015		Bandwidth results unit changed
//                                              to bps as in Kbps the value may
//                                              not be updated for a while
// v0.1.2		03/07/2015		uptime added
//						pre-configured layout added
// v1.0.1 		10/09/2015		robusness improved
// v1.1.0 		17/09/2015		New API used
// v1.1.1		25/10/2015		BBox detection command added
// v1.2.0		28/11/2015		use a configuration file
// v1.2.1               30/12/2015              add calllog command
// v1.2.2		12/08/2018		Remove "trim" function on
//						password save
// v1.2.3		07/11/208		remove http to allow the use of https
// v1.2.4		09/06/2019		New TC channel detection method
// v1.2.5		14/08/2020		Add CURLOPT_SSL_VERIFYHOST and
//						CURLOPT_SSL_VERIFYPEER to support
//						Wifi6 Box bad SSL certificat
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

include_file('core', 'bbox_sagemcom', 'config', 'bbox_sagemcom');

class bbox_sagemcom extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    public function postUpdate() {
        log::add('bbox_sagemcom', 'debug', '[postUpdate] Function called');

        // Test the mode (API or legacy mode)
        $mode = $this->getConfiguration('BBOX_USE_API');
        log::add('bbox_sagemcom', 'debug', '[postUpdate] Selected mode is : ' . $mode);

        // Test if the custom widget shall be used
        $custom = $this->getConfiguration('BBOX_CUSTOM_WIDGET', 0);
        log::add('bbox_sagemcom', 'debug', '[postUpdate] Custom variable is equal to : ' . $custom);

        // Parse the command list and add commands that don't exist if ok
        // according to the selected mode
        global $listCmdBbox_sagemcom;
        foreach ($listCmdBbox_sagemcom as $cmd) {
            log::add('bbox_sagemcom', 'debug', '[postUpdate] Start process for : ' . $cmd['name']);
            $currentCmd = $this->getCmd(null, $cmd['logicalId']);
            // If the command doesn't exist and is present in this mode: create it
            if ((!is_object($currentCmd))&&(in_array($mode,$cmd['mode'],true))) {
                log::add('bbox_sagemcom', 'debug', '[postUpdate] ID ' . $cmd['logicalId'] . ' doesn\'t exist and mode is ok so create the command');
                $this->addNewBBoxCmd($cmd);
            // If the command exists but should not be present in this mode: supress it
            } elseif((is_object($currentCmd))&&(!in_array($mode,$cmd['mode'],true))) {
                log::add('bbox_sagemcom', 'debug', '[postUpdate] ID ' . $cmd['logicalId'] . ' should no more exist so supress it');
                unset($currentCmd);
            }
            // If the command exists and shall be custumed, do it
            if (isset($currentCmd)){
                log::add('bbox_sagemcom', 'debug', '[postUpdate] currentCmd exist so test if is object and custom is requested');
                if ((is_object($currentCmd))&&($custom == 1)) {
                    log::add('bbox_sagemcom', 'debug', '[postUpdate] Apply configuration');
                    $this->configureBBoxCmd($cmd);
                    // Clear the custom widget parameter
                    $this->setConfiguration('BBOX_CUSTOM_WIDGET', 0);
                }
            }
        }
    }

    public function postSave() {
        log::add('bbox_sagemcom', 'debug', '[postSave] Function called');
        // Test if the custom widget shall be used
        $custom = $this->getConfiguration('BBOX_CUSTOM_WIDGET', 0);
        log::add('bbox_sagemcom', 'debug', '[postSave] Custom variable is equal to : ' . $custom);
        if ($custom == 1) {
            global $listCmdBbox_sagemcom;
            foreach ($listCmdBbox_sagemcom as $cmd) {
                log::add('bbox_sagemcom', 'debug', '[postSave] Start process for : ' . $cmd['name']);
                $currentCmd = $this->getCmd(null, $cmd['logicalId']);
                // If the command exists and shall be custumed, do it
                if (isset($currentCmd)){
                    log::add('bbox_sagemcom', 'debug', '[postSave] currentCmd exist so test if is object');
                    if ((is_object($currentCmd))) {
                        log::add('bbox_sagemcom', 'debug', '[postSave] Apply configuration');
                        $this->configureBBoxCmd($cmd);
                        // Clear the custom widget parameter
                        $this->setConfiguration('BBOX_CUSTOM_WIDGET', 0);
                    }
                }
            }
        }
    }

    public function addNewBBoxCmd($cmd) {
        log::add('bbox_sagemcom', 'debug', '[addNewBBoxCmd] Function called for command : ' . $cmd['name']);
        if ($cmd) {
            $bbox_sagemcomCmd = new bbox_sagemcomCmd();
            $bbox_sagemcomCmd->setName(__($cmd['name'], __FILE__));
            $bbox_sagemcomCmd->setEqLogic_id($this->id);
            $bbox_sagemcomCmd->setType($cmd['type']);
            $bbox_sagemcomCmd->setSubType($cmd['subType']);
            $bbox_sagemcomCmd->setLogicalId($cmd['logicalId']);
            if ($cmd['type'] == 'info') {
                $bbox_sagemcomCmd->setEventOnly(1);
            }
            if (array_key_exists('minValue', $cmd['configuration'])) {
                $bbox_sagemcomCmd->setConfiguration('minValue', $cmd['configuration']['minValue']);
            }
            if (array_key_exists('unite', $cmd)) {
                $bbox_sagemcomCmd->setUnite($cmd['unite']);
            }
            if (array_key_exists('visible', $cmd['configuration'])) {
                $bbox_sagemcomCmd->setIsVisible($cmd['configuration']['visible']);
            }
            $bbox_sagemcomCmd->save();
        }
    }

    public function configureBBoxCmd($cmd) {
        log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Function called for command : ' . $cmd['name']);
        if ($cmd) {
            $configureCmd = $this->getCmd(null, $cmd['logicalId']);
            log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] work on : ' . $cmd['logicalId']);

            $configureCmd->setOrder($cmd['configuration']['order']);
            log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set order to : ' . $cmd['configuration']['order']);

            if ($configureCmd->getName()!= $cmd['name']) {
                $configureCmd->setName($cmd['name']);
            }

            if (array_key_exists('template', $cmd['configuration'])) {
                $configureCmd->setTemplate('dashboard', $cmd['configuration']['template']);
                log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set template to : ' . $cmd['configuration']['template']);
            }

            if (array_key_exists('visible', $cmd['configuration'])) {
                $configureCmd->setIsVisible($cmd['configuration']['visible']);
                log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set visible to : ' . $cmd['configuration']['visible']);
            }

            if (array_key_exists('value', $cmd['configuration'])) {
                $cmdUsedForValue = $this->getCmd(null, $cmd['configuration']['value']);
                if (!is_object($cmdUsedForValue)) {
                    log::add('bbox_sagemcom', 'error', '[configureBBoxCmd] The command that should be used as value seems to not exist');
                } else {
                    $configureCmd->setValue($cmdUsedForValue->getId());
                    log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set value using : ' . $cmd['configuration']['value']);
                }
            }

            if (array_key_exists('returnAfter', $cmd['configuration'])) {
                $configureCmd->setDisplay('forceReturnLineAfter', $cmd['configuration']['returnAfter']);
                log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set returnAfter to : ' . $cmd['configuration']['returnAfter']);
            }

            if (array_key_exists('returnBefore', $cmd['configuration'])) {
                $configureCmd->setDisplay('forceReturnLineBefore', $cmd['configuration']['returnBefore']);
                log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set returnBefore to : ' . $cmd['configuration']['returnBefore']);
            }

            if (array_key_exists('minValue', $cmd['configuration'])) {
                $configureCmd->setConfiguration('minValue', $cmd['configuration']['minValue']);
                log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set minValue to : ' . $cmd['configuration']['minValue']);
            }

            if (array_key_exists('unite', $cmd)) {
                $configureCmd->setUnite($cmd['unite']);
                log::add('bbox_sagemcom', 'debug', '[configureBBoxCmd] Set unit to ' . $cmd['unite']);
            }

            $configureCmd->save();
        }
    }

    // Function called by Jeedom every minute
    public static function cron() {
        log::add('bbox_sagemcom', 'debug', '[cron] Function called');
        // execute the monitoring for each bbox (RFU)
        foreach (bbox_sagemcom::byType('bbox_sagemcom') as $eqLogic) {
            if ($eqLogic->getIsEnable() == 1) {
                $useAPI = $eqLogic->getConfiguration('BBOX_USE_API');
                if ($useAPI == 'api') {
                    $eqLogic->box_monitor_api();
                } else {
                    $eqLogic->box_monitor();
                }
            }
        }
    }

    public function box_monitor_api() {
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Function called');
        $connexion = $this->getConfiguration('BBOX_CONNEXION_TYPE');
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Selected connexion type is : ' . $connexion);
        if ($connexion == 0) {
            $type = "cable";
        } else {
            $type = "xdsl";
        }
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] corresponding key is : ' . $type);
        $bbox_detection = true;

        // wan connection detection
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Try to find a Wan connection');
        $wan_connected = 0;
        $wan = "";
        $result = $this->api_request('wan/ip');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            if ($result[0]['wan']['ip']['state'] == 'Up') {
                $wan_connected = 1;
                $wan = $result[0]['wan']['ip']['address'];
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Find a Wan connection : ' . $wan);
            }
        }

        // VoIP detection
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] VoIP detection');
        $voip_enabled = 0;
        $voip_line = 0;
        $phone_nb = '';
        $result = $this->api_request('voip');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            if (array_key_exists('exception', $result)) {
                $re_connect = $this->open_api_session();
                $result = $this->api_request('voip');
            }
            if (isset($result[0]['voip'][0]['status']) && ($result[0]['voip'][0]['status'] == 'Up')) {
                $voip_enabled = 1;
                $voip_line = $result[0]['voip'][0]['id'];
                $chaine = preg_split("/@/", $result[0]['voip'][0]['uri']);
                $phone_nb = $chaine[0];
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Found an active VoIP service');
            }
        }

        // Call Log | added the 30/12/2015 | restriction : Apply only for 1 line (the fist)
        // First, refresh data
        $re_connect = $this->open_api_session();
        if ($re_connect == true) {
            $result = $this->refresh_bbox('callLog');
            $this->waitBoxReady(120);
            if ($result == false) {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
                $bbox_detection = false;
            } else {
                // FIXME: $result is true here, not an array and what's the point to reconnect etc?

                // if (array_key_exists('exception', $result)) {
                //     $re_connect = $this->open_api_session();
                //     $result = $this->refresh_bbox('callLog');
                //     $this->waitBoxReady(120);
                // }
            }

            // Second, collect data
            $result = $this->api_request('profile/calllog/'.$voip_line);
            if ($result == false) {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
                $bbox_detection = false;
            } else {
                $calllog = $result[0]['calllog'];
                if (is_array($calllog)) {
                    foreach ($calllog as $host_key => $host_value) {
                        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] found a call in log with number : ' . $host_value['number']);
                        if ($host_value['direction']=='E'){
                            $number = $host_value['number'];
                        } else {
                            $number = $host_value['name'];
                        }

                        $calllog_List[] = [$host_value['direction'], $number, $host_value['duration'], $host_value['date']];
                    }
                } else {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] detect calllog entry is not a array');
                }
            }
        }

         // Message Log | added the 21/1/2016 | restriction : Apply only for 1 line (the fist)
        $result = $this->refresh_bbox('get_voicemail');
        $this->waitBoxReady(120);
        $result = $this->api_request('voip/voicemail');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $messagelog = $result[0]['voicemail'];
            if (is_array($messagelog)) {
                foreach ($messagelog as $host_key => $host_value) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] found a message in log with caller number : ' . $host_value['callernumber']);
                    $messagelog_List[] = [$host_value['readstatus'], $host_value['callernumber'], $host_value['duration'], $host_value['dateconsult'], $host_value['linkmsg'], strval($host_value['id'])];
                }
            } else {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] detect messagelog entry is not a array');
            }
        }

        // Uptime calculation
        $result = $this->api_request('device');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $uptime = $this->formatTime($result[0]['device']['uptime']);
            if($result[0]['device']['display']['luminosity'] == 0) {
                $light = 0;
            } else {
                $light = 1;
            }
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Result of formatTime is : ' . $uptime);
        }

        // Data Send/Received variation calculation
        $result = $this->api_request('wan/ip/stats');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $data_received = round(floatval($result[0]['wan']['ip']['stats']['rx']['bytes']));
            $data_send = round(floatval($result[0]['wan']['ip']['stats']['tx']['bytes']));
            $var_data_received = $this->variation_calculation('var_data_received', $data_received);
            $var_data_send = $this->variation_calculation('var_data_send', $data_send);
        }

        // Bandwidth calculation (depends on the selected mode)
        if ($type == "xdsl") {
            // results are given in kbps
            $rate_down = round(floatval($result[0]['wan']['ip']['stats']['rx']['bandwidth']) * 1000);
            $max_rate_down = round(floatval($result[0]['wan']['ip']['stats']['rx']['maxBandwidth']) * 1000);
            $rate_up = round(floatval($result[0]['wan']['ip']['stats']['tx']['bandwidth']) * 1000);
            $max_rate_up = round(floatval($result[0]['wan']['ip']['stats']['tx']['maxBandwidth']) * 1000);
        } else {
            // results are given in kbps and max values in bps
            $rate_down = round(floatval($result[0]['wan']['ip']['stats']['rx']['bandwidth']) * 1000);
            $max_rate_down = round(floatval($result[0]['wan']['ip']['stats']['rx']['maxBandwidth']));
            $rate_up = round(floatval($result[0]['wan']['ip']['stats']['tx']['bandwidth']) * 1000);
            $max_rate_up = round(floatval($result[0]['wan']['ip']['stats']['tx']['maxBandwidth']));
        }

        // connected devices detection + TV detection (Adapted from Bouygues box code)
        $device_detected = 0;
        $tv_detected = 0;
        $result = $this->api_request('hosts');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $device_parameters = $result[0]['hosts']['list'];
            if (is_array($device_parameters)) {
                foreach ($device_parameters as $host_key => $host_value) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Start devices detection with Key : ' . $host_key);
                    if (isset($host_value['active']) && $host_value['active'] == 1) {
                        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Connected device ' . $host_value['ipaddress'] . ' is active');
                        $IP = $host_value['ipaddress'];
                        $devices_List[] = [$IP, $host_value['hostname'], $host_value['macaddress']];
                        $device_detected++;
                        if ($host_value['devicetype'] == "STB") {
                            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Connected media device is a STB device (i.e. TV)');
                            $tv_detected = 1;
                        }
                    }
                }
            } else {
                log::add('bbox_sagemcom', 'error', '[box_monitor_api] detect connected devices entry is not a array');
            }
        }

        // TV Channel detection  (old method)
        $currentTvChannel = "";
        //$tvChannelInformation = "";
        //$result = $this->api_request('iptv');
        //if ($result == false) {
        //    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
        //} else {
        //    $iptv = $result[0]['iptv'];
        //    if (is_array($iptv)) {
        //        foreach ($iptv as $host_key => $host_value) {
        //            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Start devices detection with Key : ' . $host_key);
        //            if (isset($host_value['name']) && $host_value['name'] != "") {
        //                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Current channel is : '. $host_value['name']);
        //                $currentTvChannel = $host_value['name'];
        //            }
        //        }
        //    } else {
        //        log::add('bbox_sagemcom', 'error', '[box_monitor_api] detect iptv entry is not a array');
        //    }
        //}

        // wifi state detection and new TV detection method
        $wifi_detected = 0;
        $result = $this->api_request('summary');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            if ($result[0]['wireless']['radio'] == 1) {
                $wifi_detected = 1;

            }
            $received_calls = $result[0]['voip'][0]['notanswered'];
            $message_waiting = $result[0]['voip'][0]['message'];
	    $currentTvChannel = $result[0]['display']['frontpanel'];
        }



        // Save results in an array using cmd ID as Key
        $retourbbox = array('box_state' => $bbox_detection,
            'wan_state' => $wan_connected,
            'wifi_state' => $wifi_detected,
            'tv_state' => $tv_detected,
            'voip_state' => $voip_enabled,
            'public_ip' => $wan,
            'phone_nb' => $phone_nb,
            'uptime' => $uptime,
            'rate_down' => $rate_down,
            'max_rate_down' => $max_rate_down,
            'rate_up' => $rate_up,
            'max_rate_up' => $max_rate_up,
            'data_received' => $data_received,
            'data_send' => $data_send,
            'var_data_received' => $var_data_received,
            'var_data_send' => $var_data_send,
            'received_calls' => $received_calls,
            'message_waiting' => $message_waiting,
            'connected_devices' => $device_detected,
            'devices_List' => json_encode($devices_List),
            'calllog' => json_encode($calllog_List),
            'messagelog' => json_encode($messagelog_List),
            'lightState' => $light,
            'currentTvChannel' => $currentTvChannel,
        );

        // Save Info cmds using the array Key
        //foreach (eqLogic::byType('bbox_sagemcom') as $eqLogic){
        foreach ($this->getCmd('info') as $cmd) {
            $cmd_id = $cmd->getLogicalId();
            $stored_value = $cmd->execCmd(null, 2);
            $cmd->setCollectDate('');
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Process for : ' . $cmd_id);
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Store value is : ' . $stored_value);
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Response value is : ' . $retourbbox[$cmd_id]);

            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Test if ' . $cmd_id . ' Value has changed');

            // Update value only if needed
            if ($stored_value == null || $stored_value != $retourbbox[$cmd_id]) {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Update ' . $cmd_id . ' value with : ' . $retourbbox[$cmd_id]);
                $cmd->event($retourbbox[$cmd_id]);
            }

            // Update the rate down max value if needed
            if ($cmd_id == 'rate_down') {
                $maxStoredRateDown = $cmd->getConfiguration('maxValue');
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Max stored value is : ' . $maxStoredRateDown);
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Max response value is : ' . floatval($retourbbox['max_rate_down']));
                if ($maxStoredRateDown != floatval($retourbbox['max_rate_down'])) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Update ' . $cmd_id . ' maxValue with : ' . $retourbbox['max_rate_down']);
                    $cmd->setConfiguration('maxValue', floatval($retourbbox['max_rate_down']));
                    $cmd->save();
                }
            }

            // Update the rate up max value if needed
            if ($cmd_id == 'rate_up') {
                $maxStoredRateUp = $cmd->getConfiguration('maxValue');
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Max stored value is : ' . $maxStoredRateUp);
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Max response value is : ' . floatval($retourbbox['max_rate_up']));
                if ($maxStoredRateUp != floatval($retourbbox['max_rate_up'])) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Update ' . $cmd_id . ' maxValue with : ' . $retourbbox['max_rate_up']);
                    $cmd->setConfiguration('maxValue', floatval($retourbbox['max_rate_up']));
                    $cmd->save();
                }
            }
        }
        //}
        return true;
    }

    public function api_request($type) {
        log::add('bbox_sagemcom', 'debug', '[api_request] Function called');
        $serveur = trim($this->getConfiguration('BBOX_SERVER_IP'));
        $rurl = $serveur . '/api/v1/' . $type;
        if ($serveur == '') { // Cannot send request without an URL
            throw new Exception('Adresse de la BBox non-renseignée');
        }
        log::add('bbox_sagemcom', 'debug', '[api_request] Send request to : ' . $rurl);

        // Send request using Jeedom API
        //$http = new com_http($rurl);
        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $rurl);
        curl_setopt($http, CURLOPT_HEADER, false);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
	curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($http);

        if (empty($response)) {
            // some kind of an error happened
            $error = (curl_error($http));
            curl_close($http); // close cURL handler
            log::add('bbox_sagemcom', 'debug', '[api_request] Error is : ' . $error);
            return false;
        } else {
            log::add('bbox_sagemcom', 'debug', '[api_request] Response is : ' . $response);
            curl_close($http); // close cURL handler

            $decoded_response = json_decode($response, true);

            log::add('bbox_sagemcom', 'debug', '[api_request] Decoded response is : ' . $response);

            // Test if the BBox has returned an error (or no JSON response)
            if ((json_last_error() != 0) || array_key_exists('error', $decoded_response)) {
                log::add('bbox_sagemcom', 'debug', '[api_request] Error or bad JSON respond from the BBox.');
                return false;
            } else {
                return $decoded_response;
            }
        }
    }

    public function box_monitor() {
        log::add('bbox_sagemcom', 'debug', '[box_monitor] Function called');
        $connexion = $this->getConfiguration('BBOX_CONNEXION_TYPE');
        log::add('bbox_sagemcom', 'debug', '[box_monitor] Selected connexion type is : ' . $connexion);
        if ($connexion == 0) {
            $type = "cable";
        } else {
            $type = "xdsl";
        }
        log::add('bbox_sagemcom', 'debug', '[box_monitor] corresponding key is : ' . $type);
        $bbox_detection = true;

        $request = '&read=WLANConfig_RadioEnable'
                . '&read=VoiceProfile_1_Line_1'
                . '&read=VoiceProfile_1_Line_2'
                . '&read=LANDevice_1_Hosts'
                . '&read=Services_TR111'
                . '&read=WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}';

        // Execute a read request
        $result = $this->request_mechanism('read', $request);
        if ($result == false) {
            $bbox_detection = false;
        }

        //if($result!=false) {	20151026_1
        //if the request succeeded, start the data processing
        log::add('bbox_sagemcom', 'debug', '[box_monitor] BBox result is correct so start processing data: ');


        // wan connection detection
        $wan_connected = 0;
        $wan = "";
        log::add('bbox_sagemcom', 'debug', '[box_monitor] Try to find a Wan connection');
        foreach ($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'] as $key => $value) {
            if (isset($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$key]['Status']['State'])) {
                if ($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$key]['Status']['State'] == 'Up') {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor] Find a Wan connection numbered : ' . $key);
                    $wan_connected = 1;
                    $wan = $key;
                }
            }
        }

        // VoIP detection
        $voip_enabled = 0;
        $phone_nb = '';
        log::add('bbox_sagemcom', 'debug', '[box_monitor] VoIP detection');
        if (($result['VoiceProfile_1_Line_1']['Enable'] == '1') || ($result['VoiceProfile_1_Line_2']['Enable'] == '1')) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor] Found an active VoIP service');
            $voip_enabled = 1;
            if ($phone_nb == '') {
                $phone_nb = $result['VoiceProfile_1_Line_1']['DirectoryNumber'];
            } else {
                $phone_nb = $phone_nb . '/' . $result['VoiceProfile_1_Line_1']['DirectoryNumber'];
            }
        }

        // Uptime calculation
        $uptime = $this->formatTime($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Status']['UpTime']);
        log::add('bbox_sagemcom', 'debug', '[box_monitor] Result of formatTime is : ' . $uptime);

        // Data Send/Received variation calculation
        $data_received = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['RxBytes']));
        $data_send = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['TxBytes']));
        $var_data_received = $this->variation_calculation('var_data_received', $data_received);
        $var_data_send = $this->variation_calculation('var_data_send', $data_send);

        // connected devices detection + TV detection (Adapted from Bouygues box code)
        $device_detected = 0;
        $tv_detected = 0;
        $device_parameters = $result['LANDevice_1_Hosts'];
        if (is_array($device_parameters)) {
            foreach ($device_parameters as $host_key => $host_value) {
                log::add('bbox_sagemcom', 'debug', '[box_monitor] Start devices detection with Key : ' . $host_key);
                if (isset($host_value['Active']) && $host_value['Active'] == 1) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor] Connected device ' . $host_value['IPAddress'] . ' is active');
                    if ($host_value['IP6Address'] != '') {
                        $IP = $host_value['IP6Address'];
                    } else {
                        $IP = $host_value['IPAddress'];
                    }
                    $devices_List[] = [$IP, $host_value['Hostname'], $host_value['MACAddress']];
                    $device_detected++;
                    foreach ($result['Services_TR111']['Device'] as $tr111_key => $tr111_value) {
                        log::add('bbox_sagemcom', 'debug', '[box_monitor] compare the MACaddress with TR111 devices');
                        if (isset($tr111_value['MACAddress']) && isset($host_value['MACAddress'])) {
                            if ($tr111_value['MACAddress'] == $host_value['MACAddress']) {
                                log::add('bbox_sagemcom', 'debug', '[box_monitor] Connected media device is a TR111 device (i.e. TV)');
                                $tv_detected = 1;
                            }
                        }
                    }
                }
            }
        } else {
            log::add('bbox_sagemcom', 'error', '[box_monitor] detect connected devices entry is not a array');
        }

        // Bandwidth calculation (depends on the selected mode)
        if ($type == "xdsl") {
            // results are given in kbps
            $rate_down = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['RxBandwidth']) * 1000);
            $max_rate_down = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['RxMaxBandwidth']) * 1000);
            $rate_up = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['TxBandwidth']) * 1000);
            $max_rate_up = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['TxMaxBandwidth']) * 1000);
        } else {
            // results are given in kbps and max values in bps
            $rate_down = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['RxBandwidth']) * 1000);
            $max_rate_down = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['RxMaxBandwidth']));
            $rate_up = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['TxBandwidth']) * 1000);
            $max_rate_up = round(floatval($result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Counters']['TxMaxBandwidth']));
        }

        // Save results in an array using cmd ID as Key
        $retourbbox = array('box_state' => $bbox_detection,
            'wan_state' => $wan_connected,
            'wifi_state' => $result['WLANConfig_RadioEnable'],
            'tv_state' => $tv_detected,
            'voip_state' => $voip_enabled,
            'public_ip' => $result['WANConnectionDevice_{Layer3Forwarding_ActiveConnectionService}'][$wan]['Status']['IPAddress'],
            'phone_nb' => $phone_nb,
            'uptime' => $uptime,
            'rate_down' => $rate_down,
            'max_rate_down' => $max_rate_down,
            'rate_up' => $rate_up,
            'max_rate_up' => $max_rate_up,
            'data_received' => $data_received,
            'data_send' => $data_send,
            'var_data_received' => $var_data_received,
            'var_data_send' => $var_data_send,
            'received_calls' => $result['VoiceProfile_1_Line_1']['Stats']['IncomingCallsReceived'],
            'message_waiting' => $result['VoiceProfile_1_Line_1']['CallingFeatures']['MessageWaiting'],
            'connected_devices' => $device_detected,
            'devices_List' => json_encode($devices_List)
        );

        // Save Info cmds using the array Key
        //foreach (eqLogic::byType('bbox_sagemcom') as $eqLogic){
        foreach ($this->getCmd('info') as $cmd) {
            $cmd_id = $cmd->getLogicalId();
            $stored_value = $cmd->execCmd(null, 2);
            $cmd->setCollectDate('');
            log::add('bbox_sagemcom', 'debug', '[box_monitor] Process for : ' . $cmd_id);
            log::add('bbox_sagemcom', 'debug', '[box_monitor] Store value is : ' . $stored_value);
            log::add('bbox_sagemcom', 'debug', '[box_monitor] Response value is : ' . $retourbbox[$cmd_id]);

            log::add('bbox_sagemcom', 'debug', '[box_monitor] Test if ' . $cmd_id . ' Value has changed');
            // Update value only if needed
            if ($stored_value != $retourbbox[$cmd_id]) {
                log::add('bbox_sagemcom', 'debug', '[box_monitor] Update ' . $cmd_id . ' value with : ' . $retourbbox[$cmd_id]);
                $cmd->event($retourbbox[$cmd_id]);
            }

            // Update the rate down max value if needed
            if ($cmd_id == 'rate_down') {
                $maxStoredRateDown = $cmd->getConfiguration('maxValue');
                log::add('bbox_sagemcom', 'debug', '[box_monitor] Max stored value is : ' . $maxStoredRateDown);
                log::add('bbox_sagemcom', 'debug', '[box_monitor] Max response value is : ' . floatval($retourbbox['max_rate_down']));
                if ($maxStoredRateDown != floatval($retourbbox['max_rate_down'])) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor] Update ' . $cmd_id . ' maxValue with : ' . $retourbbox['max_rate_down']);
                    $cmd->setConfiguration('maxValue', floatval($retourbbox['max_rate_down']));
                    $cmd->save();
                }
            }

            // Update the rate up max value if needed
            if ($cmd_id == 'rate_up') {
                $maxStoredRateUp = $cmd->getConfiguration('maxValue');
                log::add('bbox_sagemcom', 'debug', '[box_monitor] Max stored value is : ' . $maxStoredRateUp);
                log::add('bbox_sagemcom', 'debug', '[box_monitor] Max response value is : ' . floatval($retourbbox['max_rate_up']));
                if ($maxStoredRateUp != floatval($retourbbox['max_rate_up'])) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor] Update ' . $cmd_id . ' maxValue with : ' . $retourbbox['max_rate_up']);
                    $cmd->setConfiguration('maxValue', floatval($retourbbox['max_rate_up']));
                    $cmd->save();
                }
            }
        }
        //}
        return true;
        //}																			20151026_1
        //else {   																	20151026_1
        //log::add('bbox_sagemcom', 'debug', '[box_monitor] No result from the BBox');	20151026_1
        //return false;															20151026_1
        //}																			20151026_1
    }

        // Function used to request a new cookie
    public function open_api_session() {
        log::add('bbox_sagemcom', 'debug', '[open_api_session] Function called');
        $serveur = trim($this->getConfiguration('BBOX_SERVER_IP'));
        $password = $this->getConfiguration('BBOX_PSSWD');
        $rurl = $serveur . '/api/v1/login';
        // Cannot send request without URL
        if ($serveur == '') {
            throw new Exception('Adresse de la BBox non-renseignée');
        }
        log::add('bbox_sagemcom', 'debug', '[open_api_session] send request to : ' . $rurl);
        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $rurl);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_COOKIEJAR, "/tmp/cookies.txt");
        curl_setopt($http, CURLOPT_POST, 1);
        curl_setopt($http, CURLOPT_POSTFIELDS, 'password=' . $password);
	curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($http);
        log::add('bbox_sagemcom', 'debug', '[open_api_session] response is : ' . $result);
        curl_close($http);
        $decodedResult = json_decode($result, true);
        if (is_array($decodedResult)){
            if (array_key_exists('exception', $decodedResult)) {
                log::add('bbox_sagemcom', 'error', '[open_api_session] Le mot de passe utilisé pour la BBox est ou était erroné. Il est nécessaire de redémarrer manuellement la Box');
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function refresh_bbox($action) {
        log::add('bbox_sagemcom', 'debug', '[refresh_bbox] Function called');
        $serveur = trim($this->getConfiguration('BBOX_SERVER_IP'));
        $rurl = $serveur . '/api/v1/profile/refresh';
        // Cannot send request without URL
        if ($serveur == '') {
            throw new Exception('Adresse de la BBox non-renseignée');
        }
        log::add('bbox_sagemcom', 'debug', '[refresh_bbox] Send request to : ' . $rurl);
        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $rurl);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
        curl_setopt($http, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($http, CURLOPT_POSTFIELDS, 'action=' . $action);
	curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($http);
        log::add('bbox_sagemcom', 'debug', '[refresh_bbox] response is : ' . $result);
        curl_close($http);
        return true;
    }

    public function waitBoxReady($timeout) {
        $bboxBusy = false;
        log::add('bbox_sagemcom', 'debug', '[waitBoxReady] Function called');
        $startTime = time();
        $result = $this->api_request('profile/consumption');
        log::add('bbox_sagemcom', 'debug', '[waitBoxReady] Consumption response is : ' . $result);
        while ($result[0]['profile']['state'] != 0){
            if(time() > $startTime + $timeout) {
                $bboxBusy = true;
                break;
            }
            $result = $this->api_request('profile/consumption');
            log::add('bbox_sagemcom', 'debug', '[waitBoxReady] Consumption response is : ' . $result);
        }
        if ($bboxBusy == true){
            return false;
        } else {
            return true;
        }
    }

    public static function deleteMessage($eqLogicId,$messageId) {
        log::add('bbox_sagemcom', 'debug', '[deleteMessage] Function called by ID : '.$eqLogicId);
        $equipment = bbox_sagemcom::byId($eqLogicId);

        $re_connect = $equipment->open_api_session();
        if ($re_connect == true) {
            $serveur = trim($equipment->getConfiguration('BBOX_SERVER_IP'));
            $rurl = $serveur . '/api/v1/voip/voicemail/1/'.$messageId;
            log::add('bbox_sagemcom', 'debug', '[deleteMessage] Send request to : ' . $rurl);
            $http = curl_init();
            curl_setopt($http, CURLOPT_URL, $rurl);
            curl_setopt($http, CURLOPT_HEADER, false);
            curl_setopt($http, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
	    curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($http);
            log::add('bbox_sagemcom', 'debug', '[deleteMessage] response is : ' . $result);
            curl_close($http);

            $equipment->waitBoxReady(120);
            $re_connect = $equipment->open_api_session();
            if ($re_connect == true) {
                $result = $equipment->refresh_bbox('callLog');
                $equipment->waitBoxReady(120);
                $result = $equipment->refreshMessageWaiting();
                if ($result == true) {
                    $result = $equipment->refresh_bbox('get_voicemail');
                    $equipment->waitBoxReady(120);
                    $result = $equipment->refreshMessageLog();
                    return $result;
                } else {
                    return false;
                }
            } else {
                log::add('bbox_sagemcom', 'debug', '[deleteMessage] Login failed before refresh');
                return false;
            }
        } else {
            log::add('bbox_sagemcom', 'debug', '[deleteMessage] Login failed before deleting');
            return false;
        }
     }

// commented for now as the API seems to be incomplet
    public static function clearMessage($eqLogicId,$messageId,$messageLink) {
        log::add('bbox_sagemcom', 'debug', '[clearMessage] Function called by ID : '.$eqLogicId);
        $equipment = bbox_sagemcom::byId($eqLogicId);

        // pipo read (mandatory to set messages to unread)
        log::add('bbox_sagemcom', 'debug', '[clearMessage] Send request to : ' . $messageLink);
        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $messageLink);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($http);
        log::add('bbox_sagemcom', 'debug', '[clearMessage] response is : ' . $result);
        curl_close($http);

        // login command is also mandatory before clearing new messages
        $equipment->waitBoxReady(120);
        $re_connect = $equipment->open_api_session();
        if ($re_connect == true) {
            $serveur = trim($equipment->getConfiguration('BBOX_SERVER_IP'));
            $rurl = $serveur . '/api/v1/voip/voicemail/1/'.$messageId;
            // Cannot send request without URL
            if ($serveur == '') {
                throw new Exception('Adresse de la BBox non-renseignée');
            }
            log::add('bbox_sagemcom', 'debug', '[clearMessage] Send request to : ' . $rurl);
            $http = curl_init();
            curl_setopt($http, CURLOPT_URL, $rurl);
            curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
            curl_setopt($http, CURLOPT_CUSTOMREQUEST, "PUT");
	    curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($http);
            log::add('bbox_sagemcom', 'debug', '[clearMessage] response is : ' . $result);
            curl_close($http);
            $result = $equipment->waitBoxReady(120);
            if ($result == true) {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox is ready to receive new data');
                $result = $equipment->refresh_bbox('callLog');
                $equipment->waitBoxReady(120);
                $result = $equipment->refreshMessageWaiting();
                if ($result == true) {
                    $result = $equipment->refresh_bbox('get_voicemail');
                    $equipment->waitBoxReady(120);
                    $result = $equipment->refreshMessageLog();
                    return $result;
                } else {
                    return false;
                }
            } else {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox seems to be locked');
                return false;
            }

        } else {
            log::add('bbox_sagemcom', 'debug', '[clearMessage] Login failed before refresh');
            return false;
        }
    }

    public function refreshMessageWaiting() {
        log::add('bbox_sagemcom', 'debug', '[refreshMessageWaiting] Function called');
        $result = $this->api_request('summary');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            return false;
        } else {
            $message_waiting = $result[0]['voip'][0]['message'];
            $cmd= $this->getCmd('info','message_waiting');
            $cmd_id= $cmd->getLogicalId();
            log::add('bbox_sagemcom', 'debug', '[refreshMessageWaiting] Save a new value for ID : '.$cmd_id);
            $cmd->setCollectDate('');
            $cmd->event($message_waiting);
            return true;
        }
    }

    public function refreshMessageLog() {
        log::add('bbox_sagemcom', 'debug', '[refreshMessageLog] Function called');
        $result = $this->api_request('voip/voicemail');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
            return false;
        } else {
            $messagelog = $result[0]['voicemail'];
            if (is_array($messagelog)) {
                foreach ($messagelog as $host_key => $host_value) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] found a message in log with caller number : ' . $host_value['callernumber']);
                    $messagelog_List[] = [$host_value['readstatus'], $host_value['callernumber'], $host_value['duration'], $host_value['dateconsult'], $host_value['linkmsg'], strval($host_value['id'])];
                }
                $cmd= $this->getCmd('info','messagelog');
                $cmd->setCollectDate('');
                $cmd->event(json_encode($messagelog_List));
            } else {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] detect messagelog entry is not a array');
            }
            return true;
        }
    }

    // Function used to ask for new tokens (specific address)
    public function open_session() {
        log::add('bbox_sagemcom', 'debug', '[open_session] Function called');
        $serveur = trim($this->getConfiguration('BBOX_SERVER_IP'));
        // Cannot send request without URL
        if ($serveur == '') {
            throw new Exception('Adresse de la BBox non-renseignée');
        }

        $http = new com_http($serveur . '/admin/index.htm');
        $result = $http->exec(30, 2);

        // subtract the token from the response
        $token = substr($result, strpos($result, 'var token = eval') + 21, 8);
        $wtoken = substr($result, strpos($result, 'var tokenWrite = eval') + 26, 8);

        if ($token != '') {
            log::add('bbox_sagemcom', 'debug', '[open_session] Save the new read token : ' . $token);
            log::add('bbox_sagemcom', 'debug', '[open_session] Save the new write token : ' . $wtoken);
            log::add('bbox_sagemcom', 'debug', '[open_session] Trying to save token');
            $this->setConfiguration('BBOX_SERVER_SESSION_TOKEN', $token);
            $this->setConfiguration('BBOX_SERVER_SESSION_WTOKEN', $wtoken);
            $this->save();
            return true;
        } else {
            log::add('bbox_sagemcom', 'error', '[open_session] Token recovery fail');
            return false;
        }
    }

    // The BBox need a token for each request
    // So, this function send a request with the saved token and, if the result
    // is not good, ask a new token
    public function request_mechanism($type, $request) {
        log::add('bbox_sagemcom', 'debug', '[request_mechanism] Function called');

        // Construct the Post Field
        $token = $this->get_token($type);
        $postField = 'token=' . $token . $request;

        // Execute request
        $response = $this->send_request($postField);
        if ($response != false) {
            $decoded_response = json_decode($response, true);

            // Test if the BBox has returned an error (or no JSON response)
            if ((json_last_error() != 0) || array_key_exists('error', $decoded_response)) {

                // First try failed, request a new token
                log::add('bbox_sagemcom', 'debug', '[request_mechanism] Error or bad JSON respond from the BBox. A new token is requested');
                $re_connect = $this->open_session();

                // Test if the new token request succeeded
                if ($re_connect) {

                    log::add('bbox_sagemcom', 'debug', '[request_mechanism] New write token request succeeded');
                    $token = $this->get_token($type);
                    $postField = 'token=' . $token . $request;

                    // Try to send the write request with the new token
                    $response = $this->send_request($postField);
                    $decoded_response = json_decode($response, true);

                    // Test if the BBox has returned an error (or no JSON response)
                    if ((json_last_error() != 0) || array_key_exists('error', $decoded_response)) {
                        log::add('bbox_sagemcom', 'error', '[request_mechanism] Error or bad JSON respond from the BBox during the second try');
                        return false;
                    } else {
                        if ($type == 'read') {
                            return $decoded_response;
                        } else {
                            return true;
                        }
                    }
                } else {
                    return false;
                }
            } else {
                if ($type == 'read') {
                    return $decoded_response;
                } else {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public function get_token($type) {
        // Get the token depending of the request type
        switch ($type) {
            case 'write':
                $token = $this->getConfiguration('BBOX_SERVER_SESSION_WTOKEN');
                break;
            case 'read':
                $token = $this->getConfiguration('BBOX_SERVER_SESSION_TOKEN');
                break;
            default:
                log::add('bbox_sagemcom', 'error', '[get_token] bad request type : ' . $type);
                $token = 0;
                break;
        }
        return $token;
    }

    public function send_api_request($postField) {
        log::add('bbox_sagemcom', 'debug', '[send_api_request] Function called');
        $serveur = trim($this->getConfiguration('BBOX_SERVER_IP'));

        // Get the token
        $rurl = $serveur . '/api/v1/device/token';
        // Send request using Jeedom API
        //$http = new com_http($rurl);
        log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $rurl);
        curl_setopt($http, CURLOPT_HEADER, false);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
	curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($http);
        log::add('bbox_sagemcom', 'debug', '[api_request] Response is : ' . $response);
        curl_close($http);

        $decoded_response = json_decode($response, true);


        $rurl = $serveur . '/api/v1/' . $postField . '?btoken=' . $decoded_response[0]['device']['token'];
        log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $rurl);
        curl_setopt($http, CURLOPT_HEADER, false);
        curl_setopt($http, CURLOPT_POST, 1);
        curl_setopt($http, CURLOPT_POSTFIELDS, 'ring_timeout=function (){}');
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
	curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($http);
        log::add('bbox_sagemcom', 'debug', '[api_request] Response is : ' . $response);
        curl_close($http);

        $decoded_response = json_decode($response, true);

        log::add('bbox_sagemcom', 'debug', '[api_request] response from BBox is : ' . $response);
    }

    // Function used to interact with the Jeedom API
    public function send_request($postField) {
        // Contruct the URL
        $serveur = trim($this->getConfiguration('BBOX_SERVER_IP'));
        $rurl = $serveur . '/cgi-bin/generic.cgi';
        if ($serveur == '') { // Cannot send request without an URL
            throw new Exception('Adresse de la BBox non-renseignée');
        }
        log::add('bbox_sagemcom', 'debug', '[send_request] Send request to : ' . $rurl);
        log::add('bbox_sagemcom', 'debug', '[send_request] Send request with the following postfield : ' . $postField);

        // Send request using Jeedom API
        $http = new com_http($rurl);
        $http->setPost($postField);
        $response = $http->exec(30, 2);
        log::add('bbox_sagemcom', 'debug', '[send_request] response from BBox is : ' . $response);
        if (empty($response)) {
            return false;
        } else {
            return $response;
        }
    }

    // Function used reboot the bbox (specific)
    public function reboot() {
        log::add('bbox_sagemcom', 'debug', '[reboot] Function called');
        $serveur = trim($this->getConfiguration('BBOX_SERVER_IP'));
        // Cannot send request without URL
        if ($serveur == '') {
            throw new Exception('Adresse de la BBox non-renseignée');
        }

        $http = new com_http($serveur . '/admin/gtw.htm');
        $result = $http->exec(30, 2);

        // subtract the token from the response
        $token = substr($result, strpos($result, 'var token = eval') + 21, 8);

        if ($token != '') {
            log::add('bbox_sagemcom', 'debug', '[reboot] Reboot token is : ' . $token);
            $http = new com_http($serveur . '/cgi-bin/generic.cgi');
            $postField = 'token=' . $token . '&fct=reboot';
            $http->setPost($postField);
            $response = $http->exec(30, 2);
            $decoded_response = json_decode($response, true);
            log::add('bbox_sagemcom', 'debug', '[send_request] response from BBox is : ' . $response);
            // Test if the BBox has returned an error
            if ((json_last_error() != 0) || array_key_exists('error', $decoded_response)) {
                return false;
            } else {
                return true;
            }
        } else {
            log::add('bbox_sagemcom', 'error', '[reboot] Token recovery fail');
            return false;
        }
    }

    // Function to format the uptime from box to a human format string
    // Only adapted from the BBox F@ast calculation function
    function formatTime($uptime) {
        log::add('bbox_sagemcom', 'debug', '[formatTime] Function called');

        $days = 0;
        $hours = 0;
        $minutes = 0;
        $secondes = $uptime;
        log::add('bbox_sagemcom', 'debug', '[formatTime] entry value is : ' . $secondes);

        if ($secondes > 86400) {
            log::add('bbox_sagemcom', 'debug', '[formatTime] entry is > 86400 so..');
            $days = floor($secondes / 86400);
            log::add('bbox_sagemcom', 'debug', '[formatTime] ..days value is : ' . $days);
            $secondes = $secondes - $days * 86400;
            log::add('bbox_sagemcom', 'debug', '[formatTime] new entry value is : ' . $secondes);
        }
        if ($secondes > 3600) {
            log::add('bbox_sagemcom', 'debug', '[formatTime] entry is > 3600 so..');
            $hours = floor($secondes / 3600);
            log::add('bbox_sagemcom', 'debug', '[formatTime] ..hours value is : ' . $hours);
            $secondes = $secondes - $hours * 3600;
            log::add('bbox_sagemcom', 'debug', '[formatTime] new entry value is : ' . $secondes);
        }
        if ($secondes > 60) {
            log::add('bbox_sagemcom', 'debug', '[formatTime] entry is > 60 so..');
            $minutes = floor($secondes / 60);
            log::add('bbox_sagemcom', 'debug', '[formatTime] ..minutes value is : ' . $minutes);
            $secondes = floor($secondes - $minutes * 60);
            log::add('bbox_sagemcom', 'debug', '[formatTime] new entry value is : ' . $secondes);
        }

        if ($days < 10) {
            $days = '0' . $days;
        }
        if ($hours < 10) {
            $hours = '0' . $hours;
        }
        if ($minutes < 10) {
            $minutes = '0' . $minutes;
        }

        return $days . " J " . $hours . " H " . $minutes . " M ";
    }

    // Function used to calculate Tx_Bytes and Rx_Bytes variations as the Jeedom Historic
    // cannot detect the BBox Overflow
    public function variation_calculation($var_name, $actual_value) {
        log::add('bbox_sagemcom', 'debug', '[variation_calculation] Function called');

        $var_last_value = $this->getConfiguration($var_name);
        log::add('bbox_sagemcom', 'debug', '[variation_calculation] Stored value was : ' . $var_last_value);
        log::add('bbox_sagemcom', 'debug', '[variation_calculation] New value is : ' . $actual_value);

        // At start-up, the last value is not yet set
        if (!is_numeric($var_last_value)) {
            log::add('bbox_sagemcom', 'debug', '[variation_calculation] Initialisation with the following value : ' . $actual_value);
            $var_last_value = $actual_value;
        }

        if ($var_last_value <= $actual_value) {
            log::add('bbox_sagemcom', 'debug', '[variation_calculation] Last value is inferior to the new one');
            $new_var = $actual_value - $var_last_value;
        } else {
            log::add('bbox_sagemcom', 'debug', '[variation_calculation] Last value is superior to the new one (BBox overflow)');
            // BBox overflow is 4294967295 (aka unsigned long int). After, the value is set to 0.
            $new_var = 4294967295 - $var_last_value + $actual_value;
        }

        log::add('bbox_sagemcom', 'debug', '[variation_calculation] Update the variation value with : ' . $new_var);
        $this->setConfiguration($var_name, $actual_value);
        $this->save();
        return $new_var;
    }

}

class bbox_sagemcomCmd extends cmd {

    public function execute($_options = array()) {
        log::add('bbox_sagemcom', 'debug', '[Execute] Function called from : ' . $this->getLogicalId());
        // Modify the post field according to the function to execute
        $useAPI = $this->getEqLogic()->getConfiguration('BBOX_USE_API');
        switch ($this->getLogicalId()) {
            case "reboot_box":
                log::add('bbox_sagemcom', 'debug', '[Execute] Reboot BBox');
                if ($useAPI == 'api') {
                    $post = 'device/reboot';
                    $result = $this->getEqLogic()->send_api_request($post);
                } else {
                    $result = $this->getEqLogic()->reboot();
                }
                break;
            case "lightOn":
                log::add('bbox_sagemcom', 'debug', '[Execute] Lumière On');
                $this->getEqLogic()->open_api_session();
                $serveur = trim($this->getEqLogic()->getConfiguration('BBOX_SERVER_IP'));
                $rurl = $serveur . '/api/v1/device/display';
                log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
                $http = curl_init();
                curl_setopt($http, CURLOPT_URL, $rurl);
                curl_setopt($http, CURLOPT_HEADER, false);
                curl_setopt($http, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($http, CURLOPT_POST, 1);
                curl_setopt($http, CURLOPT_POSTFIELDS, 'luminosity=100');
                curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
		curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($http);
                log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $result);
                curl_close($http);
                //$this->getEqLogic()->box_monitor_api();
                $result = $this->getEqLogic()->api_request('device');
                if ($result == false) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
                } else {
                    if($result[0]['device']['display']['luminosity'] == 0) {
                        $light = 0;
                    } else {
                        $light = 1;
                    }
                    $cmd= $this->getEqLogic()->getCmd('info','lightState');
                    $cmd->setCollectDate('');
                    $cmd->event($light);
                    $result = true;
                }
                break;
            case "lightOff":
                log::add('bbox_sagemcom', 'debug', '[Execute] Lumière Off');
                $this->getEqLogic()->open_api_session();
                $serveur = trim($this->getEqLogic()->getConfiguration('BBOX_SERVER_IP'));
                $rurl = $serveur . '/api/v1/device/display';
                log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
                $http = curl_init();
                curl_setopt($http, CURLOPT_URL, $rurl);
                curl_setopt($http, CURLOPT_HEADER, false);
                curl_setopt($http, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($http, CURLOPT_POST, 1);
                curl_setopt($http, CURLOPT_POSTFIELDS, 'luminosity=0');
                curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
		curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($http);
                log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $result);
                curl_close($http);
                //$this->getEqLogic()->box_monitor_api();
                $result = $this->getEqLogic()->api_request('device');
                if ($result == false) {
                    log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
                } else {
                    if($result[0]['device']['display']['luminosity'] == 0) {
                        $light = 0;
                    } else {
                        $light = 1;
                    }
                    $cmd= $this->getEqLogic()->getCmd('info','lightState');
                    $cmd->setCollectDate('');
                    $cmd->event($light);
                    $result = true;
                }
                break;
            case "wifi_start":
                log::add('bbox_sagemcom', 'debug', '[Execute] Start the Wifi');
                if ($useAPI == 'api') {
                    $serveur = trim($this->getEqLogic()->getConfiguration('BBOX_SERVER_IP'));
                    $post = 'wireless';
                    $rurl = $serveur . '/api/v1/' . $post;
                    log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
                    $http = curl_init();
                    curl_setopt($http, CURLOPT_URL, $rurl);
                    curl_setopt($http, CURLOPT_HEADER, false);
                    curl_setopt($http, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($http, CURLOPT_POST, 1);
                    curl_setopt($http, CURLOPT_POSTFIELDS, 'radio.enable=1');
                    curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
		    curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
		    curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($http);
                    log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $response);
                    curl_close($http);
                } else {
                    $post = '&write=WLANConfig_RadioEnable:1';
                    $result = $this->getEqLogic()->request_mechanism('write', $post);
                }
                break;
            case "wifi_stop":
                log::add('bbox_sagemcom', 'debug', '[Execute] Stop the Wifi');
                if ($useAPI == 'api') {
                    $serveur = trim($this->getEqLogic()->getConfiguration('BBOX_SERVER_IP'));
                    $post = 'wireless';
                    $rurl = $serveur . '/api/v1/' . $post;
                    log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
                    $http = curl_init();
                    curl_setopt($http, CURLOPT_URL, $rurl);
                    curl_setopt($http, CURLOPT_HEADER, false);
                    curl_setopt($http, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($http, CURLOPT_POST, 1);
                    curl_setopt($http, CURLOPT_POSTFIELDS, 'radio.enable=0');
                    curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
		    curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
		    curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($http);
                    log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $response);
                    curl_close($http);
                } else {
                    $post = '&write=WLANConfig_RadioEnable:0';
                    $result = $this->getEqLogic()->request_mechanism('write', $post);
                }
                break;
            case "phone1_ring":
                log::add('bbox_sagemcom', 'debug', '[Execute] ring the phone');
                if ($useAPI == 'api') {
                    $post = 'voip/ringtest/1';
                    $result = $this->getEqLogic()->send_api_request($post);
                } else {
                    $post = '&write=Diag_Services_VoIP_Ringing_Enable:1'
                            . '&write=Diag_Services_VoIP_Ringing_Timeout:20'
                            . '&write=Diag_Services_VoIP_Ringing_Method:1';
                    $result = $this->getEqLogic()->request_mechanism('write', $post);
                }
                break;
            case "phone1_unring":
                log::add('bbox_sagemcom', 'debug', '[Execute] unring the phone');
                if ($useAPI == 'api') {
                    $serveur = trim($this->getEqLogic()->getConfiguration('BBOX_SERVER_IP'));
                    $post = 'voip/ringtest/1';
                    $rurl = $serveur . '/api/v1/' . $post;
                    log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
                    $http = curl_init();
                    curl_setopt($http, CURLOPT_URL, $rurl);
                    curl_setopt($http, CURLOPT_HEADER, false);
                    curl_setopt($http, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
		    curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);
		    curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($http);
                    log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $response);
                    curl_close($http);
                } else {
                    $post = '&write=Diag_Services_VoIP_Ringing_Enable:0';
                    $result = $this->getEqLogic()->request_mechanism('write', $post);
                }
                break;
        }
        // Execute the function
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '[Execute] Write function failed');
            return false;
        } else {
            log::add('bbox_sagemcom', 'debug', '[Execute] Write function seems to have succeeded');
            return true;
        }
    }

}
