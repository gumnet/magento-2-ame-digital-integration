<?php
namespace GumNet\AME\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface AmeOrderRepositoryInterface
{

    /**
     * Save ame_order
     * @param \GumNet\AME\Api\Data\AmeOrderInterface $ameOrder
     * @return \GumNet\AME\Api\Data\AmeOrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \GumNet\AME\Api\Data\AmeOrderInterface $ameOrder
    );

    /**
     * Retrieve ame_order
     * @param string $ameOrderId
     * @return \GumNet\AME\Api\Data\AmeOrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($ameOrderId);

    /**
     * Retrieve ame_order matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \GumNet\AME\Api\Data\AmeOrderSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ame_order
     * @param \GumNet\AME\Api\Data\AmeOrderInterface $ameOrder
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \GumNet\AME\Api\Data\AmeOrderInterface $ameOrder
    );

    /**
     * Delete ame_order by ID
     * @param string $ameOrderId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($ameOrderId);
}

