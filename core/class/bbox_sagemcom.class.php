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
//                              corrected http:// added at the
//                              beginning of the URL
//						        Specific reboot function added
//						        Write function return corrected
// v0.0.3 		28/06/2015		All postUpdate parameters moved
//                              in "if" statement
//						        xDSL/Cable or Cable function
//						        added
// v0.1.0 		30/06/2015		Bandwidth informations are now
//                              computed from Layer3 Service
//                              only (more precise)
// v0.1.1 		01/07/2015		Bandwidth results unit changed
//                              to bps as in Kbps the value may
//                              not be updated for a while
// v0.1.2		03/07/2015		uptime added
//						        pre-configured layout added
// v1.0.1 		10/09/2015		robusness improved
// v1.1.0 		17/09/2015		New API used
// v1.1.1		25/10/2015		BBox detection command added
// v1.2.0		28/11/2015		use a configuration file
// v1.2.1       30/12/2015      add calllog command
// v1.2.2		12/08/2018		Remove "trim" function on
//						        password save
// v1.2.3		07/11/208		remove http to allow the use of https
// v1.2.4		09/06/2019		New TC channel detection method
// v1.3.0 		05/12/2020		ssl corrected for Jeedom v4
// v1.3.1 		12/12/2020		Calllog corrected
// v1.3.2       21/12/2020      AES256-SHA added to cypher list
// v1.3.3       22/12/2020      set CURLOPT_USE_SSL to CURLUSESSL_TRY
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

include_file('core', 'bbox_sagemcom', 'config', 'bbox_sagemcom');

class bbox_sagemcom extends eqLogic {
    /*     * *************************Attributs****************************** */

    protected $version;

    /*     * ***********************Methode static*************************** */

    // Function called by Jeedom every minute
    public static function cron() {
        log::add('bbox_sagemcom', 'debug', '[cron] Function called');

        // execute the monitoring for each bbox (RFU)
        foreach (bbox_sagemcom::byType('bbox_sagemcom') as $eqLogic) {
            if ($eqLogic->getIsEnable() == 1) {
                    $eqLogic->box_monitor_api();
            }
        }
    }

    /*     * *********************Méthodes d'instance************************* */

