<?php
declare(strict_types = 1);

namespace Core;

use ClanCats\Hydrahon\Builder;
use ClanCats\Hydrahon\Query\Sql\FetchableInterface;
use ClanCats\Hydrahon\Query\Sql\Select;
use ClanCats\Hydrahon\Query\Sql\Table;
use ClanCats\Hydrahon\Query\Expression;
use PDO;
use function App\Libs\array_path_explode;
use function App\Libs\array_path_export;
use function App\Libs\array_remove;

/**
 * Base model
 */
abstract class Model
{
    /*
     * FIELDS
     */

    protected string $table;
    protected string $pk;
    protected array $mutableFields;

    private ?Table $dbTable;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * Model constructor.
     * @param string $table
     * @param string|null $pk
     */
    public function __construct(string $table, ?string $pk = null)
    {
        $this->table = $table;
        $this->pk = $pk ?? 'id';

        $this->dbTable = null;
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Find all models. Set $isDeleted parameter to true to include deleted item as a search criteria
     * @return array models
     */
    public function all(?bool $isDeleted = false) : ?array
    {
        $models = $this->prepareSelect($isDeleted)
            ->get();

        return $models;
    }

    /**
     * Find model matching primary key $id. Set $isDeleted parameter to true to include deleted item as a search criteria
     * @return array model
     */
    public function find($id, ?bool $isDeleted = null) : ?array
    {
        $model = $this->prepareSelect($isDeleted)
            ->where("{$this->table}.{$this->pk}", '=', $id)
            ->limit(1)
            ->get() ?: null;

        return $model;
    }

    /**
     * Add a new model in database
     * @return bool True if insertion successed.
     */
    public function add(array &$model) : bool
    {
        $values = array_intersect_key($model, array_flip($this->mutableFields));

        $success = $this->dbTable()
            ->insert($values)
            ->execute();

        if ($success) {
            $this->hydrate($model, (int)self::pdo()->lastInsertId());
        }

        return $success;
    }

    /**
     * Update a model in database
     * @return bool true if update successed
     */
    public function update(array &$model) : bool
    {
        $values = array_intersect_key($model, array_flip(array_merge([$this->pk], $this->mutableFields)));

        $query = $this->dbTable()->update();

        foreach ($values as $key => $value) {
            $query->set([$key => $value]);
        }

        $success = $query
            ->set('updatedAt', new Expression('CURRENT_TIMESTAMP()'))
            ->where('deletedAt', 'IS NOT NULL =', false)
            ->andWhere($this->pk, '=', $model[$this->pk])
            ->limit(1)
            ->execute();

        if ($success) {
            $this->hydrate($model);
        }

        return $success;
    }

    /**
     * Remove a model from database
     * @return bool true if remove successed
     */
    public function remove(array &$model) : bool
    {
        $success = $this->dbTable()->update()
            ->set('deletedAt', new Expression('CURRENT_TIMESTAMP()'))
            ->where('deletedAt', 'IS NOT NULL =', false)
            ->andWhere($this->pk, '=', $model[$this->pk])
            ->limit(1)
            ->execute();

        if ($success) {
            $this->hydrate($model);
        }

        return $success;
    }

    /**
     * Run validation over the $model parameter and format it's value according to validation rules.
     * Use the field parameter to customize which field to validate. Other fields are removed from model to ensure security.
     * @return array An array of validation message as a validation result
     */
    public function validate(array &$model, array $fields = null) : array
    {
        // if no field specified, validate all properties in model
        $fields ??= array_keys($model);

        // Validation rule definitions
        $validationRules = $this->getValidationRules();

        $messages = [];

        // Remove unwanted property from model
        foreach (array_keys($model) as $propertyName) {
            if (!(in_array($propertyName, $fields))) {
                unset($model[$propertyName]);
            }
        }

        // Check each wanted property
        foreach ($fields as $field) {
            // if no validation rules exist, skip
            if (!array_key_exists($field, $validationRules)) {
                continue;
            }

            $fieldRules = $validationRules[$field];

            // Apply each rules
            foreach ($fieldRules as $fieldRule) {
                // if property exists in model
                if (!array_key_exists($field, $model)) {
                    continue;
                }
                // Apply rule
                $value = $fieldRule['rule']($model, $field);


                if (!($value instanceof \Exception)) { // Handle success
                    if (@is_callable($fieldRule['onSuccess']) === true) {
                        $fieldRule['onSuccess']($model, $field, $value);
                    }
                } else { // Handle failure
                    if (@is_callable($fieldRule['onFailure']) === true) {
                        $fieldRule['onFailure']($model, $field);
                    }

                    $messages[] = @$fieldRule['failureMessage'];
                }
            }
        }

        return array_filter($messages);
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /**
     * Hydrate the model from the database without overriding existing properties
     * @param array $model
     * @param int|null $id
     * @return bool
     */
    protected function hydrate(array &$model, ?int $id = null) : bool
    {
        $dbModel = $this->find($id ?? @$model[$this->pk]);

        if ($dbModel === null) {
            return false;
        }
        foreach ($model as $key => $value) {
            if ($value instanceof Expression) {
                unset($model[$key]);
            }
        }

        $keysToReplace = array_diff_key($dbModel, $model);
        foreach ($keysToReplace as $key => $value) {
            $model[$key] = $value;
        }

        $keysToRemove = array_diff_key($model, $dbModel);
        foreach ($keysToRemove as $key => $value) {
            unset($model[$key]);
        }

        return true;
    }

    /*
     * STATIC METHODS
     */

    /**
     * Start a new database transaction
     * @return bool if a new transaction is started
     */
    public static function beginTransaction() : bool
    {
        if (self::inTransaction()) {
            return false;
        }
        return self::pdo()->beginTransaction();
    }

    /**
     * Commit the database state
     * @return bool if the transaction is commited.
     */
    public static function commitTransaction() : bool
    {
        if (!self::inTransaction()) {
            return false;
        }

        return self::pdo()->commit();
    }

    /**
     * Rollback the database state
     * @return bool if the transaction is rolled back.
     */
    public static function rollbackTransaction() : bool
    {
        if (!self::inTransaction()) {
            return false;
        }

        return self::pdo()->rollBack();
    }

    /**
     * Indicate if the database is in transaction.
     * @return bool if the database is in a transaction. False otherwise.
     */
    public static function inTransaction() : bool
    {
        return self::pdo()->inTransaction();
    }

    /**
     * Get the PDO database connection
     *
     * @return mixed
     */
    protected static function pdo() : PDO
    {
        static $db = null;

        if ($db === null) {
            $config = Application::current()->getConfig();
            $db = new PDO($config->getDbDsn(), $config->getDbUser(), $config->getDbPass());

            // Throw an Exception when an error occurs
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }

        return $db;
    }

    /**
     * Get or create the Query builder
     * @return \ClanCats\Hydrahon\Builder
     */
    protected static function db() : Builder
    {
        static $hydrahon = null;

        if ($hydrahon === null) {
            $pdo = self::pdo();
            $hydrahon = new Builder('mysql', function ($query, $queryString, $queryParameters) use ($pdo) {
                $statement = $pdo->prepare($queryString);
                $success = $statement->execute($queryParameters);

                if ($query instanceof FetchableInterface) {
                    $records = $statement->fetchAll();
                    $results = [];

                    foreach ($records as $record) {
                        $result = array_path_explode('.', $record);
                        $result = array_path_export($result);
                        $results[] = $result;
                    }

                    return $results;
                }
                
                return $success;
            });
        }

        return $hydrahon;
    }

    /**
     * Get the table object for starting build query.
     * @return \ClanCats\Hydrahon\Query\Sql\Table
     */
    protected function dbTable() : Table
    {
        if ($this->dbTable === null) {
            $this->dbTable = self::db()->table($this->table);
        }

        return $this->dbTable;
    }

    /**
     * Create a new select object for querying datatable
     * @return Select
     */
    protected function createSelect() : Select
    {
        return $this->dbTable()->select();
    }

    /**
     * Prepare the select object with general filter
     * @param bool|null $isDeleted
     * @return Select
     */
    protected function prepareSelect(?bool $isDeleted = false) : Select
    {
        $query = $this->createSelect();

        if ($isDeleted !== null) {
            $query->where("{$this->table}.deletedAt", 'IS NOT NULL =', $isDeleted);
        }

        return $query;
    }

    /**
     * Get the rules used to validate/sanitize the values of the model.
     * @return \array[][]
     */
    protected function getValidationRules() : array
    {
        return [];
    }
}
