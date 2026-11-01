<?php

declare(strict_types=1);

namespace OCA\SmartCook\Controller;

use OCA\SmartCook\Service\SettingsService;
use OCA\SmartCook\Service\CoverImageSearchService;
use OCA\SmartCook\Service\HouseholdService;
use OCA\SmartCook\Service\UserContext;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class SettingsController extends BaseController {
    public function __construct(IRequest $request, LoggerInterface $logger, private SettingsService $settings, private CoverImageSearchService $coverImages, private UserContext $userContext, private HouseholdService $household) {
        parent::__construct($request, $logger);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/settings')]
    public function get(): JSONResponse {
        return $this->respond(fn (): array => $this->withHousehold($this->settings->get($this->userContext->userId())));
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'PUT', url: '/settings')]
    public function save(): JSONResponse {
        return $this->respond(fn (): array => $this->withHousehold($this->settings->save($this->userContext->userId(), $this->payload('settings'))));
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/settings/fill-missing-covers')]
    public function fillMissingCovers(): JSONResponse {
        return $this->respond(fn (): array => ['result' => $this->coverImages->fillMissing(10)]);
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    private function withHousehold(array $settings): array {
        $me = $this->userContext->userId();
        return [
            'settings' => $settings,
            'householdGroups' => $this->household->choosableGroups($this->userContext->groupIds()),
            'householdPeers' => array_values(array_diff($this->household->peersOf($me), [$me])),
        ];
    }
}
