<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class Mailer {

    private const SMTP_HOST = 'smtp.poczta.onet.pl';
    private const SMTP_USERNAME = 'example@org.com';
    private const SMTP_PASSWORD = 'password';
    private const SMTP_SECURE = 'ssl';
    private const SMTP_PORT = 465;
    private const FROM_EMAIL = 'example@org.com';
    private const FROM_NAME = 'OTP Source';

    public static function send2FaCode(string $address, string $code): bool {

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = self::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = self::SMTP_USERNAME;
            $mail->Password = self::SMTP_PASSWORD;
            $mail->SMTPSecure = self::SMTP_SECURE;
            $mail->Port = self::SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->SMTPDebug=2;
            $mail->Timeout=10;
            $mail->Debugoutput='html';

            $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
            $mail->addAddress($address);

            $mail->isHTML(true);
            $mail->Subject = 'Your security code';
            $mail->Body    = "This is your authentication code <b>$code</b>";
            $mail->AltBody = "This is your authentication code $code";

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}