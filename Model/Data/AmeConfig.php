<?php

namespace GumNet\AME\Model\Data;

use GumNet\AME\Api\Data\AmeConfigInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class AmeConfig extends AbstractExtensibleModel implements AmeConfigInterface
{
    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        return $this->getData(self::KEY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId(int $id): AmeConfigInterface
    {
        return $this->setData(self::KEY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getOption(): ?string
    {
        return $this->getData(self::AME_OPTION);
    }

    /**
     * @inheritDoc
     */
    public function setOption(string $option): AmeConfigInterface
    {
        return $this->setData(self::AME_OPTION, $option);
    }

    /**
     * @inheritDoc
     */
    public function getValue(): ?string
    {
        return $this->getData(self::AME_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): AmeConfigInterface
    {
        return $this->setData(self::AME_VALUE, $value);
    }
}
