<?php

declare(strict_types=1);

namespace Unzer\PAPI\Model\Payment\Status\Processor;

/**
 * Catch-all status processor used for every payment method that doesn't
 * require method-specific state routing (Cards, PayPal, Klarna, SEPA,
 * Bancontact, iDEAL, Apple Pay, Google Pay, …). Inherits the full
 * default behavior from {@see AbstractProcessor}.
 *
 * @link  https://docs.unzer.com/
 */
class DefaultProcessor extends AbstractProcessor
{
}
