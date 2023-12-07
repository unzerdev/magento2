<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NotFoundException;
use Unzer\PAPI\Model\Vault\Handlers\VaultDetailsHandlerInterface;

/**
 * Vault Details Handler Manager
 *
 * @link  https://docs.unzer.com/
 */
class VaultDetailsHandlerManager
{
    /**
     * @var VaultDetailsHandlerInterface[]|array
     */
    private array $handlers;

    /**
     * Constructor
     *
     * @param array $handlers
     */
    public function __construct(
        array $handlers
    ) {
        $this->handlers = $handlers;
    }

    /**
     * Get Handler by code
     *
     * @param string $code
     * @return VaultDetailsHandlerInterface
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function getHandlerByCode(string $code): VaultDetailsHandlerInterface
    {
        if (!array_key_exists($code, $this->handlers)) {
            throw new NotFoundException(__('Vault Details Handler for code %1 is not implemented, yet', $code));
        }

        if (!$this->handlers[$code] instanceof VaultDetailsHandlerInterface) {
            throw new InvalidArgumentException(
                __('Vault Details Handler for code %1 does not implement VaultDetailsHandlerInterface', $code)
            );
        }

        return $this->handlers[$code];
    }
}
