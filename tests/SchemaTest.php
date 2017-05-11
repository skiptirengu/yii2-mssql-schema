<?php

namespace skiptirengu\mssql\tests;

use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use skiptirengu\mssql\BaseLoader;
use skiptirengu\mssql\ColumnLoader;
use skiptirengu\mssql\ConstraintLoader;
use skiptirengu\mssql\IdentityLoader;
use skiptirengu\mssql\Schema;
use Yii;
use yii\db\DataReader;
use yii\db\TableSchema;
use yii\di\Container;

class SchemaTest extends TestCase
{
    public function testPrepareTableSchemaSkipsNoFieldsException()
    {
        $reader = $this->getMockBuilder(DataReaderMock::className())->disableOriginalConstructor()->setMethods(['readAll', 'close'])->getMock();
        $reader->expects($this->once())->method('readAll')->willThrowException(new PDOException('SQLSTATE[IMSSP]: The active result for the query contains no fields.'));
        $reader->expects($this->once())->method('close');
        $reader->init();

        $table = new TableSchema();
        $mock = $this->getMockBuilder(Schema::className())->setMethods(['createDataReader'])->getMock();
        $mock->expects($this->once())->method('createDataReader')->with($table)->willReturn($reader);
        $mock->prepareTableSchema($table);
    }

    public function testPrepareTableSchemaDoesNotSkipOtherPDOExceptions()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Another pdo exception');

        $reader = $this->getMockBuilder(DataReaderMock::className())->disableOriginalConstructor()->setMethods(['readAll', 'close'])->getMock();
        $reader->expects($this->once())->method('readAll')->willThrowException(new PDOException('Another pdo exception'));
        $reader->expects($this->once())->method('close');
        $reader->init();

        $table = new TableSchema();
        $mock = $this->getMockBuilder(Schema::className())->setMethods(['createDataReader'])->getMock();
        $mock->expects($this->once())->method('createDataReader')->with($table)->willReturn($reader);
        $mock->prepareTableSchema($table);
    }

    public function testPrepareTableSchemaDoesNotSkipOtherExceptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kaboom!');

        $reader = $this->getMockBuilder(DataReaderMock::className())->disableOriginalConstructor()->setMethods(['readAll', 'close'])->getMock();
        $reader->init();
        $reader->expects($this->once())->method('readAll')->willThrowException(new RuntimeException('Kaboom!'));
        $reader->expects($this->once())->method('close');

        $table = new TableSchema();
        $mock = $this->getMockBuilder(Schema::className())->setMethods(['createDataReader'])->getMock();
        $mock->expects($this->once())->method('createDataReader')->with($table)->willReturn($reader);
        $mock->prepareTableSchema($table);
    }

    public function testExtractData()
    {
        Yii::$container = new Container();
        Yii::$container->set(ColumnLoader::class, LoaderMock::class);
        Yii::$container->set(IdentityLoader::class, LoaderMock::class);
        Yii::$container->set(ConstraintLoader::class, LoaderMock::class);

        $schema = new Schema();
        $schema->createLoaders();
        $this->assertFalse($schema->extractData([['Name' => 'name']]));
        $this->assertFalse($schema->extractData([['Column_name' => 'colname']]));
        $this->assertInstanceOf(LoaderMock::class, $schema->columnLoader);
        $this->assertFalse($schema->extractData([['Identity' => 'id']]));
        $this->assertInstanceOf(LoaderMock::class, $schema->identityLoader);
        $this->assertTrue($schema->extractData([['constraint_type' => 'type']]));
        $this->assertInstanceOf(LoaderMock::class, $schema->constraintLoader);
    }
}

class LoaderMock extends BaseLoader
{
    public function doLoad(array $row)
    {
        //
    }
}

class DataReaderMock extends DataReader
{
    public $array = [];

    /**
     * @var \ArrayIterator
     */
    public $iterator;

    public function init()
    {
        $this->iterator = new \ArrayIterator($this->array);
    }

    public function close()
    {
        //
    }

    public function readAll()
    {
        $this->iterator->current();
    }

    public function nextResult()
    {
        $this->iterator->next();
        return $this->iterator->valid();
    }
}