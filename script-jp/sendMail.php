<?php
/*********************************************

 WISH(Web based Infomation SHaring system)
      =         =          ==  

   Copyright (C) 2003
        Isao Hara, Japan
                All rights recieved.

 *********************************************/
require("smtp.php");
require("message.php");

  function send_mail($to_address, $from, $subj, $messg){
    $smtp = new SMTP('mail.asahi-net.or.jp');
    $msg = new Message();

    $to = omitNickNameFromMailAddress($to_address);
    $subject = mb_encode_mimeheader($subj,"JIS","auto");
    $message = mb_convert_encoding($messg,"JIS","auto");

    /***** Headers ***/
    $headers = "";

    $headers .= "From: ".$from."\r\n";
/*
    if ($cc){
      $CC = omitNickNameFromMailAddress($cc);
      $headers .= "Cc: $CC \r\n";
    }
    if ($bcc)
      $BCC = omitNickNameFromMailAddress($bcc);
*/
    $msg->createHeader($subject, $from, $to, $CC,$BCC);
    $msg->createContent($message);

    $ack = $smtp->sendMessage($msg->header, $msg->getMailBody());
    return $ack;
  }

  function extractMailAddress($addr){
     list($name, $domain) = explode("@",$addr);
     $pos=strrpos($name,"<");
     if(!($pos === false)){ $name = substr($name, $pos+1);}
     $pos=strpos($domain,">");
     if (!($pos === false)){ $domain = substr($domain,0, $pos); }
     $pos=strpos($domain,"(");
     if (!($pos === false)){ $domain = trim(substr($domain,0, $pos)); }
     if($name && $domain) return $name."@".$domain;
     else return false;
  }

  function extractNickName($addr){
    if($p=strpos($addr,'<')){
      $str=substr($addr, 0, $p);
      if($str) return $str;
      else return substr($addr,1, strlen($addr)-2);
    }else if ($p=strpos($addr,"(")){
      $str=substr($addr, $p);
      if($str) return $str;
      else return substr($addr,0, $p);
    }else{
      return $addr;
    }
  }

  function omitNickNameFromMailAddress($str){
    $ar=explode(",",$str);
    foreach($ar as $x){ $addr[] = extractMailAddress($x); }
    return implode(",",$addr);
  }

?>
