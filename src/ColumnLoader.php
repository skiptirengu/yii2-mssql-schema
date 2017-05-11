<?php

namespace skiptirengu\mssql;

class ColumnLoader extends BaseLoader
{
    /**
     * Array with the columns definition.
     *
     * @see \yii\db\mssql\Schema::loadColumnSchema()
     * @var array
     */
    public $tableColumns = [];

    public function doLoad(array $row)
    {
        foreach ($row as $column) {
            $this->tableColumns[$column['Column_name']] = array_merge(
                ['Column_default' => null, 'Is_identity' => null], $column
            );
        }
    }

    /**
     * Sets a value indicating whether the column is an identity.
     *
     * @param IdentityLoader $loader
     * @return void
     */
    public function setIdentityColumn(IdentityLoader $loader)
    {
        if (isset($this->tableColumns[$loader->identityColumn])) {
            $this->tableColumns[$loader->identityColumn]['Is_identity'] = 1;
        }
    }

    /**
     * Sets the default value for all columns.
     *
     * @param ConstraintLoader $loader
     * @return void
     */
    public function setDefaultValuesForColumns(ConstraintLoader $loader)
    {
        foreach ($loader->defaultValues as $column => $defaultValue) {
            $this->tableColumns[$column]['Column_default'] = $defaultValue;
        }
    }
}