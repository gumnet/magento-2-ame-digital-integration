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

namespace GumNet\AME\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CreditMemoObserver implements ObserverInterface
{
    protected $_apiAME;
    protected $_order;
    protected $_gumAPI;
    protected $_dbAME;

    public function __construct(
        \GumNet\AME\Helper\API $api,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \GumNet\AME\Helper\GumApi $gumAPI,
        \GumNet\AME\Helper\DbAME $dbAME
    )
    {
        $this->_apiAME = $api;
        $this->_order = $order;
        $this->_gumAPI = $gumAPI;
        $this->_dbAME = $dbAME;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $refund = $observer->getEvent()->getCreditmemo();
        $order = $refund->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethod();
        if($method=="ame") {
            $valor = $refund->getGrandTotal() * 100;
            $refund = $this->_apiAME->refundOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()), $valor);
            if ($refund) {
                $refund[0] = json_decode($refund[0], true);
                $this->_dbAME->insertRefund($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()), $refund[1], $refund[0]['operationId'], $valor, $refund[0]['status']);
            } else {
                throw new LocalizedException(__('Houve um erro efetuando o reembolso.'));
            }
        }
        return $this;

    }
}
