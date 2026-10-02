<?php

declare(strict_types=1);

namespace OCA\SmartCook\Controller;

use OCA\SmartCook\Db\TaxonomyRepository;
use OCA\SmartCook\Service\UserContext;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class TaxonomyController extends BaseController {
    public function __construct(IRequest $request, LoggerInterface $logger, private TaxonomyRepository $taxonomy, private UserContext $userContext) {
        parent::__construct($request, $logger);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/taxonomy')]
    public function list(): JSONResponse {
        return $this->respond(fn (): array => $this->taxonomy->listForUser($this->userContext->userId()));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/taxonomy/manage')]
    public function managed(): JSONResponse {
        return $this->respond(fn (): array => [
            'taxonomy' => $this->taxonomy->listManagedForUser($this->userContext->userId()),
            'recipes' => $this->taxonomy->listRecipeChoicesForUser($this->userContext->userId()),
        ]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/taxonomy/{kind}')]
    public function add(string $kind): JSONResponse {
        return $this->respond(fn (): array => ['item' => $this->taxonomy->addManaged(
            $this->userContext->userId(), $kind, (string)$this->request->getParam('name', '')
        )]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'PUT', url: '/taxonomy/ingredients/{id}')]
    public function updateIngredient(int $id): JSONResponse {
        $category = $this->request->getParam('category');
        $substitutes = $this->request->getParam('substitutes');
        return $this->respond(fn (): array => ['item' => $this->taxonomy->updateIngredient(
            $this->userContext->userId(), $id,
            $category,
            is_array($substitutes) ? $substitutes : []
        )]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/taxonomy/{kind}/{id}/assign')]
    public function assign(string $kind, int $id): JSONResponse {
        return $this->respond(fn (): array => ['changed' => $this->taxonomy->applyManagedToRecipes(
            $this->userContext->userId(), $kind, $id, $this->recipeIds()
        )]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/taxonomy/{kind}/{id}/remove')]
    public function remove(string $kind, int $id): JSONResponse {
        return $this->respond(fn (): array => ['changed' => $this->taxonomy->removeManagedFromRecipes(
            $this->userContext->userId(), $kind, $id, $this->recipeIds()
        )]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'DELETE', url: '/taxonomy/{kind}/{id}')]
    public function delete(string $kind, int $id): JSONResponse {
        return $this->respond(fn (): array => ['changed' => $this->taxonomy->deleteManaged($this->userContext->userId(), $kind, $id)]);
    }

    /** @return list<int> */
    private function recipeIds(): array {
        $value = $this->request->getParam('recipeIds', []);
        return is_array($value) ? array_map('intval', $value) : [];
    }
}
