<?php
/*********************************************

 WISH(Web based Infomation SHaring system)
      =         =          ==  

   Copyright (C) 2003
        Isao Hara, Japan
                All rights recieved.

 *********************************************/
Class SMTP {
  var $host;
  var $domain;
  var $from;
  var $port;
  var $fd;
  var $debug = false;

  function SMTP($host, $port=25){
    $this->host = $host;
    $this->port = $port;
    $this->domain = substr(strstr($host,"."),1);
  }

  function openPort(){
    if (!($this->fd))
      $this->fd = fsockopen($this->host, $this->port, &$errno, &$errstr);
    if(!($this->fd)) return false;

    return true;
  }

  function closePort(){
    if ($this->fd){
	fclose($this->fd);
        $this->fd = null;
    }
  }

  function debug_info($msg){ if ($this->debug) print "$msg\n"; }

  function open(){
    if(! $this->openPort()) { print "Error in openPort (".$this->host.":".$this->port.")<br>\n"; exit; }
    if( ($code = $this->response($this->fd)) != "220"){
      print "Error in open: result code is $code\n"; 
      return exit;
    }
    $this->HELO();
    return true;
  }

  function close(){
    if($this->send("QUIT") == "221"){ print "Success to send your mail\n"; }
    $this->closePort();
  }

  function send($command){
    if(! $this->fd) return false;
    fputs($this->fd, $command."\n");
    return $this->response($this->fd);
  }

  function sendCommand($command, $ACK="250"){
    if(($err = $this->send($command)) != $ACK){
	print "Error in sendCommand ($command): $err\n";
        $this->close();
        return false;
    }
    $this->debug_info("OK: $command");
    return true;
  }

  function response($fd){
    if(! $fd) return false;
    $reply_code = "XXX-";
    while($reply_code[3] == '-'){
      $reply_code = substr(fgets($fd, 512), 0,4);
    }
    return substr($reply_code,0,3);
  }

  function HELO(){
    return  $this->sendCommand("HELO $this->domain");
  }

  function parseMailAddress($addr){
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

  function MAIL_FROM($from){
    $from = $this->parseMailAddress($from);
    if($from) $this->sendCommand("MAIL FROM: <".$from.">");
  }

  function RCPT_TO($to, $cc="", $bcc=""){
    $to_addr=array_merge(explode(",",$to),explode(",",$cc),explode(",",$bcc));
    foreach($to_addr as $addr){
       $address = $this->parseMailAddress($addr);
       if ($address) $this->sendCommand("RCPT TO: <".$address.">");
    }
  }

  function DATA($message){
    $ack=false;
    if ($this->sendCommand("DATA","354")){
      $content = $message."\n.\n";
      $ack = $this->sendCommand($content);
    }
    return $ack;
  }

  function sendMessage($header, $message){
     $ack = $this->open();
     $ack = $this->MAIL_FROM($header['from']);
     $ack = $this->RCPT_TO($header['to'], $header['cc'], $header['bcc']);
     $ack = $this->DATA($message);
     $this->close();
     return $ack;
  }
}
?>
