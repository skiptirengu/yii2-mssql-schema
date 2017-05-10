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
            $columnName = $column['Column_name'];
            $this->tableColumns[$columnName] = [
                'column_name' => $columnName,
                'is_nullable' => strtoupper($column['Nullable']),
                'data_type' => $column['Type'],
                'is_identity' => null,
                'comment' => '',
                'column_default' => null
            ];
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
            $this->tableColumns[$loader->identityColumn]['is_identity'] = 1;
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
            $this->tableColumns[$column]['column_default'] = $defaultValue;
        }
    }
}