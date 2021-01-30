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

namespace GumNet\AME\Plugin;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;

class PluginBtnOrderView
{
    protected $object_manager;
    protected $_backendUrl;
    protected $_scopeConfig;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
        $this->_scopeConfig = $scopeConfig;

    }
    public function beforeSetLayout( \Magento\Sales\Block\Adminhtml\Order\View $subject )
    {
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) != 2) {
            $sendOrder = $this->_backendUrl->getUrl('ameroutes/order/index/id/' . $subject->getOrderId());
            $subject->addButton(
                'sendorderame',
                [
                    'label' => __('AME'),
                    'onclick' => "window.open('" . $sendOrder . "','_blank')",
                    'class' => 'ship primary'
                ]
            );
        }
        return null;
    }

}
