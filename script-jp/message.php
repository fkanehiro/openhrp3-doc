<?php
/*********************************************

 WISH(Web based Infomation SHaring system)
      =         =          ==  

   Copyright (C) 2003
        Isao Hara, Japan
                All rights recieved.

 *********************************************/
Class Message {
  var $header_all;
  var $body_all;
  var $header;
  var $body;
  var $msg;
  var $content;
  var $trans = array("<" => "&lt;", ">" => "&gt;");

  function Message($msg=""){
    if($msg){
      $this->content = $msg;
      $this->parseMail();
      if($this->header_all) $this->parseMainHeader();
    }
  }

  function createHeader($subject,$from, $to, $cc="", $bcc="", $reply_to="", $type=""){
    $this->header['subject'] = $subject;
    $this->header['from'] = $from;
    $this->header['to'] = $to;
    $this->header['cc'] = $cc;
    $this->header['bcc'] = $bcc;
    $this->header['content_type'] = $type;
    $this->header_all = $this->compactHeader($this->header);
  }

  function compactHeader($header){
    $str = "From :".$header['from']."\n";
    $str .= "Subject :".$header['subject']."\n";
    $str .= "To:".$header['to']."\n";
    if($header['cc']) 
      $str .= "Cc:".$header['cc']."\n";
    if($header['content_type']){
      $str .= "Content-Type:".$header['content_type']."\n";
      $str .= "Content-Transfer-Encoding: 7bit^n";
    }
    return $str."\n";
  }
  function getMailHeader(){
    return $this->header_all;
  }
  function getMailBody(){
    return $this->header_all.mb_convert_encoding($this->body_all,"JIS","auto");
  }

  function setContentType($type, $encode){

  }

  function createContent($msg){
   $this->body_all = $msg;
  }

  function attach($mgs){

  }

  function parseMail(){
    $ar =  $this->extractHeaderAndBody($this->content);
    $this->header_all = $ar[0];
    $this->body_all = $ar[1];
  }

  function extractHeaderAndBody($content){
    if(! $content ) return array("", "");
    $ar = explode("\n", $content);
    $header = true;
    foreach($ar as $line){
      if($header) $header_cont .= $line."\n";
      else $body_cont .= $line."\n";
      if(trim($line) == "") $header = false;
    }
   
    return array($header_cont, $body_cont);
  }

  function parseMainHeader(){
    $this->header = $this->parseHeader($this->header_all);
  }

  function parseHeader($header_str){
    $ar = explode("\n",$header_str);
    $cur_item=false;
    reset($ar);
    $line = current($ar);
    while($line){
      if(eregi("^[\040\t]+",$line)) {
        if($cur_item) $header[$cur_item] .= $line;
      }else{
        if(eregi("^Subject:", $line)) { $cur_item = "subject"; }
        else if(eregi("^From:",$line)) { $cur_item = "from"; }
        else if(eregi("^Date:",$line)) { $cur_item = "date"; }
        else if(eregi("^Message-Id:",$line)) { $cur_item = "message_id"; }
        else if(eregi("^Reply-To:",$line)) { $cur_item = "reply_to"; }
        else if(eregi("^To:",$line)) { $cur_item = "to"; }
        else if(eregi("^Cc:",$line)) { $cur_item = "cc"; }
        else if(eregi("^Content-Type:",$line)) { $cur_item = "content_type"; }
        else if(eregi("^Content-Transfer-Encoding:",$line)) { $cur_item = "content_transfer_encoding"; }
        else if(eregi("^Content-Disposition:",$line)) { $cur_item = "content_disposition"; }
        else if(eregi("^Received:",$line)) { $cur_item = "received"; }
        else if(eregi("^MIME-Version:",$line)) { $cur_item = "mime_version"; }
        else{ $cur_item=false; }
        if($cur_item){
          $header[$cur_item] = substr($line, strlen($cur_item.":"));
        }else{
          $header['other'] .= $line;
        }
      }
      $line = next($ar);
    }
    return $header;
  }

  function printHeader(){
    if(is_array($this->header)){
      print "From: ".$msg->header['from']."\n";
      print "To: ".$msg->header['to']."\n";
      print "CC: ".$msg->header['cc']."\n";
      print "Subject: ".$msg->header['subject']."\n";
      print "Date: ".$msg->header['date']."\n";
      print "Message-Id: ".$msg->header['message_id']."\n";
      print "Content-Type: ".$msg->header['content_type']."\n";
    }else{
      print "Empty header \n";
    }
  }

  function mime_type($str){
    if(! $str) return array("text/plain", "JIS");
    $pos = strpos($str,";");
    if($pos){
      $type = substr($str, 0, strpos($str,";"));
      $option = substr($str, strpos($str,";")+1 );
    }else{
      $type = trim($str);
      $option = "";
    }
    return array($type, $option);
  }

  function parseBody($header,$body){
    global $MboxDir;
    $mime_type = $this->mime_type($header['content_type']);
    if (eregi("text/plain",$mime_type[0])){
      return $body;
    }else if(eregi("text/html",$mime_type[0])){
     if( ereg("quoted-printable",$header['content_transfer_encoding'])){
      return imap_qprint($body);
     }else{
      return $body;
     }
    }else if (eregi("multipart/",$mime_type[0])){
      $boundary =  str_replace("\"", "", substr(stristr($mime_type[1],"boundary="),strlen("boundary=")));

      if(($pos =  strpos($boundary,";")) > 0){
        $boundary =  substr($boundary,0,$pos);
      }

      if (eregi("multipart/mixed",$mime_type[0])){
        return $this->parseMimeMixed($body,$boundary);
      }else if(eregi("multipart/alternative",$mime_type[0])){
        return $this->parseMimeAlternate($body,$boundary);
      }else if (eregi("multipart/parallel",$mime_type[0])){
      }else if (eregi("multipart/digest",$mime_type[0])){
      }else if (eregi("multipart/report",$mime_type[0])){
        //return $body;
      }
    }else if (eregi("application/",$mime_type[0]) ||
          eregi("image/",$mime_type[0])
       ){
      $filename=substr(strstr($header['content_disposition'],"filename="),
                        strlen("filename=")) ;
      $filename = mb_decode_mimeheader($filename);
      $filename = str_replace('"','',$filename);
   
      if( is_file($MboxDir."/Attach/".$filename)){
	unlink($MboxDir."/Attach/".$filename);
	}
      if(! is_file($MboxDir."/Attach/".$filename)){

        if (eregi("base64",$header['content_transfer_encoding'])){
          writeFileContent($MboxDir."/Attach/".$filename, base64_decode($body));
        }else if (eregi("quoted-printable",$header['content_transfer_encoding'])){
          writeFileContent($MboxDir."/Attach/".$filename, imap_qprint($body));
        }
      }
      return array($mime_type[0], $filename);

    }else if (eregi("message/",$mime_type[0]) ){
      $filename= md5($body);
      if(! is_file($MboxDir."/Attach/".$filename)){
        writeFileContent($MboxDir."/Attach/".$filename, $body);
      }
      return array($mime_type[0], $filename);
    }else{
    }
    return array($mime_type[0], $body);
  }

  function parseMimeAlternate($body, $bound){
    $lines = explode("\n",$body);
    $n = -1;
    $bound = trim("--".$bound);
    $end_bound = $bound."--";

    $len = strlen($bound);
    foreach($lines  as $line){
      if ($end_bound == trim($line)){ break; }
      else if($bound == trim($line)){ $n++ ; $b[$n] = "";}
      else {
	$b[$n] .= $line."\n"; 
      }
    }
   
    for($i=0;$i < count($b) ;$i++){
       list($header, $body) = $this->extractHeaderAndBody($b[$i]);
       $ar_header = $this->parseHeader($header);
       $mime_type = $this->mime_type($ar_header['content_type']);
       if(eregi("text/plain",$mime_type[0])){
         $bb = $body;
         $mm = $mime_type[0];
         break;
       }
     }
     if( ereg("quoted-printable",$ar_header['content_transfer_encoding'])){
      $bb = imap_qprint($bb);
     }
     //$bb = strtr(mb_convert_encoding($bb,"EUC","auto"),$this->trans);
     $bb = mb_convert_encoding($bb,"EUC","auto");

     //return array($mm, $bb);
     return $bb;
  }

  function parseMimeMixed($body, $bound){
    $lines = explode("\n",$body);
    $n = -1;
    $bound = trim("--".$bound);
    $end_bound = $bound."--";
    $len = strlen($bound);
    foreach($lines  as $line){
      if ($end_bound == trim($line)){ return $b; }
      else if($bound == trim($line)){ $n++ ; $b[$n] = "";}
      else {
	$b[$n] .= $line."\n"; 
      }
    }
    return 0;
  }

  function mime_encoding($str){
 
  }

  function parseContent($content){
    list($header, $body) = $this->extractHeaderAndBody($content);
    $ar_header = $this->parseHeader($header);
    return  $this->parseBody($ar_header, $body);
  }

  function get_message(){
    $mime_type = $this->mime_type($this->header['content_type']);
    if(! $this->body) {
      $this->body = $this->parseBody($this->header, $this->body_all);
    }

    if (is_array($this->body)){
       $index = 0;
       foreach($this->body as $subbody){
         $b = $this->parseContent($subbody);
         if (is_array($b)){
          if(eregi("text/html",$b[0])){
            $msg[$index++]= "Alternative:HTML";
          }else if(eregi("text/plain",$b[0])){
            $msg[$index++]= $b[1];
          }else if(eregi("text/",$b[0])){
            $msg[$index++]= "Report: ".$b[1];
          }else if(eregi("message/",$b[0])){
            $msg[$index++]= "AttachedMessage: <a href=showAttachedMessage.php?msg_file=".urlencode($b[1]).">".$b[1]."</a>";
          }else{
//            $linkFname=$MboxDir."/Attach/".$b[1];
            $msg[$index++]= "Attached: <a href=download_file.php?fname=".urlencode($b[1]).">".$b[1]."</a>";

          }
         }else{
           $msg[$index++] = mb_convert_encoding($b, "EUC","auto");
         }
       }
       $this->msg = $msg;
       return $msg;
    }else{
      // $this->msg = mb_convert_encoding($this->body, "EUC","auto");
      // return mb_convert_encoding($this->body, "EUC","auto");
       //$this->msg = mb_convert_encoding($this->body, "EUC","auto");
       $this->msg = mb_convert_encoding($this->body, "EUC","auto");
       return $this->msg;
    }

    if ($this->header['content_transfer_encoding']){
    }
    $this->msg = $this->body_all;
    return $this->body_all;
  }

  function mime_decode($str){
    $decode_str = mb_convert_encoding(mb_decode_mimeheader(strtr($str,$this->trans)),"EUC","auto");
    return $decode_str;
  }

  function toHTML($simple = false){
    $html = "<font size=2>";
    if($simple){
//      $html .= "F:".$this->mime_decode($this->header['from'])."|";
      $html .= "F:".$this->mime_decode($this->exaddress($this->header['from']))."|";
      $html .= "S:".$this->mime_decode($this->header['subject'])."\n";
      $date=date("Y/m/d",strtotime($this->header['date']));
      $html .= "[".$date."]\n";
    }else{
      $html .= "From: ".$this->mime_decode($this->header['from'])."<br>\n";
      $html .= "To: ".$this->mime_decode($this->header['to'])."<br>\n";
      if($this->header['cc'])
        $html .= "Cc: ".$this->mime_decode($this->header['cc'])."<br>\n";
      $html .= "Subject: ".$this->mime_decode($this->header['subject'])."<br>\n";
//     $html .= "Content-Type: ".$this->header['content_type']."<br>\n";
      $html .= "Date: ".$this->header['date']."\n";
    }
    $html .= "</font>";
    $html .= "<hr>";

    $message = $this->get_message();

    if(is_array($message)){
      //$html .= "<font size=2>".nl2br(strtr($message[0],$this->trans))."</font><hr>";
      for($i=0;$i<= sizeof($message);$i++){
 	if(preg_match("(^Attached|^Report|^Alternative)",$message[$i]) == 0){ 
          $html .= "<font size=2>".nl2br(strtr($message[$i],$this->trans))."</font><hr>";
        }else{
	 $html .= $message[$i]."<br>";
        }
      }
    }else
      $html .= "<font size=2>".nl2br(strtr($message,$this->trans))."</font>";

    return $html;
  }

function exaddress($addr){
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

}

?>
