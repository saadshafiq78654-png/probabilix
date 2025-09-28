<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api;

use Ai\Infrastructure\Services\OpenAi\CompletionService as OpenAiCompletionService;
use Ai\Domain\ValueObjects\Model;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Services\ModelRegistry;

#[Route(path: '/guest/chat', method: RequestMethod::POST)]
class GuestChatApi extends GuestApi implements RequestHandlerInterface
{
    private const GUEST_CREDITS = 10;
    private const CREDIT_COST_PER_MESSAGE = 1;

    public function __construct(
        private OpenAiCompletionService $openai,
        private ModelRegistry $registry,

        #[Inject('option.features.chat.is_enabled')]
        private bool $isChatEnabled = true
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isChatEnabled) {
            return new JsonResponse([
                'error' => 'Chat feature is not enabled'
            ], StatusCode::SERVICE_UNAVAILABLE);
        }

        $payload = $request->getParsedBody();
        
        if (!isset($payload->message) || !isset($payload->guest_ip)) {
            return new JsonResponse([
                'error' => 'Message and guest IP are required'
            ], StatusCode::BAD_REQUEST);
        }

        $guestIp = $payload->guest_ip;
        $message = trim((string) $payload->message);
        
        if ($message === '') {
            return new JsonResponse([
                'error' => 'Message cannot be empty'
            ], StatusCode::BAD_REQUEST);
        }

        // Check and update credits for this IP
        $creditsUsed = $this->getGuestCreditsUsed($guestIp);
        $creditsRemaining = self::GUEST_CREDITS - $creditsUsed;

        if ($creditsRemaining <= 0) {
            return new JsonResponse([
                'error' => 'No credits remaining. Please sign up for more.',
                'credits_remaining' => 0
            ], StatusCode::PAYMENT_REQUIRED);
        }

        try {
            // Pick a default enabled OpenAI chat model from the registry
            $modelKey = $this->findDefaultOpenAiChatModel();
            if (!$modelKey) {
                return new JsonResponse([
                    'error' => 'OpenAI is not configured. Please contact admin.'
                ], StatusCode::SERVICE_UNAVAILABLE);
            }

            $generatedText = '';
            foreach ($this->openai->generateCompletion(new Model($modelKey), [
                'prompt' => $message,
                'temperature' => 1,
            ]) as $chunk) {
                // $chunk is Ai\Domain\ValueObjects\Chunk
                $generatedText .= (string) $chunk;
                // For non-streaming API, we just aggregate
            }

            // Update credits used
            $this->updateGuestCreditsUsed($guestIp, $creditsUsed + self::CREDIT_COST_PER_MESSAGE);
            $newCreditsRemaining = $creditsRemaining - self::CREDIT_COST_PER_MESSAGE;

            return new JsonResponse([
                'response' => trim($generatedText) !== '' ? $generatedText : 'No response generated.',
                'credits_remaining' => $newCreditsRemaining
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage() ?: 'Failed to generate response. Please try again.',
                'credits_remaining' => $creditsRemaining
            ], StatusCode::INTERNAL_SERVER_ERROR);
        }
    }

    private function getGuestCreditsUsed(string $ip): int
    {
        $cacheDir = sys_get_temp_dir() . '/guest_credits';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/' . md5($ip) . '.txt';
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $date = date('Y-m-d');
            
            // Reset credits daily
            if (isset($data['date']) && $data['date'] === $date) {
                return (int) ($data['credits_used'] ?? 0);
            }
        }
        
        return 0;
    }

    private function updateGuestCreditsUsed(string $ip, int $creditsUsed): void
    {
        $cacheDir = sys_get_temp_dir() . '/guest_credits';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/' . md5($ip) . '.txt';
        $data = [
            'date' => date('Y-m-d'),
            'credits_used' => $creditsUsed,
            'ip' => $ip
        ];
        
        file_put_contents($cacheFile, json_encode($data));
    }

    private function findDefaultOpenAiChatModel(): ?string
    {
        // ModelRegistry exposes a directory of services and models
        foreach ($this->registry['directory'] as $service) {
            if (($service['key'] ?? '') !== 'openai') {
                continue;
            }
            foreach ($service['models'] as $m) {
                // Use first enabled LLM model
                if (($m['type'] ?? null) === 'llm' && ($m['enabled'] ?? false)) {
                    return $m['key'] ?? null;
                }
            }
        }
        // Fallback to a common model key
        return 'gpt-4o-mini';
    }
} 