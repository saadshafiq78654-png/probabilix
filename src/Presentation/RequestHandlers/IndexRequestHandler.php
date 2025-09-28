<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Middlewares\ViewMiddleware;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Presentation\Response\ViewResponse;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/[locale:locale]?', method: RequestMethod::GET)]
class IndexRequestHandler extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.site.is_landing_page_enabled')]
        private bool $isLandingPageEnabled = true,

        #[Inject('option.features.chat.is_enabled')]
        private bool $isChatEnabled = true
    ) {
    }

    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        if (!$this->isLandingPageEnabled) {
            return new RedirectResponse('/app');
        }

        // New: Show chat interface directly for guest users
        if ($this->isChatEnabled) {
            // Get IP address for guest tracking
            $ip = $this->getClientIp($request);

        return new ViewResponse(
                '@theme/templates/guest-chat.twig',
            [
                    'guest_ip' => $ip,
                    'guest_credits' => 10 // Default 10 credits for guests
                ]
            );
        }

        // Fallback to original behavior if chat is disabled
        return new RedirectResponse('/learn-more');
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $serverParams = $request->getServerParams();
            if (!empty($serverParams[$header])) {
                $ip = trim(explode(',', $serverParams[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
