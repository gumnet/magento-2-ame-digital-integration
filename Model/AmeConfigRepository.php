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

namespace GumNet\AME\Model;

use GumNet\AME\Api\Data\AmeConfigInterface;
use GumNet\AME\Api\AmeConfigRepositoryInterface;
use GumNet\AME\Model\ResourceModel\AmeConfig as AmeConfigResource;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @codeCoverageIgnore
 */
class AmeConfigRepository implements AmeConfigRepositoryInterface
{
    /**
     * @var AmeConfigResource
     */
    private $resource;

    /**
     * @var AmeConfigFactory
     */
    private $ameConfigFactory;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * AmeConfigRepository constructor.
     * @param AmeConfigResource $resource
     * @param AmeConfigFactory $ameConfigFactory
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        AmeConfigResource $resource,
        AmeConfigFactory $ameConfigFactory,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->ameConfigFactory = $ameConfigFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * @inheritDoc
     */
    public function save(AmeConfigInterface $ameConfig): AmeConfigInterface
    {
        $ameConfigData = $this->extensibleDataObjectConverter->toNestedArray(
            $ameConfig,
            [],
            AmeConfigInterface::class
        );

        $ameConfigModel = $this->ameConfigFactory->create()
            ->setData($ameConfigData);

        $this->resource->save($ameConfigModel);

        return $ameConfig;
    }

    /**
     * @inheritDoc
     */
    public function get(int $id): AmeConfigInterface
    {
        $ameConfig = $this->ameConfigFactory->create()
            ->load($id);

        if ($ameConfig->getId() === null) {
            throw NoSuchEntityException::singleField('entity_id', $id);
        }

        return $ameConfig->getDataModel();
    }

    /**
     * @inheritDoc
     */
    public function getByConfig(string $config): ?AmeConfigInterface
    {
        $id = $this->resource->getIdByConfig($config);
        return $this->get($id);
    }
}
