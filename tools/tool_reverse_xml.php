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

 	/* This tool can reverse specified XML documents, if needed. */


	$current_dirname = dirname(__FILE__)."/";
	$file_name="../data/logs/*.xml"; // Filepath string
	$overwrite=false; // Should the files be overwritten, or create a .new file?

	$files = glob($current_dirname.$file_name);
	foreach($files as $file){
		if($xml = simplexml_load_string(file_get_contents($file))){
			if(!$overwrite) $file.=".new";

			$name = $xml["name"];
			$type = $xml["type"];

			$dom = new DOMDocument('1.0');
			$service = $dom->createElement("records");
			$dom->appendChild($service);

			$attr_name=$dom->createAttribute("name");
			$attr_name->value = $name;
			$service->appendChild($attr_name);

			$attr_type=$dom->createAttribute("type");
			$attr_type->value = $type;
			$service->appendChild($attr_type);

			for($i=count($xml->record)-1;$i>0;$i--){
				$record = $xml->record[$i];
				$new_service = $dom->createElement("record");

				$time=$dom->createAttribute("time");
				$time->value = $record["time"];
				$new_service->appendChild($time);

				if($type=="5"){
					$memory = $dom->createElement("memory",$record->memory);
					$new_service->appendChild($memory);

					$cpu = $dom->createElement("cpu",$record->cpu);
					$new_service->appendChild($cpu);

					$swap = $dom->createElement("swap",$record->swap);
					$new_service->appendChild($swap);
				}else{
					$memory = $dom->createElement("memory",$record->memory);
					$new_service->appendChild($memory);

					$cpu = $dom->createElement("cpu",$record->cpu);
					$new_service->appendChild($cpu);

					$pid = $dom->createElement("pid",$record->pid);
					$new_service->appendChild($pid);

					$uptime = $dom->createElement("uptime",$record->uptime);
					$new_service->appendChild($uptime);

					$children = $dom->createElement("children",$record->children);
					$new_service->appendChild($children);
				}

				$status = $dom->createElement("status",$record->status);
				$new_service->appendChild($status);

				$alert = $dom->createElement("alert",$record->alert);
				$new_service->appendChild($alert);

				$monitor = $dom->createElement("monitor",$record->monitor);
				$new_service->appendChild($monitor);

				$service->appendChild($new_service);
			}

			$dom->validate();
			if(!$handle=fopen($file, 'w')){
				exit("Cannot open $filename");
			}

			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = false;
			if (fwrite($handle, $dom->saveXML()) === FALSE) {
				fclose($handle);
				exit("Cannot write to $filename");
			}
			fclose($handle);
		}
	}
?>