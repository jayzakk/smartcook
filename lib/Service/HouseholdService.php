<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service;

use OCA\SmartCook\AppInfo\Application;
use OCA\SmartCook\Exception\ValidationException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;

/**
 * A household is a Nextcloud group. Everybody picks for themselves (in the SmartCook settings, from the
 * groups they belong to) which group is their household. Members of the same household see and edit each
 * other's recipes, planner entries and shopping lists; every entry still belongs to the person who created
 * it, so leaving or switching the household simply hides the data again.
 */
final class HouseholdService {
    public const SETTING = 'household';
    public const ALLOWED_GROUPS = 'household_allowed_groups';

    /** @var array<string, list<string>> */
    private array $peers = [];

    public function __construct(
        private IConfig $config,
        private IGroupManager $groups,
        private IDBConnection $db,
    ) {
    }

    /**
     * Groups the administrator allows as household; an empty list means every group.
     *
     * @return list<string>
     */
    public function allowedGroups(): array {
        $stored = json_decode($this->config->getAppValue(Application::APP_ID, self::ALLOWED_GROUPS, '[]'), true);
        return is_array($stored) ? array_values(array_filter(array_map('strval', $stored), static fn (string $group): bool => $group !== '')) : [];
    }

    /** @param list<string> $groups */
    public function setAllowedGroups(array $groups): void {
        $clean = array_values(array_unique(array_filter(array_map(static fn ($group): string => trim((string)$group), $groups), static fn (string $group): bool => $group !== '')));
        $this->config->setAppValue(Application::APP_ID, self::ALLOWED_GROUPS, json_encode($clean, JSON_THROW_ON_ERROR));
        $this->peers = [];
    }

    public function isAllowed(string $group): bool {
        $allowed = $this->allowedGroups();
        return $allowed === [] || in_array($group, $allowed, true);
    }

    /**
     * What the user chose, whether or not it works at the moment (for showing a hint in the settings).
     * The setting holds a JSON list; a plain group name from an earlier version still reads as one group.
     *
     * @return list<string>
     */
    public function chosenGroups(string $userId): array {
        $raw = trim($this->config->getUserValue($userId, Application::APP_ID, self::SETTING, ''));
        if ($raw === '') {
            return [];
        }
        $decoded = $raw[0] === '[' ? json_decode($raw, true) : [$raw];
        return is_array($decoded) ? array_values(array_unique(array_filter(array_map('strval', $decoded), static fn (string $group): bool => $group !== ''))) : [];
    }

    /**
     * The household groups that work for the user: chosen, still a member, and allowed by the administrator.
     * A choice that does not work is kept, so it applies again once the group is back.
     *
     * @return list<string>
     */
    public function groupsOf(string $userId): array {
        return array_values(array_filter(
            $this->chosenGroups($userId),
            fn (string $group): bool => $this->isAllowed($group) && $this->groups->isInGroup($userId, $group),
        ));
    }

    /**
     * Everybody who shares a library with the user, including the user: for each household group of the user,
     * the people who also chose it and are still in it. A person can be in several households; their own
     * entries are visible to everybody from all of them.
     *
     * @return list<string>
     */
    public function peersOf(string $userId): array {
        return $this->peers[$userId] ??= $this->resolve($userId);
    }

    public function sameHousehold(string $ownerId, string $userId): bool {
        return $ownerId === $userId || in_array($ownerId, $this->peersOf($userId), true);
    }

    /**
     * Checks the value for the "household" setting: a group or a list of groups, each one of the user's own and
     * allowed. Returns what is stored (a JSON list, empty string for none).
     *
     * @param string|list<string> $groups
     */
    public function validate(string $userId, string|array $groups): string {
        $list = array_values(array_unique(array_filter(array_map(static fn ($group): string => trim((string)$group), (array)$groups), static fn (string $group): bool => $group !== '')));
        foreach ($list as $group) {
            if (!$this->groups->isInGroup($userId, $group) || !$this->isAllowed($group)) {
                throw new ValidationException('You can only choose your own groups as household', ['household' => 'Invalid group']);
            }
        }
        return $list === [] ? '' : json_encode($list, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** Groups the user may choose from: their own groups that are allowed. @param list<string> $userGroups @return list<string> */
    public function choosableGroups(array $userGroups): array {
        return array_values(array_filter($userGroups, fn (string $group): bool => $this->isAllowed($group)));
    }

    /** @return list<string> */
    private function resolve(string $userId): array {
        $members = [$userId];
        foreach ($this->groupsOf($userId) as $group) {
            foreach ($this->usersWhoChose($group) as $candidate) {
                if ($candidate !== $userId && !in_array($candidate, $members, true)
                    && in_array($group, $this->groupsOf($candidate), true)) {
                    $members[] = $candidate;
                }
            }
        }
        sort($members);
        return $members;
    }

    /** @return list<string> accounts whose setting mentions the group (verified by the caller) */
    private function usersWhoChose(string $group): array {
        $users = $this->config->getUsersForUserValue(Application::APP_ID, self::SETTING, $group);
        $qb = $this->db->getQueryBuilder();
        $qb->select('userid')->from('preferences')
            ->where($qb->expr()->eq('appid', $qb->createNamedParameter(Application::APP_ID)))
            ->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter(self::SETTING)))
            ->andWhere($qb->expr()->like('configvalue', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter(json_encode($group, JSON_UNESCAPED_UNICODE)) . '%')));
        $result = $qb->executeQuery();
        while (($row = $result->fetch()) !== false) {
            $users[] = (string)$row['userid'];
        }
        $result->closeCursor();
        return array_values(array_unique($users));
    }
}
