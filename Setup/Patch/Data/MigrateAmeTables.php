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

namespace GumNet\AME\Setup\Patch\Data;

use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory;

class MigrateAmeTables implements DataPatchInterface
{
    protected $setup;

    protected $orderRepository;

    protected $orderFactory;

    protected $collectionFactory;

    protected $appState;

    public function __construct(
        ModuleDataSetupInterface $setup,
        OrderRepositoryInterface $orderRepository,
        OrderFactory $orderFactory,
        CollectionFactory $collectionFactory,
        State $appState
    ) {
        $this->setup = $setup;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->collectionFactory = $collectionFactory;
        $this->appState = $appState;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        $conn = $this->setup->getConnection();
        $conn->startSetup();
        $tableAmeOrder = $conn->getTableName(PaymentInformation::OLD_AME_ORDER_TABLE);
        if ($conn->isTableExists($tableAmeOrder)) {
            $this->migrateAmeOrder($tableAmeOrder);
        }

        $tableAmeTransaction = $conn->getTableName(PaymentInformation::OLD_AME_TRANSACTION_TABLE);
        if ($conn->isTableExists($tableAmeTransaction)) {
            $this->migrateAmeTransaction($tableAmeTransaction);
        }

        $conn->endSetup();
    }

    public function migrateAmeOrder(string $tableAmeOrder): void
    {
        $conn = $this->setup->getConnection();
        $sql = $conn->select()->from($tableAmeOrder);
        $tableAmeOrderEntries = $conn->fetchAssoc($sql);
        foreach ($tableAmeOrderEntries as $orderEntry) {
            $orderId = $this->orderFactory->create()->loadByIncrementId($orderEntry['increment_id'])->getEntityId();
            if (!$orderId) {
                continue;
            }
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();
            if (isset($orderEntry[PaymentInformation::OLD_AME_ORDER_ID])
                && $orderEntry[PaymentInformation::OLD_AME_ORDER_ID]) {
                $payment->setAdditionalInformation(
                    PaymentInformation::AME_ID,
                    $orderEntry[PaymentInformation::OLD_AME_ORDER_ID]
                );
            }
            if (isset($orderEntry[PaymentInformation::AMOUNT]) && $orderEntry[PaymentInformation::AMOUNT]) {
                $payment->setAdditionalInformation(PaymentInformation::AMOUNT, $orderEntry['amount']);
            }
            if (isset($orderEntry[PaymentInformation::OLD_AME_ORDER_CASHBACK_AMOUNT])
                && $orderEntry[PaymentInformation::OLD_AME_ORDER_CASHBACK_AMOUNT]) {
                $payment->setAdditionalInformation(
                    PaymentInformation::CASHBACK_VALUE,
                    $orderEntry[PaymentInformation::OLD_AME_ORDER_CASHBACK_AMOUNT]
                );
            }
            if (isset($orderEntry[PaymentInformation::OLD_AME_ORDER_QR_CODE])
                && $orderEntry[PaymentInformation::OLD_AME_ORDER_QR_CODE]) {
                $payment->setAdditionalInformation(
                    PaymentInformation::QR_CODE_LINK,
                    $orderEntry[PaymentInformation::OLD_AME_ORDER_QR_CODE]
                );
            }
            if (isset($orderEntry[PaymentInformation::OLD_AME_ORDER_DEEP_LINK])
                && $orderEntry[PaymentInformation::OLD_AME_ORDER_DEEP_LINK]) {
                $payment->setAdditionalInformation(
                    PaymentInformation::DEEP_LINK,
                    $orderEntry[PaymentInformation::OLD_AME_ORDER_DEEP_LINK]
                );
            }
            $order->setPayment($payment);
            $this->orderRepository->save($order);
        }
    }

    public function migrateAmeTransaction(string $tableAmeTransaction): void
    {
        $conn = $this->setup->getConnection();
        $sql = $conn->select()->from($tableAmeTransaction);
        $transactionEntries = $conn->fetchAssoc($sql);
        foreach ($transactionEntries as $transactionEntry) {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToSelect('entity_id');
            $collection->getSelect()->where(
                "JSON_EXTRACT(additional_information, '$." . PaymentInformation::AME_ID."') = '"
                . $transactionEntry[PaymentInformation::OLD_AME_TRANSACTION_ORDER_ID] . "'"
            );
            if (!$collection->count()) {
                continue;
            }
            if (!$orderId = $collection->getFirstItem()->getData('parent_id')) {
                continue;
            }
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();
            if (isset($transactionEntry[PaymentInformation::OLD_AME_TRANSACTION_ID])
                && $transactionEntry[PaymentInformation::OLD_AME_TRANSACTION_ID]) {
                $payment->setAdditionalInformation(
                    PaymentInformation::TRANSACTION_ID,
                    $transactionEntry[PaymentInformation::OLD_AME_TRANSACTION_ID]
                );
                $order->setPayment($order);
                $this->orderRepository->save($order);
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [MigrateConfig::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
