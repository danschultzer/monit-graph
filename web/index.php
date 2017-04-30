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
	$include_path = realpath(dirname(__FILE__));
 	require_once($include_path."/config/config.php");
	require_once("monit-graph.class.php");

	if(!MonitGraph::checkConfig($server_configs)) die("Fatal error. Check the error log please."); // If configs are not good we quit

	ob_start(); // Starting buffering

	$output_head = ""; // Output for header

	/* Show individuel server stats */
	if(isset($_GET['server_id']) && strlen($_GET['server_id'])>0){

		/* Variables */
		$output_body = "";
		$_SELECED = array();


		/* Chart Type */
		if(isset($_GET['chart_type'])) $chart_type = $_GET['chart_type'];
		else $chart_type = $default_chart_type;

		if($chart_type=='AnnotatedTimeLine') $package = "annotatedtimeline";
		elseif($chart_type=='Gauge') $package = "gauge";
		else $package = "corechart";
		$_SELECTED[$chart_type]=' selected="selected"';
		$output_head .= '
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load("visualization", "1.1", {packages: ["'.$package.'"]});
		</script>';


		/* Time Range */
		if(isset($_GET['time_range'])){
			$time_range = intVal($_GET['time_range']);
			$_SELECTED[$time_range]=' selected="selected"';
		}else{
			$time_range = $default_time_range;
			$_SELECTED[$time_range]=' selected="selected"';
		}


		/* Refresh data time */
		if(isset($_GET['refresh_seconds'])) $refresh_seconds = intVal($_GET['refresh_seconds']);
		else $refresh_seconds = $default_refresh_seconds;
		$refresh_miliseconds = intVal($refresh_seconds)*1000;


		/* Specific services */
		if(isset($_GET['specific_services'])) $specific_services = (string)$_GET['specific_services'];
		else $specific_services = $default_specific_service;


		/* If to show alerts */
		if(isset($_GET['dont_show_alerts']) && $_GET['dont_show_alerts']=="on"){
			$dont_show_alerts = "on";
		}elseif(!isset($_GET['chart_type'])){
			$dont_show_alerts = $default_dont_show_alerts;
		}
		if($dont_show_alerts=="on"){
			$_SELECTED['dont_show_alerts']=' checked="checked"';
		}


		/* Iterate all json files in data directory */
		$i = 0;
		$files = MonitGraph::getLogFilesForServerID($_GET['server_id'],$specific_services);
		foreach($server_configs as $config){
			if($config['server_id']==$_GET['server_id']){
				echo "<h1>".$config['name']." service logs</h1>";
				break;
			}
		}
		echo '<a href="?">Back to dashboard</a>';
		foreach($files as $file){
			$filename = basename($file);

			/* The javascript has some logic to parse the JSON, and to keep overhead down */
			$output_head .= <<<EOF
		<script type="text/javascript">
			var data$i = null;
			var chart$i = null;

			function drawVisualization$i() {
				$.ajax({
									type: "GET",
									url: "getdata.php",
									data: {
										"file": "../$file",
										"time_range": "$time_range"
									},
									async: true,
									cache: false,
								}).done(function(data){
									var evalledData = eval("("+data+")");
									if (typeof evalledData != "object") return;
									if('$chart_type'=='Gauge'){
										evalledData.rows.splice(1,evalledData.rows.length);
									}
									if("on"=="$dont_show_alerts"){
										for( i = 0; i < evalledData["cols"].length; i++ ) {
											if ( evalledData["cols"][i]["label"] == "Alerts" ) {
												for( j = 0; j < evalledData.rows.length; j++ ) {
													evalledData.rows[j].c[i].v = null;
												}
											}
										}
									}

									if(data$i==null){
										data$i = new google.visualization.DataTable(evalledData);
									}else{
										data$i.removeRows(0,data$i.getNumberOfRows());
										for(i = 0; i < evalledData.rows.length; i++){
											data$i.addRow(evalledData.rows[i].c);
										}
									}
									delete evalledData;

									if(chart$i==null) chart$i = new google.visualization.$chart_type(document.getElementById('chart_div$i'));
									chart$i.draw(data$i, {
															title : '$filename',
															vAxis: {title: "Usage in %", minValue: 0},
															hAxis: {title: "Time"}
														});
									if($refresh_seconds>0){
										var timeout = setTimeout("drawVisualization$i()",$refresh_miliseconds);
									}
								});
			}
			google.setOnLoadCallback(drawVisualization$i);
		</script>
EOF;
			$output_body .= <<<EOF
		<div class="bordered_box">
			<h2><a href="#" onclick="javascript:$('#chart_div$i').toggle('fast');return false;">$filename</a></h2>
			<div id="chart_div$i" style="width: 800px; height: 400px; margin:20px;">
				Loading Chart...
			</div>
		</div>
EOF;
			$i++;
		}

		echo '
	<div class="form_box bordered_box">
		<h2><a href="#" onclick="javascript:$(\'#view_form\').toggle(\'fast\');return false;">Change View</a></h2>
		<form action="?" method="get" id="view_form">
		<input type="hidden" name="server_id" value="'.$_GET['server_id'].'" />
		<div class="input_box">
			<label><strong>Chart type:</strong>
				<select name="chart_type">
					<option value="LineChart"'.@$_SELECTED['LineChart'].'>Line Chart</option>
					<option value="AreaChart"'.@$_SELECTED['AreaChart'].'>Area Chart</option>
					<option value="Gauge"'.@$_SELECTED['Gauge'].'>Gauge</option>
					<option value="AnnotatedTimeLine"'.@$_SELECTED['AnnotatedTimeLine'].'>Annotated Time Line</option>
				</select>
			</label>
		</div>
		<div class="input_box"><label><strong>Update Frequency:</strong> <input type="text" name="refresh_seconds" value="'.$refresh_seconds.'" size="4" /></label> seconds (don\'t go lower than update cycle of the monit instance)</div>
		<div class="input_box"><label><strong>Specific Service:</strong> <input type="text" name="specific_services" value="'.$specific_services.'" size="10" /></label></div>
		<div class="input_box">
			<label><strong>Time Range:</strong>
			<select name="time_range">
				<option value="300"'.@$_SELECTED['300'].'>Last 5 minutes</option>
				<option value="1200"'.@$_SELECTED['1200'].'>Last 20 minutes</option>
				<option value="3600"'.@$_SELECTED['3600'].'>Last 1 hour</option>
				<option value="7200"'.@$_SELECTED['7200'].'>Last 2 hours</option>
				<option value="43200"'.@$_SELECTED['43200'].'>Last 12 hours</option>
				<option value="86400"'.@$_SELECTED['86400'].'>Last 24 hours</option>
				<option value="172800"'.@$_SELECTED['172800'].'>Last 2 days</option>
				<option value="604800"'.@$_SELECTED['604800'].'>Last 7 days</option>
				<option value="2419200"'.@$_SELECTED['2419200'].'>Last 30 days</option>
				<option value="4838400"'.@$_SELECTED['4838400'].'>Last 60 days</option>
				<option value="7257600"'.@$_SELECTED['7257600'].'>Last 120 days</option>
				<option value="31536000"'.@$_SELECTED['31536000'].'>Last 365 days</option>
				<option value="0"'.@$_SELECTED['0'].'>All</option>
			</select>
			</label>
		</div>
		<div class="input_box">
			<label><strong>Hide Alerts:</strong> <input type="checkbox" name="dont_show_alerts" value="on"'.@$_SELECTED['dont_show_alerts'].'></label>
		</div>
		<div class="submit_box">
			<input type="submit" class="submit" value="Update View">
		</div>
		</form>
		<div class="clear"></div>
	</div>
	'.$output_body.'';


	/* Show dashboard */
	}elseif(isset($_GET['delete_data']) && strlen($_GET['delete_data'])>0){
		echo '<a href="?">Back to dashboard</a>';
		echo '
	<div class="server_box bordered_box">';
		$name = "";
		foreach($server_configs as $config){
			if($config['server_id']==$_GET['id']){
				$name = $config['name'];
				break;
			}
		}
		if(isset($_POST['delete']) && $_POST['delete']=="true"){
			if($_GET['delete_data']=="1") $filename = null;
			else $filename = $_GET['delete_data'];
			if(MonitGraph::deleteDataFiles($_GET['id'], $filename)) echo '<h1>All the files have been deleted successfully at '.$name.'</h1>';
			else echo '<h1>Some errors happened in the deletion process';
		}else{
			if($_GET['delete_data']=="1") $txt = 'Are you sure to delete all files associated to the server '.$name.'?';
			else $txt = 'Are you sure to delete all files associated to '.$_GET['delete_data'].' at server '.$name.'?';
			echo '
		<form action="?delete_data='.$_GET['delete_data'].'&amp;id='.$_GET['id'].'" method="post">
			<input type="hidden" name="delete" value="true" />
			'.$txt.'
			<input type="submit" value="Delete" />
		</form>';
		}
		echo '
	</div>';
	}else{
		foreach($server_configs as $config){
			$services = MonitGraph::getLastRecord($config['server_id']);
			if(!$services) die("No log files where found. Please run cron first.");

			echo '
	<div class="server_box bordered_box">
		<h2>'.$config['name'].' status</h2>';
			if(empty($services)){
				echo "<span>No log files found</span>";
			}else{
				echo '<div class="service_header">';
				echo "<div><strong>Service:</strong></div>";
				echo "<div><strong>CPU:</strong></div>";
				echo "<div><strong>Memory:</strong></div>";
				echo "<div><strong>Swap:</strong></div>";
				echo "<div><strong>Last check:</strong></div>";
				echo '<div><strong>Actions</strong></div>';
				echo '</div>';
				echo '<div class="clear"></div>';
				foreach($services as $service){
					if($service['status']==0) $status_color = "00FF00";
					else $status_color = "FF0000";
					if($service['swap']!="") $service['swap'].="%";
					else $service['swap']="-";
					echo '<div class="service_row">';
					echo '<div><span style="color:#'.$status_color.'">●</span> <a href="?server_id='.$config['server_id'].'&amp;specific_services='.urlencode($service['name']).'">'.$service['name']."</a></div>";
					echo "<div>".$service['cpu']."%</div>";
					echo "<div>".$service['memory']."%</div>";
					echo "<div>".$service['swap']."</div>";
					echo "<div>".date("H:i:s d-m-Y",$service['time'])."</div>";
					echo '<div><a class="btn" href="?delete_data='.urlencode($service['name'].".xml").'&amp;id='.$config['server_id'].'">Delete service data</a></div>';
					echo '</div>';
					echo '<div class="clear"></div>';
				}
				echo '
		<div class="actions">
			<a class="btn" href="?server_id='.$config['server_id'].'">View all logs for '.$config['name'].'</a>
			<a class="btn" href="?delete_data=1&amp;id='.$config['server_id'].'">Delete all data for '.$config['name'].'</a>
		</div>
	</div>';
			}
		}

	}

	$content = ob_get_contents();
	ob_end_clean();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="refresh" content="60">
	<title>Monit Graph</title>
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />

	<script type="text/javascript">
		function toggle_visibility(id) {
			var e = document.getElementById(id);
			if(e.style.display == "block")
				e.style.display = "none";
			else
			e.style.display = "block";
		}
	</script>
<?php
	echo $output_head;
?>
</head>
<body>
	<div class="logo"><h1>Monit Graph</h1></div>
	<div class="clear"></div>
<?php
	echo $content;
?>
</body>
</html>
