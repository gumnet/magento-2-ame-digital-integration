<?php


namespace GumNet\AME\Api\Data;


interface AmeOrderSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get ame_order list.
     * @return \GumNet\AME\Api\Data\AmeOrderInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \GumNet\AME\Api\Data\AmeOrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

