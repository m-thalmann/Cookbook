<?php

namespace API\Models;

use API\Auth\Authorization;
use API\Inc\Validation;
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
     * @var string
     */
    public $email;
    // TODO: name
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
     * @var timestamp|null
     * @editable false
     * @output false
     */
    public $lastUpdated;

    public function __set($property, $value) {
        switch ($property) {
            case 'password':
                if ($this->passwordSalt === null) {
                    $this->initSalt();
                }

                $value = Authorization::encryptPassword(
                    $value,
                    $this->passwordSalt
                );

                break;
        }
        parent::__set($property, $value);
    }

    /**
     * Initializes the salt of the user with a random string.
     * This function is called automatically, when the password is set (and the salt is empty)
     */
    public function initSalt() {
        $this->editValue(
            "passwordSalt",
            md5(random_int(PHP_INT_MIN, PHP_INT_MAX)),
            true,
            true
        );
    }

    public static function getErrors($user) {
        return Validation::getValidationErrorMessages($user, [
            "email" => ["Email", true],
            "password" => ["Password", true],
        ]);
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
