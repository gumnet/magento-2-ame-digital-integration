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

namespace GumNet\AME\Controller\Step2;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_session;
    protected $_request;
    protected $_scopeConfig;
    protected $_orderRepository;
    protected $_dbAME;
    protected $_mailerAME;
    protected $_invoiceService;
    protected $_transactionFactory;
    protected $_api;
    protected $_mlogger;
    protected $_email;
    protected $_gumApi;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \GumNet\AME\Helper\DbAME $dbAME,
                                \GumNet\AME\Helper\MailerAME $mailerAME,
                                \Magento\Sales\Model\Service\InvoiceService $invoiceService,
                                \Magento\Framework\DB\TransactionFactory $transactionFactory,
                                \GumNet\AME\Helper\API $api,
                                \Psr\Log\LoggerInterface $mlogger,
                                \GumNet\AME\Helper\MailerAME $email,
                                \GumNet\AME\Helper\GumApi $gumApi,
                                array $data = []
                                )
    {
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderRepository = $orderRepository;
        $this->_dbAME = $dbAME;
        $this->_mailerAME = $mailerAME;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->_api = $api;
        $this->_mlogger = $mlogger;
        $this->_email = $email;
        $this->_gumApi = $gumApi;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->_mlogger->log("INFO","AME Callback step 2 starting...");
        $hash = $this->_request->getParam('hash');
        $callback2_hash = $this->_dbAME->getCallback2Hash();
        if($hash != $callback2_hash){
            $this->_mlogger->log("ERROR","AME Callback step 2 wrong hash");
            die();
        }
        $ame_transaction_id = $this->_request->getParam('id');
        $ame_order_id = $this->_dbAME->getAmeOrderIdByTransactionId($ame_transaction_id);
        $incrId = $this->_dbAME->getOrderIncrementId($ame_order_id);
        $this->_mlogger->log("INFO","AME Callback getting Magento Order for ".$incrId);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderInterface = $objectManager->create('Magento\Sales\Api\Data\OrderInterface');
        $order = $orderInterface->loadByIncrementId($incrId);
        $orderId = $order->getId();
        $this->_mlogger->log("INFO","Order ID: ".$orderId);
        $order = $this->_orderRepository->get($orderId);
        $this->_mlogger->log("INFO","AME Callback invoicing Magento order ".$incrId);
        $this->_email->sendDebug("Pagamento AME recebido pedido ".$order->getIncrementId(),"AME ID: ".$ame_order_id);
        $this->_mlogger->log("INFO", "AME Callback capturing...");
        $capture = $this->_api->captureOrder($ame_order_id);
        if(!$capture) die();
        $json_capture = json_encode($capture);
        $this->_mlogger->log("INFO","AME Callback Capture response:".$json_capture);
        $this->invoiceOrder($order);
        $this->_dbAME->setCaptured($ame_transaction_id,$capture['id']);
        $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_order_id);
        $amount = $this->_dbAME->getTransactionAmount($ame_transaction_id);
        $capture2 = $this->_gumApi->captureTransaction($ame_transaction_id,$ame_order_id,$amount);
        if($capture2) $this->_dbAME->setCaptured2($ame_transaction_id);
        $this->_mlogger->log("INFO","AME Callback step 2 ended.");
        die();
    }
    public function cancelOrder($order){
        $order->cancel()->save();
    }
    public function invoiceOrder($order){
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
    public function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
