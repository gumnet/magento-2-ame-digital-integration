<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020-2021 GumNet (https://gum.net.br)
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

class CashbackText extends \Magento\Framework\View\Element\Template
{
    protected $_scopeConfig;
    protected $_helper;
    protected $_registry;
    protected $_api;
    protected $_request;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Catalog\Helper\Data $helper,
                                \Magento\Framework\Registry $registry,
                                \GumNet\AME\Helper\API $_api,
                                \GumNet\AME\Helper\SensediaAPI $sensediaAPI,
                                \Magento\Framework\App\Request\Http $request
                                )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_helper = $helper;
        $this->_registry = $registry;
        $this->_api = $_api;
        if (!$scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            || $scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 3) {
            $this->_api = $sensediaAPI;
        }
        $this->_request = $request;
        parent::__construct($context);
    }
    public function isShowCashbackProductsListEnabled()
    {
        return $this->_scopeConfig->getValue("ame/exhibition/show_cashback_products_list", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getCashbackPercent()
    {
//        return $this->_scopeConfig->getValue("ame/general/cashback_value", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $this->_api->getCashbackPercent();
    }
    public function getCashbackValue(){
        if ($this->_request->getFullActionName() == 'catalog_product_view') {
            if(!$product = $this->getProduct()){
                $product = $this->_registry->registry('product');
            }
        }
        else{
            $product = $this->getKey();
        }
        return $product->getFinalPrice() * $this->getCashbackPercent() * 0.01;
    }
}

