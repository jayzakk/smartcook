<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service;

use OCA\SmartCook\Db\RecipeRepository;
use OCA\SmartCook\Exception\ValidationException;
use OCA\SmartCook\Service\Import\UrlFetcher;
use OCP\Http\Client\IClientService;

final class CoverImageSearchService {
    private const MAX_IMAGE_BYTES = 10_000_000;
    private const MAX_PREVIEW_BYTES = 750_000;

    public function __construct(
        private RecipeAccessService $access,
        private FileStorageService $files,
        private IClientService $clients,
        private RecipeRepository $recipes,
        private SettingsService $settings,
        private UserContext $userContext,
        private UrlFetcher $fetcher,
    ) {
    }

    /** @return array{processed:int,succeeded:int,failed:int,remaining:int} */
    public function fillMissing(int $limit = 10): array {
        $ids = $this->recipes->listMissingCoverIds($this->userContext->userId());
        $batch = array_slice($ids, 0, max(1, min(25, $limit)));
        $succeeded = 0;
        foreach ($batch as $recipeId) {
            try {
                $this->findAndStore($recipeId);
                $succeeded++;
            } catch (\Throwable) {
                // Continue with the next recipe; the summary lets the user retry any remaining gaps.
            }
        }
        return [
            'processed' => count($batch),
            'succeeded' => $succeeded,
            'failed' => count($batch) - $succeeded,
            'remaining' => max(0, count($ids) - count($batch)),
        ];
    }

    /** @return array<string, mixed> */
    public function findAndStore(int $recipeId): array {
        $candidates = $this->findCandidates($recipeId);
        return $this->storeCandidate($recipeId, (string)$candidates[0]['url'], (string)($candidates[0]['downloadUrl'] ?? ''));
    }

    /** @return list<array{url:string,thumbnailUrl:string,label:string,downloadUrl?:string}> */
    public function findCandidates(int $recipeId, ?string $query = null, int $page = 1): array {
        $recipe = $this->access->owned($recipeId);
        if (trim((string)($recipe['imagePath'] ?? '')) !== '') {
            throw new ValidationException('This recipe already has a cover image');
        }
        $title = trim($query ?? (string)($recipe['title'] ?? ''));
        if ($title === '') {
            throw new ValidationException('Add a recipe title before searching for an image');
        }
        if (mb_strlen($title) > 255) {
            throw new ValidationException('The image search text is too long');
        }
        $settings = $this->settings->get($this->userContext->userId(), true);
        return $this->findImageCandidates($title, $settings, max(1, min(10, $page)));
    }

