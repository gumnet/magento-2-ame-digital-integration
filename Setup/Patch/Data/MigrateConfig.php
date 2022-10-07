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
