<?php
  require __DIR__ . '/../../src/monit_graph.class.php';

  /* This tool can build massive data files for test.
      It will comply with the rotation rules set in config.
      It is a bit heavy with I/O but it is to get exact filesize */

  function writeDom($dom, $output_file)
  {
      @$dom->validate();

      $handle = fopen($output_file, 'w');
      if (!$handle) {
          die("Cannot open $output_file");
      }

      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = false;
      if (fwrite($handle, $dom->saveXML()) === false) {
          fclose($handle);
          die("Cannot write to $output_file");
      }
  }

  $config = MonitGraph::config();

  $output_file = __DIR__ . "/../../data/massive_data_points/service.xml";
  $overwrite = true;
  $number_of_data_points = 25000;
  $seconds_difference_per_point = 60; // How many seconds between each point
  $data_time = time() - $number_of_data_points * $seconds_difference_per_point;
  $data_memory = $data_cpu = $data_pid = $data_uptime = $data_children = $data_status = $data_alert = 0;
  $data_monitor = $data_cpu = 1;
  $data_memory = 5;
  $file_size = -1;
  $file_size_total = 0;

  $name = "massive_data_points";
  $type = "3";

  for ($i = 0; $i<$number_of_data_points; $i++) {
      if ($file_size == -1 || ($config['chunk_size'] > 0 && $file_size > $config['chunk_size'])) {
          if (isset($dom)) {
              if (file_exists($output_file)) {
                  MonitGraph::rotateFiles($output_file, $config['chunk_size'], $config['limit_number_of_chunks']);
              }
              echo "Writting out $file_size bytes and reached $i points out of $number_of_data_points\n";
              $file_size_total += $file_size;
              writeDom($dom, $output_file);
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

      $data_memory += rand(-2, 2)/10;
      if ($data_memory >= 100 || $data_memory <= 0) {
          $data_memory = 0;
      }

      $data_cpu += rand(-1, 1)/10;
      if ($data_cpu >= 100 || $data_cpu <= 0) {
          $data_cpu = 0;
      }

      $data_swap = 0;

      $new_service = $dom->createElement("record");

      $time=$dom->createAttribute("time");
      $time->value = $data_time;
      $new_service->appendChild($time);

      $memory = $dom->createElement("memory", intVal($data_memory));
      $new_service->appendChild($memory);

      $cpu = $dom->createElement("cpu", $data_cpu);
      $new_service->appendChild($cpu);

      $pid = $dom->createElement("pid", $data_pid);
      $new_service->appendChild($pid);

      $uptime = $dom->createElement("uptime", $data_uptime);
      $new_service->appendChild($uptime);

      $children = $dom->createElement("children", $data_children);
      $new_service->appendChild($children);

      $status = $dom->createElement("status", $data_status);
      $new_service->appendChild($status);

      $alert = $dom->createElement("alert", $data_alert);
      $new_service->appendChild($alert);

      $monitor = $dom->createElement("monitor", $data_monitor);
      $new_service->appendChild($monitor);

      $service->appendChild($new_service);

      $file_size=$dom->save("/tmp/tmp.xml"); // Let's check the file size
  }

  writeDom($dom, $output_file);

  echo number_format($number_of_data_points) . " data points written\n";
  echo "Memory peak: " . number_format(memory_get_peak_usage() / 1024, 0, ".", ",") . " kb\n";
  echo "File size: " . number_format($file_size_total / 1024, 0, ".", ",") . " kb\n";
