<?php

namespace GumNet\AME\Api\Data;

interface AmeOrderInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const INCREMENT_ID = 'increment_id';
    const AME_ORDER_ID = 'ame_order_id';
    const AMOUNT = 'amount';
    const ID = 'id';
    const AME_ID = 'ame_id';

    /**
     * Get ame_order_id
     * @return string|null
     */
    public function getAmeOrderId();

    /**
     * Set ame_order_id
     * @param string $ameOrderId
     * @return \GumNet\AME\Api\Data\AmeOrderInterface
     */
    public function setAmeOrderId($ameOrderId);

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param string $id
     * @return \GumNet\AME\Api\Data\AmeOrderInterface
     */
    public function setId($id);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \GumNet\AME\Api\Data\AmeOrderExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \GumNet\AME\Api\Data\AmeOrderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \GumNet\AME\Api\Data\AmeOrderExtensionInterface $extensionAttributes
    );

    /**
     * Get increment_id
     * @return string|null
     */
    public function getIncrementId();

    /**
     * Set increment_id
     * @param string $incrementId
     * @return \GumNet\AME\Api\Data\AmeOrderInterface
     */
    public function setIncrementId($incrementId);

    /**
     * Get ame_id
     * @return string|null
     */
    public function getAmeId();

    /**
     * Set ame_id
     * @param string $ameId
     * @return \GumNet\AME\Api\Data\AmeOrderInterface
     */
    public function setAmeId($ameId);

    /**
     * Get amount
     * @return string|null
     */
    public function getAmount();

    /**
     * Set amount
     * @param string $amount
     * @return \GumNet\AME\Api\Data\AmeOrderInterface
     */
    public function setAmount($amount);
}

