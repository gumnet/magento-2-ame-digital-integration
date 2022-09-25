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

namespace GumNet\AME\Api\Data;

interface AmeCallbackInterface
{
    const KEY_ID = 'entity_id';
    const CONTENT = 'content';
    const STATUS = 'status';
    const RETRIES = 'retries';
    const CREATED_AT = 'updated_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @param int $id
     * @return AmeCallbackInterface
     */
    public function setId(int $id): AmeCallbackInterface;

    /**
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * @param int $entityId
     * @return AmeCallbackInterface
     */
    public function setEntityId(int $entityId): AmeCallbackInterface;


    /**
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * @param string $content
     * @return AmeCallbackInterface
     */
    public function setContent(string $content): AmeCallbackInterface;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return AmeCallbackInterface
     */
    public function setStatus(string $status): AmeCallbackInterface;

    /**
     * @return int|null
     */
    public function getRetries(): ?int;

    /**
     * @param int $retries
     * @return AmeCallbackInterface
     */
    public function setRetries(int $retries): AmeCallbackInterface;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string $createdAt
     * @return AmeCallbackInterface
     */
    public function setCreatedAt(string $createdAt): AmeCallbackInterface;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @param string $updatedAt
     * @return AmeCallbackInterface
     */
    public function setUpdatedAt(string $updatedAt): AmeCallbackInterface;
}
