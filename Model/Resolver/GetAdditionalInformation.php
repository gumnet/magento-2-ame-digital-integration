<?php

namespace GumNet\AME\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class GetAdditionalInformation implements ResolverInterface
{
    protected $orderFactory;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->orderFactory = $orderFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!is_array($value) || !isset($value['increment_id'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var \Magento\Sales\Model\Order $orderData */
        $order = $this->orderFactory->create()->loadByIncrementId($value['increment_id']);
        return $order->getPayment()->getAdditionalInformation();
    }
}
