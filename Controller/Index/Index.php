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

use GumNet\AME\Helper\GumApi;
use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

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
     * @var RawFactory
     */
    protected $resultFactory;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var GumApi
     */
    protected $gumApi;

    protected $api;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepository $orderRepository
     * @param RawFactory $resultFactory
     * @param CollectionFactory $orderCollectionFactory
     * @param GumApi $gumApi
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ScopeConfigInterface$scopeConfig,
        OrderRepository $orderRepository,
        RawFactory  $resultFactory,
        CollectionFactory $orderCollectionFactory,
        GumApi $gumApi,
        \GumNet\AME\Model\ApiClient $api,
        array $data = []
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->resultFactory = $resultFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->gumApi = $gumApi;
        $this->api = $api;
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
        $input = json_decode($json, true);
        // verify if id exists
        if (!array_key_exists('id', $input)
            || !array_key_exists('attributes', $input)) {
            $message = __('AME Callback - JSON missing keys- ' . $json);
            throw new InputException($message);
        }
        if ($input['status'] == "AUTHORIZED") {
            $this->setTransactionId($input['attributes']['orderId'], $input['id'], $input['nsu']);
            $this->gumApi->queueTransaction($json);
        } else {
            $this->gumApi->queueTransactionError($json);
        }
        $result = $this->resultFactory->create();
        return $result->setContents('');
    }

    /**
     * @param string $ameOrderId
     * @param string $ameTransactionId
     * @param string $nsu
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     */
    public function setTransactionId(string $ameOrderId, string $ameTransactionId, string $nsu = "")
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $where = "JSON_EXTRACT(additional_information, '$." . PaymentInformation::AME_ID. ") = " . $ameOrderId;
        $orderCollection->getSelect('additional_filter')->where($where);
        if (!$orderCollection->count()) {
            $message = __("AME Callback - Order with ID '" . $ameOrderId . "' not found.");
            throw new \Magento\Framework\Exception\NotFoundException($message);
        }
        /** @var \Magento\Sales\Model\Order $order */
        $order = $orderCollection->getFirstItem();
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(PaymentInformation::TRANSACTION_ID, $ameTransactionId);
        if ($nsu) {
            $payment->setAdditionalInformation(PaymentInformation::NSU, $nsu);
        }
        $this->orderRepository->save($order);
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
