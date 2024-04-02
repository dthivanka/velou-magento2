<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Observer;

use \Psr\Log\LoggerInterface;
use \Magento\Framework\MessageQueue\PublisherInterface;
use \Magento\Framework\Serialize\Serializer\Json;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

class OrderObserver implements ObserverInterface
{
    CONST TOPIC_NAME = 'velou.queue.order.sync';
    CONST TOPIC_NAME_PRODUCT = 'velou.queue.product.sync';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        PublisherInterface $publisher,
        Json $json,
        LoggerInterface $logger
    ){
        $this->publisher = $publisher;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getIncrementId();
        $orderItems = $order->getItems();
        $productIds = [];
        foreach ($orderItems as $item) {
            $productIds[] = $item->getProductId();
        }
        // Publish the order id to the order sync queue
        //$this->publisher->publish(self::TOPIC_NAME, $this->json->serialize([$orderId]));
        // Publish the product ids to the product sync queue
        $this->publisher->publish(self::TOPIC_NAME_PRODUCT, $this->json->serialize($productIds));
    }
}
