<?php

namespace skiptirengu\mssql\tests\unit;

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
    public function testLoadTableSchemaFail()
    {
        $mock = $this->getMockBuilder(Schema::className())->setMethods(['prepareTableSchema'])->getMock();
        $mock->constraintLoader = new ConstraintLoader();
        $mock->columnLoader = new ColumnLoader();
        $mock->identityLoader = new IdentityLoader();
        $this->assertNull($mock->loadTableSchema('foo'));
    }

    public function testPrepareTableSchemaSkipsNoFieldsException()
    {
        $reader = $this->getMockBuilder(DataReader::className())->disableOriginalConstructor()->getMock();
        $reader->expects($this->once())->method('readAll')->willThrowException(new PDOException('SQLSTATE[IMSSP]: The active result for the query contains no fields.'));
        $reader->expects($this->once())->method('close');
        $reader->expects($this->once())->method('nextResult')->willReturn(false);

        $table = new TableSchema();
        $mock = $this->getMockBuilder(Schema::className())->setMethods(['createDataReader'])->getMock();
        $mock->expects($this->once())->method('createDataReader')->with($table)->willReturn($reader);
        $mock->prepareTableSchema($table);
    }

    public function testPrepareTableSchemaDoesNotSkipOtherPDOExceptions()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Another pdo exception');

        $reader = $this->getMockBuilder(DataReader::className())->disableOriginalConstructor()->getMock();
        $reader->expects($this->once())->method('readAll')->willThrowException(new PDOException('Another pdo exception'));
        $reader->expects($this->once())->method('close');
        $reader->expects($this->never())->method('nextResult')->willReturn(false);

        $table = new TableSchema();
        $mock = $this->getMockBuilder(Schema::className())->setMethods(['createDataReader'])->getMock();
        $mock->expects($this->once())->method('createDataReader')->with($table)->willReturn($reader);
        $mock->prepareTableSchema($table);
    }

    public function testPrepareTableSchemaDoesNotSkipOtherExceptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kaboom!');

        $reader = $this->getMockBuilder(DataReader::className())->disableOriginalConstructor()->getMock();
        $reader->expects($this->once())->method('readAll')->willThrowException(new RuntimeException('Kaboom!'));
        $reader->expects($this->once())->method('close');
        $reader->expects($this->never())->method('nextResult')->willReturn(false);

        $table = new TableSchema();
        $mock = $this->getMockBuilder(Schema::className())->setMethods(['createDataReader'])->getMock();
        $mock->expects($this->once())->method('createDataReader')->with($table)->willReturn($reader);
        $mock->prepareTableSchema($table);
    }

    public function testExtractData()
    {
        Yii::$container = new Container();
        Yii::$container->set(ColumnLoader::class, LoaderStub::class);
        Yii::$container->set(IdentityLoader::class, LoaderStub::class);
        Yii::$container->set(ConstraintLoader::class, LoaderStub::class);

        $schema = new Schema();
        $schema->createLoaders();
        $this->assertFalse($schema->extractData([['Name' => 'name']]));
        $this->assertFalse($schema->extractData([['Column_name' => 'colname']]));
        $this->assertInstanceOf(LoaderStub::class, $schema->columnLoader);
        $this->assertFalse($schema->extractData([['Identity' => 'id']]));
        $this->assertInstanceOf(LoaderStub::class, $schema->identityLoader);
        $this->assertTrue($schema->extractData([['constraint_type' => 'type']]));
        $this->assertInstanceOf(LoaderStub::class, $schema->constraintLoader);
    }
}

class LoaderStub extends BaseLoader
{
    public function doLoad(array $row)
    {
        //
    }
}