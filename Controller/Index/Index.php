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

namespace GumNet\AME\Controller\Index;

use GumNet\AME\Model\ApiClient;
use GumNet\AME\Model\GumApi;
use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Helper\Api;
use Psr\Log\LoggerInterface;

class Index extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var GumApi
     */
    protected $gumApi;

    /**
     * @var ApiClient
     */
    protected $api;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepository $orderRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param GumApi $gumApi
     * @param ApiClient $api
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ScopeConfigInterface$scopeConfig,
        OrderRepository $orderRepository,
        CollectionFactory $orderCollectionFactory,
        GumApi $gumApi,
        ApiClient $api,
        LoggerInterface $logger,

        array $data = []
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->gumApi = $gumApi;
        $this->api = $api;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $json = $this->request->getContent();
        if (!$this->api->isJson($json)) {
            $message = __('AME Callback - invalid JSON - ' . $json);
            throw new InputException($message);
        }
        $this->logger->info('AME callback received - ' . $json);
        $input = json_decode($json, true);
        // verify if id exists
        if (!array_key_exists('id', $input)
            || !array_key_exists('attributes', $input)) {
            $message = __('AME Callback - JSON missing keys - ' . $json);
            throw new InputException($message);
        }
        if (isset($input['attributes'])
            && isset($input['attributes']['trustWallet'])
            && isset($input['attributes']['trustWallet']['originOrderUuid'])
        ) {
            // Valid trustWallet additional charge callback
            $this->gumApi->queueTransaction($json);
            $this->_eventManager->dispatch(
                'ame_callback_trust_wallet_additional_charge',
                [
                    'ame_original_order_id' => $input['attributes']['trustWallet']['originOrderUuid'],
                    'ame_transaction_id' => $input['id'],
                    'callback_json' => $json
                ]
            );
        } else {
            $debitWalletId = $input['debitWalletId'] ?? "";
            $trustWalletId = "";
            if (isset($input['attributes'])
                && isset($input['attributes']['trustWallet'])
                && isset($input['attributes']['trustWallet']['uuid'])) {
                $trustWalletId = $input['attributes']['trustWallet']['uuid'];
            }
            if ($input['status'] == "AUTHORIZED") {
                $this->setTransactionId(
                    $input['attributes']['orderId'],
                    $input['id'],
                    $input['nsu'],
                    $debitWalletId,
                    $trustWalletId,
                    $json
                );
                $this->gumApi->queueTransaction($json);
            } elseif ($input['status'] == "CANCELED") {
                $this->cancelOrder($input['attributes']['orderId']);
                $this->gumApi->queueTransactionError($json);
            } else {
                $this->gumApi->queueTransactionError($json);
            }
        }
        /** @var Raw $result */
        $result = $this->context->getResultFactory()->create(ResultFactory::TYPE_RAW);
        return $result->setContents('');
    }

    /**
     * @param string $ameOrderId
     * @return void
     * @throws NotFoundException
     */
    public function cancelOrder(string $ameOrderId): void
    {
        /** @var Order $order */
        if ($order = $this->getOrderByAmeId($ameOrderId)) {
            if (!$order->isCanceled() && $order->canCancel()) {
                $order->addCommentToStatusHistory(__('Received cancel API callback'));
                $order->cancel()->save();
            }
        } else {
            /* @note This allows developers to process a not found canceled order callback. */
            $this->_eventManager->dispatch(
                'ame_callback_order_not_found_canceled',
                [
                    'ame_order_id' => $ameOrderId
                ]
            );
        }
    }

    /**
     * @param string $ameOrderId
     * @param string $ameTransactionId
     * @param string $nsu
     * @param string $debitWalledId
     * @param string $trustWalletId
     * @param string $json
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function setTransactionId(
        string $ameOrderId,
        string $ameTransactionId,
        string $nsu = "",
        string $debitWalledId = "",
        string $trustWalletId = "",
        string $json = ""
    ): void {
        if ($order = $this->getOrderByAmeId($ameOrderId)) {
            $payment = $order->getPayment();
            $payment->setAdditionalInformation(PaymentInformation::TRANSACTION_ID, $ameTransactionId);
            if ($nsu) {
                $payment->setAdditionalInformation(PaymentInformation::NSU, $nsu);
            }
            if ($trustWalletId) {
                $payment->setAdditionalInformation(PaymentInformation::TRUST_WALLET_UUID, $trustWalletId);
            }
            $order->addCommentToStatusHistory('Transaction ID: ' . $ameTransactionId . " | NSU: " . $nsu);
            $this->orderRepository->save($order);
        } else {
            /* @note This allows developers to process a not found APPROVED order callback. */
            $this->_eventManager->dispatch(
                'ame_callback_order_not_found_approved',
                [
                    'ame_order_id' => $ameOrderId,
                    'ame_transaction_id' => $ameTransactionId,
                    'nsu' => $nsu,
                    'trust_wallet_id' => $trustWalletId,
                    'callback_json' => $json
                ]
            );
        }
    }

    /**
     * @param string $ameOrderId
     * @return null|Order
     */
    public function getOrderByAmeId(string $ameOrderId): ?Order
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $where = "JSON_EXTRACT(sales_order_payment.additional_information, \"$."
            . PaymentInformation::AME_ID. "\") = '" . $ameOrderId . "'";
        $orderCollection->getSelect('additional_filter')
            ->join('sales_order_payment', 'main_table.entity_id = sales_order_payment.parent_id')
            ->where($where);
        if (!$orderCollection->count()) {
            return null;
        }
        /** @var \Magento\Sales\Model\Order $order */
        return $orderCollection->getFirstItem();
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

    /**
     * @return string
     */
    public function getCallbackUrl(): string
    {
        return $this->context->getUrl()->getBaseUrl() . 'm2amecallbackendpoint';
    }
}
