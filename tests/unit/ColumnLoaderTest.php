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
            'Column_name' => 'foreign_key2',
            'Type' => 'int',
            'Computed' => 'no',
            'Length' => '4',
            'Prec' => '10',
            'Scale' => '0',
            'Nullable' => 'no',
            'TrimTrailingBlanks' => '(n/a)',
            'FixedLenNullInSource' => '(n/a)',
            'Collation' => '[NULL]',
        ];
        $expected = [
            'Column_default' => null,
            'Is_identity' => null,
            'Is_primary' => null,
            'Column_name' => 'foreign_key2',
            'Type' => 'int',
            'Computed' => 'no',
            'Length' => '4',
            'Prec' => '10',
            'Scale' => '0',
            'Nullable' => false,
            'TrimTrailingBlanks' => '(n/a)',
            'FixedLenNullInSource' => '(n/a)',
            'Collation' => '[NULL]',
        ];
        $loader = new ColumnLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load([$dataRow]);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame(['foreign_key2' => $expected], $loader->tableColumns);
    }

    public function testSetIdentityColumn()
    {
        $loader = new ColumnLoader([
            'tableColumns' => [
                'id' => ['Column_name' => 'id', 'Type' => 'int', 'Nullable' => 'no', 'Is_identity' => null]
            ]
        ]);
        $this->assertNull($loader->tableColumns['id']['Is_identity']);
        $identityLoader = new IdentityLoader();
        $identityLoader->identityColumn = 'id';
        $loader->setIdentityColumn($identityLoader);
        $this->assertTrue($loader->tableColumns['id']['Is_identity']);
    }

    public function testSetDefaultValuesForColumns()
    {
        $loader = new ColumnLoader([
            'tableColumns' => [
                'id' => ['Column_name' => 'id', 'Type' => 'int', 'Column_default' => null],
                'int_col' => ['Column_name' => 'int_col', 'Type' => 'int', 'Column_default' => null],
            ]
        ]);
        $this->assertNull($loader->tableColumns['id']['Column_default']);
        $this->assertNull($loader->tableColumns['int_col']['Column_default']);
        $constraintLoader = new ConstraintLoader([
            'defaultValues' => ['id' => 123, 'int_col' => 42]
        ]);
        $loader->setDefaultValuesForColumns($constraintLoader);
        $this->assertSame(123, $loader->tableColumns['id']['Column_default']);
        $this->assertSame(42, $loader->tableColumns['int_col']['Column_default']);
    }

    public function testSetPrimaryKeyOnColumn()
    {
        $loader = new ColumnLoader([
            'tableColumns' => [
                'table_id' => [
                    'Column_name' => 'table_id',
                    'Is_primary' => null,
                    'Nullable' => 'yes'
                ],
                'not_id' => [
                    'Column_name' => 'not_id',
                    'Is_primary' => null,
                    'Nullable' => 'yes'
                ],
                'id' => [
                    'Column_name' => 'id',
                    'Is_primary' => null,
                    'Nullable' => 'yes'
                ],
            ]
        ]);
        $this->assertNull($loader->tableColumns['table_id']['Is_primary']);
        $this->assertNull($loader->tableColumns['not_id']['Is_primary']);
        $this->assertNull($loader->tableColumns['id']['Is_primary']);
        $loader->setPrimaryKeys(new ConstraintLoader(['tablePks' => ['table_id', 'id']]));
        $this->assertTrue($loader->tableColumns['table_id']['Is_primary']);
        $this->assertTrue($loader->tableColumns['id']['Is_primary']);
        $this->assertNull($loader->tableColumns['not_id']['Is_primary']);
    }
}
