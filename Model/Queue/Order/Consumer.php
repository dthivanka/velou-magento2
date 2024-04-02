<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model\Queue\Order;

use \Psr\Log\LoggerInterface;
use \Magento\Framework\Serialize\Serializer\Json;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Velou\DataFeed\Model\Apiconnector\Rest;


class Consumer
{
    private const TYPE_PURCHASE = 'Purchase';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Rest
     */
    protected $rest;

    /**
     * @param LoggerInterface $logger
     * @param ProductCollectionFactory $productCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Json $json
     * @param Rest $rest
     */
    public function __construct(
        LoggerInterface $logger,
        ProductCollectionFactory $productCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        Json $json,
        Rest $rest,
    ){
        $this->logger = $logger;
        $this->json = $json;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->rest = $rest;
    }

    /**
     * Process the order data
     *
     * @param string $orders
     * @return void
     */
    public function process($orders)
    {
        $orderIds = $this->json->unserialize($orders);
        if(is_array($orderIds)) {
            foreach ($orderIds as $orderId){
                $feedData['type'] = self::TYPE_PURCHASE;
                $feedData['data'] = [
                    'orderId' => $orderId,
                    'products' => $this->getProductDetailsFromOrder($orderId),
                ];
                $response = $this->rest->doPost($feedData,'/tracking');
                $this->logger->debug("Syncing order id: ".$orderId);
                $this->logger->debug($response);
            }
        }
    }

    /**
     * Get product details from order
     *
     * @param int $orderId
     * @return array
     */
    private function getProductDetailsFromOrder($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $items = $order->getAllItems();
        $productIds = [];
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
        }
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('entity_id', ['in' => $productIds]);
        $productCollection->load();
        $productData = [];
        foreach ($productCollection as $product) {
            $productData[] = [
                'id' => $product->getId(),
                'price' => $product->getPrice(),
            ];
        }
        return $productData;
    }
}
