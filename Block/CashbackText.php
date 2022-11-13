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

namespace GumNet\AME\Block;

use GumNet\AME\Model\ApiClient;
use GumNet\AME\Model\SensediaApiClient;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use GumNet\AME\Model\Values\Config;

class CashbackText extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var SensediaApiClient
     */
    protected $api;

    /**
     * @var Http
     */
    protected $request;

    /**
     * CashbackText constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ApiClient $api
     * @param SensediaApiClient $sensediaAPI
     * @param Http $request
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ApiClient $api,
        SensediaApiClient $sensediaAPI,
        Http $request
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->api = $api;

        if ($this->_scopeConfig->getValue(Config::ENVIRONMENT, ScopeInterface::SCOPE_STORE) === 3) {
            $this->api = $sensediaAPI;
        }
        $this->request = $request;
    }

    /**
     * @return bool
     */
    public function isShowCashbackProductsListEnabled(): bool
    {
        return (bool)$this->_scopeConfig
            ->getValue(Config::EXHIBITION_LIST, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return float
     * @throws NoSuchEntityException
     */
    public function getCashbackPercent()
    {
        return $this->api->getCashbackPercent();
    }

    /**
     * @return float
     * @throws NoSuchEntityException
     */
    public function getCashbackValue(): float
    {
        if ($this->request->getFullActionName() == 'catalog_product_view') {
            $product = $this->registry->registry('product');
            return $product->getFinalPrice() * $this->getCashbackPercent() * 0.01;
        }
        return 0;
    }
}
