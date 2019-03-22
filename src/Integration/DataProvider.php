<?php
declare(strict_types=1);

namespace src\Integration;

use RuntimeException;
use src\ExtRestInterface;

/**
 * @package src\Integration
 */
final class DataProvider implements ExtRestInterface
{
    /** @var string */
    private $host;

    /** @var string|null */
    private $user;

    /** @var string|null */
    private $password;

    /**
     * @param string      $host
     * @param string|null $user
     * @param string|null $password
     */
    public function __construct(string $host, ?string $user = null, ?string $password = null)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $request): array
    {
        // returns a response from external service

        // Абсолютно бесполезный код, нужнен лишь для того, чтобы можно было написать тесты
        if (count($request) === 0) {
            throw new RuntimeException('Could not fetch data');
        }

        return $request;
    }
}
