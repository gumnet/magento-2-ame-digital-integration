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

namespace GumNet\AME\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
        $installer = $setup;

        $installer->startSetup();
        // 0.0.2
        if(version_compare($context->getVersion(), '0.0.2', '<')) {
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_config' ),
                'ame_value',
                'ame_value',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_log' ),
                'message',
                'message',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_log' ),
                'input',
                'input',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_order' ),
                'ame_id',
                'ame_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_order' ),
                'splits',
                'splits',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_order' ),
                'qr_code_link',
                'qr_code_link',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_order' ),
                'deep_link',
                'deep_link',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_refund' ),
                'ame_id',
                'ame_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_refund' ),
                'refund_id',
                'refund_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
            $installer->getConnection()->changeColumn(
                $installer->getTable( 'ame_refund' ),
                'operation_id',
                'operation_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
        }
        // 0.0.3
        if(version_compare($context->getVersion(), '0.0.3', '<')) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable('ame_callback'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'json',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Json Input'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'created_at'
                )                ->setComment("AME Callback Log");
            $setup->getConnection()->createTable($table);
        }
        // 0.0.4
//        if(version_compare($context->getVersion(), '0.0.4', '<')) {
//            $installer->getConnection()->changeColumn(
//                $installer->getTable( 'ame_config' ),
//                'option',
//                'ame_option',
//                [
//                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//                    'length' => 255
//                ]
//            );
//            $installer->getConnection()->changeColumn(
//                $installer->getTable( 'ame_config' ),
//                'value',
//                'ame_value',
//                [
//                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//                    'length' => 65535
//                ]
//            );
//        }
        // 0.0.5
        if(version_compare($context->getVersion(), '0.0.5', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('ame_order'),
                'created_at',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'comment' => 'Created at'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('ame_order'),
                'updated_at',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'comment' => 'Updated at'
                ]
            );

        }
        if(version_compare($context->getVersion(), '0.0.6', '<')) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable('ame_transaction'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'ame_order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    2048,
                    ['nullable' => false, 'default' => ''],
                    'AME ORDER ID'
                )
                ->addColumn(
                    'ame_transaction_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    2048,
                    ['nullable' => false, 'default' => ''],
                    'AME TRANSACTION ID'
                )
                ->addColumn(
                    'amount',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                    'Amount'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Status'
                )
                ->addColumn(
                    'operation_type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Operation Type'
                )
                ->setComment("AME Transactions");
            $setup->getConnection()->createTable($table);
        }
        if(version_compare($context->getVersion(), '0.0.7', '<')) {
            $installer->getConnection()->changeColumn(
                $installer->getTable('ame_callback'),
                'json',
                'json',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 65535
                ]
            );
        }
        if(version_compare($context->getVersion(), '0.0.8', '<')) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable('ame_transaction_split'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'ame_transaction_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    2048,
                    ['nullable' => false, 'default' => ''],
                    'AME TRANSACTION ID'
                )
                ->addColumn(
                    'ame_transaction_split_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    2048,
                    ['nullable' => false, 'default' => ''],
                    'AME TRANSACTION SPLIT ID'
                )
                ->addColumn(
                    'ame_transaction_split_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'AME Transaction Split Date'
                )
                ->addColumn(
                    'amount',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                    'Amount'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Status'
                )
                ->addColumn(
                    'cash_type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Cash Type'
                )
                ->addColumn(
                    'others',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    65535,
                    ['nullable' => false, 'default' => ''],
                    'Others'
                )
                ->setComment("AME Transactions");
            $setup->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
