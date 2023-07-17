<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NotFoundException;
use Unzer\PAPI\Model\Vault\Handlers\VaultDetailsHandlerInterface;

/**
 * Vault Details Handler Manager
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
