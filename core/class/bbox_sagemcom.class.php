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
// v2.0.0       TBC             Code refactored
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

include_file('core', 'bbox_sagemcom', 'config', 'bbox_sagemcom');

class bbox_sagemcom extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    /**
     *  Function called by Jeedom every minute
     */
    public static function cron() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');

        // execute the monitoring for each enabled bbox
        foreach (self::byType('bbox_sagemcom') as $bbox) {

            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Working on : '.$bbox->getHumanName());
            if ($bbox->getIsEnable() == 1) {

                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Device is enabled');
                //retourne la commande "refresh si elle existe
                $cmd = $bbox->getCmd(null, 'refresh'); 

                // execute if command exist
                if (is_object($cmd)) {
                    $cmd->execCmd();
                }
            }
        }
    } 

    /*     * *********************Méthodes d'instance************************* */

    /**
     * Jeedom Method called after object is updated
     */
    public function postUpdate() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');

        // Test if the custom widget shall be used
        $custom = $this->getConfiguration('BBOX_CUSTOM_WIDGET', 0);
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Custom variable is equal to : ' . $custom);

        // Parse the command list and add commands that don't exist if ok
        // according to the selected mode
        global $listCmdBbox_sagemcom;
        foreach ($listCmdBbox_sagemcom as $cmd) {

            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Start process for : ' . $cmd['name']);
            $currentCmd = $this->getCmd(null, $cmd['logicalId']);

            // If the command doesn't exist and is present in this mode: create it
            // Note that according to Jeedom Documentation, when using checkAndUpdateCmd and template.js,
            // Jeedom make a diff and will delete commands in base but not in the equipment. So apparently,
            // there is no need to sanitize with a cmd remove here.
            if (!is_object($currentCmd)) {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] ID ' . $cmd['logicalId'] . ' doesn\'t exist so create it');
                $this->addNewBBoxCmd($cmd);
            }

            // If the command exists and shall be custumed, do it
            if (isset($currentCmd)){
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] currentCmd exist so test if is object and custom is requested');
                if ((is_object($currentCmd))&&($custom == 1)) {
                    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Apply configuration');
                    $this->configureBBoxCmd($cmd);
                    // Clear the custom widget parameter
                    $this->setConfiguration('BBOX_CUSTOM_WIDGET', 0);
                }
            }
        }
    }

    /**
     * Jeedom Method called after object is saved
     */
    public function postSave() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        // Test if the custom widget shall be used
        $custom = $this->getConfiguration('BBOX_CUSTOM_WIDGET', 0);
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Custom variable is equal to : ' . $custom);
        if ($custom == 1) {
            global $listCmdBbox_sagemcom;
            foreach ($listCmdBbox_sagemcom as $cmd) {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Start process for : ' . $cmd['name']);
                $currentCmd = $this->getCmd(null, $cmd['logicalId']);
                // If the command exists and shall be custumed, do it
                if (isset($currentCmd)){
                    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] currentCmd exist so test if is object');
                    if ((is_object($currentCmd))) {
                        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Apply configuration');
                        $this->configureBBoxCmd($cmd);
                        // Clear the custom widget parameter
                        $this->setConfiguration('BBOX_CUSTOM_WIDGET', 0);
                    }
                }
            }
        }
    }

    /**
     * Add a new Jeedom Command
     * 
     * @param array $cmd command parameters
     */
    public function addNewBBoxCmd($cmd) {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called for command : ' . $cmd['name']);
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

    /**
     * Configure the Jeedom Command
     * 
     * @param array $cmd command parameters
     */
    public function configureBBoxCmd($cmd) {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called for command : ' . $cmd['name']);
        if ($cmd) {
            $configureCmd = $this->getCmd(null, $cmd['logicalId']);
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] work on : ' . $cmd['logicalId']);

            $configureCmd->setOrder($cmd['configuration']['order']);
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set order to : ' . $cmd['configuration']['order']);

            if ($configureCmd->getName()!= $cmd['name']) {
                $configureCmd->setName($cmd['name']);
            }

            if (array_key_exists('template', $cmd['configuration'])) {
                $configureCmd->setTemplate('dashboard', $cmd['configuration']['template']);
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set template to : ' . $cmd['configuration']['template']);
            }

            if (array_key_exists('visible', $cmd['configuration'])) {
                $configureCmd->setIsVisible($cmd['configuration']['visible']);
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set visible to : ' . $cmd['configuration']['visible']);
            }

            if (array_key_exists('value', $cmd['configuration'])) {
                $cmdUsedForValue = $this->getCmd(null, $cmd['configuration']['value']);
                if (!is_object($cmdUsedForValue)) {
                    log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] The command that should be used as value seems to not exist');
                } else {
                    $configureCmd->setValue($cmdUsedForValue->getId());
                    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set value using : ' . $cmd['configuration']['value']);
                }
            }

            if (array_key_exists('returnAfter', $cmd['configuration'])) {
                $configureCmd->setDisplay('forceReturnLineAfter', $cmd['configuration']['returnAfter']);
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set returnAfter to : ' . $cmd['configuration']['returnAfter']);
            }

            if (array_key_exists('returnBefore', $cmd['configuration'])) {
                $configureCmd->setDisplay('forceReturnLineBefore', $cmd['configuration']['returnBefore']);
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set returnBefore to : ' . $cmd['configuration']['returnBefore']);
            }

            if (array_key_exists('minValue', $cmd['configuration'])) {
                $configureCmd->setConfiguration('minValue', $cmd['configuration']['minValue']);
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set minValue to : ' . $cmd['configuration']['minValue']);
            }

            if (array_key_exists('unite', $cmd)) {
                $configureCmd->setUnite($cmd['unite']);
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Set unit to ' . $cmd['unite']);
            }

            $configureCmd->save();
        }
    }

    /**
     * TBC
     */
    public function box_monitor_api() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');      

        // wan connection detection
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Try to find a Wan connection');
        $bbox_detection = true;
        $wan_connected = 0;
        $wan = "";
        $result = $this->api_request('wan/ip');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            if ($result[0]['wan']['ip']['state'] == 'Up') {
                $wan_connected = 1;
                $wan = $result[0]['wan']['ip']['address'];
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Find a Wan connection : ' . $wan);
            }
        }

        // VoIP detection
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] VoIP detection');
        $voip_enabled = 0;
        $voip_line = 0;
        $phone_nb = '';
        $result = $this->api_request('voip');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            if (array_key_exists('exception', $result)) {
                $re_connect = $this->refreshToken();
                $result = $this->api_request('voip');
            }
            if (isset($result[0]['voip'][0]['status']) && ($result[0]['voip'][0]['status'] == 'Up')) {
                $voip_enabled = 1;
                $voip_line = $result[0]['voip'][0]['id'];
                $chaine = preg_split("/@/", $result[0]['voip'][0]['uri']);
                $phone_nb = $chaine[0];
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Found an active VoIP service');
            }
        }

        // Call Log | added the 30/12/2015 | restriction : Apply only for 1 line (the fist)
        // First, refresh data
        $re_connect = $this->refreshToken();
        if ($re_connect == true) {
            $result = $this->refresh_bbox('callLog');
            $this->waitBoxReady(120);
            if ($result == false) {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
                $bbox_detection = false;
            } else {
                // FIXME: $result is true here, not an array and what's the point to reconnect etc?

                // if (array_key_exists('exception', $result)) {
                //     $re_connect = $this->refreshToken();
                //     $result = $this->refresh_bbox('callLog');
                //     $this->waitBoxReady(120);
                // }
            }
 
            // Second, collect data
            $result = $this->api_request('voip/fullcalllog/'.$voip_line);
            if ($result == false) {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
                $bbox_detection = false;
            } else {
                $calllog = $result[0]['calllog'];
                if (is_array($calllog)) {
                    foreach ($calllog as $host_key => $host_value) {
                        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] found a call in log with number : ' . $host_value['number']);
                      //  if ($host_value['direction']=='E'){
                            $number = $host_value['number'];
                       // } else {
                       //     $number = $host_value['name'];
                       // }
						if($host_value['type'] == 'in')
						{
							if($host_value['answered'] == 0)
							{
								$callType = 'A';
							} else {
								$callType = 'R';
							}
						} else {
							if($host_value['answered'] == 0)
							{
								$callType = 'U';
							} else {
								$callType = 'E';
							}
						}
					   
						$date = date('d-m-Y H:i:s', $host_value['date']);
						log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] the call date is : '.$date);
                        $calllog_List[] = [$callType, $number, $host_value['duree'], $date];
                    }
                } else {
                    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] detect calllog entry is not a array');
                }
            }
        }

         // Message Log | added the 21/1/2016 | restriction : Apply only for 1 line (the fist)
        $result = $this->refresh_bbox('get_voicemail');
        $this->waitBoxReady(120);
        $result = $this->api_request('voip/voicemail');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $messagelog = $result[0]['voicemail'];
            if (is_array($messagelog)) {
                foreach ($messagelog as $host_key => $host_value) {
                    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] found a message in log with caller number : ' . $host_value['callernumber']);
                    $messagelog_List[] = [$host_value['readstatus'], $host_value['callernumber'], $host_value['duration'], $host_value['dateconsult'], $host_value['linkmsg'], strval($host_value['id'])];
                }
            } else {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] detect messagelog entry is not a array');
            }
        }

        // Uptime calculation
        $result = $this->api_request('device');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $uptime = $this->formatTime($result[0]['device']['uptime']);
            if($result[0]['device']['display']['luminosity'] == 0) {
                $light = 0;
            } else {
                $light = 1;
            }
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Result of formatTime is : ' . $uptime);
        }

        // Data Send/Received variation calculation
        $result = $this->api_request('wan/ip/stats');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $data_received = round(floatval($result[0]['wan']['ip']['stats']['rx']['bytes']));
            $data_send = round(floatval($result[0]['wan']['ip']['stats']['tx']['bytes']));
            $var_data_received = $this->variation_calculation('var_data_received', $data_received);
            $var_data_send = $this->variation_calculation('var_data_send', $data_send);
        }

        // Bandwidth calculation (depends on the selected mode)
        $connexion = $this->getConfiguration('BBOX_CONNEXION_TYPE');
        if ($connexion == 0) {
            $type = "cable";
        } else {
            $type = "xdsl";
        }
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Selected connexion type is : ' . $type);


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
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            $bbox_detection = false;
        } else {
            $device_parameters = $result[0]['hosts']['list'];
            if (is_array($device_parameters)) {
                foreach ($device_parameters as $host_key => $host_value) {
                    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Start devices detection with Key : ' . $host_key);
                    if (isset($host_value['active']) && $host_value['active'] == 1) {
                        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Connected device ' . $host_value['ipaddress'] . ' is active');
                        $IP = $host_value['ipaddress'];
                        $devices_List[] = [$IP, $host_value['hostname'], $host_value['macaddress']];
                        $device_detected++;
                        if ($host_value['devicetype'] == "STB") {
                            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Connected media device is a STB device (i.e. TV)');
                            $tv_detected = 1;
                        }
                    }
                }
            } else {
                log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] detect connected devices entry is not a array');
            }
        }

        // TV Channel detection  (old method)
        $currentTvChannel = "";
        //$tvChannelInformation = "";
        //$result = $this->api_request('iptv');
        //if ($result == false) {
        //    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
        //} else {
        //    $iptv = $result[0]['iptv'];
        //    if (is_array($iptv)) {
        //        foreach ($iptv as $host_key => $host_value) {
        //            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Start devices detection with Key : ' . $host_key);
        //            if (isset($host_value['name']) && $host_value['name'] != "") {
        //                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Current channel is : '. $host_value['name']);
        //                $currentTvChannel = $host_value['name'];
        //            }
        //        }
        //    } else {
        //        log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] detect iptv entry is not a array');
        //    }
        //}

        // wifi state detection and new TV detection method
        $wifi_detected = 0;
        $result = $this->api_request('summary');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
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
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Process for : '.$cmd_id.' started' );

            $checkAndUpdateCmdResult = $this->checkAndUpdateCmd($cmd_id,$retourbbox[$cmd_id]);

            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Process for : '.$cmd_id.' ended with '.$checkAndUpdateCmdResult ? 'Success' : 'Failure');

            // Update the rate down max value if needed
            if ($cmd_id == 'rate_down') $this->updateMaxRate($cmd, $cmd_id, floatval($retourbbox['max_rate_down']));
              
            // Update the rate up max value if needed
            if ($cmd_id == 'rate_up') $this->updateMaxRate($cmd, $cmd_id, floatval($retourbbox['max_rate_up']));
        }
        return true;
    }

    /**
     * Update cache max value of the command if needed
     * 
     * @param object $cmd the Command
     * @param string $cmd_id the Command Logical Id
     */
    public function updateMaxRate(object $cmd, string $cmdId, float $reguestValue)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called for : '.$cmdId.' Max stored value was : '.$maxStored.' when Max response value is : '.$reguestValue);
        $maxStored = $cmd->getConfiguration('maxValue');
        if ($maxStored != $reguestValue) {
            $cmd->setConfiguration('maxValue', $reguestValue);
            $cmd->save();
        }
    }

    /**
     * Get the debian version running on.
     * 
     * @param   void 
     * @return  string the debian version or null 
     */
    public function getDebianVersion()
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $return = shell_exec('lsb_release -sr');
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Found version : '.$return);
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
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $canLowerSslSecurity = $this->getCache('canLowerSslSecurity');
        if(empty($canLowerSslSecurity)){
            $version = $this->getDebianVersion();

            // Set a default value if not a debian or something else
            isset($version) ? $version = intval($version) : $version = 0;
            $return = $version > 9 ? true : false ;

            // The SSL Security Strategy cannot be lowered for Debian version less than 10
            $this->setCache('canLowerSslSecurity',$return);
            $canLowerSslSecurity = $return;
        }
        return $canLowerSslSecurity;
    }

    public function api_request($type) {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');

        $response = $this->sendCurlRequest('/api/v1/'.$type,false);

        $decoded_response = json_decode($response, true);

        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Decoded response is : ' . $response);

        // Test if the BBox has returned an error (or no JSON response)
        if ((json_last_error() != 0) || array_key_exists('error', $decoded_response)) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Error or bad JSON respond from the BBox.');
            return false;
        } else {
            return $decoded_response;
        }
    }

    // Function used to request a new cookie
    public function refreshToken() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $password = $this->getConfiguration('BBOX_PSSWD');
        $result = $this->sendCurlRequest('/api/v1/login',true,null,'password='.$password);

        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] response is : ' . $result);
        $decodedResult = json_decode($result, true);
        if (is_array($decodedResult)){
            if (array_key_exists('exception', $decodedResult)) {
                log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] Le mot de passe utilisé pour la BBox est ou était erroné. Il est nécessaire de redémarrer manuellement la Box');
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function refresh_bbox($action) {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $result = $this->sendCurlRequest('/api/v1/profile/refresh',false,'PUT','action='.$action);
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] response is : ' . $result);
        return true;
    }

    public function waitBoxReady($timeout) {
        $bboxBusy = false;
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $startTime = time();
        $result = $this->api_request('profile/consumption');
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Consumption response is : ' . $result);
        while ($result[0]['profile']['state'] != 0){
            if(time() > $startTime + $timeout) {
                $bboxBusy = true;
                break;
            }
            $result = $this->api_request('profile/consumption');
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Consumption response is : ' . $result);
        }
        if ($bboxBusy == true){
            return false;
        } else {
            return true;
        }
    }

    public function refreshMessageWaiting() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $result = $this->api_request('summary');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            return false;
        } else {
            $message_waiting = $result[0]['voip'][0]['message'];
            $cmd= $this->getCmd('info','message_waiting');
            $cmd_id= $cmd->getLogicalId();
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Save a new value for ID : '.$cmd_id);
            $cmd->setCollectDate('');
            $cmd->event($message_waiting);
            return true;
        }
    }

    public function refreshMessageLog() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $result = $this->api_request('voip/voicemail');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
            return false;
        } else {
            $messagelog = $result[0]['voicemail'];
            if (is_array($messagelog)) {
                foreach ($messagelog as $host_key => $host_value) {
                    log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] found a message in log with caller number : ' . $host_value['callernumber']);
                    $messagelog_List[] = [$host_value['readstatus'], $host_value['callernumber'], $host_value['duration'], $host_value['dateconsult'], $host_value['linkmsg'], strval($host_value['id'])];
                }
                $cmd= $this->getCmd('info','messagelog');
                $cmd->setCollectDate('');
                $cmd->event(json_encode($messagelog_List));
            } else {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] detect messagelog entry is not a array');
            }
            return true;
        }
    }

    // Function to format the uptime from box to a human format string
    // Only adapted from the BBox F@ast calculation function
    function formatTime($uptime) {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');

        $days = 0;
        $hours = 0;
        $minutes = 0;
        $secondes = $uptime;
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] entry value is : ' . $secondes);

        if ($secondes > 86400) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] entry is > 86400 so..');
            $days = floor($secondes / 86400);
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] ..days value is : ' . $days);
            $secondes = $secondes - $days * 86400;
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] new entry value is : ' . $secondes);
        }
        if ($secondes > 3600) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] entry is > 3600 so..');
            $hours = floor($secondes / 3600);
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] ..hours value is : ' . $hours);
            $secondes = $secondes - $hours * 3600;
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] new entry value is : ' . $secondes);
        }
        if ($secondes > 60) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] entry is > 60 so..');
            $minutes = floor($secondes / 60);
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] ..minutes value is : ' . $minutes);
            $secondes = floor($secondes - $minutes * 60);
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] new entry value is : ' . $secondes);
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
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');

        $var_last_value = $this->getCache($var_name);
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Stored value was : '.$var_last_value.' when the new one is : '. $actual_value);

        // At start-up, the last value is not yet set
        if (!is_numeric($var_last_value)) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Initialisation with the following value : ' . $actual_value);
            $var_last_value = $actual_value;
        }

        if ($var_last_value <= $actual_value) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Last value is inferior to the new one');
            $new_var = $actual_value - $var_last_value;
        } else {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Last value is superior to the new one (BBox overflow)');
            // BBox overflow is 4294967295 (aka unsigned long int). After, the value is set to 0.
            $new_var = 4294967295 - $var_last_value + $actual_value;
        }

        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Update the variation value with : ' . $new_var);
        $this->setCache($var_name, $actual_value);
        //$this->save();
        return $new_var;
    }

    /**
     * Retrieve the configured bbox IP Address
     * 
     * @return mixed false if failed or the serverIP
     */
    public function getBboxIp()
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $server = $this->getConfiguration('BBOX_SERVER_IP');
        if ($server == '') { // Cannot send request without an URL
            throw new Exception('Adresse de la BBox non-renseignée');
            return false;
        }
        return trim($server);
    }

    /**
     * Check the BBox Response
     * 
     * @param string $response Curl response
     * @return bool true if the response is the expected one false else
     */
    public function responseCheck(string $response)
    {
        //Todo
        return true;
    }

    /**
     * Get token from BBox
     * 
     * @return string the Token
     */
    public function getToken()
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $response = $this->sendCurlRequest('/api/v1/device/token',false);
        $decoded_response = json_decode($response, true);
        return $decoded_response[0]['device']['token'];
    }

    /**
     * Send a request after asking for the Token
     * 
     * @param string $function The CURLOPT_URL part option
     * @param string $postfield The CURLOPT_POSTFIELDS option
     * @return string Curl execution response
     */
    public function sendRequestWithToken(string $function, string $postField = null) {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $response = $this->sendCurlRequest('/api/v1/'.$function.'?btoken='.$this->getToken(),false,null,$postField);
        return $this->responseCheck($response);
    }

    /**
     * Send a Curl request
     * 
     * @param string $api The API address part of the URL
     * @param bool $header The CURLOPT_HEADER option
     * @param string $method The CURLOPT_CUSTOMREQUEST option
     * @param string $postfield The CURLOPT_POSTFIELDS option
     * @return mixed Curl execution response or false if failed
     */
    public function sendCurlRequest(string $api,bool $header, string $method = null,string $postfield = null)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');

        $serverIp = $this->getBboxIp();
        if($serverIp !== false) {
            $url = $serverIp.$api;

            $http = curl_init();
            curl_setopt($http, CURLOPT_URL, $url);
            curl_setopt($http, CURLOPT_HEADER, $header);
            curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
            if(!is_null($method)) curl_setopt($http, CURLOPT_CUSTOMREQUEST, $method);
            if(!is_null($postfield))
            {
                curl_setopt($http, CURLOPT_POST, true);
                curl_setopt($http, CURLOPT_POSTFIELDS, $postfield);
            }
            if($this->canLowerSslSecurity()) curl_setopt($http, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
            curl_setopt($http, CURLOPT_COOKIEJAR, "/tmp/cookies.txt");
            curl_setopt($http, CURLOPT_COOKIEFILE, "/tmp/cookies.txt");
            
            $result = curl_exec($http);
    
            foreach(curl_getinfo($http) as $key => $value) {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Curl info : '.$key.' is  : '.$value ); 
            }
            
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Response is : ' . $result);
            curl_close($http);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Set the Bbox lights to the value specified by $value
     * 
     * @param string $value : '100' to set On '0' else
     * @return mixed Curl execution response or false if failed
     */
    public function setBboxLightsTo(string $value)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $this->refreshToken();
        $postField = 'luminosity='.$value;
        $response = $this->sendCurlRequest('/api/v1/device/display',false,'PUT',$postField);
        return $this->responseCheck($response);
    }

    /**
     * Get the Bbox front panel lights status
     * 
     * @return bool $lightStatus = true if On, false if Off 
     *                  and null if error occured
     */
    public function getBboxLightsStatus()
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $result = $this->api_request('device');
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox not detected or bad response');
        } else {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] BBox response is : '.$result);
            return $result[0]['device']['display']['luminosity'] == 0 ?  false : true;
        }
    }

    /**
     * Set the Bbox wifi to the value specified by $value
     * 
     * @param bool $value : true to set On false else
     */
    public function setWifiTo(bool $value)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $this->refreshToken();
        $postField = $value === true ? 'radio.enable=1' : 'radio.enable=0';
        $response = $this->sendCurlRequest('/api/v1/wireless',false,'PUT',$postField);
        return $this->responseCheck($response);
    }

    /**
     * Unring the specified phone line
     * 
     * @param bool $value : true to set On false else
     */
    public function setPhoneUnRing(int $phoneNumber)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $this->refreshToken();
        $response = $this->sendCurlRequest('/api/v1/voip/ringtest/'.$phoneNumber,false,"DELETE");
        return $this->responseCheck($response);
    }
}

