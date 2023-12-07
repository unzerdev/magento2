<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Checkout;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Unzer\PAPI\Api\CheckoutInterface;
use Unzer\PAPI\Api\Data\CustomerInterface;
use Unzer\PAPI\Api\Data\CustomerInterfaceFactory;
use Unzer\PAPI\Helper\Order as OrderHelper;

/**
 * Checkout API Interface Implementation.
 *
 * @link  https://docs.unzer.com/
 */
class Checkout implements CheckoutInterface
{
    /**
     * @var Session
     */
    protected Session $_checkoutSession;

    /**
     * @var OrderHelper
     */
    protected OrderHelper $_orderHelper;

    /**
     * @var CustomerInterfaceFactory
     */
    private CustomerInterfaceFactory $customerInterfaceFactory;

    /**
     * Checkout constructor.
     *
     * @param Session $checkoutSession
     * @param OrderHelper $orderHelper
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     */
    public function __construct(
        Session $checkoutSession,
        OrderHelper $orderHelper,
        CustomerInterfaceFactory $customerInterfaceFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderHelper = $orderHelper;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
    }

    /**
     * Returns the external customer for the current quote.
     *
     * @param string|null $guestEmail E-Mail address used for quote, in case customer is not logged in.
     *
     * @return \Unzer\PAPI\Api\Data\CustomerInterface|null
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getExternalCustomer(?string $guestEmail = null): ?\Unzer\PAPI\Api\Data\CustomerInterface
    {
        /** @var Quote $quote */
        $quote = $this->_checkoutSession->getQuote();

        $email = $guestEmail ?? $quote->getCustomerEmail();

        if ($email === null) {
            return null;
        }

        try {
            $customerResource = $this->_orderHelper->createCustomerFromQuote($quote, $email);
        } catch (Exception $e) {
            $customerResource = null;
        }

        if ($customerResource === null) {
            return null;
        }

        return $this->customerInterfaceFactory->create()->fromResource($customerResource);
    }
}
