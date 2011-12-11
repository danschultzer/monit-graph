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
 
 	/* This tool can build massive data files for test.
 		It will comply with the rotation rules set in config.
 		It is a bit heavy with I/O but it is to get exact filesize */

	function writeDom($dom, $file_name){
		@$dom->validate();
		if(!$handle=fopen(dirname(__FILE__)."/".$file_name, 'w')){
			exit("Cannot open $filename");
		}

		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		if (fwrite($handle, $dom->saveXML()) === FALSE) {
			fclose($handle);
			exit("Cannot write to $file_name");
		}
	}

	$current_dirname = dirname(__FILE__)."/";
	require_once($current_dirname."../config.php");
	require_once($current_dirname."../monit-graph.class.php");

	$file_name="../data/logs/massive_data_points.xml";
	$overwrite=true;
	$number_of_data_points = 25000;
	$seconds_difference_per_point = 60; // How many seconds between each point
	$data_time = time()-$number_of_data_points*$seconds_difference_per_point;
	$data_memory = $data_cpu = $data_pid = $data_uptime = $data_children = $data_status = $data_alert = 0;
	$data_monitor = $data_cpu = 1;
	$data_memory = 5;
	$file_size = -1;
	$file_size_total = 0;

	$name = "massive_data_points";
	$type = "3";

	for($i=0;$i<$number_of_data_points;$i++){
		if($file_size==-1 || ($chunk_size>0 && $file_size>$chunk_size)){
			if(isset($dom)){
				$file = $current_dirname.$file_name;
				if(file_exists($file)) MonitGraph::rotateFiles($file,$chunk_size,$limit_number_of_chunks);
				echo "<p>Writting out $file_size bytes and reached $i points out of $number_of_data_points</p>";
				$file_size_total+=$file_size;
				writeDom($dom, $file_name);
				usleep(100); //relaxing the cpu
			}
			$file_size = 0;
			$dom = null;
			unset($dom);

			$dom = new DOMDocument('1.0');
			$service = $dom->createElement("records");
			$dom->appendChild($service);
	
			$attr_name=$dom->createAttribute("name");
			$attr_name->value = $name;
			$service->appendChild($attr_name);
	
			$attr_type=$dom->createAttribute("type");
			$attr_type->value = $type;
			$service->appendChild($attr_type);
		}
		$data_time += $seconds_difference_per_point;

		$data_memory += rand(-2,2)/10;
		if($data_memory>=100 || $data_memory<=0) $data_memory = 0;

		$data_cpu += rand(-1,1)/10;
		if($data_cpu>=100 || $data_cpu<=0) $data_cpu = 0;

		$data_swap = 0;

		$new_service = $dom->createElement("record");

		$time=$dom->createAttribute("time");
		$time->value = $data_time;
		$new_service->appendChild($time);

		$memory = $dom->createElement("memory",intVal($data_memory));
		$new_service->appendChild($memory);

		$cpu = $dom->createElement("cpu",$data_cpu);
		$new_service->appendChild($cpu);

		$pid = $dom->createElement("pid",$data_pid);
		$new_service->appendChild($pid);

		$uptime = $dom->createElement("uptime",$data_uptime);
		$new_service->appendChild($uptime);

		$children = $dom->createElement("children",$data_children);
		$new_service->appendChild($children);

		$status = $dom->createElement("status",$data_status);
		$new_service->appendChild($status);

		$alert = $dom->createElement("alert",$data_alert);
		$new_service->appendChild($alert);

		$monitor = $dom->createElement("monitor",$data_monitor);
		$new_service->appendChild($monitor);

		$service->appendChild($new_service);
	
		$file_size=$dom->save("/tmp/tmp.xml"); // Let's check the file size
	}

	writeDom($dom, $file_name);

	echo "<p>".number_format($number_of_data_points)." data points written</p>";
	echo "<p>Memory peak: ".number_format(memory_get_peak_usage()/1024, 0, ".", ",")." kb</p>";
	echo "<p>File size: ".number_format($file_size_total/1024, 0, ".", ",")." kb</p>";

?>