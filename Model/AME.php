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

namespace GumNet\AME\Model;

use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Validator\Exception;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use GumNet\AME\Model\Values\Config;

/**
 * Pay In Store payment method model
 */
class AME extends AbstractMethod
{
    public const CODE = 'ame';
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::CODE;
    protected $_methodCode = self::CODE;
    protected $_isOffline = false;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['BRL'];

    /**
     * @var ApiClient
     */
    protected $ame;

    /**
     * AME constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ApiClient $api
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DirectoryHelper|null $directory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ApiClient $api,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
        $this->ame = $api;
    }

    /**
     * Order creation
     * @param InfoInterface $payment
     * @param float $amount
     * @return AME
     * @throws NoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function order(InfoInterface $payment, $amount): AME
    {
        /** @var  \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $resultArray = json_decode($this->ame->createOrder($order), true);
        $this->setAdditionalInformation($payment, $resultArray);
        $newStatus = "pending_payment";
        $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus($newStatus);
        $order->addStatusHistoryComment('AME Order ID: ' . $resultArray['id']);
        return $this;
    }

    /**
     * @param $order
     * @param array $resultArray
     * @return void
     */
    public function setAdditionalInformation(
        $payment,
        array $resultArray
    ): void {
        $payment->setAdditionalInformation(PaymentInformation::AME_ID, $resultArray['id']);
        $payment->setAdditionalInformation(PaymentInformation::AMOUNT, $resultArray[PaymentInformation::AMOUNT]);
        $payment->setAdditionalInformation(
            PaymentInformation::QR_CODE_LINK,
            $resultArray[PaymentInformation::QR_CODE_LINK]
        );
        $payment->setAdditionalInformation(PaymentInformation::DEEP_LINK, $resultArray[PaymentInformation::DEEP_LINK]);
        if (array_key_exists('cashbackAmountValue', $resultArray['attributes'])) {
            $payment->setAdditionalInformation(
                PaymentInformation::CASHBACK_VALUE,
                $resultArray['attributes']['cashbackAmountValue']
            );
        }
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        return (bool)$this->_scopeConfig->getValue(Config::ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return AME
     * @throws LocalizedException
     * @throws Exception
     */
    public function refund(InfoInterface $payment, $amount): AME
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        }
        try {
            if ($transactionId = $payment->getAdditionalInformation(PaymentInformation::TRANSACTION_ID)) {
                $this->ame->refundOrder($transactionId, $amount * 100);
            }
        } catch (\Exception $e) {
            throw new IntegrationException(__('Payment ApiClient refund error.'));
        }
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @return AME
     */
    public function cancel(InfoInterface $payment): AME
    {
        if ($ameId = $payment->getAdditionalInformation(PaymentInformation::AME_ID)) {
            $this->ame->cancelOrder($ameId);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function canRefund(): bool
    {
        return true;
    }
}
