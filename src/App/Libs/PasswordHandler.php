<?php
declare(strict_types = 1);

namespace App\Libs;

/**
 * Class PasswordHandler provides password hashing functionality
 * @package App\Libs
 */
class PasswordHandler
{
    /*
     * FIELDS
     */

    private int $cost;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * PasswordHandler constructor.
     * @param int|null $cost
     */
    public function __construct(?int $cost = null)
    {
        $this->cost = $cost ?? 12;
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Get the hash for the specified value
     * @param $value
     * @return string
     */
    public function hash($value) : string
    {
        return password_hash($value, PASSWORD_BCRYPT, [ 'cost' => $this->cost]);
    }

    /**
     * Verify that the plaintext $value and the hashed $encValue match.
     * @param $value
     * @param $encValue
     * @return bool if the hash match. Otherwise false
     */
    public function verify($value, $encValue) : bool
    {
        return password_verify($value, $encValue);
    }

    /**
     * Verify if the hash $envValue needs to be re-encrypted due to cost change.
     * @param $encValue
     * @return bool
     */
    public function checkForUpdate($encValue) : bool
    {
        $pattern = '/^\$2y\$(?<cost>\d{2})\$.{53}$/i';
        if (preg_match($pattern, $encValue, $matches) === false) {
            return true;
        }

        $actualCost = (int)$matches['cost'];

        return $actualCost !== $this->cost;
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
