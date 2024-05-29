<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Store\Model\StoreManagerInterface;
use Velou\DataFeed\Model\LogFactory as LogFactory;
use Velou\DataFeed\Model\ResourceModel\LogFactory as LogResourceModelFactory;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var LogFactory
     */
    protected $logFactory;

    /**
     * @var LogResourceModelFactory
     */
    protected $logResourceModelFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LogFactory $logFactory,
        LogResourceModelFactory $logResourceModelFactory,
        StoreManagerInterface $storeManager,
    ){
        $this->scopeConfig = $scopeConfig;
        $this->logFactory = $logFactory;
        $this->logResourceModelFactory = $logResourceModelFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get store config value
     *
     * @param $path
     * @return mixed
     */
    private function  getStoreConfig($path)
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

    /**
     * Add log record to the velou_datafeed_log table
     *
     */
    public function addLogMessage($job, $message, $messageType = 'info',$trace = null) {
        $log = $this->logFactory->create();
        $logResourceModel = $this->logResourceModelFactory->create();
        $log->setJob($job);
        $log->setCreatedAt(date('Y-m-d H:i:s'));
        $log->setMessageType($messageType);
        $log->setLogMessage($message);
        $log->setLogTrace($trace);
        $logResourceModel->save($log);
    }

    /**
     * Get the product placeholder image
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getProductPlaceHolderImage()
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $placeholderImage = $this->scopeConfig->getValue('catalog/placeholder/image_placeholder', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$placeholderImage) {
            $placeholderImage = 'default/thumbnail.jpg';
        }
        return $mediaUrl.'catalog/product/placeholder/'.$placeholderImage;
    }
}
