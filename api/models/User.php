<?php

namespace API\models;

use API\auth\Authorization;
use API\config\Config;
use API\inc\Functions;
use API\inc\Validation;
use PAF\Model\Model;

/**
 * @tablename users
 */
class User extends Model {
    /**
     * @prop
     * @primary
     * @autoincrement
     * @var integer
     */
    public $id;

    /**
     * @prop
     * @email
     * @maxLength 100
     * @var string
     */
    public $email;

    /**
     * @prop
     * @maxLength 20
     * @var string
     */
    public $name;

    /**
     * @prop
     * @var string
     * @output false
     */
    public $password;

    /**
     * @prop
     * @var string
     * @editable false
     * @output false
     */
    public $passwordSalt = null;

    /**
     * @prop
     * @var string|null
     * @editable false
     * @output false
     */
    public $verifyEmailCode;

    /**
     * @prop
     * @var timestamp|null
     * @editable false
     * @output false
     */
    public $verifyEmailCodeExpires;

    /**
     * @prop
     * @var boolean
     * @editable false
     * @output false
     */
    public $isAdmin = false;

    /**
     * @prop
     * @var timestamp|null
     * @editable false
     * @output false
     */
    public $lastUpdated;

    public function __set($property, $value) {
        switch ($property) {
            case 'password':
                $this->generateSalt();

                $value = Authorization::encryptPassword(
                    $value,
                    $this->passwordSalt
                );

                break;
            case 'email':
                $this->generateVerifyEmailCode();
                break;
        }
        parent::__set($property, $value);
    }

    public function jsonSerialize($full = false) {
        $ret = parent::jsonSerialize();

        if ($full) {
            $ret = array_merge($ret, [
                "emailVerified" =>
                    !Config::get("email_verification.enabled", false) ||
                    self::isEmailVerified($this),
                "isAdmin" => $this->isAdmin,
                "lastUpdated" => $this->lastUpdated,
            ]);
        }

        return $ret;
    }

    /**
     * Generates the salt of the user with a random string.
     * This function is called automatically, when the password is set
     */
    private function generateSalt() {
        $this->editValue(
            "passwordSalt",
            Functions::getRandomString(),
            false,
            true
        );
    }

    public function generateVerifyEmailCode() {
        $this->editValue(
            "verifyEmailCode",
            str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT),
            false,
            true
        );
        $this->editValue(
            "verifyEmailCodeExpires",
            time() + Config::get("email_verification.ttl", 3600),
            false,
            true
        );
    }

    public function setIsAdmin($isAdmin) {
        $this->editValue("isAdmin", $isAdmin, true, true);
    }

    public function verifyEmail($code) {
        if (!User::isEmailVerified($this)) {
            if ($this->verifyEmailCodeExpires >= time()) {
                if (strcmp($this->verifyEmailCode, $code) === 0) {
                    $this->editValue("verifyEmailCode", null, false, true);
                    $this->editValue(
                        "verifyEmailCodeExpires",
                        null,
                        false,
                        true
                    );
                    return true;
                }
            } else {
                return false; // expired
            }
        }

        return false;
    }

    public static function getErrors($user) {
        return Validation::getValidationErrorMessages($user, [
            "email" => ["Email", true],
            "name" => ["Name", true],
            "password" => ["Password", true],
        ]);
    }

    public static function isEmailVerified($user) {
        return !$user->isNew() && $user->verifyEmailCode === null;
    }

    /**
     * Returns a specific user
     *
     * @param int $id The id of the user
     *
     * @return User|null The found user or null if not found
     */
    public static function getById($id) {
        return self::get("id = ?", [$id])->getFirst();
    }
}
