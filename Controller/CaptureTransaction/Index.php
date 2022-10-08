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

namespace GumNet\AME\Controller\CaptureTransaction;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_request;
    protected $_scopeConfig;
    protected $_orderRepository;
    protected $_dbAME;
    protected $_invoiceService;
    protected $_transactionFactory;
    protected $_api;
    protected $logger;
    protected $_gumApi;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \GumNet\AME\Helper\DbAME $dbAME,
                                \Magento\Sales\Model\Service\InvoiceService $invoiceService,
                                \Magento\Framework\DB\TransactionFactory $transactionFactory,
                                \GumNet\AME\Helper\API $api,
                                \Psr\Log\LoggerInterface $mlogger,
                                \GumNet\AME\Helper\GumApi $gumApi,
                                array $data = []
                                )
    {
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderRepository = $orderRepository;
        $this->_dbAME = $dbAME;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->_api = $api;
        $this->logger = $mlogger;
        $this->_gumApi = $gumApi;
        parent::__construct($context);
    }
    public function execute()
    {
        $ame_transaction_id = $this->_request->getParam('transactionid');
        $request_ame_order_id = $this->_request->getParam('orderid');

        if (!$db_ame_order_id = $this->_dbAME->getAmeOrderIdByTransactionId($ame_transaction_id)) {
            die("AME Callback - ERROR Transaction not found - " . $ame_transaction_id);
        }
        if ($request_ame_order_id != $db_ame_order_id) {
            die("AME Callback - ERROR Invalid transaction for order - ");
        }
        $incrId = $this->_dbAME->getOrderIncrementId($request_ame_order_id);
        $this->logger->log("INFO","AME Capture Transacton getting Magento Order for ".$incrId);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderInterface = $objectManager->create(Magento\Sales\Api\Data\OrderInterface::class);
        $order = $orderInterface->loadByIncrementId($incrId);
        $orderId = $order->getId();
        $order = $this->_orderRepository->get($orderId);
        $this->logger->log("INFO", "AME Callback invoicing Magento order ".$incrId);
        $this->invoiceOrder($order);
        $order->addStatusHistoryComment(
            'AME transaction ID: '.$this->_dbAME->getCallBackTransactionId($request_ame_order_id) . PHP_EOL
            . 'NSU: '.$this->_dbAME->getCallBackNsu($request_ame_order_id));
        $order->save();

        $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($request_ame_order_id);
        $amount = $this->_dbAME->getTransactionAmount($ame_transaction_id);
        $capture2 = $this->_gumApi->captureTransaction($ame_transaction_id,$request_ame_order_id,$amount);
        if ($capture2) {
            $this->_dbAME->setCaptured2($ame_transaction_id);
        }
        $this->logger->log("INFO","AME Capture Transaction ended.");
        die();
    }

    public function invoiceOrder($order): void
    {
        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $transaction = $this->_transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
        $order->setState('processing')->setStatus('processing');
        $order->save();
    }
}
