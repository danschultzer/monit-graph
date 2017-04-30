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

	/* Monit login information */
	$server_configs = array(
				array(
						"name"=>"My Server 1",
						"server_id"=>1, 										// server_id HAS to be unique always
						"config"=>
						array(
							"url" => "", 											// E.g. yourdomain.com:2934
							"uri_xml" => "_status?format=xml", 	// No need to edit
							"url_ssl" => true, 									// If you wish SSL encryption, turn on. The monit_url need to accept requests on :443 then!
							"http_username" => "", 						// Username to login at monit http
							"http_password" => "", 						// Password to login at monit http
							"verify_ssl" => false 								// Set to true in production. This verifies that the SSL certificate is good.
						)
					)
/*
					,
				array(
						"name"=>"My Server 2",
						"server_id"=>2, 										// server_id HAS to be unique always
						"config"=>
						array(
							"url" => "", 											// E.g. yourdomain.com:2934
							"uri_xml" => "_status?format=xml", 	// No need to edit
							"url_ssl" => true, 									// If you wish SSL encryption, turn on. The monit_url need to accept requests on :443 then!
							"http_username" => "", 						// Username to login at monit http
							"http_password" => "", 						// Password to login at monit http
							"verify_ssl" => false 								// Set to true in production. This verifies that the SSL certificate is good.
						)
					)
*/
				);

	/* Monit-Graph display information */
	$default_time_range = 3600; // Amount in seconds of the default view should be (0 equals all available data)
	$default_chart_type = "LineChart"; // Default chart type
	$default_refresh_seconds = 120; // Default amount of seconds before data is reloaded (0 equals never)
	$default_specific_service = ""; // Default service to be displayed (none is equal to all services)
	$default_dont_show_alerts = "on"; // Default not showing alerts (needs to be set to "on" for not showing alerts)
	$limit_records_shown = 750; // Number of maximum history records to be shown, if the records retrieved are larger than this, the PHP script will try minimize by deleting every second (0 equals unlimited)

	/* Monit-Graph history handling */
	$chunk_size = 500*1024; // Maximum size in bytes for each service history chunk (0 equals unlimited, remember to set php.ini so the scripts can handle it as well)
	$limit_number_of_chunks = 0; // Maximum number of chunks saved per service records, will delete all above this (0 equals unlimited)

 ?>