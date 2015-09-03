<?php

$_referer = parse_url($_SERVER['HTTP_REFERER']); 

if($_referer['host'] != 'openrtp.jp' and $_referer['host'] != 'www.openrtp.jp'){ 
  include("error.html");
}

$path = "/var/www/download/openhrp3/";
$fname = $_POST['fname'];

download_file($path.$fname);


function download_file($fname){

    if (!file_exists($fname)) {
        die("Error: File does not exist");
    }

    if (!($fp = fopen($fname, "r"))) {
        die("Error: Cannot open the file");
    }
    fclose($fp);

    if (($content_length = filesize($fname)) == 0) {
        die("Error: File size is 0");
    }

    header("Content-Disposition: File Transfer");
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"".basename($fname)."\"");
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Cache-Controlh: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");
    header("Content-Length: ".$content_length);
    ob_clean();
    flush();

    if (!readfile($fname)) {
        die("Cannot read the file(".$fname.")");

    }
}

?>

