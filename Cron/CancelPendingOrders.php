<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020-2021 GumNet (https://gum.net.br)
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

class CancelPendingOrders
{
    protected $logger;
    protected $collectionFactory;
    protected $orderFactory;
    protected $scopeConfig;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
    }
    public function execute()
    {
        $expires_in = 1;
        if(!$expires_in) return;
        $to = date('Y-m-d H:i:s',time()-86400*$expires_in);
        $orderCollection = $this->getOrderCollection($to,'ame');
        $this->cancelOrders($orderCollection);
    }
    public function getOrderCollection($to,$method)
    {
        $orderCollection = $this->collectionFactory->create()->addFieldToSelect(array('*'));
        $orderCollection->addFieldToFilter('created_at', array('lteq' => $to));
        $orderCollection->addFieldToFilter('state', array('eq' => 'new'));
        $orderCollection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?',[$method] );
        return $orderCollection;
    }
    public function cancelOrders($orderCollection)
    {
        foreach($orderCollection as $item) {
            $order = $this->orderFactory->create()->load($item->getId());
            $order->cancel()->save();
            $this->logger->info("AME: automatic cancel expired order ID: ".$order->getId());
        }
    }
}
