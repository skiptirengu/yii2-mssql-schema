<?php

namespace skiptirengu\mssql\tests\unit;

use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use skiptirengu\mssql\Schema;
use Yii;
use yii\console\Application;
use yii\db\Connection;

/**
 * @group integration
 */
class SchemaIntegrationTest extends TestCase
{
    /**
     * @var Application
     */
    protected $app;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $pdo = new PDO(getenv('CON_STRING'), getenv('CON_DBUSER'), getenv('CON_PASSWD'));
        $sql = explode('--', file_get_contents(TESTS_BASE_PATH . '/data/tables.sql'));
        foreach ($sql as $statement) {
            if (!$pdo->prepare($statement)->execute()) {
                throw new Exception("Unable to execute statement $statement");
            }
        }

    }

    public function setUp()
    {
        parent::setUp();
        Yii::$app = $this->app = new Application([
            'id' => 'schema-test-app',
            'basePath' => __DIR__,
            'components' => [
                'db' => [
                    'class' => Connection::class,
                    'dsn' => getenv('CON_STRING'),
                    'username' => getenv('CON_DBUSER'),
                    'password' => getenv('CON_PASSWD'),
                    'charset' => 'UTF-8',
                    'schemaMap' => [
                        'dblib' => Schema::class, 'mssql' => Schema::class, 'sqlsrv' => Schema::class
                    ]
                ]
            ]
        ]);
    }

    public function columnsProvider()
    {
        return [
            [[
                'column' => 'foreign_key1',
                'allowNull' => false,
                'phpType' => 'integer',
                'type' => 'integer',
                'dbType' => 'int',
                'autoIncrement' => true,
                'isPrimaryKey' => true,
                'defaultValue' => null
            ]],
            [[
                'column' => 'foreign_key2',
                'allowNull' => false,
                'phpType' => 'integer',
                'type' => 'integer',
                'dbType' => 'int',
                'autoIncrement' => null,
                'isPrimaryKey' => true,
                'defaultValue' => null
            ]],
            [[
                'column' => 'varchar_col',
                'allowNull' => true,
                'phpType' => 'string',
                'type' => 'string',
                'dbType' => 'varchar',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => null
            ]],
            [[
                'column' => 'varchar_col2',
                'allowNull' => true,
                'phpType' => 'string',
                'type' => 'string',
                'dbType' => 'varchar',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => 'text'
            ]],
            [[
                'column' => 'integer_col',
                'allowNull' => true,
                'phpType' => 'integer',
                'type' => 'bigint',
                'dbType' => 'bigint',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => 0
            ]],
            [[
                'column' => 'decimal_col',
                'allowNull' => false,
                'phpType' => 'string',
                'type' => 'decimal',
                'dbType' => 'decimal',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => '1.2'
            ]],
            [[
                'column' => 'float_col',
                'allowNull' => true,
                'phpType' => 'double',
                'type' => 'float',
                'dbType' => 'float',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => null
            ]],
            [[
                'column' => 'tiny_col',
                'allowNull' => false,
                'phpType' => 'integer',
                'type' => 'smallint',
                'dbType' => 'tinyint',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => null
            ]],
            [[
                'column' => 'bit_col',
                'allowNull' => false,
                'phpType' => 'boolean',
                'type' => 'boolean',
                'dbType' => 'bit',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => null
            ]],
            [[
                'column' => 'bin_col',
                'allowNull' => false,
                'phpType' => 'resource',
                'type' => 'binary',
                'dbType' => 'varbinary',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => '0xE240' //upper
            ]],
            [[
                'column' => 'geo_col',
                'allowNull' => false,
                'phpType' => 'string',
                'type' => 'string',
                'dbType' => 'geometry',
                'autoIncrement' => null,
                'isPrimaryKey' => null,
                'defaultValue' => '[geometry]::STGeomFromText(\'LINESTRING (100 100, 20 180, 180 180)\',(0))'
            ]],
        ];
    }

    /**
     * @dataProvider columnsProvider
     */
    public function testColumns($info)
    {
        $schema = $this->app->getDb()->getTableSchema('testschema1', true);
        $column = $schema->getColumn($info['column']);
        $this->assertSame($info['allowNull'], $column->allowNull, 'allowNull does not match');
        $this->assertSame($info['phpType'], $column->phpType, 'phpType does not match');
        $this->assertSame($info['type'], $column->type, 'type does not match');
        $this->assertSame($info['dbType'], $column->dbType, 'dbType does not match');
        $this->assertSame($info['autoIncrement'], $column->autoIncrement, 'autoIncrement does not match');
        $this->assertSame($info['isPrimaryKey'], $column->isPrimaryKey, 'isPrimaryKey does not match');
        $this->assertSame($info['defaultValue'], $column->defaultValue, 'defaultValue does not match');
    }

    public function testForeignKeys()
    {
        $schema = $this->app->getDb()->getTableSchema('testschema2');
        $this->assertSame(
            ['FK_testschema1' => [0 => 'testschema1', 'local_key1' => 'foreign_key1', 'local_key2' => 'foreign_key2']],
            $schema->foreignKeys
        );
    }

    public function testUniqueIndexes()
    {
        $schema = $this->app->getDb()->getSchema();
        $indexes = $schema->findUniqueIndexes($schema->getTableSchema('testschema2'));
        // indexes created with CREATE UNIQUE INDEX are not listed
        $this->assertCount(1, $indexes);
        $first = reset($indexes);
        $this->assertSame(['int_unique3'], array_values($first));
    }

    public function testView()
    {
        $schema = $this->app->getDb()->getTableSchema('testchemaview');

        $col = $schema->getColumn('geo_col');
        $this->assertSame('geo_col', $col->name);
        $this->assertSame('string', $col->phpType);
        $this->assertSame('geometry', $col->dbType);
        $this->assertNull($col->defaultValue);

        $col = $schema->getColumn('int_unique3');
        $this->assertSame('int_unique3', $col->name);
        $this->assertSame('integer', $col->phpType);
        $this->assertSame('int', $col->dbType);
        $this->assertNull($col->defaultValue);

        $this->assertSame([], $schema->foreignKeys);
    }
}
