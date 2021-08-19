<?php

namespace API\inc;

use API\config\Config;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer {
    /**
     * Sends an email to the specified email, with the given subject and content.
     * The content is wrapped inside of HTML
     *
     * @param string $to The recipients email
     * @param string $subject The subject
     * @param string $content The (HTML-)content
     *
     * @return bool whether it was successfull or not
     */
    public static function send($to, $subject, $content) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = Config::get('mail.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = Config::get('mail.smtp.username');
            $mail->Password = Config::get('mail.smtp.password');
            if (Config::get('mail.smtp.encrypted')) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->Port = Config::get('mail.smtp.port');

            $mail->setFrom(
                Config::get('mail.from.mail'),
                Config::get('mail.from.name')
            );
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::getHTML($subject, $content);
            $mail->AltBody = $content;

            $mail->send();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the generated HTML-mail with the given subject and content
     *
     * @param string $subject The subject
     * @param string $content The content
     *
     * @return string The HTML-mail
     */
    private static function getHTML($subject, $content) {
        ob_start();

        require ROOT_DIR . "/templates/mail.php";

        return ob_get_clean();
    }
}
