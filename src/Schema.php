<?php

namespace skiptirengu\mssql;

use PDOException;
use yii\db\mssql\Schema as BaseSchema;
use yii\db\mssql\TableSchema;

/**
 * Optimized Schema class for MS SQL databases.
 *
 * @author Skiptir Engu <skiptir.engu@yandex.com>
 * @see https://docs.microsoft.com/en-us/sql/relational-databases/system-stored-procedures/sp-help-transact-sql
 */
class Schema extends BaseSchema
{
    /**
     * Exception thrown when trying to read a resulset without any fields
     */
    const NO_FIELDS_EXCEPTION = 'The active result for the query contains no fields';

    /**
     * @var ConstraintLoader
     */
    protected $constraintLoader;
    /**
     * @var ColumnLoader
     */
    protected $columnLoader;
    /**
     * @var IdentityLoader
     */
    protected $identityLoader;

    /**
     * @inheritdoc
     */
    public function loadTableSchema($name)
    {
        $table = new TableSchema();

        $this->resolveTableNames($table, $name);
        $this->prepareTableSchema($table);

        $this->findPrimaryKeys($table);
        if ($this->findColumns($table)) {
            $this->findForeignKeys($table);
        } else {
            $table = null;
        }

        return $table;
    }

    /**
     * Query the database for metadata for the given table
     *
     * @param TableSchema $table the table metadata
     * @return void
     */
    public function prepareTableSchema($table)
    {
        $reader = $this->db->createCommand('sp_help :tbl', [':tbl' => $table->fullName])->query();
        do {
            try {
                if ($this->extractData($reader->readAll())) {
                    break;
                }
            } catch (PDOException $ex) {
                // just skip this exception in case NOCOUNT is set to OFF
                if (strpos($ex->getMessage(), self::NO_FIELDS_EXCEPTION) === false) {
                    throw $ex;
                }
            }
        } while ($reader->nextResult());
    }

    /**
     * Extract the metadata information for then given data row.
     *
     * @param array $info
     * @return bool
     */
    protected function extractData($info)
    {
        // peek the first value of the rs to determine the kind of information it has
        $first = reset($info);

        if (isset($first['constraint_type'])) {
            $this->constraintLoader = new ConstraintLoader();
            $this->constraintLoader->load($info);
        } elseif (isset($first['Column_name'])) {
            $this->columnLoader = new ColumnLoader();
            $this->columnLoader->load($info);
        } elseif (isset($first['Identity'])) {
            $this->identityLoader = new IdentityLoader();
            $this->identityLoader->load($info);
        }

        return $this->constraintLoader->isLoaded
            && $this->columnLoader->isLoaded
            && $this->identityLoader->isLoaded;
    }

    /**
     * Resets the current table info
     *
     * @return void
     */
    protected function resetTableInfo()
    {
        preg_match('/\(([\w,\s])+\)/', '');
    }
}