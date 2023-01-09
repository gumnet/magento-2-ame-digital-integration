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

namespace GumNet\AME\ViewModel;

use GumNet\AME\Model\ApiClient;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use GumNet\AME\Model\Values\Config;

class CashbackText implements ArgumentInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ApiClient
     */
    protected $api;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * CashbackText constructor.
     * @param Registry $registry
     * @param ApiClient $api
     * @param Http $request
     * @param ScopeInterface $scopeConfig
     */
    public function __construct(
        Registry $registry,
        ApiClient $api,
        Http $request,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->registry = $registry;
        $this->api = $api;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isShowCashbackProduct(): bool
    {
        return (bool)$this->scopeConfig
            ->getValue(Config::EXHIBITION_LIST, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return float
     */
    public function getCashbackPercent()
    {
        return $this->api->getCashbackPercent();
    }

    /**
     * @return float
     */
    public function getCashbackValue(): float
    {
        try {
            if ($this->request->getFullActionName() == 'catalog_product_view') {
                $product = $this->registry->registry('product');
                return $product->getFinalPrice() * $this->getCashbackPercent() * 0.01;
            }
        } catch (NoSuchEntityException $e) {
            return 0;
        }
        return 0;
    }
}
