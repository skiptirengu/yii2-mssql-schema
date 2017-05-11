<?php

namespace skiptirengu\mssql\tests\unit;

use PHPUnit\Framework\TestCase;
use skiptirengu\mssql\IdentityLoader;

class IdentityLoaderTest extends TestCase
{
    public function testLoadNoIdentity()
    {
        $loader = new IdentityLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load([[]]);
        $this->assertNull($loader->identityColumn);
        $this->assertTrue($loader->isLoaded);
    }

    public function testLoadIdentity()
    {
        $loader = new IdentityLoader();
        $this->assertFalse($loader->isLoaded);
        $loader->load([['Identity' => 'id_column']]);
        $this->assertSame('id_column', $loader->identityColumn);
        $this->assertTrue($loader->isLoaded);
    }
}
