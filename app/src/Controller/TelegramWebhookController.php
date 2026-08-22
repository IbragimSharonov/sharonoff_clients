<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TelegramWebhookController
{
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

        if ($chatId && $text === '/start') {
            $token = getenv('TELEGRAM_BOT_TOKEN');

            $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => [
                    'chat_id' => $chatId,
                    'text' => 'Привет! Бот подключён к clients.sharonoff.com.',
                ],
            ]);

            curl_exec($ch);
        }

        return new JsonResponse(['ok' => true]);
    }
}
