<?php

declare(strict_types=1);

namespace OCA\SmartCook\Dashboard;

use OCA\SmartCook\AppInfo\Application;
use OCA\SmartCook\Service\RecipeService;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;
use OCP\IL10N;
use OCP\IURLGenerator;

final class SmartCookWidget implements IAPIWidgetV2, IButtonWidget, IIconWidget {
    public function __construct(
        private RecipeService $recipes,
        private IL10N $l10n,
        private IURLGenerator $urlGenerator,
    ) {
    }

    public function getId(): string {
        return Application::APP_ID;
    }

    public function getTitle(): string {
        return $this->l10n->t('SmartCook');
    }

    public function getOrder(): int {
        return 30;
    }

    public function getIconClass(): string {
        return 'icon-smartcook';
    }

    public function getIconUrl(): string {
        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_ID, 'dashboard.png'),
        );
    }

    public function getUrl(): ?string {
        return $this->dashboardUrl();
    }

    public function load(): void {
    }

    public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
        $recipes = $this->recipes->list([], max(1, min(7, $limit)));
        $items = array_map(function (array $recipe): WidgetItem {
            $totalTime = (int)($recipe['totalTime'] ?? 0);
            $subtitle = $totalTime > 0
                ? $this->l10n->t('Total time') . ': ' . $totalTime . ' ' . $this->l10n->t('min')
                : $this->l10n->t('Recipe');

            return new WidgetItem(
                (string)$recipe['title'],
                $subtitle,
                $this->dashboardUrl() . '#/recipes/' . (int)$recipe['id'],
                $this->recipeIconUrl($recipe),
                (string)($recipe['updatedAt'] ?? $recipe['id']),
            );
        }, $recipes);

        return new WidgetItems(
            $items,
            $items === [] ? $this->l10n->t('No recipes found') : '',
        );
    }

    public function getWidgetButtons(string $userId): array {
        return [
            new WidgetButton(
                WidgetButton::TYPE_MORE,
                $this->dashboardUrl(),
                $this->l10n->t('View all'),
            ),
        ];
    }

    private function dashboardUrl(): string {
        return $this->urlGenerator->linkToRouteAbsolute('smartcook.page.index');
    }

    /** @param array<string, mixed> $recipe */
    private function recipeIconUrl(array $recipe): string {
        $imagePath = trim((string)($recipe['imagePath'] ?? ''));
        if (preg_match('/^media:(\d+)$/', $imagePath, $matches) === 1) {
            return $this->urlGenerator->linkToRouteAbsolute('smartcook.media.display', ['id' => (int)$matches[1]]);
        }
        if (preg_match('#^https?://#i', $imagePath) === 1) {
            return $imagePath;
        }

        $title = trim((string)($recipe['title'] ?? ''));
        $initial = mb_strtoupper(mb_substr($title !== '' ? $title : '?', 0, 1));
        $colors = ['#4f8fc0', '#b8644d', '#6f8f4e', '#8b6ea9', '#c28b36'];
        $color = $colors[abs((int)crc32($title)) % count($colors)];
        $label = htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="8" fill="' . $color . '"/><text x="32" y="41" fill="#fff" font-family="sans-serif" font-size="32" font-weight="600" text-anchor="middle">' . $label . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
