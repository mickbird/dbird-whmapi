<?php
declare(strict_types = 1);

namespace App;

use Dotenv\Dotenv;

class Config
{
    /*
     * FIELDS
     */

    protected array $config;

    /*
     * GETTERS / SETTERS
     */

    /**
     * Get the database DSN
     * @return string
     */
    public function getDbDSN() : string
    {
        return $this->config['DB_DSN'];
    }

    /**
     * Get the database username
     * @return string
     */
    public function getDbUser() : string
    {
        return $this->config['DB_USER'];
    }

    /**
     * Get the database password
     * @return string
     */
    public function getDbPass() : string
    {
        return $this->config['DB_PASS'];
    }

    /**
     * Get the BCrypt cost used when hashing passwords
     * @return int
     */
    public function getBcryptCost() : int
    {
        return (int)$this->config['BCRYPT_COST'];
    }

    /**
     * Get the environment name (production or development)
     * @return string
     */
    public function getEnvironment() : string
    {
        return $this->config['ENVIRONMENT'];
    }

    /**
     * Get the log level
     * @return int
     */
    public function getLogLevel() : int
    {
        return (int)$this->config['LOG_LEVEL'];
    }

    /**
     * Get the CPanel host
     * @return int
     */
    public function getCPanelHost() : string
    {
        return $this->config['CPANEL_HOST'];
    }

    /**
     * Get the CPanel user
     * @return int
     */
    public function getCPanelUser() : string
    {
        return $this->config['CPANEL_USER'];
    }

    /**
     * Get the CPanel password
     * @return int
     */
    public function getCPanelPass() : string
    {
        return $this->config['CPANEL_PASS'];
    }

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * Config constructor.
     * @param string|null $path
     * @param string|null $filename
     */
    public function __construct(?string $path = null, ?string $filename = null)
    {
        $dotenv = Dotenv::createImmutable($path, $filename);
        $config = $dotenv->load();
        $dotenv->required('DB_DSN')->notEmpty();
        $dotenv->required('DB_USER')->notEmpty();
        $dotenv->required('DB_PASS');
        $dotenv->required('ENVIRONMENT')->allowedValues(['production', 'development']);
        $dotenv->required('LOG_LEVEL')->allowedValues(['100', '200', '250', '300', '400', '500', '550', '600']);
        $dotenv->required('CPANEL_HOST')->notEmpty();
        $dotenv->required('CPANEL_USER')->notEmpty();
        $dotenv->required('CPANEL_PASS')->notEmpty();

        $this->config = array_change_key_case($config, CASE_UPPER);
    }
    /*
     * PUBLIC METHODS
     */

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */

    protected static function convertStringToArray(string $valueString, string $itemSeparator = ';', string $fieldSeparator = ',') : ?array
    {
        $results = [];

        $values = explode($itemSeparator, $valueString);
        $values = array_map('trim', $values);
        $values = array_filter($values);


        foreach ($values as $index => $value) {
            $fields = explode($fieldSeparator, $value, 2);
            $fields = array_map('trim', $fields);

            $fields = array_reverse($fields);
            $fields = array_replace([null, null], $fields);
            $fields = array_reverse($fields);

            $fieldKey = $fields[0];
            $fieldKey = !empty($fieldKey) ? $fieldKey : $index;

            $fieldValue = $fields[1];
            $fieldValue = !empty($fieldValue) ? $fieldValue : null;

            $results[$fieldKey] = $fieldValue;
        }

        return $results;
    }
}
