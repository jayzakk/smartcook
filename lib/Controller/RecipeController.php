<?php

declare(strict_types=1);

namespace OCA\SmartCook\Controller;

use OCA\SmartCook\Service\CoverImageSearchService;
use OCA\SmartCook\Service\DuplicateService;
use OCA\SmartCook\Service\RecipeService;
use OCA\SmartCook\Service\AI\RecipeRefinementService;
use OCA\SmartCook\Service\AI\RecipeTranslationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class RecipeController extends BaseController {
    public function __construct(IRequest $request, LoggerInterface $logger, private RecipeService $recipes, private DuplicateService $duplicates, private CoverImageSearchService $coverImages, private RecipeRefinementService $refinement, private RecipeTranslationService $translation) {
        parent::__construct($request, $logger);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/recipes')]
    public function list(): JSONResponse {
        return $this->respond(function (): array {
            $params = $this->request->getParams();
            $filters = [
                'search' => $params['search'] ?? '',
                'status' => $params['status'] ?? null,
                'difficulty' => $params['difficulty'] ?? null,
                'maxTime' => $params['maxTime'] ?? null,
                'maxCalories' => $params['maxCalories'] ?? null,
                'tags' => $params['tags'] ?? [],
                'categories' => $params['categories'] ?? [],
                'tools' => $params['tools'] ?? [],
                'ingredients' => $params['ingredients'] ?? [],
                'excludeAllergens' => $params['excludeAllergens'] ?? [],
                'sort' => $params['sort'] ?? 'updated_at',
                'direction' => $params['direction'] ?? 'DESC',
            ];
            if (array_key_exists('favorite', $params) && $params['favorite'] !== '') {
                $filters['favorite'] = filter_var($params['favorite'], FILTER_VALIDATE_BOOLEAN);
            }
            return ['recipes' => $this->recipes->list($filters, (int)($params['limit'] ?? 200))];
        });
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/recipes/{id}')]
    public function get(int $id): JSONResponse {
        return $this->respond(fn (): array => ['recipe' => $this->recipes->get($id)]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes')]
    public function create(): JSONResponse {
        return $this->respond(fn (): array => ['recipe' => $this->recipes->create($this->payload('recipe'))], Http::STATUS_CREATED);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'PUT', url: '/recipes/{id}')]
    public function update(int $id): JSONResponse {
        return $this->respond(fn (): array => ['recipe' => $this->recipes->update($id, $this->payload('recipe'))]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'DELETE', url: '/recipes/{id}')]
    public function delete(int $id): JSONResponse {
        return $this->respond(function () use ($id): array {
            $this->recipes->delete($id);
            return ['ok' => true];
        });
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/delete')]
    public function deleteSelected(): JSONResponse {
        $recipeIds = $this->recipeIds();
        return $this->respond(fn (): array => ['changed' => $this->recipes->deleteMany($recipeIds)]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/{id}/favorite')]
    public function favorite(int $id): JSONResponse {
        return $this->respond(function () use ($id): array {
            $favorite = filter_var($this->request->getParam('favorite', true), FILTER_VALIDATE_BOOLEAN);
            $this->recipes->setFavorite($id, $favorite);
            return ['ok' => true, 'favorite' => $favorite];
        });
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/{id}/cooked')]
    public function cooked(int $id): JSONResponse {
        return $this->respond(function () use ($id): array {
            $this->recipes->markCooked($id);
            return ['ok' => true];
        });
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/{id}/cover/search')]
    public function searchCover(int $id): JSONResponse {
        return $this->respond(function () use ($id): array {
            $query = trim((string)$this->request->getParam('query', ''));
            $page = max(1, (int)$this->request->getParam('page', 1));
            return ['candidates' => $this->coverImages->findCandidates($id, $query !== '' ? $query : null, $page)];
        });
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/recipes/{id}/cover/preview')]
    public function previewCover(int $id): DataDisplayResponse|JSONResponse {
        try {
            $preview = $this->coverImages->previewCandidate($id, (string)$this->request->getParam('url', ''));
            return new DataDisplayResponse($preview['content'], 200, [
                'Content-Type' => $preview['mime'],
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $e) {
            return $this->respond(static fn () => throw $e);
        }
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/{id}/cover')]
    public function setCover(int $id): JSONResponse {
        return $this->respond(fn (): array => ['media' => $this->coverImages->storeCandidate(
            $id,
            (string)$this->request->getParam('url', ''),
            (string)$this->request->getParam('downloadUrl', ''),
        )]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/recipes/{id}/versions')]
    public function versions(int $id): JSONResponse {
        return $this->respond(fn (): array => ['versions' => $this->recipes->versions($id)]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/{id}/restore/{revision}')]
    public function restore(int $id, int $revision): JSONResponse {
        return $this->respond(fn (): array => ['recipe' => $this->recipes->restore($id, $revision)]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/duplicates/check')]
    public function duplicates(): JSONResponse {
        return $this->respond(fn (): array => ['matches' => $this->duplicates->find($this->payload('recipe'))]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/{id}/merge')]
    public function merge(int $id): JSONResponse {
        return $this->respond(function () use ($id): array {
            $incoming = $this->payload('recipe');
            $incomingId = (int)$this->request->getParam('incomingRecipeId', 0);
            if ($incomingId > 0) {
                $incoming = $this->recipes->get($incomingId);
            }
            $merged = $this->duplicates->merge($this->recipes->get($id), $incoming);
            return ['recipe' => $this->recipes->update($id, $merged)];
        });
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/translate')]
    public function translate(): JSONResponse {
        $recipe = $this->request->getParam('recipe', []);
        return $this->respond(fn (): array => ['translation' => $this->translation->translate(
            is_array($recipe) ? $recipe : [],
            (string)$this->request->getParam('language', ''),
        )]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/refinement/analyze')]
    public function analyzeRefinement(): JSONResponse {
        $recipeIds = $this->recipeIds();
        return $this->respond(fn (): array => ['proposals' => count($recipeIds) === 1
            ? [$this->refinement->analyzeOne($recipeIds[0])]
            : $this->refinement->analyze($recipeIds)]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/recipes/refinement/apply')]
    public function applyRefinement(): JSONResponse {
        $proposals = $this->request->getParam('proposals', []);
        return $this->respond(fn (): array => ['changed' => $this->refinement->apply(is_array($proposals) ? $proposals : [])]);
    }

    /** @return list<int> */
    private function recipeIds(): array {
        $ids = $this->request->getParam('recipeIds', []);
        return is_array($ids) ? array_map('intval', $ids) : [];
    }
}
