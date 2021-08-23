<?php

namespace API\models;

use API\config\Config;
use API\inc\Functions;
use PAF\Model\Model;

/**
 * @tablename reset_password
 */
class ResetPassword extends Model {
    /**
     * @prop
     * @primary
     * @var integer
     */
    public $userId;

    /**
     * @prop
     * @primary
     * @var string
     */
    public $token;

    /**
     * @prop
     * @var timestamp
     */
    public $expires;

    /**
     * Returns the user
     *
     * @return User|null The reset-password-token's user
     */
    public function user() {
        return User::get("id = ?", [$this->userId])->getFirst();
    }

    /**
     * Generates a new ResetPassword-entry for the given user and deletes all previous entries
     *
     * @param User $user The user
     *
     * @return ResetPassword|false The generated instance or false if an error occurred
     */
    public static function generate($user) {
        $resetPassword = self::create([
            "userId" => $user->id,
            "token" => Functions::getRandomString(),
            "expires" => time() + Config::get("password.reset_ttl", 600),
        ]);

        if ($resetPassword->save()) {
            ResetPassword::deleteByQuery("userId = ? AND token != ?", [$user->id, $resetPassword->token]);

            return $resetPassword;
        }

        return false;
    }

    /**
     * Clears the expired tokes and checks whether the user with the given email
     * has this reset-token. If he does, the ResetPassword-Instance is returned
     *
     * @param string $email The users email
     * @param string $token The reset-token
     *
     * @return ResetPassword|null The ResetPassword-Instance or null if not found
     */
    public static function search($email, $token) {
        self::clearExpired();

        return self::get(
            "userId = (SELECT id FROM users WHERE email = ?) AND token = ? AND expires >= CURRENT_TIMESTAMP",
            [$email, $token]
        )->getFirst();
    }

    /**
     * Deletes all expired reset-password-tokens
     */
    public static function clearExpired() {
        ResetPassword::deleteByQuery("expires < CURRENT_TIMESTAMP");
    }
}
