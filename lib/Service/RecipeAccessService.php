<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service;

use OCA\SmartCook\Db\RecipeRepository;
use OCA\SmartCook\Db\ShareRepository;
use OCA\SmartCook\Exception\ForbiddenException;
use OCA\SmartCook\Exception\NotFoundException;

final class RecipeAccessService {
    public function __construct(
        private RecipeRepository $recipes,
        private ShareRepository $shares,
        private UserContext $userContext,
        private HouseholdService $household,
    ) {
    }

    /** @return list<int> */
    public function accessibleRecipeIds(): array {
        return $this->shares->accessibleRecipeIds($this->userContext->userId(), $this->userContext->groupIds());
    }

    /** @return array<string, mixed> */
    public function readable(int $recipeId, bool $detailed = true): array {
        $recipe = $detailed ? $this->recipes->findDetailed($recipeId) : $this->recipes->find($recipeId);
        if ($recipe === null) {
            throw new NotFoundException('Recipe not found');
        }
        if ($this->household->sameHousehold((string)$recipe['ownerId'], $this->userContext->userId())) {
            return $recipe;
        }
        $permission = $this->shares->permissions($recipeId, $this->userContext->userId(), $this->userContext->groupIds());
        if (($permission & ShareRepository::PERMISSION_READ) === 0) {
            throw new ForbiddenException('You cannot read this recipe');
        }
        return $recipe;
    }

    /** @return array<string, mixed> */
    public function editable(int $recipeId): array {
        $recipe = $this->readable($recipeId);
        if ($this->household->sameHousehold((string)$recipe['ownerId'], $this->userContext->userId())) {
            return $recipe;
        }
        $permission = $this->shares->permissions($recipeId, $this->userContext->userId(), $this->userContext->groupIds());
        if (($permission & ShareRepository::PERMISSION_UPDATE) === 0) {
            throw new ForbiddenException('You cannot edit this recipe');
        }
        return $recipe;
    }

    /** @return array<string, mixed> */
    public function owned(int $recipeId): array {
        $recipe = $this->readable($recipeId);
        if (!$this->household->sameHousehold((string)$recipe['ownerId'], $this->userContext->userId())) {
            throw new ForbiddenException('Only the owner can perform this operation');
        }
        return $recipe;
    }
}
