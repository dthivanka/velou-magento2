<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model\Queue;

use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use \Magento\CatalogInventory\Model\Stock\StockItemRepository;
use \Magento\Framework\Serialize\Serializer\Json;
use \Magento\Review\Model\RatingFactory;
use Magento\Catalog\Model\Product\Type;
use Velou\DataFeed\Model\Apiconnector\Rest;
use Velou\DataFeed\Helper\Data as HelperData;
use Velou\DataFeed\Logger\Logger;

class Consumer
{
    const IN_STOCK = "InStock";
    const OUT_OF_STOCK = "OutOfStock";
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Rest
     */
    protected $rest;

    /**
     * @var Json
     */
    protected $json;

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
     * @var StockItemRepository
     */
    protected $stockItemRepository;

    /**
     * @var RatingFactory
     */
    protected $ratingFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @param LoggerInterface $logger
     * @param Rest $rest
     * @param Json $json
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param Image $imageHelper
     * @param StockItemRepository $stockItemRepository
     * @param RatingFactory $ratingFactory
     * @param HelperData $helperData
     */
    public function __construct(
        Logger $logger,
        Rest $rest,
        Json $json,
        ProductRepositoryInterface $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        Image $imageHelper,
        StockItemRepository $stockItemRepository,
        RatingFactory $ratingFactory,
        HelperData $helperData
    )
    {
        $this->logger = $logger;
        $this->rest = $rest;
        $this->json = $json;
        $this->productRepository = $productRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->stockItemRepository = $stockItemRepository;
        $this->ratingFactory = $ratingFactory;
        $this->helperData = $helperData;
    }

    /**
     * Process the product data and generate the feed
     *
     * @param string $products
     * @return void
     */
    public function process($products)
    {
        $productsIds = $this->json->unserialize($products);
        try {
            if (isset($productsIds['delete'])) {
                $this->deleteProduct($productsIds['delete']);
            } else {
                $feed = [];
                foreach ($productsIds as $productId) {
                    $product = $this->productRepository->getById($productId);

                    $feedData ['id'] = $productId;
                    $feedData ['url'] = $product->getProductUrl();
                    $feedData ['product_type'] = $product->getTypeId();
                    $feedData ['name'] = $product->getName();
                    $feedData ['description'] = $product->getDescription() ? $product->getDescription() : ' ';
                    $feedData ['categories'] = $this->getProductCategoryNames($product->getCategoryIds());
                    $feedData ['media'] = $this->getProductMedia($product);
                    if ($product->getTypeId() === Configurable::TYPE_CODE) {
                        $configurableProductAttributes = [];
                        $configurableProductOptions = $product->getTypeInstance()->getConfigurableOptions($product);
                        foreach ($configurableProductOptions as $configurableProductOption) {
                            foreach ($configurableProductOption as $optionValues) {
                                $configurableProductAttributes [] = $optionValues['attribute_code'];
                                $feedData ['configurable_product_options'][] = [
                                    'attribute_code' => $optionValues['attribute_code'],
                                    'atrribute_label' => $optionValues['option_title'],
                                ];
                            }
                        }
                        $children = $product->getTypeInstance()->getUsedProducts($product);
                        $feedData ['skus'] = $this->getChildrenDetails($children, array_unique($configurableProductAttributes));
                    } elseif ($product->getTypeId() === Type::TYPE_SIMPLE) {
                        $feedData ['skus'] = [
                            [
                                'variationId' => $productId,
                                'price' => $this->getPriceByType($product, 'final_price'),
                                'availability' => $this->getStockItem($productId)->getIsInStock() ? self::IN_STOCK : self::OUT_OF_STOCK,
                                'media' => $this->getProductMedia($product),
                            ]
                        ];
                    }
                    $feedData ['custom_options'] = [];
                    $feedData ['custom_attributes'] = $this->getCustomAttributeOfProduct($product);
                    $feed[] = $feedData;
                }
                //Post product details to Velou
                $this->logger->info(print_r($feed, true));
                $response = $this->rest->doPost($feed, '/products');
                $this->logger->info($response);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get category names of given category ids
     *
     * @param array $categoryIds
     * @return array
     */
    public function getProductCategoryNames($categoryIds)
    {
        $categoryCollection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addIdFilter($categoryIds);
        $categoryNames = [];
        foreach ($categoryCollection as $category){
            $categoryNames[] = $category->getName();
        }
        return $categoryNames;
    }

    /**
     * Get media of the product
     * @param $product
     * @return array
     */
    public function getProductMedia($product)
    {
        $media = [];
        $childMedia['url'] = $this->imageHelper->init($product,'product_small_image')->getUrl();
        $media[] = $childMedia;
        return $media;
    }

    /**
     * Get details of the children of the configurable product
     *
     * @param array $children
     * @param array $configurableAttributes
     * @return array
     */

    public function getChildrenDetails($children, $configurableAttributes = [])
    {
        $skus = [];
        foreach ($children as $child) {
            $sku = [];
            $childrenProduct = $this->productRepository->getById($child->getEntityId());
            $sku['variationId'] = $child->getEntityId();
            $sku['price'] = $this->getPriceByType($childrenProduct, 'final_price');
            $sku['availability'] = $this->getStockItem($child->getEntityId())->getIsInStock() ? self::IN_STOCK : self::OUT_OF_STOCK;
            foreach ($configurableAttributes as $attribute){
                $sku[$attribute] = $childrenProduct->getResource()->getAttribute($attribute)->getFrontend()->getValue($childrenProduct);
            }
            $sku['media'] = $this->getProductMedia($childrenProduct);
            $skus[] = $sku;
        }
        return $skus;
    }

    /**
     * Get price of the product by price type
     * @param $product
     * @param $priceType
     * @return mixed
     */
    public function getPriceByType($product, $priceType)
    {
        return $product->getPriceInfo()->getPrice($priceType)->getValue();
    }

    /**
     * Get stock item of the product
     * @param $productId
     * @return mixed
     */
    public function getStockItem($productId)
    {
        return $this->stockItemRepository->get($productId);
    }

    /**
     * Get custom attributes of the product
     * @param $product
     * @return array
     */
    public function getCustomAttributeOfProduct($product)
    {
        $customAttributes = [];
        $attributes = explode(',', $this->helperData->getProductCustomAttributesToSync());
        foreach ($attributes as $attribute){
            $attribute = trim($attribute);
            $customAttribute = [];
            $customAttribute['attribute_code'] = $attribute;
            $customAttribute['attribute_label'] = $product->getResource()->getAttribute($attribute)->getFrontendLabel();
            $customAttribute['value'] = $product->getResource()->getAttribute($attribute)->getFrontend()->getValue($product);
            $customAttributes[] = $customAttribute;
        }
        return $customAttributes;
    }

    /**
     * Delete product from Velou
     * @param $product
     */
    private function deleteProduct($product)
    {
        $response = $this->rest->doDelete($product, '/products');
        $this->logger->info($response);
    }

}
