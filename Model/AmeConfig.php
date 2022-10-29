<?php


declare(strict_types=1);

namespace GumNet\AME\Model;

use GumNet\AME\Api\Data\AmeConfigInterface;
use GumNet\AME\Api\Data\AmeConfigInterfaceFactory;
use GumNet\AME\Model\ResourceModel\AmeConfig\Collection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

/**
 * @codeCoverageIgnore
 */
class AmeConfig extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'ame_config';

    protected $_cacheTag = 'ame_config';

    protected $_eventPrefix = 'ame_config';

    protected $ameConfigFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * AmeConfig constructor.
     * @param Context $context
     * @param Registry $registry
     * @param AmeConfigInterfaceFactory $ameConfigFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel\AmeConfig $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AmeConfigInterfaceFactory $ameConfigFactory,
        DataObjectHelper $dataObjectHelper,
        ResourceModel\AmeConfig $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->ameConfigFactory = $ameConfigFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->_init(GumNet\AME\Model\ResourceModel\AmeConfig::class);
    }

    public function _construct()
    {
        $this->_init(ResourceModel\AmeConfig::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
    /**
     * Return the Data Model
     *
     * @return AmeConfigInterface
     */
    public function getDataModel(): AmeConfigInterface
    {
        $dataModel = $this->ameConfigFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $dataModel,
            $this->getData(),
            AmeConfigInterface::class
        );

        return $dataModel;
    }
}
