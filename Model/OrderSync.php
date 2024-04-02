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

class OrderSync
{
    CONST TOPIC_NAME = 'velou.queue.order.sync';

    const BATCH_SIZE = 1000;

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

    private $json;

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

    public function process()
    {
        $orderData = [];
        if (is_array($orderData)) {
            $chunks = array_chunk($orderData,self::BATCH_SIZE);
            foreach ($chunks as $chunk){
                $this->publisher->publish(self::TOPIC_NAME, $this->json->serialize($chunk));
            }
        }

    }
}
