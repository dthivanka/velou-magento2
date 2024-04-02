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
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

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
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Image $imageHelper
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        Image $imageHelper,
    ){
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Get configurable product ids as an array
     *
     * @return array
     */
    public function getConfigurableProductCollectionData() {
        $configProductCollection = $this->productCollectionFactory->create();
        $configProductCollection->addFieldToFilter('type_id', Configurable::TYPE_CODE)->setPageSize(1)->setCurPage(1);
        //$configProductCollection->addAttributeToFilter('entity_id', [62,78,142,158,190,254,334])->setPageSize(1)->setCurPage(1);
        return $configProductCollection->getAllIds();
    }


    /**
     * Get simple product ids as an array
     *
     * @return array
     */
    public function getSimpleProductCollectionData(){
        $simpleProductCollection = $this->productCollectionFactory->create();
        $simpleProductCollection->addAttributeToFilter('type_id', 'simple');
        $simpleProductCollection->addAttributeToFilter('status', 1);
        //$simpleProductCollection->addAttributeToFilter('entity_id', [1,2,3,4,5,6,7,8,9,10])->setPageSize(1)->setCurPage(1);
        return $simpleProductCollection->getAllIds();
    }
}
