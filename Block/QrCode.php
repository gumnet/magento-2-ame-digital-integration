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
//SELECT * FROM sales_order_payment WHERE JSON_EXTRACT(additional_information, '$.method_title') = 'AME';
namespace GumNet\AME\Block;

class QrCode extends \Magento\Checkout\Block\Onepage\Success
{
    protected $checkoutSession;

    protected $customerSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext
    ) {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }
    public function getCashbackValue(): float
    {
        return (float)$this->getOrder()->getPayment()->getAdditionalInformation('cashback_amount') * 0.01;
    }

    public function getPrice(): float
    {
        return $this->getOrder()->getGrandTotal();
    }

    public function getOrder(): \Magento\Sales\Model\Order
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerSession->getCustomer()->getId();
    }

    public function getDeepLink(): string
    {
        return $this->getOrder()->getPayment()->getAdditionalInformation('deep_link');
    }

    public function getQrCodeLink(): string
    {
        return $this->getOrder()->getPayment()->getAdditionalInformation('qr_code_link');
    }

    public function getPaymentMethod(): string
    {
        return $this->getOrder()->getPayment()->getMethod();
    }
}
