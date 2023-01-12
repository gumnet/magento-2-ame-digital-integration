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

use GumNet\AME\Model\GumApi;
use GumNet\AME\Model\Values\PaymentInformation;
use http\Exception\InvalidArgumentException;
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
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Raw;
use GumNet\AME\Model\Values\Config;

class Index extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GumApi
     */
    protected $gumApi;

    /**
     * @var CollectionFactory
     */
    protected $paymentCollectionFactory;

    /**
     * @var RawFactory
     */
    protected $rawResultFactory;

    protected $invoiceSender;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepository $orderRepository
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param LoggerInterface $logger
     * @param GumApi $gumApi
     * @param CollectionFactory $paymentCollectionFactory
     * @param RawFactory $rawResultFactory
     * @param InvoiceSender $invoiceSender
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        OrderRepository $orderRepository,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        LoggerInterface $logger,
        GumApi $gumApi,
        CollectionFactory $paymentCollectionFactory,
        RawFactory $rawResultFactory,
        InvoiceSender $invoiceSender,
        array $data = []
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->logger = $logger;
        $this->gumApi = $gumApi;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->rawResultFactory = $rawResultFactory;
        $this->invoiceSender = $invoiceSender;
        parent::__construct($context);
    }

    /**
     * @return Raw
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): Raw
    {
        $transactionId = $this->getRequest()->getParam('transactionid', '');
        if (!$request_ame_order_id = $this->getRequest()->getParam('orderid', '')) {
            if ($ameOriginOrderId = $this->getRequest()->getParam('originorderid', '')) {
                $this->_eventManager->dispatch(
                /* @note the following event should be used to process trust wallet additional charge */
                'ame_callback_trust_wallet_captured',
                    [
                        'ame_original_order_id' => $ameOriginOrderId,
                        'ame_transaction_id' => $transactionId,
                    ]
                );
            } else {
                $params = json_encode($this->getRequest()->getParams());
                $this->logger->error('AME - Invalid capture transaction callback - ' . $params);
                throw new InvalidArgumentException('AME - Invalid capture transaction callback');
            }
        } else {
            if (!$order = $this->getOrderByTransactionId($transactionId)) {
                /* @note the following event should be used to process not found orders */
                $this->_eventManager->dispatch(
                    'ame_callback_order_not_found_captured',
                    [
                        'ame_order_id' => $request_ame_order_id,
                        'ame_transaction_id' => $transactionId,
                    ]
                );
            } else {
                $ameOrderId = $order->getPayment()->getAdditionalInformation(PaymentInformation::AME_ID);
                if ($request_ame_order_id != $ameOrderId) {
                    $message = __("AME Callback - ERROR Invalid transaction for order - " . $request_ame_order_id);
                    throw new InputException($message);
                }
                $this->invoiceOrder($order);
                $comment = 'AME transaction ID: ' . $transactionId . PHP_EOL . 'NSU: ' . $this->getNsu($transactionId);
                $order->addStatusHistoryComment($comment);
                $order->save();
                $amount = $order->getGrandTotal();
                $this->gumApi->captureTransaction($transactionId, $request_ame_order_id, $amount);
            }
        }
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
        return $order->getPayment()->getAdditionalInformation(PaymentInformation::NSU);
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
        $paymentCollection->getSelect()->where(
            "JSON_EXTRACT(additional_information, '$.".PaymentInformation::TRANSACTION_ID."') = '"
            . $transactionId ."'"
        );
        if (!$paymentCollection->count()) {
            return null;
        }
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentCollection->getFirstItem();
        return $this->orderRepository->get($payment->getParentId());
    }

    /**
     * @param Order $order
     * @return void
     * @throws InputException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function invoiceOrder(Order $order): void
    {
        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->setCustomerNoteNotify(true);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->register();
        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->_transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
        try {
            $this->invoiceSender->send($invoice);
        } catch (\Exception $e) {
            $this->logger->warning(__($e->getMessage()));
        }
        $processingStatus = $this->_scopeConfig->getValue(
            Config::STATUS_PROCESSING,
            ScopeInterface::SCOPE_STORE
        );
        $order->setState('processing')->setStatus($processingStatus);
        $this->orderRepository->save($order);
    }
}