    /**
     * Method called after object is updated
     */
    public function postUpdate() {
        log::add('bbox_sagemcom', 'debug', '[postUpdate] Function called');

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
            // Note that according to Jeedom Documentation, when using checkAndUpdateCmd and template.js,
            // Jeedom make a diff and will delete commands in base but not in the equipment. So apparently,
            // there is no need to sanitize with a cmd remove here.
            if (!is_object($currentCmd)) {
                log::add('bbox_sagemcom', 'debug', '[postUpdate] ID ' . $cmd['logicalId'] . ' doesn\'t exist so create it');
                $this->addNewBBoxCmd($cmd);
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

    public function box_monitor_api() {
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Function called');

        // get the debian version
        $this->version = $this->getDebianVersion();
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] System version name is : '.$this->version);

        $connexion = $this->getConfiguration('BBOX_CONNEXION_TYPE');
        if ($connexion == 0) {
            $type = "cable";
        } else {
            $type = "xdsl";
        }
        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] Selected connexion type is : ' . $type);
        
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
            $result = $this->api_request('voip/fullcalllog/'.$voip_line);
            if ($result == false) {
                log::add('bbox_sagemcom', 'debug', '[box_monitor_api] BBox not detected or bad response');
                $bbox_detection = false;
            } else {
                $calllog = $result[0]['calllog'];
                if (is_array($calllog)) {
                    foreach ($calllog as $host_key => $host_value) {
                        log::add('bbox_sagemcom', 'debug', '[box_monitor_api] found a call in log with number : ' . $host_value['number']);
                      //  if ($host_value['direction']=='E'){
                            $number = $host_value['number'];
                       // } else {
                       //     $number = $host_value['name'];
                       // }
						if($host_value['type'] == 'in')
						{
							if($host_value['answered'] == 0)
							{
								$type = 'A';
							} else {
								$type = 'R';
							}
						} else {
							if($host_value['answered'] == 0)
							{
								$type = 'U';
							} else {
								$type = 'E';
							}
						}
					   
						$date = date('d-m-Y H:i:s', $host_value['date']);
						log::add('bbox_sagemcom', 'debug', '[box_monitor_api] the call date is : '.$date);
                        $calllog_List[] = [$type, $number, $host_value['duree'], $date];
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

    /**
     * Get the debian version running on.
     * 
     * @param   void 
     * @return  string the debian version or null 
     */
    public function getDebianVersion()
    {
        log::add('bbox_sagemcom', 'debug', '[getDebianVersion] Function called');
        $return = shell_exec('lsb_release -sr');
        log::add('bbox_sagemcom', 'debug', '[getDebianVersion] Found version : '.$return);
        return $return;
    }

    /**
     * Define Curl SSL Security Strategy
     * 
     * @param   void 
     * @return  bool true if we can lower the SSL Security Strategy else false
     */
    public function canLowerSslSecurity()
    {
        log::add('bbox_sagemcom', 'debug', '[canLowerSslSecurity] Function called');
        $this->version = $this->getDebianVersion();

        // Set a default value if not a debian or something else
        isset($this->version) ? $this->version = intval($this->version) : $this->version = 0;

        $return = $this->version > 9 ? true : false ;
        log::add('bbox_sagemcom', 'debug', '[canLowerSslSecurity] result is : '.$return);

        // The SSL Security Strategy cannot be lowered for Debian version less than 10
        return $return;
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
        if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
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
        if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($http, CURLOPT_COOKIEJAR, "/tmp/cookies.txt");
        curl_setopt($http, CURLOPT_POST, 1);
        curl_setopt($http, CURLOPT_POSTFIELDS, 'password=' . $password);
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
        if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
        curl_setopt($http, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($http, CURLOPT_POSTFIELDS, 'action=' . $action);
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
        if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
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
        if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
        $response = curl_exec($http);
        log::add('bbox_sagemcom', 'debug', '[api_request] Response is : ' . $response);
        curl_close($http);

        $decoded_response = json_decode($response, true);

        log::add('bbox_sagemcom', 'debug', '[api_request] response from BBox is : ' . $response);
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

        /**
     * Get the debian version running on.
     * 
     * @param   void 
     * @return  string the debian version or null 
     */
    public function getDebianVersion()
    {
        return shell_exec('lsb_release -sr');
    }

    /**
     * Define Curl SSL Security Strategy
     * 
     * @param   void 
     * @return  bool true if we can lower the SSL Security Strategy else false
     */
    public function canLowerSslSecurity()
    {
        $this->version = $this->getDebianVersion();

        // Set a default value if not a debian or something else
        isset($this->version) ? $this->version = intval($this->version) : $this->version = 0;

        // The SSL Security Strategy cannot be lowered for Debian version less than 10
        return $this->version > 9 ? true : false ;
    }

    public function execute($_options = array()) {
        log::add('bbox_sagemcom', 'debug', '[Execute] Function called from : ' . $this->getLogicalId());
        switch ($this->getLogicalId()) {
            case "reboot_box":
                log::add('bbox_sagemcom', 'debug', '[Execute] Reboot BBox');
                $post = 'device/reboot';
                $result = $this->getEqLogic()->send_api_request($post);
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
                if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
                curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
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
                if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
                curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
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
                if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
                curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
                $response = curl_exec($http);
                log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $response);
                curl_close($http);
                break;
            case "wifi_stop":
                log::add('bbox_sagemcom', 'debug', '[Execute] Stop the Wifi');
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
                if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
                curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
                $response = curl_exec($http);
                log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $response);
                curl_close($http);
                break;
            case "phone1_ring":
                log::add('bbox_sagemcom', 'debug', '[Execute] ring the phone');
                $post = 'voip/ringtest/1';
                $result = $this->getEqLogic()->send_api_request($post);
                break;
            case "phone1_unring":
                log::add('bbox_sagemcom', 'debug', '[Execute] unring the phone');
                $serveur = trim($this->getEqLogic()->getConfiguration('BBOX_SERVER_IP'));
                $post = 'voip/ringtest/1';
                $rurl = $serveur . '/api/v1/' . $post;
                log::add('bbox_sagemcom', 'debug', '[send_api_request] Send request to : ' . $rurl);
                $http = curl_init();
                curl_setopt($http, CURLOPT_URL, $rurl);
                curl_setopt($http, CURLOPT_HEADER, false);
                curl_setopt($http, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
                if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
                curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
                $response = curl_exec($http);
                log::add('bbox_sagemcom', 'debug', '[execute] Response is : ' . $response);
                curl_close($http);
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
