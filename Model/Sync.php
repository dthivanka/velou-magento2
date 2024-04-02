<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model;

use \Magento\Framework\MessageQueue\PublisherInterface;
use \Magento\Framework\Serialize\Serializer\Json;
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
    ){
        $this->rest = $rest;
        $this->catalog = $catalog;
        $this->publisher = $publisher;
        $this->json = $json;
    }

    /**
     * Process the product data and publish to the queue
     */
    public function process()
    {
        $configProductData = $this->catalog->getConfigurableProductCollectionData();
        $simpleProductData = $this->catalog->getSimpleProductCollectionData();
        $productData = array_merge($configProductData, $simpleProductData);
        if ($productData) {
            $chunks = array_chunk($productData,self::BATCH_SIZE);
            foreach ($chunks as $chunk){
                $this->publisher->publish(self::TOPIC_NAME, $this->json->serialize($chunk));
            }
        }

    }
}
