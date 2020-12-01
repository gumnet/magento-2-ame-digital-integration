<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020 GumNet (https://gum.net.br)
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

namespace GumNet\AME\Controller\Index;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
    protected $_storeManager;

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
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
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
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->_mlogger->log("INFO","AME Callback starting...");
        $json = file_get_contents('php://input');
//        $json = fopen('php://input','r');
        $this->_dbAME->insertCallback($json);
        if(!$this->isJson($json)){
            $this->_mlogger->log("ERROR","AME Callback is not json");
            return;
        }
        $input = json_decode($json,true);
        $this->_mlogger->log("INFO",print_r($input,true));
        // verify if id exists
        if(!array_key_exists('id',$input)){
            $this->_mlogger->log("ERROR","AME Callback AME ID not found in JSON");
            return;
        }
        $ame_order_id = $input['attributes']['orderId'];
        $incrId = $this->_dbAME->getOrderIncrementId($ame_order_id);
        if(!$incrId){
            $this->_mlogger->log("ERROR","AME Callback Increment ID not found in the database");
            return;
        }
        if($input['status']=="AUTHORIZED") {
            $this->_dbAME->insertTransaction($input);
            $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_order_id);
            if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 2) {
                $this->_mlogger->log("INFO","AME Callback Calling step 2");
                $hash = $this->_dbAME->getCallback2Hash();
                $url = $this->getCallbackUrl() . '/step2/index/hash/' . $hash . '/id/' . $ame_transaction_id;
                $this->_mlogger->log("INFO", "Step 2 URL: " . $url);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $result = curl_exec($ch);
                $this->_mlogger->log("INFO", "Step 2 call OK");
            }
        }
        else{
            $this->_mlogger->log("ERROR","Wrong Order status: ".$input['status']);
        }
//        if($input['status']=="CANCELED"){
//            $this->_mlogger->log("INFO","AME Callback cancel order ".$incrId);
//            $this->cancelOrder($order);
//        }
//        if($input['status']=="ERROR"||$input['status']=="DENIED"){
//            $this->_mlogger->log("INFO","AME Callback error/denied - cancel order ".$incrId);
//            $this->cancelOrder($order);
//        }
        $this->_mlogger->log("INFO","AME Callback ended.");
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
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    public function getCallbackUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }
}