    /** @return array<string, mixed> */
    public function storeCandidate(int $recipeId, string $url, string $unsplashDownloadUrl = ''): array {
        $recipe = $this->access->owned($recipeId);
        $currentCover = trim((string)($recipe['imagePath'] ?? ''));
        if ($currentCover !== '' && preg_match('#^https?://#i', $currentCover) !== 1) {
            throw new ValidationException('This recipe already has a cover image');
        }
        if ($unsplashDownloadUrl !== '') {
            $this->trackUnsplashDownload($unsplashDownloadUrl, trim((string)($this->settings->get($this->userContext->userId(), true)['unsplashAccessKey'] ?? '')));
        }
        $download = $this->fetcher->fetch($url, self::MAX_IMAGE_BYTES);
        $mime = mb_strtolower(trim(explode(';', $download['contentType'], 2)[0]));
        if (!in_array($mime, ['image/avif', 'image/gif', 'image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new ValidationException('The selected result is not an image');
        }
        $extension = match ($mime) { 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif', default => 'jpg' };
        $tmp = tempnam(sys_get_temp_dir(), 'smartcook-cover-');
        if ($tmp === false || file_put_contents($tmp, $download['body']) === false) {
            throw new ValidationException('The image could not be prepared for storage');
        }
        try {
            return $this->files->upload($recipeId, [
                'tmp_name' => $tmp,
                'name' => 'cover-' . time() . '.' . $extension,
                'type' => $mime,
                'size' => strlen($download['body']),
                'error' => UPLOAD_ERR_OK,
            ], 'image', 'Cover image');
        } finally {
            @unlink($tmp);
        }
    }

    /** @return array{content:string,mime:string} */
    public function previewCandidate(int $recipeId, string $url): array {
        $this->access->owned($recipeId);
        $download = $this->fetcher->fetch($url, self::MAX_PREVIEW_BYTES);
        $mime = mb_strtolower(trim(explode(';', $download['contentType'], 2)[0]));
        if (!in_array($mime, ['image/avif', 'image/gif', 'image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new ValidationException('The selected preview is not an image');
        }
        return ['content' => $download['body'], 'mime' => $mime];
    }

    /** @param array<string, mixed> $settings */
    private function findImageCandidates(string $title, array $settings, int $page): array {
        return match ((string)($settings['coverImageProvider'] ?? 'google')) {
            'pexels' => $this->pexelsImageCandidates($title, trim((string)($settings['pexelsApiKey'] ?? '')), $page),
            'unsplash' => $this->unsplashImageCandidates($title, trim((string)($settings['unsplashAccessKey'] ?? '')), $page),
            default => $this->googleImageCandidates($title, trim((string)($settings['googleImageSearchApiKey'] ?? '')), trim((string)($settings['googleImageSearchEngineId'] ?? '')), $page),
        };
    }

    /** @return list<array{url:string,thumbnailUrl:string,label:string}> */
    private function googleImageCandidates(string $title, string $apiKey, string $engineId, int $page): array {
        if ($apiKey === '' || $engineId === '') {
            throw new ValidationException('Configure Google image search in Settings before using this feature');
        }
        $response = $this->getJson('https://www.googleapis.com/customsearch/v1', [
            'cx' => $engineId, 'q' => $title, 'searchType' => 'image', 'num' => 10, 'start' => (($page - 1) * 10) + 1, 'safe' => 'active', 'imgType' => 'photo',
        ], ['X-Goog-Api-Key' => $apiKey]);
        return $this->candidates($response['items'] ?? [], static fn (array $item): array => ['url' => (string)($item['link'] ?? ''), 'thumbnailUrl' => (string)($item['image']['thumbnailLink'] ?? $item['link'] ?? ''), 'label' => (string)($item['title'] ?? '')], 'Google image search');
    }

    /** @return list<array{url:string,thumbnailUrl:string,label:string}> */
    private function pexelsImageCandidates(string $title, string $apiKey, int $page): array {
        if ($apiKey === '') {
            throw new ValidationException('Configure the Pexels API key in Settings before using this feature');
        }
        $response = $this->getJson('https://api.pexels.com/v1/search', ['query' => $title, 'per_page' => 10, 'page' => $page, 'orientation' => 'landscape'], ['Authorization' => $apiKey]);
        return $this->candidates($response['photos'] ?? [], static fn (array $item): array => ['url' => (string)($item['src']['large'] ?? ''), 'thumbnailUrl' => (string)($item['src']['medium'] ?? ''), 'label' => (string)($item['alt'] ?? $item['photographer'] ?? '')], 'Pexels image search');
    }

    /** @return list<array{url:string,thumbnailUrl:string,label:string,downloadUrl:string}> */
    private function unsplashImageCandidates(string $title, string $accessKey, int $page): array {
        if ($accessKey === '') {
            throw new ValidationException('Configure the Unsplash access key in Settings before using this feature');
        }
        $response = $this->getJson('https://api.unsplash.com/search/photos', ['query' => $title, 'per_page' => 10, 'page' => $page, 'orientation' => 'landscape'], ['Authorization' => 'Client-ID ' . $accessKey]);
        return $this->candidates($response['results'] ?? [], static fn (array $item): array => ['url' => (string)($item['urls']['regular'] ?? ''), 'thumbnailUrl' => (string)($item['urls']['small'] ?? ''), 'label' => (string)($item['alt_description'] ?? $item['user']['name'] ?? ''), 'downloadUrl' => (string)($item['links']['download_location'] ?? '')], 'Unsplash image search');
    }

    private function trackUnsplashDownload(string $endpoint, string $accessKey): void {
        $parts = parse_url($endpoint);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'api.unsplash.com' || !str_starts_with((string)($parts['path'] ?? ''), '/photos/')) {
            throw new ValidationException('The selected Unsplash download reference is invalid');
        }
        $response = $this->clients->newClient()->get($endpoint, [
            'headers' => ['Authorization' => 'Client-ID ' . $accessKey, 'User-Agent' => 'SmartCook/1.0 cover image search'],
            'timeout' => 25,
            'allow_redirects' => false,
            'http_errors' => false,
        ]);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            throw new ValidationException('Unsplash could not register the image download');
        }
    }

    /** @param array<string, scalar> $query @param array<string, string> $headers @return array<string, mixed> */
    private function getJson(string $endpoint, array $query, array $headers): array {
        $response = $this->clients->newClient()->get($endpoint, [
            'query' => $query,
            'headers' => array_merge(['Accept' => 'application/json', 'User-Agent' => 'SmartCook/1.0 cover image search'], $headers),
            'timeout' => 25,
            'http_errors' => false,
        ]);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new ValidationException('The configured image search provider could not complete the request');
        }
        $payload = json_decode((string)$response->getBody(), true);
        if (!is_array($payload)) {
            throw new ValidationException('The image search provider returned an invalid response');
        }
        return $payload;
    }

    /** @param array<string, mixed> $payload @param list<int|string> $path */
    private function resultUrl(array $payload, array $path, string $provider): string {
        return $this->requiredValue($payload, $path, $provider);
    }

    /** @param mixed $items @param callable(array<string, mixed>):array<string, string> $map @return list<array<string, string>> */
    private function candidates(mixed $items, callable $map, string $provider): array {
        $candidates = [];
        foreach (is_array($items) ? $items : [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $candidate = $map($item);
            if (trim((string)($candidate['url'] ?? '')) !== '' && trim((string)($candidate['thumbnailUrl'] ?? '')) !== '') {
                $candidates[] = $candidate;
            }
        }
        if ($candidates === []) {
            throw new ValidationException('No suitable image was found with ' . $provider);
        }
        return $candidates;
    }

    /** @param array<string, mixed> $payload @param list<int|string> $path */
    private function requiredValue(array $payload, array $path, string $provider): string {
        $value = $payload;
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                throw new ValidationException('No suitable image was found with ' . $provider);
            }
            $value = $value[$key];
        }
        $url = trim((string)$value);
        if ($url === '') {
            throw new ValidationException('No suitable image was found with ' . $provider);
        }
        return $url;
    }
}
