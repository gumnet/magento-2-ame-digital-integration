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

namespace GumNet\AME\Plugin\Order;

use GumNet\AME\Helper\DbAME;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\OrderRepositoryInterface;
use GumNet\AME\Model\Values\PaymentInformation;

class Info
{
    /**
     * @var OrderRepositoryInterface
     */

    protected $orderRepository;
    /**
     * @var Http
     */
    protected $request;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Http $request
    ) {
        $this->orderRepository = $orderRepository;
        $this->request = $request;
    }
    public function afterGetPaymentInfoHtml(
        \Magento\Sales\Block\Order\Info $subject,
        $payment_info_html
    ) {
        $orderId = $this->request->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();
        if ($payment->getMethod() != "ame") {
            return $payment_info_html;
        }
        $qrcode = $payment->getAdditionalInformation(PaymentInformation::QR_CODE_LINK);
        $payment_info_html .= "<br>";
        $payment_info_html .= "<img src='".$qrcode."' alt='qrcode'>";
        return $payment_info_html;
    }
}
