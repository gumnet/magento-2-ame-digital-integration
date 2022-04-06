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

class QrCode extends \Magento\Checkout\Block\Onepage\Success
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    protected $_connection;
    protected $_apiAME;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \GumNet\AME\Helper\API $apiAME
    ) {
        parent::__construct($context, $checkoutSession,$orderConfig,$httpContext);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_connection = $resource->getConnection();
        $this->_apiAME = $apiAME;
    }
    public function getCashbackValue(){
        $increment_id = $this->getOrderId();
        $sql = "SELECT cashback_amount FROM ame_order WHERE increment_id = ".$increment_id;
        $value = $this->_connection->fetchOne($sql);
        return $value * 0.01;
    }

//    public function getCashbackValue(){
//        $total_discount = 0;
//        $items = $this->getOrder()->getAllItems();
//        foreach ($items as $item) {
//            $total_discount = $total_discount + $item->getDiscountAmount();
//        }
//        return ($this->getPrice()-abs($total_discount)) * $this->getCashbackPercent() * 0.01;
//    }
//    public function getCashbackPercent(){
//        return $this->_apiAME->getCashbackPercent();
//    }
    public function getPrice(){
        return $this->getOrder()->getGrandTotal();
    }
    public function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId($this->getOrderId());
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
    public function getDeepLink(){
        $increment_id = $this->getOrderId();
        $sql = "SELECT deep_link FROM ame_order WHERE increment_id = ".$increment_id;
        $qr = $this->_connection->fetchOne($sql);
        return $qr;
    }
    public function getQrCodeLink(){
        $increment_id = $this->getOrderId();
        $sql = "SELECT qr_code_link FROM ame_order WHERE increment_id = ".$increment_id;
        $qr = $this->_connection->fetchOne($sql);
        return $qr;
    }
    public function getPaymentMethod(){
        return $this->getOrder()->getPayment()->getMethod();
    }
}
