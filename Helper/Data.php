<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ){
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get store config value
     *
     * @param $path
     * @return mixed
     */
    private function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Check if the data feed module is enabled
     *
     * @return mixed
     */
    public function isDataFeedModuleEnabled()
    {
        return $this->getStoreConfig('velou_config/settings/module_enable');
    }

    /**
     * Get the API URL
     *
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->getStoreConfig('velou_config/settings/api_host_url');
    }

    /**
     * Get the API Key
     *
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->getStoreConfig('velou_config/settings/api_key');
    }

    /**
     * Get the store ID
     *
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->getStoreConfig('velou_config/settings/store_id');
    }

    /**
     * Check if the catalog sync is enabled
     *
     * @return mixed
     */
    public function isCatalogSyncEnabled()
    {
        return $this->getStoreConfig('velou_config/data_sync/catalog_sync');
    }

    /**
     * Get the catalog product attributes to sync
     *
     * @return mixed
     */
    public function getProductCustomAttributesToSync()
    {
        return $this->getStoreConfig('velou_config/data_sync/catalog_custom_attribute_to_sync');
    }
}
