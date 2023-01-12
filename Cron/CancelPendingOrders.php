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
declare(strict_types=1);

namespace GumNet\AME\Cron;

use GumNet\AME\Model\AME;
use GumNet\AME\Model\Values\Config;
use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;

class CancelPendingOrders
{
    public const PAYMENT_TABLE = 'sales_order_payment';
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var AbstractDb
     */
    protected $abstractDb;

    /**
     * @param LoggerInterface $logger
     * @param CollectionFactory $orderCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param AbstractDb $abstractDb
     */
    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository,
        AbstractDb $abstractDb
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->abstractDb = $abstractDb;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!$cancelAfterDays = $this->scopeConfig->getValue(
            Config::CANCEL_PENDING_DAYS,
            ScopeInterface::SCOPE_STORE
        )) {
            return;
        }

        $this->logger->info('AME - Starting cancel pending orders CRON.');
        $orderCollection = $this->getPendingOrders((int)$cancelAfterDays);
        /** @var Order $order */
        foreach ($orderCollection->getItems() as $order) {
            if (!$order->getPayment()->getAdditionalInformation(PaymentInformation::TRANSACTION_ID)
                && !$order->hasInvoices()) {
                $this->logger->info("AME - Payment not detected - cancel pending order: " . $order->getIncrementId());
                $order->cancel();
                $this->orderRepository->save($order);
            }
        }
    }

    /**
     * @param int $cancelAfterDays
     * @return OrderCollection
     */
    public function getPendingOrders(int $cancelAfterDays): OrderCollection
    {
        $cancelAfterDays++;
        $cancelTo = date("Y-m-d h:i:s", strtotime("-" . $cancelAfterDays . " day"));
        $orderCollection = $this->orderCollectionFactory->create();
        $table = $this->abstractDb->getTable(self::PAYMENT_TABLE);
        $orderCollection->join(
            [self::PAYMENT_TABLE => $table],
            'main_table.entity_id = ' . self::PAYMENT_TABLE .'.parent_id',
            ['method']
        );
        $orderCollection->addFieldToFilter(self::PAYMENT_TABLE . '.method', ['eq' => AME::CODE]);
        $orderCollection->addFieldToFilter('created_at', ['lteq' => $cancelTo]);
        $orderCollection->addFieldToFilter('state', ['in' => [Order::STATE_PENDING_PAYMENT, Order::STATE_NEW]]);
        return $orderCollection;
    }
}
