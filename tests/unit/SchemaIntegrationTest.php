<?php

namespace skiptirengu\mssql\tests\unit;

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
    protected static $conStr = 'sqlsrv:Server=localhost;Database=testdb';
    protected static $conUsr = 'sa';
    protected static $conPwd = 'Admin1234!';

    /**
     * @var Application
     */
    protected $app;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $sql = file_get_contents(TESTS_BASE_PATH . '/data/tables.sql');
        $pdo = new PDO(self::$conStr, self::$conUsr, self::$conPwd);
        foreach (explode('--', $sql) as $statement) {
            $pdo->exec($statement);
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
                    'dsn' => self::$conStr,
                    'username' => self::$conUsr,
                    'password' => self::$conPwd,
                    'charset' => 'UTF-8',
                    'schemaMap' => [
                        'dblib' => Schema::class,
                        'mssql' => Schema::class,
                        'sqlsrv' => Schema::class
                    ]
                ]
            ]
        ]);
    }

    public function testSchema()
    {
        $schema = $this->app->getDb()->getTableSchema('testschema1', true);
        $this->assertSame(
            ['foreign_key1', 'foreign_key2', 'varchar_col', 'varchar_col2', 'integer_col', 'decimal_col'],
            $schema->getColumnNames()
        );
        $this->assertSame(
            ['foreign_key1', 'foreign_key2'],
            $schema->primaryKey
        );
        $this->assertSame(null, $schema->getColumn('varchar_col')->defaultValue);
        $this->assertSame(1.2, $schema->getColumn('decimal_col')->defaultValue);
        $this->assertSame(0, $schema->getColumn('integer_col')->defaultValue);
        $this->assertSame('text', $schema->getColumn('varchar_col2')->defaultValue);
        $this->assertFalse($schema->getColumn('foreign_key1')->allowNull);
        $this->assertFalse($schema->getColumn('foreign_key2')->allowNull);
        $this->assertTrue($schema->getColumn('varchar_col')->allowNull);
        $this->assertTrue($schema->getColumn('varchar_col2')->allowNull);
        $this->assertTrue($schema->getColumn('integer_col')->allowNull);
        $this->assertFalse($schema->getColumn('decimal_col')->allowNull);
        $this->assertTrue($schema->getColumn('foreign_key1')->autoIncrement);
        $this->assertSame([], $schema->foreignKeys);

        //('1.23')('1.23')
    }
}
