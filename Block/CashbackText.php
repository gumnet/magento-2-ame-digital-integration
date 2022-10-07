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

use GumNet\AME\Helper\API;
use GumNet\AME\Helper\SensediaAPI;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class CashbackText extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var SensediaAPI
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
     * @param API $_api
     * @param SensediaAPI $sensediaAPI
     * @param Http $request
     */
    public function __construct(
        Context $context,
        Registry $registry,
        API $api,
        SensediaAPI $sensediaAPI,
        Http $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->api = $api;

        if (!$scopeConfig->getValue('ame/general/environment', ScopeInterface::SCOPE_STORE)
            || $scopeConfig->getValue('ame/general/environment', ScopeInterface::SCOPE_STORE) == 3) {
            $this->api = $sensediaAPI;
        }
        $this->request = $request;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isShowCashbackProductsListEnabled(): bool
    {
        return (bool)$this->_scopeConfig
            ->getValue("ame/exhibition/show_cashback_products_list", ScopeInterface::SCOPE_STORE);
    }

    public function getCashbackPercent()
    {
        return $this->api->getCashbackPercent();
    }

    /**
     * @return float
     */
    public function getCashbackValue(): float
    {
        if ($this->request->getFullActionName() == 'catalog_product_view') {
            if (!$product = $this->getProduct()) {
                $product = $this->registry->registry('product');
                return $product->getFinalPrice() * $this->getCashbackPercent() * 0.01;
            }
        }
        if (isset($product)) {
            return $product->getFinalPrice() * $this->getCashbackPercent() * 0.01;
        }
        return 0;
    }
}

