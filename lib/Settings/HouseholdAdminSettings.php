<?php

declare(strict_types=1);

namespace OCA\SmartCook\Settings;

use OCA\SmartCook\Service\HouseholdService;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\IDeclarativeSettingsFormWithHandlers;

final class HouseholdAdminSettings implements IDeclarativeSettingsFormWithHandlers {
    private const FIELD = 'allowed_groups';

    public function __construct(
        private IL10N $l,
        private HouseholdService $household,
        private IGroupManager $groups,
    ) {
    }

    public function getSchema(): array {
        $options = [];
        foreach ($this->groups->search('', 500) as $group) {
            $options[] = ['name' => $group->getDisplayName(), 'value' => $group->getGID()];
        }
        return [
            'id' => 'smartcook-household',
            'priority' => 10,
            'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
            'section_id' => 'additional',
            'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL,
            'title' => $this->l->t('SmartCook households'),
            'description' => $this->l->t('People choose their household themselves in the SmartCook settings, from the groups they belong to. Here you can limit which groups are available. Leave it empty to allow every group. If a group is removed from the list, the people who chose it only see their own data until it is allowed again; nothing is deleted.'),
            'fields' => [
                [
                    'id' => self::FIELD,
                    'title' => $this->l->t('Groups that can be chosen as household'),
                    'type' => DeclarativeSettingsTypes::MULTI_SELECT,
                    'default' => [],
                    'options' => $options,
                ],
            ],
        ];
    }

    public function getValue(string $fieldId, IUser $user): mixed {
        return $fieldId === self::FIELD ? $this->household->allowedGroups() : null;
    }

    public function setValue(string $fieldId, mixed $value, IUser $user): void {
        if ($fieldId === self::FIELD && is_array($value)) {
            $this->household->setAllowedGroups(array_map('strval', $value));
        }
    }
}
