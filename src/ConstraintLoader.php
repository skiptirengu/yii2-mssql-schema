<?php

namespace skiptirengu\mssql;


class ConstraintLoader extends BaseLoader
{
    /**
     * @var array
     */
    public $tablePks = [];
    /**
     * @var array
     */
    public $tableForeignKeys = [];
    /**
     * @var array
     */
    public $defaultValues = [];
    /**
     * @var array
     */
    public $uniqueIndexes = [];

    public function doLoad(array $row)
    {
        $pksFound = false;
        foreach ($row as $constraint) {
            if ($this->extractDefaultValue($constraint)) {
                continue;
            }
            if ($this->extractUniqueIndex($constraint)) {
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
        if ($found = preg_match('/^DEFAULT on column ([\w ]+)$/', $constraint['constraint_type'], $matches)) {
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
            return preg_split(
                '/(,\s?)/', $constraint['constraint_keys'], 0, PREG_SPLIT_NO_EMPTY
            );
        }
        return false;
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