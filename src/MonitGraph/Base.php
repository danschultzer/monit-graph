<?php
/**
 * Monit Graph
 *
 * Copyright (c) 2011, Dan Schultzer <http://abcel-online.com/>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Dan Schultzer nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL DAN SCHULTZER BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package monit-graph
 * @author Dan Schultzer <http://abcel-online.com/>
 * @copyright Dan Schultzer
 */

namespace MonitGraph;

/**
 * Monit Graph base class
 *
*/
class Base
{

    /**
     * Version of MonitGraph
    */
    const VERSION = '2.0';

    /**
     * Identifier of MonitGraph
    */
    const IDENTIFIER = 'MonitGraph';

    /**
     * Path to data directory
    */
    const DATA_PATH = __DIR__ . '/../../data';

    /**
     * Path to data directory
    */
    const SERVER_XML_FILE_NAME = 'server.xml';

    /**
     * Configs
    */
    public static function config()
    {
        if (getenv("CONFIG_FILE")) {
            $config = require(getenv("CONFIG_FILE"));
        } elseif (file_exists(__DIR__ . "/../../config/config.php")) {
            $config = require(__DIR__ . "/../../config/config.php");
        } else {
            $config = require(__DIR__ . "/../../config/config.default.php");
        }

        return $config;
    }

        /**
     * Get data directory
    */
    public static function dataDir()
    {
        if (isset(self::config()['data_dir'])) {
            return self::config()['data_dir'] . "/";
        }

        return self::DATA_PATH . "/";
    }

