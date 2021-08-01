<?php
declare(strict_types = 1);

namespace App\Models;

use ClanCats\Hydrahon\Query\Sql\Select;
use Core\Model;
use function App\Libs\filter_var_typed;

/**
 * _Model model
 */
class _Model extends Model
{
    /*
     * FIELDS
     */

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * Post constructor.
     */
    public function __construct()
    {
        parent::__construct('<entity name>', '<primary key field>');

        $this->mutableFields = ["<list of fields>"];
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Find all published posts
     * @return array
     */
    public function all() : array
    {
        $models = $this->prepareSelect()
            ->where("/*...*/")
            ->get();

        return $models;
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /**
     * Create a new select object for querying datatable
     * @return Select
     */
    protected function createSelect() : Select
    {
        $dbTable = $this->dbTable();

        return $dbTable->select([
            "{$this->table}.{$this->pk}",
            "{$this->table}.field1",
            "{$this->table}.field2"
            // ....
        ]);
    }

    /**
     * Get the rules used to validate/sanitize the values of the model.
     * @return \array[][]
     */
    protected function getValidationRules() : array
    {
        return [
            $this->pk => [
                'is_numeric_greater_than_0' => [
                    'rule' => fn (array $mdl, string $prop) => filter_var_typed(@$mdl[$prop], FILTER_VALIDATE_INT, ['min_range' => 1]),
                    'onSuccess' => fn(array &$mdl, string $prop, $val) => $mdl[$prop] = $val,
                    'onFailure' => null,
                    'failureMessage' => 'L\'ID est invalide'
                ]
            ]
        ];
    }

    /*
     * STATIC METHODS
     */
}
