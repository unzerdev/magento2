<?php
declare(strict_types=1);

namespace Unzer\PAPI\Setup;

use Unzer\PAPI\Helper\Payment;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;

/**
 * Module Data Setup
 *
 * @link  https://docs.unzer.com/
 */
class InstallData implements InstallDataInterface
{
    private const STATUS_READY_TO_CAPTURE_LABEL = 'Ready to Capture';

    /**
     * @var StatusFactory
     */
    private StatusFactory $_orderStatusFactory;

    /**
     * InstallData constructor.
     * @param StatusFactory $orderStatusFactory
     */
    public function __construct(StatusFactory $orderStatusFactory)
    {
        $this->_orderStatusFactory = $orderStatusFactory;
    }

    /**
     * Install
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $status = $this->_orderStatusFactory->create();
            $status->setStatus(Payment::STATUS_READY_TO_CAPTURE);
            $status->setData('label', self::STATUS_READY_TO_CAPTURE_LABEL);
            $status->save();
        }
    }
}
