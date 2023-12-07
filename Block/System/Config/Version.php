<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config;

/**
 * Adminhtml Module Version Info Block
 *
 * @link  https://docs.unzer.com/
 */
class Version extends AbstractConfigInfo
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::system/config/version_info.phtml';

    /**
     * Get Info
     *
     * @return string
     */
    public function getInfo(): string
    {
        return $this->moduleResource->getDbVersion('Unzer_PAPI') ?? '';
    }
}
