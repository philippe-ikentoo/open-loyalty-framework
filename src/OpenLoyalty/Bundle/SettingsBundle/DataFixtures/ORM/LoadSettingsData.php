<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OpenLoyalty\Bundle\SettingsBundle\Entity\BooleanSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\FileSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\IntegerSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\JsonSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\StringSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Model\Logo;
use OpenLoyalty\Bundle\SettingsBundle\Model\Settings;
use OpenLoyalty\Bundle\SettingsBundle\Provider\AvailableMarketingVendors;
use OpenLoyalty\Bundle\SettingsBundle\Service\LogoUploader;
use OpenLoyalty\Component\Customer\Domain\Model\AccountActivationMethod;
use OpenLoyalty\Component\Customer\Domain\Model\Status;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use OpenLoyalty\Component\Translation\Domain\Command\CreateLanguage;
use OpenLoyalty\Component\Translation\Domain\LanguageId;
use Symfony\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class LoadSettingsData.
 */
class LoadSettingsData extends ContainerAwareFixture implements OrderedFixtureInterface
{
    /**
     * @var array
     */
    private $languageMap = [
        'english.json' => [
            'code' => 'en',
            'name' => 'English',
            'default' => true,
            'order' => 0,
        ],
        'polish.json' => [
            'code' => 'pl',
            'name' => 'Polski',
            'default' => false,
            'order' => 1,
        ],
    ];

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->loadDefaultTranslations();

        $settings = new Settings();

        $currency = new StringSettingEntry('currency', 'eur');
        $settings->addEntry($currency);

        $timezone = new StringSettingEntry('timezone', 'Europe/Warsaw');
        $settings->addEntry($timezone);

        $programName = new StringSettingEntry('programName', 'Loyalty Program');
        $settings->addEntry($programName);

        $pointsSingular = new StringSettingEntry('programPointsSingular', 'Point');
        $settings->addEntry($pointsSingular);

        $pointsPlural = new StringSettingEntry('programPointsPlural', 'Points');
        $settings->addEntry($pointsPlural);

        $pointsDaysActive = new IntegerSettingEntry('pointsDaysActive', 30);
        $settings->addEntry($pointsDaysActive);

        $expirePointsNotificationDays = new IntegerSettingEntry('expirePointsNotificationDays', 10);
        $settings->addEntry($expirePointsNotificationDays);

        $expireCouponsNotificationDays = new IntegerSettingEntry('expireCouponsNotificationDays', 10);
        $settings->addEntry($expireCouponsNotificationDays);

        $expireLevelsNotificationDays = new IntegerSettingEntry('expireLevelsNotificationDays', 10);
        $settings->addEntry($expireLevelsNotificationDays);

        $allTimeNotLocked = new BooleanSettingEntry('allTimeNotLocked', true);
        $settings->addEntry($allTimeNotLocked);

        $returns = new BooleanSettingEntry('returns', true);
        $settings->addEntry($returns);

        $entry = new StringSettingEntry('tierAssignType');
        $entry->setValue(TierAssignTypeProvider::TYPE_TRANSACTIONS);
        $settings->addEntry($entry);

        $entry3 = new JsonSettingEntry('excludedLevelCategories');
        $entry3->setValue(['category_excluded_from_level']);
        $settings->addEntry($entry3);

        $downgradeMode = new StringSettingEntry('levelDowngradeMode');
        $downgradeMode->setValue(LevelDowngradeModeProvider::MODE_NONE);
        $settings->addEntry($downgradeMode);

        // copy logo
        $rootDirectory = $this->getContainer()->getParameter('kernel.root_dir');
        $destinationDirectory = $rootDirectory.'/uploads/logo';
        $filesystem = $this->getContainer()->get('filesystem');
        if (!$filesystem->exists($destinationDirectory)) {
            $filesystem->mkdir($destinationDirectory);
        }
        $kernel = $this->getContainer()->get('kernel');

        $photoNames = [
            LogoUploader::LOGO,
            LogoUploader::SMALL_LOGO,
            LogoUploader::HERO_IMAGE,
            LogoUploader::ADMIN_COCKPIT_LOGO,
            LogoUploader::CLIENT_COCKPIT_LOGO_BIG,
            LogoUploader::CLIENT_COCKPIT_LOGO_SMALL,
            LogoUploader::CLIENT_COCKPIT_HERO_IMAGE,
        ];

        foreach ($photoNames as $name) {
            $filesystem->copy(
                $kernel->locateResource('@OpenLoyaltySettingsBundle/Resources/images/logo/logo.png'),
                $destinationDirectory.'/'.$name.'.png'
            );

            $photo = new Logo();
            $photo->setMime('image/png');
            $photo->setPath('logo/'.$name.'.png');
            $entry = new FileSettingEntry($name, $photo);
            $settings->addEntry($entry);
        }

        $earningStatuses = new JsonSettingEntry('customerStatusesEarning');
        $earningStatuses->setValue([Status::TYPE_ACTIVE]);
        $settings->addEntry($earningStatuses);

        $spendingStatuses = new JsonSettingEntry('customerStatusesSpending');
        $spendingStatuses->setValue([Status::TYPE_ACTIVE]);
        $settings->addEntry($spendingStatuses);

        $priority = new JsonSettingEntry('customersIdentificationPriority');
        $priorities = [
            [
                'priority' => 3,
                'field' => 'phone',
            ],
            [
                'priority' => 2,
                'field' => 'loyaltyCardNumber',
            ],
            [
                'priority' => 1,
                'field' => 'email',
            ],
        ];
        $priority->setValue($priorities);
        $settings->addEntry($priority);

        $cssJsonTemplate = new JsonSettingEntry('cssJsonTemplate');
        $cssJsonTemplate->setValue([
            'accent_color' => 'rgba( 255,166,90, 1)',
            'template_css' => '.c-accent { color: rgb(20,19,19) !important }; .hvc-accent:hover { color: rgb(20,19,19) }',
        ]);
        $settings->addEntry($cssJsonTemplate);

        $accountActivationMethod = new StringSettingEntry('accountActivationMethod');
        $accountActivationMethod->setValue(AccountActivationMethod::METHOD_EMAIL);
        $settings->addEntry($accountActivationMethod);

        $marketingVendor = new StringSettingEntry('marketingVendorsValue');
        $marketingVendor->setValue(AvailableMarketingVendors::NONE);
        $settings->addEntry($marketingVendor);

        $this->getContainer()->get('ol.settings.manager')->save($settings);
    }

    /**
     * Copy default translations to translations directory.
     */
    protected function loadDefaultTranslations(): void
    {
        $commandBus = $this->container->get('broadway.command_handling.command_bus');
        $uuidGenerator = $this->container->get('broadway.uuid.generator');

        /** @var Kernel $kernel */
        $kernel = $this->getContainer()->get('kernel');

        $transDir = $kernel->locateResource('@OpenLoyaltySettingsBundle/DataFixtures/ORM/translations/');
        $finder = Finder::create();
        $finder->files()->in($transDir);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $fileName = $file->getFilename();

            if (array_key_exists($fileName, $this->languageMap)) {
                $languageData = $this->languageMap[$fileName];

                $commandBus->dispatch(new CreateLanguage(
                    new LanguageId($uuidGenerator->generate()),
                    [
                        'code' => $languageData['code'],
                        'name' => $languageData['name'],
                        'order' => $languageData['order'],
                        'default' => $languageData['default'],
                        'translations' => $file->getContents(),
                    ]
                ));
            }
        }
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the order of this fixture.
     *
     * @return int
     */
    public function getOrder()
    {
        return 0;
    }
}
