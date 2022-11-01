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

namespace GumNet\AME\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Setup\Context;
use GumNet\AME\Model\Values\Config;

class MigrateConfig implements DataPatchInterface
{
    protected $connection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->connection = $resource->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
          $table = $this->connection->getTableName(Config::TABLE_CORE_DATA);
        foreach ($this->getConfigArray() as $config) {
              $this->updateConfig($table, $config['old'], $config['new']);
        }
    }

    /**
     * @param string $table
     * @param string $oldPath
     * @param string $newPath
     * @return void
     */
    protected function updateConfig(string $table, string $oldPath, string $newPath): void
    {
        $data = ['path' => $newPath];
        $where = ['path = ?' => $oldPath];
        $this->connection->update($table, $data, $where);
    }

    /**
     * @return array[]
     */
    protected function getConfigArray(): array
    {
        return [
            [
                'old' => Config::API_USER_OLD,
                'new' => Config::API_USER
            ],
            [
                'old' => Config::API_PASSWORD_OLD,
                'new' => Config::API_PASSWORD
            ],
            [
                'old' => Config::ENVIRONMENT_OLD,
                'new' => Config::ENVIRONMENT
            ],
            [
                'old' => Config::STATUS_CREATED_OLD,
                'new' => Config::STATUS_CREATED
            ],
            [
                'old' => Config::STATUS_PROCESSING_OLD,
                'new' => Config::STATUS_PROCESSING
            ],
            [
                'old' => Config::ADDRESS_STREET_OLD,
                'new' => Config::ADDRESS_STREET
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NEIGHBORHOOD_OLD,
                'new' => Config::ADDRESS_NEIGHBORHOOD
            ],
            [
                'old' => Config::EXHIBITION_LIST_OLD,
                'new' => Config::EXHIBITION_LIST
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
