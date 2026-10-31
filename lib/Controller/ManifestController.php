<?php

declare(strict_types=1);

namespace OCA\SmartCook\Controller;

use OCA\SmartCook\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

final class ManifestController extends Controller {
    public function __construct(IRequest $request, private IURLGenerator $urlGenerator, private IL10N $l10n) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/manifest.webmanifest')]
    public function index(): JSONResponse {
        $response = new JSONResponse([
            'id' => 'smartcook',
            'name' => 'SmartCook',
            'short_name' => 'SmartCook',
            'description' => $this->l10n->t('Recipe intelligence'),
            'start_url' => $this->urlGenerator->linkToRoute('smartcook.page.index'),
            'scope' => $this->urlGenerator->linkToRoute('smartcook.page.index'),
            'display' => 'standalone',
            'background_color' => '#f7fafc',
            'theme_color' => '#4f8fc0',
            'icons' => [
                ['src' => $this->urlGenerator->linkTo(Application::APP_ID, 'img/app-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => $this->urlGenerator->linkTo(Application::APP_ID, 'img/app-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ]);
        $response->setHeaders(['Content-Type' => 'application/manifest+json']);
        return $response;
    }
}
