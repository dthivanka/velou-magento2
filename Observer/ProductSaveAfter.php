<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Event\Observer;

class ProductSaveAfter implements ObserverInterface
{
    CONST TOPIC_NAME = 'velou.queue.product.sync';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    private $json;

    /**
     * @param PublisherInterface $publisher
     * @param Json $json
     */
    public function __construct(
        PublisherInterface $publisher,
        Json $json
    ){
        $this->publisher = $publisher;
        $this->json = $json;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug($product->getStoreId());
        // Publish the product id to the product sync queue
        $this->publisher->publish(self::TOPIC_NAME, $this->json->serialize([$product->getId() => $product->getStoreId()]));
    }
}
