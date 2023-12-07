<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config;

/**
 * Adminhtml About us Info Block
 *
 * @link  https://docs.unzer.com/
 */
class AboutUs extends AbstractConfigInfo
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::system/config/about_us.phtml';

    /**
     * Get Info
     *
     * @return string
     */
    public function getInfo(): string
    {
        return (string)__('UNZER_ABOUT_US');
    }
}
