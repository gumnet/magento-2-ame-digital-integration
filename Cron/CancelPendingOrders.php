<?php
declare(strict_types=1);

namespace GumNet\AME\Observer;

use GumNet\AME\Model\AME;
use GumNet\AME\Model\Values\Config;
use GumNet\AME\Model\Values\PaymentInformation;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;

class CancelPendingOrders
{
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
     * @param LoggerInterface $logger
     * @param CollectionFactory $orderCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
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
                && $order->getPayment()->getMethod() == AME::CODE
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
        $orderCollection->addFieldToFilter('created_at', ['lteq' => $cancelTo]);
        $orderCollection->addFieldToFilter('state', ['eq' => Order::STATE_PENDING_PAYMENT]);
        return $orderCollection;
    }
}
