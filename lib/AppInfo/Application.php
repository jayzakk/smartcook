<?php

declare(strict_types=1);

namespace OCA\SmartCook\AppInfo;

use OCA\SmartCook\BackgroundJob\CleanupImportsJob;
use OCA\SmartCook\Dashboard\SmartCookWidget;
use OCA\SmartCook\Notification\Notifier;
use OCA\SmartCook\Search\RecipeSearchProvider;
use OCA\SmartCook\Settings\HouseholdAdminSettings;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'smartcook';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerDashboardWidget(SmartCookWidget::class);
        $context->registerSearchProvider(RecipeSearchProvider::class);
        $context->registerNotifierService(Notifier::class);
        $context->registerDeclarativeSettings(HouseholdAdminSettings::class);
    }

    public function boot(IBootContext $context): void {
        $context->injectFn(static function (IJobList $jobList): void {
            if (!$jobList->has(CleanupImportsJob::class, null)) {
                $jobList->add(CleanupImportsJob::class);
            }
        });
    }
}
