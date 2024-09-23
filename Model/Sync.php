<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model;

use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Framework\MessageQueue\PublisherInterface;
use \Magento\Framework\Serialize\Serializer\Json;
use \Magento\Catalog\Model\Product\Type;
use \Magento\GroupedProduct\Model\Product\Type\Grouped;
use \Magento\Downloadable\Model\Product\Type as Downloadable;
use \Magento\Store\Model\StoreManagerInterface;
use Velou\DataFeed\Model\Apiconnector\Rest;
use Velou\DataFeed\Model\Feed\Catalog;

class Sync
{
    CONST TOPIC_NAME = 'velou.queue.product.sync';

    const BATCH_SIZE = 100;

    /**
     * @var Rest
     */
    private $rest;

    /**
     * @var Catalog
     */
    protected $catalog;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var Json
     */
    private $json;

    private $storeManager;

    /**
     * @param Rest $rest
     * @param Catalog $catalog
     * @param PublisherInterface $publisher
     * @param Json $json
     */
    public function __construct(
        Rest $rest,
        Catalog $catalog,
        PublisherInterface $publisher,
        Json $json,
        StoreManagerInterface $storeManager
    ){
        $this->rest = $rest;
        $this->catalog = $catalog;
        $this->publisher = $publisher;
        $this->json = $json;
        $this->storeManager = $storeManager;
    }

    /**
     * Process the product data and publish to the queue
     */
    public function process()
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store){
            $configProductData = $this->catalog->getProductCollectionData(Configurable::TYPE_CODE, $store);
            $simpleProductData = $this->catalog->getProductCollectionData(Type::DEFAULT_TYPE, $store);
            $bundleProductData = $this->catalog->getProductCollectionData(Type::TYPE_BUNDLE, $store);
            $groupProductData = $this->catalog->getProductCollectionData(Grouped::TYPE_CODE, $store);
            $virtualProductData = $this->catalog->getProductCollectionData(Type::TYPE_VIRTUAL, $store);
            $downloadableProductData = $this->catalog->getProductCollectionData(Downloadable::TYPE_DOWNLOADABLE, $store);

            $productData = $configProductData + $simpleProductData + $bundleProductData + $groupProductData + $virtualProductData + $downloadableProductData;
            if ($productData) {
                $chunks = array_chunk($productData,self::BATCH_SIZE,true);
                foreach ($chunks as $chunk){
                    $this->publisher->publish(self::TOPIC_NAME, $this->json->serialize($chunk));
                }
            }
        }
    }
}