class bbox_sagemcomCmd extends cmd {

    public function execute($_options = array()) {
        $bbox = $this->getEqLogic();
        $cmd = $this->getLogicalId();
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called for : '.$cmd.' on : '.$bbox->getHumanName());
        
        switch ($cmd) {
            case "refresh":
                $result = $bbox->box_monitor_api();
                break;

            case "reboot_box":
                $function = 'device/reboot';
                $result = $bbox->sendRequestWithToken($function);
                break;

            case "lightOn":
                $result = $bbox->setBboxLightsTo('100');
                $lightStatus = $bbox->getBboxLightsStatus();
                if(!is_null($lightStatus)) $bbox->checkAndUpdateCmd('lightState', $lightStatus);
                break;

            case "lightOff":
                $result = $bbox->setBboxLightsTo('0');
                $lightStatus = $bbox->getBboxLightsStatus();
                if(!is_null($lightStatus)) $bbox->checkAndUpdateCmd('lightState', $lightStatus);
                break;

            case "wifi_start":
                $result = $bbox->setWifiTo(true);
                break;

            case "wifi_stop":
                $result = $bbox->setWifiTo(false);
                break;

            case "phone1_ring":
                $function = 'voip/ringtest/1';
                $result = $bbox->sendRequestWithToken($function);
                break;
            case "phone1_unring":
                $result = $bbox->setPhoneUnRing(1);
                break;
        }
        // Execute the function
        if ($result == false) {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Write function failed');
            return false;
        } else {
            log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Write function seems to have succeeded');
            return true;
        }
    }

}
