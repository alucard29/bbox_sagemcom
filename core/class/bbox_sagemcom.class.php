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
// v2.0.0       09/01/2021      Code refactored
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
     * Refresh all cmds 
     */
    public function refreshAll() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');      

        // wan connection detection
        $bboxDetected = $wanConnected = $wanAddress = false;
        $resultWan = $this->sendApiRequest('wan/ip');
        if(is_array($resultWan)) {
            $bboxDetected = true;
            $wanConnected = $resultWan[0]['wan']['ip']['state'] == 'Up' ? 1 : 0;
            $wanAddress = $resultWan[0]['wan']['ip']['address'];
        }

        // VoIP detection
        $voipEnabled = $voipLine = $phoneNb = false;
        $resultVoip = $this->sendApiRequest('voip');
        if(is_array($resultVoip)){
            $voipEnabled = $resultVoip[0]['voip'][0]['status'] == 'Up' ? 1 : 0;
            $voipLine = $resultVoip[0]['voip'][0]['id'];
            $phoneNb = preg_split("/@/", $resultVoip[0]['voip'][0]['uri'])[0];
        }

        // Call Log | added the 30/12/2015 | restriction : Apply only for 1 line (the fist)
        $calllogList = false;
        if($voipLine){
            $resultCallLog = $this->sendApiRequest('voip/fullcalllog/'.$voipLine);
            $calllog = $resultCallLog[0]['calllog'];
            if (is_array($calllog)) {
                foreach ($calllog as $value) {
                    $number = $value['number'];
                    if($value['type'] == 'in'){
                        $callType = $value['answered'] == 0 ? 'A' : 'R';
                    } else {
                        $callType = $value['answered'] == 0 ? 'U' : 'E';
                    }
                    $date = date('d-m-Y H:i:s', $value['date']);
                    $calllogList[] = [$callType, $number, $value['duree'], $date];
                }
            }
        }
        

        // Uptime calculation
        $uptime = $light = false;
        $resultDevice = $this->sendApiRequest('device');
        if(is_array($resultDevice)) {
            $uptime = $this->formatTime($resultDevice[0]['device']['uptime']);
            $light = $resultDevice[0]['device']['display']['luminosity'];
        }

        // Data Send/Received variation calculation
        $dataReceived = $dataSend = $varDataReceived = $varDataSend = false;
        $rateDown = $maxRateDown = $rateUp = $maxRateUp = false;
        $factor = $this->getConfiguration('BBOX_CONNEXION_TYPE') == 0 ? 1 : 1000;
        $resultIpStats = $this->sendApiRequest('wan/ip/stats');
        if(is_array($resultIpStats)) {
            $dataReceived = round(floatval($resultIpStats[0]['wan']['ip']['stats']['rx']['bytes']));
            $dataSend = round(floatval($resultIpStats[0]['wan']['ip']['stats']['tx']['bytes']));
            $varDataReceived = $this->variation_calculation('var_data_received', $dataReceived);
            $varDataSend = $this->variation_calculation('var_data_send', $dataSend);
            $rateDown = round(floatval($resultIpStats[0]['wan']['ip']['stats']['rx']['bandwidth']) * 1000);
            $maxRateDown = round(floatval($resultIpStats[0]['wan']['ip']['stats']['rx']['maxBandwidth']) * $factor);
            $rateUp = round(floatval($resultIpStats[0]['wan']['ip']['stats']['tx']['bandwidth']) * 1000);
            $maxRateUp = round(floatval($resultIpStats[0]['wan']['ip']['stats']['tx']['maxBandwidth']) * $factor);
        }

        // connected devices detection + TV detection (Adapted from Bouygues box code)
        $deviceDetected = $tvDetected = $devicesList = false;
        $hostsResult = $this->sendApiRequest('hosts');
        if(is_array($hostsResult)) {
            $hostList = $hostsResult[0]['hosts']['list'];
            if (is_array($hostList)) {
                $deviceDetected = 0;
                $tvDetected = 0;
                foreach ($hostList as $hostValue) {
                    if (isset($hostValue['active']) && $hostValue['active'] == 1) {
                        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Connected device ' . $hostValue['ipaddress'] . ' is active');
                        $IP = $hostValue['ipaddress'];
                        $devicesList[] = [$IP, $hostValue['hostname'], $hostValue['macaddress']];
                        $deviceDetected++;
                        if($hostValue['devicetype'] == "STB") $tvDetected = 1;
                    }
                }
            }
        }

        // wifi state detection and new TV detection method
        $wifiDetected = $receivedCalls = $messageWaiting = false;
        $SummaryResult = $this->sendApiRequest('summary');
        if(is_array($SummaryResult)){
            $wifiDetected = $SummaryResult[0]['wireless']['radio'];
            $receivedCalls = $SummaryResult[0]['voip'][0]['notanswered'];
            $messageWaiting = $SummaryResult[0]['voip'][0]['message'];
        }

        //  TV Detection
        $iptvResult = $this->sendApiRequest('iptv');
        $currentTvChannel = is_array($iptvResult) ? $iptvResult[0]['iptv'][0]['name'] : false;

        // Save results in an array using cmd ID as Key
        $retourbbox = array('box_state' => $bboxDetected,
            'wan_state' => $wanConnected,
            'wifi_state' => $wifiDetected,
            'tv_state' => $tvDetected,
            'voip_state' => $voipEnabled,
            'public_ip' => $wanAddress,
            'phone_nb' => $phoneNb,
            'uptime' => $uptime,
            'rate_down' => $rateDown,
            'max_rate_down' => $maxRateDown,
            'rate_up' => $rateUp,
            'max_rate_up' => $maxRateUp,
            'data_received' => $dataReceived,
            'data_send' => $dataSend,
            'var_data_received' => $varDataReceived,
            'var_data_send' => $varDataSend,
            'received_calls' => $receivedCalls,
            'message_waiting' => $messageWaiting,
            'connected_devices' => $deviceDetected,
            'devices_List' => json_encode($devicesList),
            'calllog' => json_encode($calllogList),
            'lightState' => $light,
            'currentTvChannel' => $currentTvChannel,
        );

        // Save Info cmds using the array Key
        //foreach (eqLogic::byType('bbox_sagemcom') as $eqLogic){
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Start save process');
        foreach ($this->getCmd('info') as $cmd) {
            $cmd_id = $cmd->getLogicalId();
            $checkAndUpdateCmdResult = $this->checkAndUpdateCmd($cmd_id,$retourbbox[$cmd_id]);

            if($checkAndUpdateCmdResult) {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Process for : '.$cmd_id.' ended. command has been updated');
            } else {
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Process for : '.$cmd_id.' ended. command hasn\'t been updated');
            }

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
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $maxStored = $cmd->getConfiguration('maxValue');
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called for : '.$cmdId.' Max stored value was : '.$maxStored.' when Max response value is : '.$reguestValue);
        if ($maxStored != $reguestValue) {
            $cmd->setConfiguration('maxValue', $reguestValue);
            $cmd->save();
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
        return $new_var;
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

    /**
     * Function used to extend the current session
     * 
     * @return mixed http response or false if failed
     */
    public function extendSession() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        return $this->sendCurlRequest('/api/v1/login',false,'PUT');
    }

    /**
     * Function used to request a new cookie
     * 
     * @return mixed http response or false if failed
     */
    public function createSession() {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $password = $this->getConfiguration('BBOX_PSSWD');
        return $this->sendCurlRequest('/api/v1/login',true,null,'password='.$password);
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
     * Get token from BBox
     * 
     * @return string the Token
     */
    public function getToken()
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $response = $this->sendCurlRequest('/api/v1/device/token',false);
        return $response[0]['device']['token'];
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
        return $this->sendApiRequest($function.'?btoken='.$this->getToken(),false,null,$postField);
    }

    /**
     * Method called to send a request to the BBox API
     * 
     * @param string $api The API address part of the URL
     * @param bool $header The CURLOPT_HEADER option
     * @param string $method The CURLOPT_CUSTOMREQUEST option
     * @param string $postfield The CURLOPT_POSTFIELDS option
     * @return mixed Curl execution response or false if failed
     */
    public function sendApiRequest(string $api,bool $header = false, string $method = null,string $postfield = null)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called for : '.$api);
        $result = $this->sendCurlRequest('/api/v1/'.$api,$header,$method ,$postfield);
        if($result !== false){
            if(is_array($result)){
                $http_code = $result[0];
                log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Response HTTP Code is :'.$http_code);
                switch ($http_code) {
                    case 200:  # OK
                    case 302:  # Redirected is OK too
                        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] response is : '.$result[1]);
                        return json_decode($result[1], true);
                        break;
                    case 401: # Unauthorized
                        $this->createSession();
                        $result2 = $this->sendCurlRequest('/api/v1/'.$api,$header,$method ,$postfield);
                        return is_array($result2) ? json_decode($result2[1], true) : false;
                        break;
                    default:
                        log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] http receive code isn\'t 200 but : '.$http_code);
                        log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] message is : '.$result[1]);
                        return false;
                }
            } else {
                // unexpected error
                log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] Unexpected error. Result is not an array');
                log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] Result is :'.$result);
                throw new Exception('Unexpected error in '.__FUNCTION__);
                return false;
            }
        }
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Error, result is null or empty');
        return $result;
    }

    /**
     * Send a Curl request
     * 
     * @param string $api The API address part of the URL
     * @param bool $header The CURLOPT_HEADER option
     * @param string $method The CURLOPT_CUSTOMREQUEST option
     * @param string $postfield The CURLOPT_POSTFIELDS option
     * @return mixed Array of HTTP code and Curl execution response or false if failed
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
            curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);
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
            $errorNb = curl_errno($http);
            $http_code = curl_getinfo($http, CURLINFO_HTTP_CODE);
            curl_close($http);

            // Http state verify
            if (!$errorNb) 
            {
                return [$http_code,$result];
            }
            else
            {
                log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] http failed. error code is : '.$errorNb.' for url :'.$url);
                return false;
            }
        } 
        else 
        {
            log::add('bbox_sagemcom', 'error', '['.__FUNCTION__.'] fail to find bbox addresss');
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
        $this->extendSession();
        $postField = 'luminosity='.$value;
        return $this->sendApiRequest('device/display',false,'PUT',$postField);
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
        $result = $this->sendApiRequest('device');
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
     * @return mixed return of sendApiRequest function
     */
    public function setWifiTo(bool $value)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called');
        $this->extendSession();
        $postField = $value === true ? 'radio.enable=1' : 'radio.enable=0';
        return $this->sendApiRequest('wireless',false,'PUT',$postField);
    }

    /**
     * Ring test all phone lines
     * 
     * @param int $enable 1: Start the test. 0: End the test.
     * @return mixed return of sendApiRequest function
     */
    public function setPhonesTest(int $testState)
    {
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called with param : '.$testState);
        $this->extendSession();
        return $this->sendApiRequest('voip/ringtest',false,"PUT",'enable='.$testState);
    }
}

class bbox_sagemcomCmd extends cmd {

    public function execute($_options = array()) {
        $bbox = $this->getEqLogic();
        $cmd = $this->getLogicalId();
        log::add('bbox_sagemcom', 'debug', '['.__FUNCTION__.'] Function called for : '.$cmd.' on : '.$bbox->getHumanName());
        
        switch ($cmd) {
            case "refresh":
                $result = $bbox->refreshAll();
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
                $result = $bbox->setPhonesTest(1);
                break;
            case "phone1_unring":
                $result = $bbox->setPhonesTest(0);
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
