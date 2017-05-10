<?php

namespace skiptirengu\mssql;

class IdentityLoader extends BaseLoader
{
    /**
     * The identity column name, null if the table doesn't have an identity column.
     *
     * @var string|null
     */
    public $identityColumn = null;

    /**
     * @inheritdoc
     */
    public function doLoad(array $row)
    {
        if (($identity = reset($row)) !== false && !empty($identity)) {
            $this->identityColumn = $identity['Identity'];
        }
    }
}