    /**
     * Testing the server configs
    */
    public static function checkConfig($server_configs)
    {
        $id = [];
        $url = [];
        foreach ($server_configs as $config) {
            $id[] = $config['server_id'];
            $url[] = $config['config']['url'];
        }
        if (count($id) != count(array_unique($id))) {
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": ID's in server config needs to be unique");
            return false;
        }
        if (count($url) != count(array_unique($url))) {
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": You should not use the same URL for individual servers");
            return false;
        }
        return true;
    }

    /**
     * Running cron
     *
     * Will connect and download data from the monit URL.
    */
    public static function cron(
        $server_id,
        $monit_url,
        $monit_uri_xml,
        $monit_url_ssl = true,
        $monit_http_username = "",
        $monit_http_password = "",
        $verify_ssl = true,
        $chunk_size = 0,
        $number_of_chunks = 0
    ) {

        $found_settings = $ssl_on = $http_login = false;

        if (!($server_id = self::isServerIDValid($server_id))) {
            exit("Server ID invalid");
        }

        $xml=self::getSettings($server_id);
        if ($xml!=false) {
            $found_settings = true;
        } // Do we already have the settings file saved or is this fresh version?
        if ($monit_url_ssl) {
            $ssl_on = true;
        } // Setting ssl true
        if (strlen($monit_http_username)>0) {
            $http_login = true;
        } // If username are used, http login has to be done

        if ($found_settings) {
            $time_difference = intval($xml->incarnation)+intval($xml->uptime)+intval($xml->poll)-20-time(); // 20 seconds connection time
            if ($time_difference>0) {
                error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": Poll time has not been reached (missing $time_difference seconds), waiting for one more cycle");
                return false;
            }
        }

        $headers = [];
        $headers[] = 'Accept: application/xml';
        $user_agent = 'Cron Monit Graph '.(self::VERSION);

        if ($monit_url_ssl) {
            $url = "https://";
        } else {
            $url = "http://";
        }

        $url .= $monit_url . "/" . $monit_uri_xml;
        $ch = curl_init($url);

        if ($monit_url_ssl) {
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            if ($verify_ssl) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // We want a return

        if ($http_login) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC) ;
            curl_setopt($ch, CURLOPT_USERPWD, $monit_http_username . ":" . $monit_http_password) ;
        }

        $data = curl_exec($ch);

        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);

        curl_close($ch);

        if ($curl_errno > 0) {
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": cURL Error ($curl_errno): $curl_error");
        } else {
            libxml_use_internal_errors(true);
            if ($xml = simplexml_load_string($data)) {
                if (isset($xml->server)) {
                    if (self::putSettings($server_id, $xml->server)) {
                        foreach ($xml->service as $service) {
                            self::writeServiceHistoric($server_id, $service, $service["type"], $chunk_size, $number_of_chunks);
                        }
                    }
                }
            } else {
                foreach (libxml_get_errors() as $error) {
                    error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": " . $error->message);
                }
            }
        }
    }

    /**
     * Return false or the simplexml object depending if the settings file exists
    */
    public static function getSettings($server_id)
    {
        if (!self::settingsWriteable($server_id)) {
            exit("Cannot write settings");
        }
        $filename = self::dataDir() . $server_id . "-" . self::SERVER_XML_FILE_NAME;
        if (file_exists($filename)) {
            return simplexml_load_string(file_get_contents($filename));
        }
        return false;
    }

    /**
     * Save the settings file from simplexml object to DOM
    */
    public static function putSettings($server_id, $xml)
    {
        if (!self::settingsWriteable($server_id)) {
            exit("Cannot write settings");
        }
        $filename = self::dataDir() . $server_id . "-" . self::SERVER_XML_FILE_NAME;
        if (!$handle=fopen($filename, 'w')) {
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": Cannot open $filename");
            exit("Cannot open $filename");
        }
        $dom_xml = dom_import_simplexml($xml);

        $dom = new \DOMDocument('1.0');
        $dom_xml = $dom->importNode($dom_xml, true);
        $dom_xml = $dom->appendChild($dom_xml);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (fwrite($handle, $dom->saveXML()) === false) {
            fclose($handle);
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": Cannot write to $filename");
            exit("Cannot write to $filename");
        }
        fclose($handle);

        return true;
    }

    /**
     * Return true or false if the data path is writeable
    */
    public static function datapathWriteable()
    {
        if (!is_writeable(self::dataDir())) {
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": " . self::dataDir() . " is not write-able!");
            return false;
        }
        return true;
    }

    /**
     * Return true or false if the settings file is writeable
    */
    public static function settingsWriteable($server_id)
    {
        if (!self::datapathWriteable()) {
            return false;
        }
        $filename = self::dataDir() . $server_id . "-" . self::SERVER_XML_FILE_NAME;
        if (file_exists($filename) && !is_writeable($filename)) {
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": " . $filename . " is not write-able!");
            return false;
        }
        return true;
    }

    /**
     * Will write the XML history file for a specific service. Inputs simplexml object and service type
    */
    public static function writeServiceHistoric($server_id, $xml, $type, $chunk_size = 0, $number_of_chunks = 0)
    {
        if ($type=="3" || $type=="5" || $type=="7") { // Only services and programs
            $name = $xml->name;
            if (!self::datapathWriteable()) {
                exit("Cannot write in data path");
            }

            $dom = new \DOMDocument('1.0');
            $service = $dom->createElement("records");
            $attr_name=$dom->createAttribute("name");
            $attr_name->value = $name;
            $service->appendChild($attr_name);
            $dom->appendChild($service);

            $attr_type=$dom->createAttribute("type");
            $attr_type->value = $type;
            $service->appendChild($attr_type);

            $new_service = $dom->createElement("record");
            $time=$dom->createAttribute("time");
            $time->value = $xml->collected_sec;
            $new_service->appendChild($time);

            if ($type=="5") { // System
                $memory = $dom->createElement("memory", $xml->system->memory->percent);
                $new_service->appendChild($memory);

                $cpu_user = (float) $xml->system->cpu->user;
                $cpu_system = (float) $xml->system->cpu->system;
                $cpu_wait = (float) $xml->system->cpu->wait;
                $total_cpu = $cpu_user + $cpu_system + $cpu_wait;
                $cpu = $dom->createElement("cpu", $total_cpu);
                $new_service->appendChild($cpu);

                $swap = $dom->createElement("swap", $xml->system->swap->percent);
                $new_service->appendChild($swap);
            } elseif ($type == "7") { // Program

                $program_status = $dom->createElement("program_status", $xml->program->status);
                $new_service->appendChild($program_status);

                // Uncomment those lines to get program output logged into XML,
                // but keep in mind that it's not yet supported in front-end.
                //  $program_output = $dom->createElement( "program_output", $xml->program->output );
                //  $new_service->appendChild($program_output);
            } else { // Process
                $memory = $dom->createElement("memory", self::getMonitPercentage($xml->memory));
                $new_service->appendChild($memory);

                $cpu = $dom->createElement("cpu", self::getMonitPercentage($xml->cpu));
                $new_service->appendChild($cpu);

                $pid = $dom->createElement("pid", $xml->pid);
                $new_service->appendChild($pid);

                $uptime = $dom->createElement("uptime", $xml->uptime);
                $new_service->appendChild($uptime);

                $children = $dom->createElement("children", $xml->children);
                $new_service->appendChild($children);
            }

            $status = $dom->createElement("status", $xml->status);
            $new_service->appendChild($status);

            $alert = $dom->createElement("alert", intVal($xml->status>0));
            $new_service->appendChild($alert);

            $monitor = $dom->createElement("monitor", $xml->monitor);
            $new_service->appendChild($monitor);

            $service->appendChild($new_service);

            $dom->validate();
            $dir = self::dataDir() . $server_id;
            if (!is_dir($dir)) {
                if (!mkdir($dir)) {
                    exit("Could not create data path $dir");
                }
            }

            $filename = $dir . "/" . $name . ".xml";
            if (file_exists($filename)) {
                if (!self::rotateFiles($filename, $chunk_size, $number_of_chunks)) {
                    exit("Fatal error, could not rotate file $filename");
                }

                /* Load in the previous xml */
                if (file_exists($filename) && $existing_xml=simplexml_load_string(file_get_contents($filename))) {
                    $dom_xml = dom_import_simplexml($existing_xml);
                    foreach ($dom_xml->childNodes as $node) {
                        $node = $dom->importNode($node, true);
                        $node = $service->appendChild($node);
                    }
                }
            }
            if (!$handle=fopen($filename, 'w')) {
                error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": Cannot open $filename");
                exit("Cannot open $filename");
            }

            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;
            if (fwrite($handle, $dom->saveXML()) === false) {
                fclose($handle);
                error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": Cannot write to $filename");
                exit("Cannot write to $filename");
            }
            fclose($handle);
        }
        return true;
    }

    /**
     * Will return a Google Graph JSON string or false
    */
    public static function returnGoogleGraphJSON($filename, $time_range, $limit_number_of_items = 0)
    {
        if (!file_exists($filename) or !$xml=simplexml_load_string(file_get_contents($filename))) {
            error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": $filename could not be found!");
            return false;
        }

        $array = [];
        if ($xml["type"]=="5") {
            $array["cols"]=[
                            ["label"=>"Time","type"=>"datetime"],
                            ["label"=>"CPU Usage","type"=>"number"],
                            ["label"=>"Memory Usage","type"=>"number"],
                            ["label"=>"Swap","type"=>"number"],
                            ["label"=>"Alerts","type"=>"number"]
                        ];
        } elseif ($xml["type"]=="7") {
            $array["cols"]=[
                            ["label"=>"Time","type"=>"datetime"],
                            ["label"=>"Status","type"=>"number"],
                            ["label"=>"Alerts","type"=>"number"]
                        ];
        } else {
            $array["cols"]=[
                            ["label"=>"Time","type"=>"datetime"],
                            ["label"=>"CPU Usage","type"=>"number"],
                            ["label"=>"Memory Usage","type"=>"number"],
                            ["label"=>"Alerts","type"=>"number"]
                        ];
        }

        $array["rows"]=[];

        $include_file_number = 0;
        $allowed_memory = self::letToNum(ini_get('memory_limit'));
        $run_time = self::letToNum(ini_get('memory_limit'));
        $run_while = true;

        while ($run_while) {
            /* We run through each record to built the JSON */
            foreach ($xml->record as $record) {
                if ($time_range>0 && $record['time']<(time()-$time_range)) {
                    break;
                } // Stop with data if we have reached the time range

                /* Different setup for different service types */
                if ($xml["type"]=="5") {
                    $array["rows"][]["c"]=[
                            ["v"=>"%%new Date(" . (intVal($record['time']) * 1000) . ")%%"],
                            ["v"=>(float)$record->cpu],
                            ["v"=>(float)$record->memory],
                            ["v"=>(float)$record->swap],
                            ["v"=>(float)$record->alert*100]
                        ];
                } elseif ($xml["type"]=="7") {
                    $array["rows"][]["c"]=[
                            ["v"=>"%%new Date(" . (intVal($record['time']) * 1000) . ")%%"],
                            ["v"=>(float)$record->program_status],
                            ["v"=>(float)$record->alert*100]
                        ];
                } else {
                    $array["rows"][]["c"]=[
                            ["v"=>"%%new Date(" . (intVal($record['time']) * 1000) . ")%%"],
                            ["v"=>(float)$record->cpu],
                            ["v"=>(float)$record->memory],
                            ["v"=>(float)$record->alert*100]
                        ];
                }

                /* Just checking if we reach memory limit and stop when that happens */
                if ((memory_get_usage()/$allowed_memory)>0.9) {
                    error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": Memory usage is using over 90% (of $allowed_memory) with currently " . count($array["rows"]) . " rows (last record with date of " . date("Y-m-d H:i:s P", intVal($record['time'])) . "). Please increase allowed memory use if you wish parse more data.");
                    $run_while = false;
                    break;
                }
            }

            /* We check if the next file exists, and load the simplexml object if so */
            $next_file = $filename . "." . (string)$include_file_number;
            if (file_exists($next_file)) {
                $xml = null;
                unset($xml);
                if (!$xml=simplexml_load_string(file_get_contents($next_file))) {
                    error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": " . $next_file . " could not be opened");
                    break;
                }
            } else {
                break; // No other files, this is all the data we can get
            }
            $include_file_number++;
        }

        /* Reversing the array, so oldest historic is first */
        $array["rows"] = array_reverse($array["rows"]);

        /* We don't want to pass too much information, let's keep it under the limit */
        $number_of_items = count($array["rows"]);
        if ($limit_number_of_items>0 && $number_of_items>$limit_number_of_items) {
            $exponent = ceil(log($limit_number_of_items/$number_of_items)/log(0.5)); // Calculating how many iterations we should do until we are below the maximum number of items
            for ($i=0; $i<$exponent; $i++) {
                foreach (range(1, count($array["rows"]), 2) as $key) { // Go through every second element and delete it
                    unset($array["rows"][$key]);
                }
                $array["rows"] = array_merge($array["rows"]); // Redo index
            }
        }

        /* JSON encode and enable javascript function */
        $json = json_encode($array);

        return $json;
    }

    /**
     * Will return a Google Graph JSON string or false
    */
    public static function getLastRecord($server_id)
    {
        $files = self::getLogFilesForServerID($server_id);
        if (!$files) {
            return false;
        }

        /* Check the directory for the Monit instance ID */
        $return_array = [];
        foreach ($files as $file) {
            if (!file_exists($file) or !$xml=simplexml_load_string(file_get_contents($file))) {
                error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": $filename could not be loaded!");
                return false;
            }
            $return_array[]=[
                                "name"=>$xml['name'],
                                "time"=>intVal($xml->record[0]['time']),
                                "memory"=>$xml->record[0]->memory,
                                "cpu"=>$xml->record[0]->cpu,
                                "swap"=>@$xml->record[0]->swap,
                                "status"=>$xml->record[0]->status];
        }
        return $return_array;
    }


    /**
     * Function to return XML of the server id
    */
    public static function getInformationServerID($server_id)
    {
        /* First retrieve the server configuration */
        $server_file = self::dataDir() . $server_id . "-server.xml";
        if (!file_exists($server_file) or !$server_xml=simplexml_load_string(file_get_contents($server_file))) {
            error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": $server_file could not be loaded!");
            return false;
        }
        return $server_xml;
    }

    /**
     * Function to return list of log files for the server id and optional for a specific service
    */
    public static function getLogFilesForServerID($server_id, $specific_services = "")
    {
        /* Check the directory for the Monit instance ID */
        $files = [];
        foreach (glob(self::dataDir() . $server_id . "/" . $specific_services . "*.xml") as $file) {
            $files[] = $file;
        }
        return $files;
    }

    /**
     * Function to delete all datafiles bound to a server id and optionally to a filename
    */
    public static function deleteDataFiles($server_id, $xml_file_name = false)
    {
        if (strlen($xml_file_name)<0) {
            // We are deleting everything to this server id

            $dirname = self::dataDir() . $server_id . "/";

            // First everything in the data directory
            foreach (glob($dirname."*") as $file) {
                if (!unlink($file)) {
                    error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": Could not delete $file");
                    return false;
                }
            }

            // Now the data directory itself
            if (!unlink($dirname)) {
                error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": Could not delete $dirname");
                return false;
            }

            // Now the server file
            $server_file = self::dataDir() . $server_id . "-server.xml";
            if (!unlink($server_file)) {
                error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": Could not delete $server_file");
                return false;
            }
        } else {
            // Only delete specific data file
            foreach (glob(self::dataDir() . $server_id . "/" . $xml_file_name . "*") as $file) {
                if (!unlink($file)) {
                    error_log("[" . self::IDENTIFIER . "] " . __FILE__ . " line " . __LINE__ . ": Could not delete $file");
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * A file rotator function, will rotate specific filename depending on size and limitation
    */
    public static function rotateFiles($filename, $chunk_size, $limit_of_chunks)
    {
        /* Chunk rotating */
        if (intVal($chunk_size) > 0) {
            if (file_exists($filename) && filesize($filename) > $chunk_size) { // If file size are larger than allowed chunk size, rotate it
                $files = glob($filename.".*");
                usort($files, ["MonitGraph\Base","sortRotatedFilesLastFirst"]);
                for ($i=0; $i<count($files); $i++) {
                    $number = str_replace($filename.".", "", $files[$i]); // Get the actual number of the file
                    $number = intVal($number)+1; // The new number to be used
                    if ($limit_of_chunks==0 || $number<$limit_of_chunks) { // Rotate chunk
                        if (!rename($files[$i], $filename.".".$number)) {
                            error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": ".$files[$i]." could not be rename to ".$filename.".".$number."");
                            return false;
                        }
                    } else { // If this chunk will be too many for defined, delete it
                        if (!unlink($files[$i])) {
                            error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": could not unlink ".$files[$i]."");
                            return false;
                        }
                    }
                }
                // Finally rename the current head
                if (!rename($filename, $filename.".0")) {
                    error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": ".$filename." could not be rename to ".$filename.".0");
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Sorting option for files, used for rotating files
    */
    public static function sortRotatedFilesLastFirst($file1, $file2)
    {
        $number1 = intVal(substr(strrchr($file1, "."), 1));
        $number2 = intVal(substr(strrchr($file2, "."), 1));
        if ($number1 == $number2) {
            return 0;
        }
        return ($number1 < $number2) ? 1 : -1;
    }

    /**
     * A function to convert php.ini notation for numbers to integer (e.g. '25M')
    */
    public static function letToNum($v)
    {
        $l = substr($v, -1);
        $ret = substr($v, 0, -1);
        switch (strtoupper($l)) {
            case 'P':
                $ret *= 1024;
                # We'll continue to multiply
            case 'T':
                $ret *= 1024;
                # We'll continue to multiply
            case 'G':
                $ret *= 1024;
                # We'll continue to multiply
            case 'M':
                $ret *= 1024;
                # We'll continue to multiply
            case 'K':
                $ret *= 1024;
                break;
        }
        return $ret;
    }

    /**
     * Return true/false if server id is valid
    */
    public static function isServerIDValid($server_id)
    {
        if (strlen($server_id)>0 && intval($server_id)) {
            return intVal($server_id);
        }
        error_log("[".self::IDENTIFIER."] ".__FILE__." line ".__LINE__.": Server ID is not valid $server_id!");
        return false;
    }

    /**
     * Return the real percentage usage
    */
    public static function getMonitPercentage($xml)
    {
        if (isset($xml->percenttotal)) {
            return $xml->percenttotal;
        } else {
            return $xml->percent;
        }
    }
}
