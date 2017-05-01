<?php
require __DIR__ . '/../../vendor/autoload.php';

/* This tool can reverse specified XML documents, if needed. */

$files = __DIR__ . "/../../data/*.xml"; // Filepath string
$overwrite = false; // Should the files be overwritten, or create a .new file?

$files = glob($files);
foreach ($files as $file) {
    if ($xml = simplexml_load_string(file_get_contents($file))) {
        if (!$overwrite) {
            $file.=".new";
        }

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

        for ($i = count($xml->record) -1; $i>0; $i--) {
            $record = $xml->record[$i];
            $new_service = $dom->createElement("record");

            $time=$dom->createAttribute("time");
            $time->value = $record["time"];
            $new_service->appendChild($time);

            if ($type=="5") {
                $memory = $dom->createElement("memory", $record->memory);
                $new_service->appendChild($memory);

                $cpu = $dom->createElement("cpu", $record->cpu);
                $new_service->appendChild($cpu);

                $swap = $dom->createElement("swap", $record->swap);
                $new_service->appendChild($swap);
            } else {
                $memory = $dom->createElement("memory", $record->memory);
                $new_service->appendChild($memory);

                $cpu = $dom->createElement("cpu", $record->cpu);
                $new_service->appendChild($cpu);

                $pid = $dom->createElement("pid", $record->pid);
                $new_service->appendChild($pid);

                $uptime = $dom->createElement("uptime", $record->uptime);
                $new_service->appendChild($uptime);

                $children = $dom->createElement("children", $record->children);
                $new_service->appendChild($children);
            }

            $status = $dom->createElement("status", $record->status);
            $new_service->appendChild($status);

            $alert = $dom->createElement("alert", $record->alert);
            $new_service->appendChild($alert);

            $monitor = $dom->createElement("monitor", $record->monitor);
            $new_service->appendChild($monitor);

            $service->appendChild($new_service);
        }

        $dom->validate();
        $handle = fopen($file, 'w');
        if (!$handle) {
            die("Cannot open $file");
        }

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (fwrite($handle, $dom->saveXML()) === false) {
            fclose($handle);
            die("Cannot write to $file");
        }
        fclose($handle);
    }
}
