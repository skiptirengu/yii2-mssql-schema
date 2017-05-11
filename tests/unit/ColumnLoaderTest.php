<?php

namespace skiptirengu\mssql\tests\unit;

use PHPUnit\Framework\TestCase;
use skiptirengu\mssql\ColumnLoader;
use skiptirengu\mssql\ConstraintLoader;
use skiptirengu\mssql\IdentityLoader;

class ColumnLoaderTest extends TestCase
{
    public function testLoadColumn()
    {
        $dataRow = [
            'Column_name' => 'foreign_key2', 'Type' => 'int', 'Computed' => 'no', 'Length' => '4',
            'Prec' => '10', 'Scale' => '0', 'Nullable' => 'no', 'TrimTrailingBlanks' => '(n/a)',
            'FixedLenNullInSource' => '(n/a)', 'Collation' => '[NULL]',
        ];
        $expected = [
            'Column_default' => null, 'Is_identity' => null, 'Column_name' => 'foreign_key2',
            'Type' => 'int', 'Computed' => 'no', 'Length' => '4', 'Prec' => '10', 'Scale' => '0',
            'Nullable' => 'no', 'TrimTrailingBlanks' => '(n/a)',
            'FixedLenNullInSource' => '(n/a)', 'Collation' => '[NULL]',
        ];
        $loader = new ColumnLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load([$dataRow]);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame(['foreign_key2' => $expected], $loader->tableColumns);
    }

    public function testSetIdentityColumn()
    {
        $loader = new ColumnLoader();
        $loader->load([['Column_name' => 'id', 'Type' => 'int', 'Nullable' => 'no']]);
        $this->assertNull($loader->tableColumns['id']['Is_identity']);

        $identityLoader = new IdentityLoader();
        $identityLoader->identityColumn = 'id';
        $loader->setIdentityColumn($identityLoader);
        $this->assertSame(1, $loader->tableColumns['id']['Is_identity']);
    }

    public function testSetDefaultValuesForColumns()
    {
        $loader = new ColumnLoader();
        $loader->load([
            ['Column_name' => 'id', 'Type' => 'int', 'Nullable' => 'no'],
            ['Column_name' => 'int_col', 'Type' => 'int', 'Nullable' => 'no'],
        ]);
        $this->assertNull($loader->tableColumns['id']['Column_default']);
        $this->assertNull($loader->tableColumns['int_col']['Column_default']);

        $constraintLoader = new ConstraintLoader();
        $constraintLoader->defaultValues = [
            'id' => 123,
            'int_col' => 42
        ];
        $loader->setDefaultValuesForColumns($constraintLoader);
        $this->assertSame(123, $loader->tableColumns['id']['Column_default']);
        $this->assertSame(42, $loader->tableColumns['int_col']['Column_default']);
    }
}
