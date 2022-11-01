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

use Magento\Sales\Model\Order\Config;
use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Checkout\Block\Onepage\Success;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as ContextAlias;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session as CheckoutSession;

class QrCode extends Success
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    protected $assetRepository;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Config $orderConfig,
        ContextAlias $httpContext,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->assetRepository = $assetRepository;
    }

    /**
     * @return float
     */
    public function getCashbackValue(): float
    {
        if ($order = $this->getOrder()) {
            return (float)$order->getPayment()->getAdditionalInformation('cashback_amount') * 0.01;
        }
        return 0;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        if ($order = $this->getOrder()) {
            return $order->getGrandTotal();
        }
        return 0;
    }

    /**
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        if (!$this->customerSession->isLoggedIn()) {
            return null;
        }
        return $this->checkoutSession->getLastRealOrder();
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        if (!$this->customerSession->isLoggedIn()) {
            return 0;
        }
        return $this->customerSession->getCustomerId();
    }

    /**
     * @return string
     */
    public function getDeepLink(): string
    {
        if ($order = $this->getOrder()) {
            return $order->getPayment()->getAdditionalInformation(PaymentInformation::DEEP_LINK);
        }
        return "";
    }

    /**
     * @return string
     */
    public function getQrCodeLink(): string
    {
        if ($order = $this->getOrder()) {
            return $order->getPayment()->getAdditionalInformation(PaymentInformation::QR_CODE_LINK);
        }
        return "";
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        if ($order = $this->getOrder()) {
            return $order->getPayment()->getMethod();
        }
        return "";
    }

    public function getLogoUrl(): ?string
    {
        return $this->assetRepository->getUrl("GumNet_AME::images/ame-digital.png");
    }
}
