<?php
declare(strict_types=1);

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\ExtRestInterface;

/**
 * @package src\Decorator
 */
final class DecoratorManager implements ExtRestInterface
{
    /** @var \src\ExtRestInterface */
    private $dataProvider;

    /** @var \Psr\Cache\CacheItemPoolInterface */
    private $cache;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param \src\ExtRestInterface             $dataProvider
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     * @param \Psr\Log\LoggerInterface          $logger
     */
    public function __construct(ExtRestInterface $dataProvider, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->dataProvider = $dataProvider;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $request): array
    {
        $cacheKey = $this->getCacheKey($request);

        // CacheItemPoolInterface::getItem() может выкинуть CacheItemInterface если передать неправильный ключик,
        // но getCacheKey() всегда возвращяет корректную не пустую строку, поэтому можно не ловить этот exception
        /** @noinspection PhpUnhandledExceptionInspection */
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return (array)$cacheItem->get();
        }

        try {
            $result = $this->dataProvider->getResponse($request);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            throw $e;
        }

        $cacheItem
            ->set($result)
            ->expiresAt(
                (new DateTime())->modify('+1 day')
            );

        if (!$this->cache->save($cacheItem)) {
            $this->logger->warning('Could not cache response');
        }

        return $result;
    }

    /**
     * @param array $input
     * @return string
     */
    public function getCacheKey(array $input): string
    {
        return md5((string)json_encode($input, JSON_UNESCAPED_UNICODE));
    }
}
