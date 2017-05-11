<?php

namespace skiptirengu\mssql\tests\unit;

use PHPUnit\Framework\TestCase;
use skiptirengu\mssql\ConstraintLoader;
use yii\db\ColumnSchema;

class ConstraintLoaderTest extends TestCase
{
    public function defaultValuesProvider()
    {
        return [
            [
                [['constraint_type' => 'DEFAULT on column time', 'constraint_keys' => '(\'2002-01-01 00:00:00\')']],
                ['time' => '2002-01-01 00:00:00']
            ],
            [
                [['constraint_type' => 'DEFAULT on column space col', 'constraint_keys' => '(\'1\')']],
                ['space col' => '1']
            ],
            [
                [['constraint_type' => 'DEFAULT on column space col2 num', 'constraint_keys' => '(\'something\')']],
                ['space col2 num' => 'something']
            ],
            [
                [['constraint_type' => 'DEFAULT on column Under_score', 'constraint_keys' => '(\'1.23\')']],
                ['Under_score' => '1.23']
            ],
            [
                [['constraint_type' => 'DEFAULT on column column_column', 'constraint_keys' => '(\'42.42\')']],
                ['column_column' => '42.42']
            ],
            [
                [['constraint_type' => 'DEFAULT on column column column column', 'constraint_keys' => '(NULL)']],
                ['column column column' => '(NULL)']
            ],
            [
                [['constraint_type' => 'DEFAULT on column new_line', 'constraint_keys' => "('new\nline')"]],
                ['new_line' => "new\nline"]
            ],
            [
                [['constraint_type' => 'DEFAULT on column integer_number', 'constraint_keys' => "((42))"]],
                ['integer_number' => '42']
            ],
            [
                [['constraint_type' => 'DEFAULT on column double_number', 'constraint_keys' => "((42.2))"]],
                ['double_number' => '42.2']
            ],
            [
                [
                    ['constraint_type' => 'DEFAULT on column foo', 'constraint_keys' => '(\'foo\')'],
                    ['constraint_type' => 'DEFAULT on column bar', 'constraint_keys' => '(\'bar\')'],
                ],
                ['foo' => 'foo', 'bar' => 'bar']
            ]
        ];
    }

    /**
     * @dataProvider defaultValuesProvider
     */
    public function testLoadExtractsDefaultValues($dataRows, $expected)
    {
        $loader = new ConstraintLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load($dataRows);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame($expected, $loader->defaultValues);
    }

    public function indexesProvider()
    {
        return [
            [
                [[
                    'constraint_type' => 'UNIQUE (non-clustered)',
                    'constraint_name' => 'UQ_cons_1', 'constraint_keys' => 'id'
                ]],
                ['UQ_cons_1' => ['id']]
            ],
            [
                [[
                    'constraint_type' => 'UNIQUE (non-clustered)',
                    'constraint_name' => 'UQ_cons_2', 'constraint_keys' => 'id, col1'
                ]],
                ['UQ_cons_2' => ['id', 'col1']]
            ],
            [
                [[
                    'constraint_type' => 'UNIQUE (clustered)',
                    'constraint_name' => 'UQ_cons_3', 'constraint_keys' => 'id, col1, col2, col3'
                ]],
                ['UQ_cons_3' => ['id', 'col1', 'col2', 'col3']]
            ],
            [
                [
                    ['constraint_type' => 'UNIQUE', 'constraint_name' => 'UQ_cons_4', 'constraint_keys' => 'one'],
                    ['constraint_type' => 'UNIQUE', 'constraint_name' => 'UQ_cons_5', 'constraint_keys' => 'one, two']
                ],
                ['UQ_cons_4' => ['one'], 'UQ_cons_5' => ['one', 'two']]
            ]
        ];
    }

    /**
     * @dataProvider indexesProvider
     */
    public function testLoadExtractsUniqueIndexes($dataRows, $expected)
    {
        $loader = new ConstraintLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load($dataRows);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame($expected, $loader->uniqueIndexes);
    }

