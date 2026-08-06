<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Payment\Status\Processor;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;

/**
 * Registry of {@see ProcessorInterface} instances keyed by Magento
 * payment-method code, with a mandatory `default` fallback. Modeled on
 * Magento\Payment\Gateway\Command\CommandPool — same shape, narrower
 * concern (Unzer payment-state translation).
 *
 * @link  https://docs.unzer.com/
 */
class ProcessorPool
{
    public const DEFAULT_KEY = 'default';

    /**
     * @var ProcessorInterface[]
     */
    private array $processors;

    /**
     * @param ProcessorInterface[] $processors keyed by Magento payment-method code
     *
     * @throws NotFoundException when no `default` processor is wired
     */
    public function __construct(array $processors)
    {
        if (!isset($processors[self::DEFAULT_KEY])) {
            throw new NotFoundException(
                new Phrase('A "%1" processor must be configured.', [self::DEFAULT_KEY])
            );
        }

        foreach ($processors as $code => $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new NotFoundException(
                    new Phrase(
                        'Processor for "%1" must implement %2.',
                        [$code, ProcessorInterface::class]
                    )
                );
            }
        }

        $this->processors = $processors;
    }

    /**
     * Resolve the processor for the given payment-method code, falling
     * back to the default when no method-specific processor is wired.
     *
     * @param string $methodCode Magento payment-method code (e.g. unzer_open_banking)
     *
     * @return ProcessorInterface
     */
    public function get(string $methodCode): ProcessorInterface
    {
        return $this->processors[$methodCode] ?? $this->processors[self::DEFAULT_KEY];
    }
}
