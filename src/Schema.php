<?php

namespace skiptirengu\mssql;

use Exception;
use PDOException;
use Yii;
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
        $this->columnLoader = Yii::$container->get(ColumnLoader::class);
        $this->identityLoader = Yii::$container->get(IdentityLoader::class);
        $this->constraintLoader = Yii::$container->get(ConstraintLoader::class);
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
     * @inheritdoc
     */
    protected function findPrimaryKeys($table)
    {
        $table->primaryKey = $this->constraintLoader->tablePks;
    }

    /**
     * @inheritdoc
     */
    protected function findColumns($table)
    {
        if (empty($this->columnLoader->tableColumns)) {
            return false;
        }

        $this->columnLoader->setIdentityColumn($this->identityLoader);
        $this->columnLoader->setDefaultValuesForColumns($this->constraintLoader);
        $this->columnLoader->setPrimaryKeys($this->constraintLoader);

        foreach ($this->columnLoader->tableColumns as $column) {
            $column = $this->loadColumnSchema($column);
            $table->columns[$column->name] = $column;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function loadColumnSchema($info)
    {
        $column = $this->createColumnSchema();

        $column->name = $info['Column_name'];
        $column->allowNull = $info['Nullable'];
        $column->dbType = $info['Type'];
        $column->enumValues = [];
        $column->isPrimaryKey = $info['Is_primary'];
        $column->autoIncrement = $info['Is_identity'];
        $column->unsigned = false;
        $column->comment = '';
        $column->size = (int) $info['Length'];
        $column->precision = (int) $info['Prec'];
        $column->scale = (int) $info['Scale'];

        $isBit = $column->dbType === 'bit';
        if ($column->size === 1 && ($column->dbType === 'tinyint' || $isBit)) {
            $column->type = 'boolean';
        } elseif ($isBit && $column->size > 32) {
            $column->type = 'bigint';
        } elseif ($isBit && $column->size === 32) {
            $column->type = 'integer';
        } elseif (isset($this->typeMap[$column->dbType])) {
            $column->type = $this->typeMap[$column->dbType];
        } else {
            $column->type = self::TYPE_STRING;
        }

        $column->phpType = $this->getColumnPhpType($column);

        $default = $info['Column_default'];
        if (!$column->isPrimaryKey && ($column->type !== 'timestamp' || $default !== 'CURRENT_TIMESTAMP')) {
            $column->defaultValue = $column->phpTypecast($default);
        }

        return $column;
    }

    /**
     * @inheritdoc
     */
    protected function findForeignKeys($table)
    {
        $table->foreignKeys = $this->constraintLoader->foreignKeys;
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