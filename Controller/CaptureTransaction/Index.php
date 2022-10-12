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

use GumNet\AME\Helper\API;
use GumNet\AME\Helper\DbAME;
use GumNet\AME\Helper\GumApi;
use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    protected $_scopeConfig;
    protected $orderRepository;
    protected $_dbAME;
    protected $_invoiceService;
    protected $_transactionFactory;
    protected $api;
    protected $logger;
    protected $gumApi;

    protected $paymentCollectionFactory;

    protected $rawResultFactory;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        OrderRepository $orderRepository,
        DbAME $dbAME,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        API $api,
        LoggerInterface $logger,
        GumApi $gumApi,
        CollectionFactory $paymentCollectionFactory,
        RawFactory $rawResultFactory,
        array $data = []
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->_dbAME = $dbAME;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->api = $api;
        $this->logger = $logger;
        $this->gumApi = $gumApi;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->rawResultFactory = $rawResultFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $transactionId = $this->getRequest()->getParam('transactionid');
        $request_ame_order_id = $this->getRequest()->getParam('orderid');
        $order = $this->getOrderByTransactionId($transactionId);
        $ameOrderId = $order->getPayment()->getAdditionalInformation(PaymentInformation::AME_ID);
        if ($request_ame_order_id != $ameOrderId) {
            $message = __("AME Callback - ERROR Invalid transaction for order - " . $request_ame_order_id);
            throw new InputException($message);
        }
        $this->invoiceOrder($order);
        $comment = 'AME transaction ID: '. $transactionId . PHP_EOL . 'NSU: '.$this->getNsu($transactionId);
        $order->addStatusHistoryComment($comment);
        $order->save();

        $amount = $this->_dbAME->getTransactionAmount($transactionId);
        $this->gumApi->captureTransaction($transactionId, $request_ame_order_id, $amount);
        return $this->rawResultFactory->create()->setContents('');
    }

    /**
     * @param string $transactionId
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getNsu(string $transactionId)
    {
        $order = $this->getOrderByTransactionId($transactionId);
        return $order->getPayment()->getAdditionalInformation(Config::NSU);
    }

    /**
     * @param $transactionId
     * @return OrderInterface|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getOrderByTransactionId($transactionId): ?OrderInterface
    {
        $paymentCollection = $this->paymentCollectionFactory->create();
        $paymentCollection->addFieldToFilter();
        if (!$paymentCollection->count()) {
            return null;
        }
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentCollection->getFirstItem();
        return $this->orderRepository->get($payment->getParentId());
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws InputException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function invoiceOrder(OrderInterface $order): void
    {
        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $transaction = $this->_transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
        $order->setState('processing')->setStatus('processing');
        $this->orderRepository->save($order);
    }
}