    public function foreignKeyProvider()
    {
        return [
            [
                [[
                    'constraint_type' => 'FOREIGN KEY',
                    'constraint_name' => 'FK_my_fk1', 'constraint_keys' => 'local1_1'
                ], [
                    'constraint_type' => ' ',
                    'constraint_name' => ' ', 'constraint_keys' => 'REFERENCES testdb.dbo.tbl1 (for1_1)'
                ]],
                ['FK_my_fk1' => ['tbl1', 'local1_1' => 'for1_1']]
            ],
            [
                [[
                    'constraint_type' => 'FOREIGN KEY',
                    'constraint_name' => 'FK_my_fk2', 'constraint_keys' => 'local2_1, local2_2'
                ], [
                    'constraint_type' => ' ',
                    'constraint_name' => ' ', 'constraint_keys' => 'REFERENCES testdb.dbo.tbl2 (for2_1, for2_2)'
                ]],
                ['FK_my_fk2' => ['tbl2', 'local2_1' => 'for2_1', 'local2_2' => 'for2_2']]
            ],
            [
                [[
                    'constraint_type' => 'FOREIGN KEY',
                    'constraint_name' => 'FK_my_fk3', 'constraint_keys' => 'local3_ 1, local3_ 2'
                ], [
                    'constraint_type' => ' ',
                    'constraint_name' => ' ', 'constraint_keys' => 'REFERENCES tbl3 (for3_ 1, for3_2)'
                ]],
                ['FK_my_fk3' => ['tbl3', 'local3_ 1' => 'for3_ 1', 'local3_ 2' => 'for3_2']]
            ],
            [
                [[
                    'constraint_type' => 'FOREIGN KEY',
                    'constraint_name' => 'FK_my_fk4', 'constraint_keys' => 'local4_1, local4_2, local4_3'
                ], [
                    'constraint_type' => ' ',
                    'constraint_name' => ' ', 'constraint_keys' => 'REFERENCES dbo.tbl 4_ (for4_1, for4_2, for4_3)'
                ]],
                ['FK_my_fk4' => ['tbl 4_', 'local4_1' => 'for4_1', 'local4_2' => 'for4_2', 'local4_3' => 'for4_3']]
            ],
            [
                [[
                    'constraint_type' => 'FOREIGN KEY',
                    'constraint_name' => 'FK_my_fk5_1', 'constraint_keys' => 'local5_1'
                ], [
                    'constraint_type' => ' ',
                    'constraint_name' => ' ', 'constraint_keys' => 'REFERENCES tbl5 (for5_1)'
                ], [
                    'constraint_type' => 'FOREIGN KEY',
                    'constraint_name' => 'FK_my_fk5_2', 'constraint_keys' => 'local5_2'
                ], [
                    'constraint_type' => ' ',
                    'constraint_name' => ' ', 'constraint_keys' => 'REFERENCES tbl5 (for5_2)'
                ]],
                [
                    'FK_my_fk5_1' => ['tbl5', 'local5_1' => 'for5_1'],
                    'FK_my_fk5_2' => ['tbl5', 'local5_2' => 'for5_2']
                ]
            ]
        ];
    }

    /**
     * @dataProvider foreignKeyProvider
     */
    public function testLoadExtractsForeignKeys($dataRows, $expected)
    {
        $loader = new ConstraintLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load($dataRows);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame($expected, $loader->foreignKeys);
    }

    public function pksProvider()
    {
        return [
            [
                [['constraint_type' => 'PRIMARY KEY (clustered)', 'constraint_keys' => 'id, col1']],
                ['id', 'col1']
            ],
            [
                [['constraint_type' => 'PRIMARY KEY (clustered)', 'constraint_keys' => 'id2, col_ 2']],
                ['id2', 'col_ 2']
            ],
            [
                [
                    ['constraint_type' => 'PRIMARY KEY (clustered)', 'constraint_keys' => 'id3, col3'],
                    ['constraint_type' => 'PRIMARY KEY (clustered)', 'constraint_keys' => 'id4, col4'],
                ],
                ['id3', 'col3']
            ]
        ];
    }

    /**
     * @dataProvider pksProvider
     */
    public function testLoadExtractsPks($dataRows, $expected)
    {
        $loader = new ConstraintLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load($dataRows);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame($expected, $loader->tablePks);
    }

    public function testSetPrimaryKeyOnColumn()
    {
        $loader = new ConstraintLoader(['tablePks' => ['table_id']]);

        $column = new ColumnSchema();
        $column->name = 'table_id';
        $this->assertNull($column->isPrimaryKey);
        $loader->setIsPrimaryKeyOnColumn($column);
        $this->assertTrue($column->isPrimaryKey);

        $column = new ColumnSchema();
        $column->name = 'not_id';
        $this->assertNull($column->isPrimaryKey);
        $loader->setIsPrimaryKeyOnColumn($column);
        $this->assertNull($column->isPrimaryKey);
    }
}
