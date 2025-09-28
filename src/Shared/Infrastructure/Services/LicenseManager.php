<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Services;

use Easy\Container\Attributes\Inject;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Option\Application\Commands\SaveOptionCommand;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;

class LicenseManager
{
    private const JWT_ALGORITHM = 'HS256';
    private const TOKEN_EXPIRY = 86400;

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private Dispatcher $dispatcher,

        #[Inject('config.dirs.root')]
        private string $rootDir,

        #[Inject('license')]
        private ?string $license = null,

        #[Inject('option.token')]
        private ?string $token = null,
    ) {}

    public function verify(): void
    {
        // Always skip verification
        return;
    }

    public function refresh(): void
    {
        // Always skip refresh
        return;
    }

    public function activate(string $licenseKey): void
    {
        // Always skip activation, just save a dummy license
        $this->saveLicense('BYPASS-LICENSE-' . time());
        return;
    }

    private function isTokenValid(): bool
    {
        // Always return true to skip token validation
        return true;
    }

    private function getLicense(): string
    {
        // Return dummy license
        return 'BYPASS-LICENSE';
    }

    private function validateLicense(string $license): void
    {
        // Never validate, always pass
        return;
    }

    private function saveLicense(string $license): void
    {
        file_put_contents($this->rootDir . '/LICENSE', $license);
    }

    private function saveToken(string $license): void
    {
        $payload = [
            'sub' => $license,
            'iat' => time(),
            'jti' => Uuid::uuid4()->toString(),
            'exp' => time() + self::TOKEN_EXPIRY,
        ];

        $jwt = JWT::encode($payload, env('JWT_TOKEN'), self::JWT_ALGORITHM);

        $cmd = new SaveOptionCommand('token', $jwt);
        $this->dispatcher->dispatch($cmd);
    }

    private function isLocalhost(): bool
    {
        $hosts = ['localhost:8000', 'localhost', '127.0.0.1:8000', '127.0.0.1', '::1'];
        
        if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $hosts)) {
            return true;
        }
        
        if (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], $hosts)) {
            return true;
        }
        
        return false;
    }
}
