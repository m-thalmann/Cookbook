<?php

namespace API\inc;

use API\config\Config;
use API\models\User;
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
     * @param boolean $ignoreEnabled Whether it should be ignored if emails are enabled in the config
     *
     * @throws ApiException If the email could not be sent
     *
     * @return boolean False if emails are disabled and the email was therefore not sent, true otherwise
     */
    public static function send(
        $to,
        $subject,
        $content,
        $ignoreEnabled = false
    ) {
        if (!$ignoreEnabled && !Config::get("mail.enabled")) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = Config::get("mail.smtp.host");
            $mail->SMTPAuth = true;
            $mail->Username = Config::get("mail.smtp.username");
            $mail->Password = Config::get("mail.smtp.password");
            if (Config::get("mail.smtp.encrypted")) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->Port = Config::get("mail.smtp.port");

            $mail->setFrom(
                Config::get("mail.from.mail"),
                Config::get("mail.from.name")
            );
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::getHTML($subject, $content);
            $mail->AltBody = $content;

            $mail->send();

            return true;
        } catch (Exception $e) {
            throw ApiException::error("sending_email", $e->getMessage());
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

    /**
     * Sends an email-verification email to the user
     *
     * @param User $user The user
     *
     * @throws ApiException If the email could not be sent
     *
     * @return boolean False if emails are disabled and the email was therefore not sent, true otherwise
     */
    public static function sendEmailVerification($user) {
        $expires = date("d.m.Y H:i", $user->verifyEmailCodeExpires);

        return self::send(
            $user->email,
            "Cookbook email verification",
            "Hi $user->name,<br />please use the following code to verify this email address:<br /><b>$user->verifyEmailCode</b><br />It will expire at <i>$expires</i>"
        );
    }

    /**
     * Sends a reset-password email to the user
     *
     * @param User $user The user
     * @param string $token The token used to identify the user
     *
     * @throws ApiException If the email could not be sent
     *
     * @return boolean False if emails are disabled and the email was therefore not sent, true otherwise
     */
    public static function sendResetPassword($user, $token) {
        return self::send(
            $user->email,
            "Cookbook password reset",
            "Hi $user->name,<br />please use the following code to reset your password:<br /><b>$token</b>"
        );
    }
}
