<?php
require('sendMail.php');

$error_html = "error.html";
$error_en_html = "error_en.html";
$email_error_html = "email_error.html";
$email_error_en_html = "email_error_en.html";
$avoid_license_html = "avoid_license.html";
$avoid_license_en_html = "avoid_license_en.html";
$invalid_access_html = "invalid_access.html";
$invalid_access_en_html = "invalid_access_en.html";
$download_html = "/var/www/download/openhrp3/download.html";
$download_en_html = "/var/www/download/openhrp3/download_en.html";

$saveFileName = "download_list2.csv";

$name = trim($_POST['name']);
$affiliation = trim($_POST['affiliation']);
$email1 = trim($_POST['email1']);
$email2 = trim($_POST['email2']);

$data = $name.",".$affiliation.",".$email1.",".$_SERVER['HTTP_X_FORWARDED_FOR'].",";

$from = "openhrp-contact@m.aist.go.jp";
$subject = "Thank you for downloading OpenHRP3";
$message =<<<_MSG
$affiliaion  $name 様：

OpenHRP3 をダウンロードいただきまして、どうもありがとうございます。
登録していただきましたこのメールアドレスは、メールマガジン用のメーリングリストに
登録させていただ来ました。
アップデート等のお知らせを、このメールマガジンとして遅らせていただきたいと存じます。
今後とも、よろしくお願いいたします。
_MSG;


$_referer = parse_url($_SERVER['HTTP_REFERER']); 

if( $_referer['host'] != 'openrtp.jp' && $_referer['host'] != 'www.openrtp.jp' ){
    if( $_referer['path'] != '/openhrp3/jp/download.html' && $_referer['path'] != '/openhrp3/en/download.html' ){
        invalid_access( $invalid_access_html, $invalid_access_en_html );
    }
    exit();
}


if( $_POST['sub'] == "承諾" ){
    if($email1 == $email2){
        $data = $data . 'ja' . ',' . date('Y-m-d H:i:s') . "\n";
        if(add_to_data($saveFileName, $data)){
            include($download_html);
            //if($email1) send_mail($email1, $from, $subject, $message);
        } else {
            include($error_html);
        }
    } else {
        include($email_error_html);
    }
}else if( $_POST['sub'] == "Agree" ){
    if($email1 == $email2){
        $data = $data . 'en' . ',' . date('Y-m-d H:i:s') . "\n";
        if(add_to_data($saveFileName, $data)){
            include($download_en_html);
            //if($email1) send_mail($email1, $from, $subject, $message);
        } else {
            include($error_en_html);
        }
    } else {
        include($email_error_en_html);
    }
}else if($_POST['sub'] == "承諾しない"){
    include($avoid_license_html);
}else if($_POST['sub'] == "Disagree" ){
    include($avoid_license_en_html);
}else{
    invalid_access( $invalid_access_html, $invalid_access_en_html );
}
?>

<?php
function add_to_data($file, $data){
    $fp = fopen($file, "a");

    for($lock=flock($fp, LOCK_EX),$count=0; !$lock && $count<5; $lock=flock($fp, LOCK_EX),$count++){
        usleep(100000);
    }

    if($lock){
        fputs($fp, $data);
        fclose($fp);
        return true; 
    }else{
        fclose($fp);
        return false; 
    }
}

#ブラウザが要求する言語設定の優先順位が英語より日本語が高い場合TRUE
#日本語も英語も設定されていなければFALSEを返す
function chk_jp_en_priority(){
    $ret = FALSE;
    $local_str = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    if( $local_str )
    {
        $ja_ret = strpos( $local_str, 'ja');
        $en_ret = strpos( $local_str, 'en');

        #日本語が設定されていない時はFALSE
        if ( $ja_ret === FALSE ){
            return $ret;
        }
        if ( $en_ret === FALSE ){
            $ret = TRUE;
        } else {
            if( $ja_ret < $en_ret) {
                $ret = TRUE;
            }
        }
    }
    return $ret;
}

function invalid_access( $jp_html, $en_html ){
    if( chk_jp_en_priority() ){
        include($jp_html);
    } else {
        include($en_html);
    }
}

?>

