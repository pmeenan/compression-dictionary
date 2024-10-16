<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header('Content-Type: text/plain; charset=UTF-8');
$format = null;
if (isset($_REQUEST['f'])) {
    if ($_REQUEST['f'] == 'dcb') {
        $format = 'dcb';
    } elseif ($_REQUEST['f'] == 'dcz') {
        $format = 'dcz';
    }
}
if (isset($format)) {
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
        $accept = $_SERVER['HTTP_ACCEPT_ENCODING'];
        if (strpos($accept, $format) !== false) {
            if (isset($_SERVER['HTTP_AVAILABLE_DICTIONARY'])) {
                $dictionary = $_SERVER['HTTP_AVAILABLE_DICTIONARY'];
                if ($dictionary == ":IO7tkX/cw80DDlZGb/lF1ljDXQaVKyDEMRlECaT44IA=:") {
                    if (file_exists(__DIR__ . "/data.txt.$format")) {
                        header("Content-Encoding: $format");
                        readfile(__DIR__ . "/data.txt.$format");
                    } else {
                        echo "$format file not found";
                    }
                } else {
                    echo "Expected 'Available-Dictionary: :IO7tkX/cw80DDlZGb/lF1ljDXQaVKyDEMRlECaT44IA=:' but received 'Available-Dictionary: $dictionary'";
                }
            } else {
                echo "Missing Available-Dictionary request header.";
            }
        } else {
            echo "$format not included in 'Accept-Encoding' request header. Received 'Accept-Encoding: $accept'";
        }
    } else {
        echo "Missing Accept-Encoding request header.";
    }
} else {
    echo "Invalid request, type not specified.";
}
