<?php

namespace skiptirengu\mssql;

use yii\base\Object;

abstract class BaseLoader extends Object
{
    /**
     * @var bool
     */
    public $isLoaded = false;

    /**
     * Loads the data onto the loader class.
     *
     * @param array $row
     * @return void
     */
    public function load(array $row)
    {
        $this->doLoad($row);
        $this->isLoaded = true;
    }

    /**
     * Loads the data row.
     *
     * @param array $row
     * @return void
     */
    public abstract function doLoad(array $row);
}