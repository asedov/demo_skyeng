<?php
declare(strict_types=1);

namespace tests\unit\Decorator;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use PHPUnit\Framework\TestCase;
use src\Decorator\DecoratorManager;
use src\ExtRestInterface;

/**
 * @package Tests\unit\Decorator
 */
final class DecoratorManagerTest extends TestCase
{
    /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->logger);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testMustThrowAnExceptionOnError(): void
    {
        $this->expectException(Exception::class);

        /** @var \src\ExtRestInterface|\PHPUnit\Framework\MockObject\MockObject $data */
        $data = $this->createMock(ExtRestInterface::class);
        $data->method('getResponse')->willThrowException(new \Exception(''));

        /** @var \Psr\Cache\CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        /** @var \Psr\Cache\CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);

        $obj = new DecoratorManager($data, $cache, $this->logger);
        $obj->getResponse([]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testMustLogCriticalOnException(): void
    {
        /** @var \src\ExtRestInterface|\PHPUnit\Framework\MockObject\MockObject $data */
        $data = $this->createMock(ExtRestInterface::class);
        $data->method('getResponse')->willThrowException(new \Exception(''));

        /** @var \Psr\Cache\CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        /** @var \Psr\Cache\CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);

        /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('critical')->withAnyParameters();

        $obj = new DecoratorManager($data, $cache, $logger);
        try {
            $obj->getResponse([]);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testMustReturnCachedResultOnHit(): void
    {
        $hit = ['a' => 'b'];

        /** @var \src\ExtRestInterface $data */
        $data = $this->createMock(ExtRestInterface::class);

        /** @var \Psr\Cache\CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn($hit);

        /** @var \Psr\Cache\CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);

        $obj = new DecoratorManager($data, $cache, $this->logger);
        $res = $obj->getResponse(['a' => 'b']);

        $this->assertEquals($hit, $res);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testMustCallDataProviderOnCacheMiss(): void
    {
        $req = ['a' => 'b'];

        /** @var \src\ExtRestInterface|\PHPUnit\Framework\MockObject\MockObject $data */
        $data = $this->createMock(ExtRestInterface::class);
        $data->expects($this->once())->method('getResponse')->with($this->equalTo($req));

        /** @var \Psr\Cache\CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->method('set')->willReturnSelf();
        $cacheItem->method('expiresAt')->willReturnSelf();

        /** @var \Psr\Cache\CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);

        $obj = new DecoratorManager($data, $cache, $this->logger);
        $obj->getResponse($req);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testMustPersistResultToCacheOnCacheMiss(): void
    {
        /** @var \src\ExtRestInterface|\PHPUnit\Framework\MockObject\MockObject $data */
        $data = $this->createMock(ExtRestInterface::class);
        $data->method('getResponse')->willReturn([]);

        /** @var \Psr\Cache\CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->method('set')->willReturnSelf();
        $cacheItem->method('expiresAt')->willReturnSelf();

        /** @var \Psr\Cache\CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);
        $cache->expects($this->once())->method('save')->with($this->equalTo($cacheItem));

        $obj = new DecoratorManager($data, $cache, $this->logger);
        $obj->getResponse(['a' => 'b']);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testMustLogWarningOnCacheError(): void
    {
        /** @var \src\ExtRestInterface|\PHPUnit\Framework\MockObject\MockObject $data */
        $data = $this->createMock(ExtRestInterface::class);
        $data->method('getResponse')->willReturn([]);

        /** @var \Psr\Cache\CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->method('set')->willReturnSelf();
        $cacheItem->method('expiresAt')->willReturnSelf();

        /** @var \Psr\Cache\CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($cacheItem);
        $cache->method('save')->willReturn(false);

        /** @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->withAnyParameters();

        $obj = new DecoratorManager($data, $cache, $logger);
        $obj->getResponse(['a' => 'b']);
    }
}
