<?php
declare(strict_types=1);

namespace tests\unit\Integration;

use Exception;
use PHPUnit\Framework\TestCase;
use src\Integration\DataProvider;

/**
 * @package Tests\unit\Integration
 */
final class DataProviderTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testMustThrowAnExceptionOnError(): void
    {
        $this->expectException(Exception::class);

        $obj = new DataProvider('');
        $obj->getResponse([]);
    }

    /**
     * @throws \Exception
     */
    public function testResponseMustBeEqualToRequest(): void
    {
        $req = ['a' => 'b'];

        $obj = new DataProvider('');
        $res = $obj->getResponse($req);

        $this->assertEquals($req, $res);
    }
}
