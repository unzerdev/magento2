<?php

namespace Unzer\PAPI\Block\System\Config;

/**
 * Adminhtml Module Version Info Block
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
 *
 * @author David Owusu
 *
 * @package  unzerdev/magento2
 */
class Version extends AbstractConfigInfo
{
    protected $_template = 'Unzer_PAPI::system/config/version_info.phtml';

    public function getInfo() :string
    {
        return $this->moduleResource->getDbVersion('Unzer_PAPI')??'';
    }
}
