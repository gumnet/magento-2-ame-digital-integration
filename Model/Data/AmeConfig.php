<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020-2022 GumNet (https://gum.net.br)
 * @package GumNet AME
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY GUM Net (https://gum.net.br). AND CONTRIBUTORS
 * ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE FOUNDATION OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace GumNet\AME\Model\Data;

use GumNet\AME\Api\Data\AmeConfigInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class AmeConfig extends AbstractExtensibleModel implements AmeConfigInterface
{
    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        return $this->getData(self::KEY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId(int $id): AmeConfigInterface
    {
        return $this->setData(self::KEY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getOption(): ?string
    {
        return $this->getData(self::AME_OPTION);
    }

    /**
     * @inheritDoc
     */
    public function setOption(string $option): AmeConfigInterface
    {
        return $this->setData(self::AME_OPTION, $option);
    }

    /**
     * @inheritDoc
     */
    public function getValue(): ?string
    {
        return $this->getData(self::AME_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): AmeConfigInterface
    {
        return $this->setData(self::AME_VALUE, $value);
    }
}
