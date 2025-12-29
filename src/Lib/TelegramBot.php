<?php

/**
 * TelegramBot - Client per Telegram API
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
namespace LibreBot\Lib;

class TelegramBot
{
    private string $apiUrl;
    private Logger $logger;

    public function __construct(string $botToken, Logger $logger)
    {
        $this->apiUrl = "https://api.telegram.org/bot{$botToken}";
        $this->logger = $logger;
    }

    /**
     * Invia messaggio Telegram
     */
    public function sendMessage(int $chatId, string $text, ?int $threadId = null): bool
    {
        // Escape special characters for MarkdownV2
        $escapedText = $this->escapeMarkdownV2($text);
        
        $params = [
            'chat_id' => $chatId,
            'text' => $escapedText,
            'parse_mode' => 'MarkdownV2'
        ];
        
        if ($threadId !== null) {
            $params['message_thread_id'] = $threadId;
        }
        
        $url = $this->apiUrl . '/sendMessage?' . http_build_query($params);
        
        $this->logger->debug("Sending Telegram message", [
            'chat_id' => $chatId, 
            'length' => strlen($escapedText)
        ]);
        
        $response = @file_get_contents($url);
        
        if ($response === false) {
            $this->logger->error("Failed to send Telegram message", ['chat_id' => $chatId]);
            return false;
        }
        
        $this->logger->debug("Telegram API response", ['response' => $response]);
        return true;
    }

    /**
     * Escape testo per MarkdownV2
     */
    private function escapeMarkdownV2(string $text): string
    {
        $specials = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($specials as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
    }

    /**
     * Ottieni aggiornamenti
     */
    public function getUpdates(int $offset = 0, int $timeout = 10): ?array
    {
        $url = "{$this->apiUrl}/getUpdates?timeout={$timeout}&offset={$offset}";
        $response = @file_get_contents($url);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }
}
