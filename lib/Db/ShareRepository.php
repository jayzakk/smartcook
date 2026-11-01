<?php

declare(strict_types=1);

namespace OCA\SmartCook\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\SmartCook\Service\HouseholdService;
use OCP\IDBConnection;

final class ShareRepository extends AbstractRepository {
    public const PERMISSION_READ = 1;
    public const PERMISSION_UPDATE = 2;

    public function __construct(IDBConnection $db, HouseholdService $household) {
        parent::__construct($db, $household);
    }

    /** @return list<array<string, mixed>> */
    public function listForRecipe(int $recipeId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_shares')
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->orderBy('created_at', 'DESC');
        return array_map(fn (array $row): array => $this->map($row), $this->fetchAll($qb));
    }

    /** @param array<string, mixed> $data */
    public function create(int $recipeId, string $createdBy, array $data): array {
        $type = (string)($data['type'] ?? 'user');
        if (!in_array($type, ['user', 'group', 'link'], true)) {
            throw new \InvalidArgumentException('Unsupported share type');
        }
        $shareWith = $type === 'link' ? null : trim((string)($data['shareWith'] ?? ''));
        if ($type !== 'link' && $shareWith === '') {
            throw new \InvalidArgumentException('Share target is required');
        }
        $permission = (int)($data['permission'] ?? self::PERMISSION_READ);
        $permission = ($permission & self::PERMISSION_UPDATE) !== 0
            ? self::PERMISSION_READ | self::PERMISSION_UPDATE
            : self::PERMISSION_READ;
        $token = $type === 'link' ? bin2hex(random_bytes(32)) : null;
        $password = trim((string)($data['password'] ?? ''));
        $expires = $data['expiresAt'] ?? null;

        $id = $this->insert('smartcook_shares', [
            'recipe_id' => $recipeId,
            'share_type' => $type,
            'share_with' => $shareWith,
            'permission' => $permission,
            'token' => $token,
            'password' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
            'expires_at' => $expires !== null && $expires !== '' ? (int)$expires : null,
            'created_by' => $createdBy,
            'created_at' => time(),
        ]);

        return $this->find($id) ?? throw new \RuntimeException('Share could not be reloaded');
    }

    public function deleteShare(int $shareId, int $recipeId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('smartcook_shares')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($shareId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->executeStatement();
    }

    /** @param list<string> $groupIds @return list<int> */
    public function accessibleRecipeIds(string $userId, array $groupIds): array {
        $qb = $this->db->getQueryBuilder();
        $conditions = [
            $qb->expr()->andX(
                $qb->expr()->eq('share_type', $qb->createNamedParameter('user')),
                $qb->expr()->eq('share_with', $qb->createNamedParameter($userId)),
            ),
        ];
        if ($groupIds !== []) {
            $params = array_map(fn (string $id): mixed => $qb->createNamedParameter($id), array_values(array_unique($groupIds)));
            $conditions[] = $qb->expr()->andX(
                $qb->expr()->eq('share_type', $qb->createNamedParameter('group')),
                $qb->expr()->in('share_with', $params),
            );
        }
        $qb->selectDistinct('recipe_id', 'expires_at')->from('smartcook_shares')
            ->where($qb->expr()->orX(...$conditions));

        $ids = [];
        $now = time();
        foreach ($this->fetchAll($qb) as $row) {
            if ($row['expires_at'] === null || (int)$row['expires_at'] >= $now) {
                $ids[] = (int)$row['recipe_id'];
            }
        }
        return array_values(array_unique($ids));
    }

    /** @param list<string> $groupIds */
    public function permissions(int $recipeId, string $userId, array $groupIds): int {
        $qb = $this->db->getQueryBuilder();
        $conditions = [
            $qb->expr()->andX(
                $qb->expr()->eq('share_type', $qb->createNamedParameter('user')),
                $qb->expr()->eq('share_with', $qb->createNamedParameter($userId)),
            ),
        ];
        if ($groupIds !== []) {
            $params = array_map(fn (string $id): mixed => $qb->createNamedParameter($id), array_values(array_unique($groupIds)));
            $conditions[] = $qb->expr()->andX(
                $qb->expr()->eq('share_type', $qb->createNamedParameter('group')),
                $qb->expr()->in('share_with', $params),
            );
        }
        $qb->select('permission', 'expires_at')->from('smartcook_shares')
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->orX(...$conditions));

        $permissions = 0;
        $now = time();
        foreach ($this->fetchAll($qb) as $row) {
            if ($row['expires_at'] === null || (int)$row['expires_at'] >= $now) {
                $permissions |= (int)$row['permission'];
            }
        }
        return $permissions;
    }

    public function findByToken(string $token): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_shares')
            ->where($qb->expr()->eq('share_type', $qb->createNamedParameter('link')))
            ->andWhere($qb->expr()->eq('token', $qb->createNamedParameter($token)))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        if ($row === null || ($row['expires_at'] !== null && (int)$row['expires_at'] < time())) {
            return null;
        }
        return $row;
    }

    public function verifyPublicPassword(array $rawShare, ?string $password): bool {
        if ($rawShare['password'] === null || $rawShare['password'] === '') {
            return true;
        }
        return is_string($password) && password_verify($password, (string)$rawShare['password']);
    }

    private function find(int $id): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_shares')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        return $row === null ? null : $this->map($row);
    }

    /** @return array<string, mixed> */
    private function map(array $row): array {
        return [
            'id' => (int)$row['id'],
            'recipeId' => (int)$row['recipe_id'],
            'type' => (string)$row['share_type'],
            'shareWith' => $row['share_with'],
            'permission' => (int)$row['permission'],
            'token' => $row['token'],
            'passwordProtected' => $row['password'] !== null && $row['password'] !== '',
            'expiresAt' => $row['expires_at'] !== null ? (int)$row['expires_at'] : null,
            'createdBy' => (string)$row['created_by'],
            'createdAt' => (int)$row['created_at'],
        ];
    }
}
