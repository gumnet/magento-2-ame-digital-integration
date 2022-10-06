<?php


namespace GumNet\AME\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Setup\Context;
use GumNet\AME\Values\Config;

class MigrateConfig implements DataPatchInterface
{
    const TABLE_CORE_DATA = 'core_config_data';
    const API_USER_OLD = 'ame/general/api_user';
    const API_USER = 'payment/ame/api_user';
    const API_PASSWORD_OLD = 'ame/general/api_password';
    const API_PASSWORD = 'payment/ame/api_password';
    const ENVIRONMENT_OLD = 'ame/general/environment';
    const ENVIRONMENT = 'payment/ame/environment';
    const STATUS_CREATED_OLD = 'ame/general/order_status_created';
    const STATUS_CREATED = 'payment/ame/order_status_created';
    const STATUS_PROCESSING_OLD = 'ame/general/order_status_payment_received';
    const STATUS_PROCESSING = 'payment/ame/order_status_payment_received';
    const ADDRESS_STREET_OLD = 'ame/address/street';
    const ADDRESS_STREET = 'payment/ame/address_street';
    const ADDRESS_NUMBER_OLD = 'ame/address/number';
    const ADDRESS_NUMBER = 'payment/ame/address_number';
    const ADDRESS_NEIGHBORHOOD_OLD = 'ame/address/neighborhood';
    const ADDRESS_NEIGHBORHOOD = 'payment/ame/address_neighborhood';
    const EXHIBITION_LIST_OLD = 'ame/exhibition/show_cashback_products_list';
    const EXHIBITION_LIST = 'payment/ame/show_cashback_products_list';


    protected $context;

    protected $connection;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->context = $context;
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

    protected function updateConfig(string $table, string $oldPath, string $newPath): void
    {
        $data = ['path' => $newPath];
        $where = ['path = ?' => $oldPath];
        $this->connection->update($table, $data, $where);
    }

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
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
            ],
            [
                'old' => Config::ADDRESS_NUMBER_OLD,
                'new' => Config::ADDRESS_NUMBER
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

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }
}
