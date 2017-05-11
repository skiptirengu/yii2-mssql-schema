<?php

namespace skiptirengu\mssql\tests\unit;

use PDO;
use PHPUnit\Framework\TestCase;
use skiptirengu\mssql\Schema;
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
        $this->app = new Application([
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

    public function testGetDb()
    {
        $this->assertInstanceOf(Connection::class, $this->app->getDb());
        $this->app->getDb()->open();
        $this->app->getDb()->close();
        $schema = $this->app->getDb()->getSchema()->getTableSchema('testschema1');
        $this->assertNotNull($schema);
    }
}
