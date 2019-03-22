<?php
declare(strict_types=1);

namespace src;

/**
 * @package src
 */
interface ExtRestInterface
{
    /**
     * @param array $request
     * @return array
     * @throws \Exception
     */
    public function getResponse(array $request): array;
}
