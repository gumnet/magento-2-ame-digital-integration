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

use GumNet\AME\Api\Data\AmeOrderInterface;
use GumNet\AME\Api\Data\AmeOrderInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;


class AmeOrder extends \Magento\Framework\Model\AbstractModel
{

    protected $_eventPrefix = 'gumnet_ame_ame_order';
    protected $ame_orderDataFactory;

    protected $dataObjectHelper;


    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param AmeOrderInterfaceFactory $ame_orderDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \GumNet\AME\Model\ResourceModel\AmeOrder $resource
     * @param \GumNet\AME\Model\ResourceModel\AmeOrder\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        AmeOrderInterfaceFactory $ame_orderDataFactory,
        DataObjectHelper $dataObjectHelper,
        \GumNet\AME\Model\ResourceModel\AmeOrder $resource,
        \GumNet\AME\Model\ResourceModel\AmeOrder\Collection $resourceCollection,
        array $data = []
    ) {
        $this->ame_orderDataFactory = $ame_orderDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve ame_order model with ame_order data
     * @return AmeOrderInterface
     */
    public function getDataModel()
    {
        $ame_orderData = $this->getData();

        $ame_orderDataObject = $this->ame_orderDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $ame_orderDataObject,
            $ame_orderData,
            AmeOrderInterface::class
        );

        return $ame_orderDataObject;
    }
}

