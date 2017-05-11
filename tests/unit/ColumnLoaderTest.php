<?php

namespace skiptirengu\mssql\tests\unit;

use PHPUnit\Framework\TestCase;
use skiptirengu\mssql\ColumnLoader;
use skiptirengu\mssql\ConstraintLoader;
use skiptirengu\mssql\IdentityLoader;

class ColumnLoaderTest extends TestCase
{
    public function loadDataProvider()
    {
        return [
            [
                [['Column_name' => 'id', 'Type' => 'int', 'Nullable' => 'no']],
                ['id' => [
                    'column_name' => 'id', 'is_nullable' => 'NO',
                    'data_type' => 'int', 'is_identity' => null,
                    'comment' => '', 'column_default' => null
                ]]
            ],

            [
                [['Column_name' => 'int_col', 'Type' => 'int', 'Nullable' => 'no']],
                ['int_col' => [
                    'column_name' => 'int_col', 'is_nullable' => 'NO',
                    'data_type' => 'int', 'is_identity' => null,
                    'comment' => '', 'column_default' => null
                ]]
            ],

            [
                [['Column_name' => 'smallint_col', 'Type' => 'smallint', 'yes', 'Nullable' => 'yes']],
                ['smallint_col' => [
                    'column_name' => 'smallint_col', 'is_nullable' => 'YES',
                    'data_type' => 'smallint', 'is_identity' => null,
                    'comment' => '', 'column_default' => null
                ]]
            ]
        ];
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoadColumn($dataRow, $expected)
    {
        $loader = new ColumnLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load($dataRow);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame($expected, $loader->tableColumns);
    }

    public function testSetIdentityColumn()
    {
        $loader = new ColumnLoader();
        $loader->load([['Column_name' => 'id', 'Type' => 'int', 'Nullable' => 'no']]);
        $this->assertNull($loader->tableColumns['id']['is_identity']);

        $identityLoader = new IdentityLoader();
        $identityLoader->identityColumn = 'id';
        $loader->setIdentityColumn($identityLoader);
        $this->assertSame(1, $loader->tableColumns['id']['is_identity']);
    }

    public function testSetDefaultValuesForColumns()
    {
        $loader = new ColumnLoader();
        $loader->load([
            ['Column_name' => 'id', 'Type' => 'int', 'Nullable' => 'no'],
            ['Column_name' => 'int_col', 'Type' => 'int', 'Nullable' => 'no'],
        ]);
        $this->assertNull($loader->tableColumns['id']['column_default']);
        $this->assertNull($loader->tableColumns['int_col']['column_default']);

        $constraintLoader = new ConstraintLoader();
        $constraintLoader->defaultValues = [
            'id' => 123,
            'int_col' => 42
        ];
        $loader->setDefaultValuesForColumns($constraintLoader);
        $this->assertSame(123, $loader->tableColumns['id']['column_default']);
        $this->assertSame(42, $loader->tableColumns['int_col']['column_default']);
    }
}
