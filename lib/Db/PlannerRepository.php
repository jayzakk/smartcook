<?php

declare(strict_types=1);

namespace OCA\SmartCook\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\SmartCook\Service\HouseholdService;
use OCP\IDBConnection;

final class PlannerRepository extends AbstractRepository {
    public function __construct(IDBConnection $db, HouseholdService $household) {
        parent::__construct($db, $household);
    }

    /** @return list<array<string, mixed>> */
    public function listRange(string $userId, string $from, string $to): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('m.*', 'r.title', 'r.image_path')->from('smartcook_meals', 'm')
            ->innerJoin('m', 'smartcook_recipes', 'r', $qb->expr()->eq('m.recipe_id', 'r.id'))
            ->where($this->ownedBy($qb, 'm.user_id', $userId))
            ->andWhere($qb->expr()->gte('m.meal_date', $qb->createNamedParameter($from)))
            ->andWhere($qb->expr()->lte('m.meal_date', $qb->createNamedParameter($to)))
            ->orderBy('m.meal_date', 'ASC')
            ->addOrderBy('m.slot', 'ASC');
        return array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'recipeId' => (int)$row['recipe_id'],
            'recipeTitle' => (string)$row['title'],
            'imagePath' => $row['image_path'],
            'date' => (string)$row['meal_date'],
            'slot' => (string)$row['slot'],
            'servings' => (int)$row['servings'],
            'notes' => $row['notes'],
            'createdAt' => (int)$row['created_at'],
            'updatedAt' => (int)$row['updated_at'],
        ], $this->fetchAll($qb));
    }

    /** @param array<string, mixed> $data */
    public function createMeal(string $userId, array $data): array {
        $now = time();
        $id = $this->insert('smartcook_meals', [
            'user_id' => $userId,
            'recipe_id' => (int)$data['recipeId'],
            'meal_date' => (string)$data['date'],
            'slot' => $this->validSlot((string)($data['slot'] ?? 'dinner')),
            'servings' => max(1, (int)($data['servings'] ?? 1)),
            'notes' => $this->nullString($data['notes'] ?? null),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->find($id, $userId) ?? throw new \RuntimeException('Meal could not be reloaded');
    }

    /** @param array<string, mixed> $data */
    public function updateMeal(int $id, string $userId, array $data): array {
        $existing = $this->find($id, $userId) ?? throw new \RuntimeException('Meal not found');
        $this->update('smartcook_meals', $id, [
            'meal_date' => (string)($data['date'] ?? $existing['date']),
            'slot' => $this->validSlot((string)($data['slot'] ?? $existing['slot'])),
            'servings' => max(1, (int)($data['servings'] ?? $existing['servings'])),
            'notes' => $this->nullString($data['notes'] ?? $existing['notes']),
            'updated_at' => time(),
        ]);
        return $this->find($id, $userId) ?? throw new \RuntimeException('Meal could not be reloaded');
    }

    public function deleteMeal(int $id, string $userId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('smartcook_meals')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($this->ownedBy($qb, 'user_id', $userId))
            ->executeStatement();
    }

    private function find(int $id, string $userId): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('m.*', 'r.title', 'r.image_path')->from('smartcook_meals', 'm')
            ->innerJoin('m', 'smartcook_recipes', 'r', $qb->expr()->eq('m.recipe_id', 'r.id'))
            ->where($qb->expr()->eq('m.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($this->ownedBy($qb, 'm.user_id', $userId))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        return $row === null ? null : [
            'id' => (int)$row['id'],
            'recipeId' => (int)$row['recipe_id'],
            'recipeTitle' => (string)$row['title'],
            'imagePath' => $row['image_path'],
            'date' => (string)$row['meal_date'],
            'slot' => (string)$row['slot'],
            'servings' => (int)$row['servings'],
            'notes' => $row['notes'],
            'createdAt' => (int)$row['created_at'],
            'updatedAt' => (int)$row['updated_at'],
        ];
    }

    private function validSlot(string $slot): string {
        return in_array($slot, ['breakfast', 'lunch', 'dinner', 'snack'], true) ? $slot : 'dinner';
    }

    private function nullString(mixed $value): ?string {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
