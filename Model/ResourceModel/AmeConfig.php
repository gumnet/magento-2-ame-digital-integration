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

namespace GumNet\AME\Model\ResourceModel;

use GumNet\AME\Api\Data\AmeConfigInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @codeCoverageIgnore
 */
class AmeConfig extends AbstractDb
{
    public const TABLE = 'ame_config';
    public const KEY_ID = 'entity_id';
    /**
     * {@inheritdoc}
     */
    protected $_useIsObjectNew = true;

    /**
     * {@inheritdoc}
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, self::KEY_ID);
    }

    public function getIdByConfig(string $config): int
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(self::TABLE, self::KEY_ID)
            ->where(AmeConfigInterface::AME_OPTION . " = '" . $config . "'");
        return (int)$connection->fetchOne($select);
    }
}
