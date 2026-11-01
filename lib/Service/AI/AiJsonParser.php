<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\AppInfo\Application;
use OCA\SmartCook\Exception\ImportException;
use Psr\Log\LoggerInterface;

final class AiJsonParser {
    public function __construct(private ?LoggerInterface $logger = null) {
    }

    /** @return array<string, mixed> */
    public function parse(string $text): array {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end >= $start) {
            $text = substr($text, $start, $end - $start + 1);
        }
        try {
            try {
                $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                if ($e->getCode() !== JSON_ERROR_CTRL_CHAR) {
                    throw $e;
                }
                // Models sometimes put a raw line break or tab inside a string, which JSON does not allow.
                $decoded = json_decode($this->escapeControlCharacters($text), true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (\JsonException $e) {
            // Only the shape of the answer, to see whether it was cut off or wrapped in prose; no recipe content beyond the edges.
            $this->logger?->warning('SmartCook AI answer is not valid JSON', [
                'app' => Application::APP_ID,
                'error' => $e->getMessage(),
                'length' => strlen($text),
                'start' => substr($text, 0, 120),
                'end' => substr($text, -160),
            ]);
            throw new ImportException('The AI provider did not return valid JSON', $e);
        }
        if (!is_array($decoded)) {
            throw new ImportException('The AI provider returned an invalid recipe');
        }
        return $decoded;
    }

    /** Escapes control characters that sit inside JSON strings; whitespace between tokens is left alone. */
    private function escapeControlCharacters(string $json): string {
        $result = '';
        $inString = false;
        $escaped = false;
        $length = strlen($json);
        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];
            if ($inString && ord($char) < 0x20) {
                $result .= match ($char) {
                    "\n" => '\\n',
                    "\r" => '\\r',
                    "\t" => '\\t',
                    default => sprintf('\\u%04x', ord($char)),
                };
                continue;
            }
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
            } elseif ($char === '"') {
                $inString = true;
            }
            $result .= $char;
        }
        return $result;
    }
}
