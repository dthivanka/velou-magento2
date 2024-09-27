<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Model\Feed;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Catalog
{
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Status
     */
    protected $status;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Image $imageHelper
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        Status $status,
        Image $imageHelper,
    ){
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->status = $status;
    }

    /**
     * Get product ids by type
     *
     * @param $type
     * @return array
     */
    public function getProductCollectionData($type, $store){
        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToFilter('type_id', $type)
            ->addStoreFilter($store)
            ->addAttributeToFilter('status', array('eq' => $this->status->getVisibleStatusIds()));
        $allProductIds = $productCollection->getAllIds();
        $returnData = [];
        foreach ($allProductIds as $productId){
            $returnData[$productId] = $store->getId();
        }
        return $returnData;
    }
}
