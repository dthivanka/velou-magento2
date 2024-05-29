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
use Velou\DataFeed\Model\RetryCount;
use Velou\DataFeed\Model\ResourceModel\RetryCount\CollectionFactory as RetryCountCollectionFactory;

class SyncRetry
{
    CONST TOPIC_NAME = 'velou.queue.product.sync';

    const BATCH_SIZE = 100;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var RetryCountCollection
     */
    protected $retryCountCollection;

    /**
     * @param PublisherInterface $publisher
     * @param Json $json
     */
    public function __construct(
        PublisherInterface $publisher,
        Json $json,
        RetryCountCollectionFactory $retryCountCollectionFactory,
    ){
        $this->publisher = $publisher;
        $this->json = $json;
        $this->retryCountCollection = $retryCountCollectionFactory;
    }

    /**
     * Process retry product data and publish to the queue
     */
    public function process()
    {
        $productData = $this->retryCountCollection->create()
            ->addFieldToFilter('entity', RetryCount::ENTITY_TYPE_PRODUCT)->getItems();
        if (count($productData) > 0) {
            $chunks = array_chunk($productData,self::BATCH_SIZE);
            foreach ($chunks as $chunk){
                $entityIds = [];
                foreach ($chunk as $item){
                    $entityIds[] = $item->getEntityId();
                }
                $this->publisher->publish(self::TOPIC_NAME, $this->json->serialize($entityIds));
            }
        }

    }
}
