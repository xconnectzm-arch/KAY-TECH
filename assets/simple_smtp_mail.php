<?php
function smtp_send($host, $port, $username, $password, $to, $fromEmail, $fromName, $subject, $bodyHtml, $bodyText = '') {
    $timeout = 30;
    $errno = 0; $errstr='';
    $fp = stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$fp) { return "Connection failed: $errstr ($errno)"; }
    $read = function() use($fp){ return fgets($fp, 515); };
    $send = function($cmd) use($fp){ fwrite($fp, $cmd."\r\n"); };
    $expect = function($code) use($read){
        $resp = ''; $line = '';
        while ($line === '' || isset($line[3]) && $line[3]=='-') { $line = $read(); if($line===false) break; $resp .= $line; if(strlen($line)>=4 && $line[3]==' ') break; }
        if (substr($resp,0,3)!=$code) { return $resp; }
        return true;
    };

    $greet = $read();
    $send("EHLO ".$_SERVER['SERVER_NAME']);
    $ehlo = $expect('250'); if ($ehlo!==true) { return "EHLO failed: ".$ehlo; }

    $send("AUTH LOGIN");
    $auth = $expect('334'); if ($auth!==true) { return "AUTH failed: ".$auth; }
    $send(base64_encode($username));
    if ($expect('334')!==true) { return "Username rejected"; }
    $send(base64_encode($password));
    if ($expect('235')!==true) { return "Password rejected"; }

    $send("MAIL FROM:<{$fromEmail}>");
    if ($expect('250')!==true) { return "MAIL FROM failed"; }

    $send("RCPT TO:<{$to}>");
    if ($expect('250')!==true && $expect('251')!==true) { return "RCPT TO failed"; }

    $send("DATA");
    if ($expect('354')!==true) { return "DATA failed"; }

    $boundary = md5(uniqid(time()));
    $headers = "From: {$fromName} <{$fromEmail}>\r\n".
               "Reply-To: {$fromName} <{$fromEmail}>\r\n".
               "MIME-Version: 1.0\r\n".
               "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    if ($bodyText==='') { $bodyText = strip_tags($bodyHtml); }
    $msg = $headers.
           "\r\n--{$boundary}\r\n".
           "Content-Type: text/plain; charset=UTF-8\r\n".
           "Content-Transfer-Encoding: 8bit\r\n\r\n".
           $bodyText."\r\n".
           "--{$boundary}\r\n".
           "Content-Type: text/html; charset=UTF-8\r\n".
           "Content-Transfer-Encoding: 8bit\r\n\r\n".
           $bodyHtml."\r\n".
           "--{$boundary}--\r\n".
           "\r\n.";
    fwrite($fp, $msg."\r\n");
    $send("");
    $send(".");
    if ($expect('250')!==true) { return "Message not accepted"; }
    $send("QUIT");
    fclose($fp);
    return true;
}
?>