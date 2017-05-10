<?php

namespace skiptirengu\mssql;

use Exception;
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
     * @var string
     */
    public $constraintLoaderClass = ConstraintLoader::class;
    /**
     * @var string
     */
    public $columnLoaderClass = ColumnLoader::class;
    /**
     * @var string
     */
    public $identityLoderClass = IdentityLoader::class;
    /**
     * @var ConstraintLoader
     */
    public $constraintLoader;
    /**
     * @var ColumnLoader
     */
    public $columnLoader;
    /**
     * @var IdentityLoader
     */
    public $identityLoader;

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
     * @throws Exception
     */
    public function prepareTableSchema($table)
    {
        $this->createLoaders();
        $reader = $this->createDataReader($table);
        do {
            try {
                if ($this->extractData($reader->readAll())) {
                    break;
                }
            } catch (PDOException $ex) {
                // just skip this exception in case NOCOUNT is set to OFF
                if (strpos($ex->getMessage(), self::NO_FIELDS_EXCEPTION) === false) {
                    $reader->close();
                    throw $ex;
                }
            } catch (Exception $ex) {
                // close cursor before re-throwing the exception
                $reader->close();
                throw $ex;
            }
        } while ($reader->nextResult());
        $reader->close();
    }

    /**
     * @return void
     */
    public function createLoaders()
    {
        $this->constraintLoader = new $this->constraintLoaderClass();
        $this->columnLoader = new $this->columnLoaderClass();
        $this->identityLoader = new $this->identityLoderClass();
    }

    /**
     * @param $table
     * @return \yii\db\DataReader
     */
    protected function createDataReader($table)
    {
        return $this->db->createCommand('sp_help :tbl', [':tbl' => $table->fullName])->query();
    }

    /**
     * Extract the metadata information for then given data row.
     *
     * @param array $info
     * @return bool
     */
    public function extractData($info)
    {
        // peek the first value of the rs to determine the kind of information it has
        $first = reset($info);

        if (isset($first['constraint_type'])) {
            $this->constraintLoader->load($info);
        } elseif (isset($first['Column_name'])) {
            $this->columnLoader->load($info);
        } elseif (isset($first['Identity'])) {
            $this->identityLoader->load($info);
        } else {
            return false;
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
        $this->constraintLoader = null;
        $this->columnLoader = null;
        $this->identityLoader = null;
    }
}