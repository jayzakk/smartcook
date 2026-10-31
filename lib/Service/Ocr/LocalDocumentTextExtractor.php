<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\Ocr;

use OCA\SmartCook\Exception\ImportException;

final class LocalDocumentTextExtractor implements DocumentTextExtractorInterface {
    public function supports(string $provider): bool {
        return $provider === 'local';
    }

    public function extract(string $path, string $mimeType, string $originalName, array $config): string {
        if (!is_file($path) || !is_readable($path)) {
            throw new ImportException('The uploaded document is not readable');
        }
        if (str_contains($mimeType, 'pdf') || str_ends_with(mb_strtolower($originalName), '.pdf')) {
            $binary = $this->binary((string)($config['pdfToTextPath'] ?? 'pdftotext'));
            return $this->run([$binary, '-layout', $path, '-']);
        }
        if (str_starts_with($mimeType, 'image/')) {
            $binary = $this->binary((string)($config['tesseractPath'] ?? 'tesseract'));
            $language = preg_replace('/[^a-zA-Z0-9_+\-]/', '', (string)($config['language'] ?? 'eng')) ?: 'eng';
            return $this->run([$binary, $path, 'stdout', '-l', $language]);
        }
        if (str_starts_with($mimeType, 'text/') || preg_match('/\.(?:txt|md|markdown|html?)$/i', $originalName) === 1) {
            $text = file_get_contents($path);
            return is_string($text) ? $text : throw new ImportException('Could not read the uploaded document');
        }
        throw new ImportException('The local extractor does not support this file type');
    }

    private function binary(string $binary): string {
        $binary = trim($binary);
        if ($binary === '' || preg_match('/^[A-Za-z0-9_.\/-]+$/', $binary) !== 1) {
            throw new ImportException('The configured local processor path is invalid');
        }
        return $binary;
    }

    /** @param list<string> $arguments */
    private function run(array $arguments): string {
        if (!function_exists('proc_open')) {
            throw new ImportException('Local command execution is disabled on this server');
        }
        $process = proc_open($arguments, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new ImportException('Could not start the local document processor');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0 || !is_string($stdout) || trim($stdout) === '') {
            throw new ImportException('Document extraction failed: ' . trim((string)$stderr));
        }
        return $stdout;
    }
}
