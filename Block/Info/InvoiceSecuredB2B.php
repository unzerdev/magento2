<?php

namespace Unzer\PAPI\Block\Info;

/**
 * Customer Account Order Invoice Information Block
 *
 * Copyright (C) 2021 Unzer GmbH
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
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
 */
class InvoiceSecuredB2b extends InvoiceSecured
{
    protected $_template = 'Unzer_PAPI::info/invoice_secured_b2b.phtml';

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Unzer_PAPI::info/pdf/invoice_secured_b2b.phtml');
        return $this->toHtml();
    }
}
