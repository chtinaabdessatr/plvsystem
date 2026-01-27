<?php
class Mail {
    public static function send($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: no-reply@lapplvsytem.com' . "\r\n";
        // Use PHP mail() or configure a library like PHPMailer here
        return mail($to, $subject, $message, $headers);
    }
}
?>