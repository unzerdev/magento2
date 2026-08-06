<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;

class PluginResolver
{
    private const REACT_CHECKOUT_FLAG = 'hyva_react_checkout/general/enable';
    private const UNZER_MODULE = 'Unzer_PAPI';
    private const HYVA_CHECKOUT_MODULE = 'Unzer_HyvaCheckout';
    private const HYVA_THEME_PREFIX = 'Hyva/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ModuleListInterface $moduleList,
        private readonly DesignInterface $design,
        private readonly ThemeCollectionFactory $themeCollectionFactory,
    ) {
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function resolve(int $storeId): array
    {
        if (!$this->isHyvaThemeActive($storeId)) {
            return [
                'type' => 'unzerdev/magento2',
                'version' => $this->getModuleVersion(self::UNZER_MODULE),
            ];
        }

        if ($this->isReactCheckoutEnabled($storeId)) {
            return [
                'type' => 'unzerdev/magento2-hyva-react-checkout',
            ];
        }

        return [
            'type' => 'unzerdev/magento2-hyva-checkout',
            'version' => $this->getModuleVersion(self::HYVA_CHECKOUT_MODULE),
        ];
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    private function isHyvaThemeActive(int $storeId): bool
    {
        $themeId = $this->design->getConfigurationDesignTheme(
            Area::AREA_FRONTEND,
            ['store' => $storeId]
        );

        if (!$themeId) {
            return false;
        }

        $theme = $this->themeCollectionFactory->create()->getItemById((int)$themeId);

        while ($theme) {
            if (str_starts_with((string)$theme->getCode(), self::HYVA_THEME_PREFIX)) {
                return true;
            }
            $theme = $theme->getParentTheme();
        }

        return false;
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    private function isReactCheckoutEnabled(int $storeId): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::REACT_CHECKOUT_FLAG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param string $moduleName
     *
     * @return string|null
     */
    private function getModuleVersion(string $moduleName): ?string
    {
        $module = $this->moduleList->getOne($moduleName);
        return $module['setup_version'] ?? null;
    }
}
