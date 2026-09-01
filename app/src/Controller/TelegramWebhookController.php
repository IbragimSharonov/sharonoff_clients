<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TelegramWebhookController
{
    public function __construct(
        private readonly UserRepository                         $userRepository,
        #[Target('telegramClient')] private HttpClientInterface $telegramClient,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/telegram/webhook', name: 'app_telegram_webhook', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $secret = $request->headers->get('X-Telegram-Bot-Api-Secret-Token');

        if (!hash_equals((string) getenv('TELEGRAM_WEBHOOK_SECRET'), (string) $secret)) {
            return new JsonResponse(['ok' => false], 403);
        }

        $update = json_decode($request->getContent(), true);

        if (!is_array($update)) {
            return new JsonResponse(['ok' => false], 400);
        }

        $message = $update['message'] ?? null;
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? null;
        $telegramId = $update['message']['from']['id'] ?? null;

        $user = $telegramId ? $this->userRepository->findOneBy(['telegramId' => $telegramId]) : null;

        if (!$user) {
            $this->telegramClient->request('POST', '/sendMessage', [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => 'Кто вы?',
                    'reply_markup' => [
                        'keyboard' => [
                            [['text' => '👤 Я клиент']],
                            [['text' => '💅 Я мастер']],
                            [['text' => '🏢 Я владелец бизнеса']],
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true,
                    ],
                ],
            ]);
        }

        return new JsonResponse(['ok' => true]);
    }
}
