<?php

namespace skiptirengu\mssql\tests;

use PHPUnit\Framework\TestCase;
use skiptirengu\mssql\ConstraintLoader;

class ConstraintLoaderTest extends TestCase
{
    public function testLoadExtractsDefaultValues()
    {
        $dataRows = [
            ['constraint_type' => 'DEFAULT on column time', 'constraint_keys' => '(\'2002-01-01 00:00:00\')'],
            ['constraint_type' => 'DEFAULT on column space col', 'constraint_keys' => '(\'1\')'],
            ['constraint_type' => 'DEFAULT on column space col2 num', 'constraint_keys' => '(\'something\')'],
            ['constraint_type' => 'DEFAULT on column Under_score', 'constraint_keys' => '(\'1.23\')'],
            ['constraint_type' => 'DEFAULT on column column_column', 'constraint_keys' => '(\'42.42\')'],
            ['constraint_type' => 'DEFAULT on column column column column', 'constraint_keys' => '(NULL)'],
        ];
        $expected = [
            'time' => '(\'2002-01-01 00:00:00\')',
            'space col' => '(\'1\')',
            'space col2 num' => '(\'something\')',
            'Under_score' => '(\'1.23\')',
            'column_column' => '(\'42.42\')',
            'column column column' => '(NULL)'
        ];
        $loader = new ConstraintLoader();
        $loader->load($dataRows);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame($expected, $loader->defaultValues);
    }

    public function testLoadExtractsUniqueIndexes()
    {
        $dataRows = [
            ['constraint_type' => 'UNIQUE (non-clustered)', 'constraint_name' => 'UQ_cons_1', 'constraint_keys' => 'id'],
            ['constraint_type' => 'UNIQUE (non-clustered)', 'constraint_name' => 'UQ_cons_2', 'constraint_keys' => 'id, col1'],
            ['constraint_type' => 'UNIQUE (clustered)', 'constraint_name' => 'UQ_cons_3', 'constraint_keys' => 'id, col1, col2, col3'],
        ];
        $expected = [
            'UQ_cons_1' => ['id'],
            'UQ_cons_2' => ['id', 'col1'],
            'UQ_cons_3' => ['id', 'col1', 'col2', 'col3']
        ];
        $loader = new ConstraintLoader();
        $loader->load($dataRows);
        $this->assertTrue($loader->isLoaded);
        $this->assertSame($expected, $loader->uniqueIndexes);
    }
}
