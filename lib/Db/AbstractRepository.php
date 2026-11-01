<?php

declare(strict_types=1);

namespace OCA\SmartCook\Db;

use OCA\SmartCook\Service\HouseholdService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

abstract class AbstractRepository {
    public function __construct(protected IDBConnection $db, protected HouseholdService $household) {
    }

    /** Rows of the user and of everybody in the user's household. */
    protected function ownedBy(IQueryBuilder $qb, string $column, string $userId): string {
        $peers = $this->household->peersOf($userId);
        return count($peers) === 1
            ? (string)$qb->expr()->eq($column, $qb->createNamedParameter($userId))
            : (string)$qb->expr()->in($column, $qb->createNamedParameter($peers, IQueryBuilder::PARAM_STR_ARRAY));
    }

    protected function insert(string $table, array $data): int {
        $qb = $this->db->getQueryBuilder();
        $values = [];
        foreach ($data as $column => $value) {
            $values[$column] = $this->parameter($qb, $value);
        }
        $qb->insert($table)->values($values)->executeStatement();
        return (int)$this->db->lastInsertId($table);
    }

    protected function update(string $table, int $id, array $data): void {
        if ($data === []) {
            return;
        }
        $qb = $this->db->getQueryBuilder();
        $qb->update($table);
        foreach ($data as $column => $value) {
            $qb->set($column, $this->parameter($qb, $value));
        }
        $qb->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    protected function deleteBy(string $table, string $column, int|string $value): void {
        $qb = $this->db->getQueryBuilder();
        $type = is_int($value) ? IQueryBuilder::PARAM_INT : IQueryBuilder::PARAM_STR;
        $qb->delete($table)
            ->where($qb->expr()->eq($column, $qb->createNamedParameter($value, $type)))
            ->executeStatement();
    }

    protected function fetchOne(IQueryBuilder $qb): ?array {
        $result = $qb->executeQuery();
        try {
            $row = $result->fetch();
            return is_array($row) ? $row : null;
        } finally {
            $result->closeCursor();
        }
    }

    /** @return list<array<string, mixed>> */
    protected function fetchAll(IQueryBuilder $qb): array {
        $result = $qb->executeQuery();
        $rows = [];
        try {
            while (($row = $result->fetch()) !== false) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        } finally {
            $result->closeCursor();
        }
        return $rows;
    }

    protected function parameter(IQueryBuilder $qb, mixed $value): mixed {
        if ($value === null) {
            return $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL);
        }
        if (is_bool($value)) {
            return $qb->createNamedParameter($value, IQueryBuilder::PARAM_BOOL);
        }
        if (is_int($value)) {
            return $qb->createNamedParameter($value, IQueryBuilder::PARAM_INT);
        }
        return $qb->createNamedParameter((string)$value, IQueryBuilder::PARAM_STR);
    }

    protected function encode(mixed $value): ?string {
        if ($value === null) {
            return null;
        }
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function decode(mixed $value, mixed $default = []): mixed {
        if (!is_string($value) || $value === '') {
            return $default;
        }
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $default;
        }
    }
}
