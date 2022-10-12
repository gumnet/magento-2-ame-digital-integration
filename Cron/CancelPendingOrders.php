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

namespace GumNet\AME\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

class CancelPendingOrders
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param LoggerInterface $logger
     * @param CollectionFactory $collectionFactory
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $expiresIn = 1;
        if (!$expiresIn) {
            return;
        }
        $to = date('Y-m-d H:i:s', time() - 86400 * $expiresIn);
        $orderCollection = $this->getOrderCollection($to, 'ame');
        $this->cancelOrders($orderCollection);
    }

    /**
     * @param $to
     * @param $method
     * @return Collection
     */
    public function getOrderCollection($to, $method): Collection
    {
        $orderCollection = $this->collectionFactory->create()->addFieldToSelect(['*']);
        $orderCollection->addFieldToFilter('created_at', ['lteq' => $to]);
        $orderCollection->addFieldToFilter('state', ['eq' => 'new']);
        $orderCollection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                ['method']
            )
            ->where('sop.method = ?', [$method]);
        return $orderCollection;
    }

    /**
     * @param $orderCollection
     * @return void
     * @throws \Exception
     */
    public function cancelOrders($orderCollection): void
    {
        foreach ($orderCollection as $item) {
            $order = $this->orderFactory->create()->load($item->getId());
            $order->cancel()->save();
            $this->logger->info("AME: automatic cancel expired order ID: ".$order->getId());
        }
    }
}
