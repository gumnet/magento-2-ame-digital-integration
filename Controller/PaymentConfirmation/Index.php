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

namespace GumNet\AME\Controller\PaymentConfirmation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;

class Index extends Action
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var RawFactory
     */
    protected $rawFactory;

    /**
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param RawFactory $rawFactory
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        RawFactory $rawFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->rawFactory = $rawFactory;
        parent::__construct($context);
    }

    /**
     * @return Raw
     */
    public function execute(): Raw
    {
        $id = $this->getRequest()->getParam('id', '');
        if (!$id) {
            return $this->rawFactory->create()->setContents('0');
        }
        try {
            $order = $this->orderRepository->get($id);
        } catch (\Exception $e) {
            return $this->rawFactory->create()->setContents('0');
        }
        $result = '0';
        if ($order->hasInvoices()) {
            $result = '1';
        }
        return $this->rawFactory->create()->setContents($result);
    }
}
