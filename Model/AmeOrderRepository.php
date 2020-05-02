<?php
/**
 * @author Gustavo Ulyssea - gustavo.ulyssea@gmail.com
 * @copyright Copyright (c) 2020 GumNet (https://gum.net.br)
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

use GumNet\AME\Api\AmeOrderRepositoryInterface;
use GumNet\AME\Api\Data\AmeOrderInterfaceFactory;
use GumNet\AME\Api\Data\AmeOrderSearchResultsInterfaceFactory;
use GumNet\AME\Model\ResourceModel\AmeOrder as ResourceAmeOrder;
use GumNet\AME\Model\ResourceModel\AmeOrder\CollectionFactory as AmeOrderCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;


class AmeOrderRepository implements AmeOrderRepositoryInterface
{

    protected $extensionAttributesJoinProcessor;

    protected $searchResultsFactory;

    private $storeManager;

    protected $dataObjectProcessor;

    protected $ameOrderCollectionFactory;

    protected $dataObjectHelper;

    protected $ameOrderFactory;

    protected $extensibleDataObjectConverter;
    protected $resource;

    protected $dataAmeOrderFactory;

    private $collectionProcessor;


    /**
     * @param ResourceAmeOrder $resource
     * @param AmeOrderFactory $ameOrderFactory
     * @param AmeOrderInterfaceFactory $dataAmeOrderFactory
     * @param AmeOrderCollectionFactory $ameOrderCollectionFactory
     * @param AmeOrderSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceAmeOrder $resource,
        AmeOrderFactory $ameOrderFactory,
        AmeOrderInterfaceFactory $dataAmeOrderFactory,
        AmeOrderCollectionFactory $ameOrderCollectionFactory,
        AmeOrderSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->ameOrderFactory = $ameOrderFactory;
        $this->ameOrderCollectionFactory = $ameOrderCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataAmeOrderFactory = $dataAmeOrderFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \GumNet\AME\Api\Data\AmeOrderInterface $ameOrder
    ) {
        /* if (empty($ameOrder->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $ameOrder->setStoreId($storeId);
        } */

        $ameOrderData = $this->extensibleDataObjectConverter->toNestedArray(
            $ameOrder,
            [],
            \GumNet\AME\Api\Data\AmeOrderInterface::class
        );

        $ameOrderModel = $this->ameOrderFactory->create()->setData($ameOrderData);

        try {
            $this->resource->save($ameOrderModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the ameOrder: %1',
                $exception->getMessage()
            ));
        }
        return $ameOrderModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($ameOrderId)
    {
        $ameOrder = $this->ameOrderFactory->create();
        $this->resource->load($ameOrder, $ameOrderId);
        if (!$ameOrder->getId()) {
            throw new NoSuchEntityException(__('ame_order with id "%1" does not exist.', $ameOrderId));
        }
        return $ameOrder->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->ameOrderCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \GumNet\AME\Api\Data\AmeOrderInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \GumNet\AME\Api\Data\AmeOrderInterface $ameOrder
    ) {
        try {
            $ameOrderModel = $this->ameOrderFactory->create();
            $this->resource->load($ameOrderModel, $ameOrder->getAmeOrderId());
            $this->resource->delete($ameOrderModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ame_order: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($ameOrderId)
    {
        return $this->delete($this->get($ameOrderId));
    }
}

