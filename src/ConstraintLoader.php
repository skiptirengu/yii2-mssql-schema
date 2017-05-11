<?php

namespace skiptirengu\mssql;

use ArrayIterator;
use yii\db\ColumnSchema;

class ConstraintLoader extends BaseLoader
{
    /**
     * @var array
     */
    public $tablePks = [];
    /**
     * @var array
     */
    public $defaultValues = [];
    /**
     * @var array
     */
    public $uniqueIndexes = [];
    /**
     * @var array
     */
    public $foreignKeys = [];

    /**
     * @inheritdoc
     */
    public function doLoad(array $row)
    {
        $pksFound = false;
        foreach ($row as $key => $constraint) {
            if (empty($constraint['constraint_type']) || empty(trim($constraint['constraint_type']))) {
                continue;
            }
            if ($this->extractDefaultValue($constraint)) {
                continue;
            }
            if ($this->extractUniqueIndex($constraint)) {
                continue;
            }
            if ($this->extractForeignKeys($key, $row)) {
                continue;
            }
            if ($pksFound === false) {
                $pksFound = $this->extractPks($constraint);
            }
        }
    }

    /**
     * Extract the default value for the given constraint, if any.
     *
     * @param array $constraint
     * @return bool Whether the constraint is a DEFAULT constraint
     */
    protected function extractDefaultValue(array $constraint)
    {
        if ($found = preg_match('/^DEFAULT on column ([\w\s]+)$/', $constraint['constraint_type'], $matches)) {
            $this->defaultValues[$matches[1]] = $constraint['constraint_keys'];
        }
        return $found;
    }

    /**
     * Extract the columns of the unique index for the given constraint, if any.
     *
     * @param $constraint
     * @return bool Whether the constraint is a UNIQUE constraint
     */
    protected function extractUniqueIndex(array $constraint)
    {
        if (($index = $this->extractConstraint($constraint, 'UNIQUE')) !== false) {
            $this->uniqueIndexes[$constraint['constraint_name']] = $index;
        }
        return $index !== false;
    }

    /**
     * Extract the constraint for the given type.
     *
     * @param array $constraint
     * @param string $type The constraint type
     * @return array|bool An array with the columns of the constraint, or false if
     * the constraint does not match the type.
     */
    protected function extractConstraint(array $constraint, $type)
    {
        if (strpos($constraint['constraint_type'], $type) === 0) {
            return $this->splitColumns($constraint['constraint_keys']);
        }
        return false;
    }

    /**
     * Split the comma separated columns, sanitazing white spaces.
     *
     * @param string $columns
     * @return array
     */
    protected function splitColumns($columns)
    {
        return preg_split('/(,\s?)/', $columns, 0, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Extract the columns of a given FOREIGN KEY constraint, if any.
     *
     * @param mixed $key
     * @param array $row
     * @return bool Whether the constraint is a FOREIGN KEY constraint.
     */
    protected function extractForeignKeys($key, array $row)
    {
        $iterator = new ArrayIterator($row);
        $iterator->seek($key);
        $current = $iterator->current();

        if ($found = (strpos($current['constraint_type'], 'FOREIGN KEY') === 0)) {
            $tableColumns = $this->splitColumns($current['constraint_keys']);
            $fkConstraint = $current['constraint_name'];

            // the next row points to the REFERENCES part of the constraint
            $iterator->next();
            $current = $iterator->current();
            preg_match(
                '/^REFERENCES (?:\w+\.)?(?:\w+\.)?([\w\s]+)\s\(([\w,\s]+)\)$/',
                $current['constraint_keys'],
                $matches
            );
            list(, $foreignTable, $foreignColumns) = $matches;
            $foreignColumns = $this->splitColumns($foreignColumns);

            $this->foreignKeys[$fkConstraint] = array_merge(
                [$foreignTable],
                array_combine($tableColumns, $foreignColumns)
            );
        }

        return $found;
    }

    /**
     * @param ColumnSchema $schema
     */
    public function setIsPrimaryKeyOnColumn(ColumnSchema $schema)
    {
        if (in_array($schema->name, $this->tablePks)) {
            $schema->isPrimaryKey = true;
        }
    }

    /**
     * Extract the primary key information for the given constraint, if any.
     *
     * @param array $constraint
     * @return bool Whether the constraint is a PK constraint
     */
    protected function extractPks(array $constraint)
    {
        if (($pks = $this->extractConstraint($constraint, 'PRIMARY KEY')) !== false) {
            $this->tablePks = $pks;
        }
        return $pks !== false;
    }
}