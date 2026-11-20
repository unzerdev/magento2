<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Unzer\PAPI\Model\System\Config\Source\PaymentAction;

/**
 * Fixes the order payment action to authorize_capture for methods without a booking mode selector,
 * so a value left in core_config_data, config.php or env.php cannot switch them to authorize.
 *
 * @link  https://docs.unzer.com/
 */
class AuthorizeCapturePaymentActionHandler implements ValueHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(array $subject, $storeId = null)
    {
        return PaymentAction::ACTION_AUTHORIZE_CAPTURE;
    }
}
