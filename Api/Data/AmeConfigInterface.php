<?php

namespace GumNet\AME\Api\Data;

interface AmeConfigInterface
{
    const KEY_ID = 'id';
    const AME_OPTION = 'ame_option';
    const AME_VALUE = 'ame_value';

    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @param int $id
     * @return AmeConfigInterface
     */
    public function setId(int $id): AmeConfigInterface;

    /**
     * @return string|null
     */
    public function getOption(): ?string;

    /**
     * @param string $option
     * @return AmeConfigInterface
     */
    public function setOption(string $option): AmeConfigInterface;

    /**
     * @return string|null
     */
    public function getValue(): ?string;

    /**
     * @param string $value
     * @return AmeConfigInterface
     */
    public function setValue(string $value): AmeConfigInterface;
}
