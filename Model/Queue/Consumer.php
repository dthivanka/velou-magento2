<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model\Queue;

use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\GroupedProduct\Model\Product\Type\Grouped;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Catalog\Model\ProductFactory;
use \Magento\Catalog\Helper\Image;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use \Magento\CatalogInventory\Model\Stock\StockItemRepository;
use \Magento\Framework\MessageQueue\PublisherInterface;
use \Magento\Framework\Serialize\Serializer\Json;
use \Magento\Catalog\Model\Product\Option;
use \Magento\Catalog\Model\Product\Type;
use \Magento\Catalog\Model\Product\Action;
use Velou\DataFeed\Model\Apiconnector\Rest;
use Velou\DataFeed\Helper\Data as HelperData;
use Velou\DataFeed\Logger\Logger;
use Velou\DataFeed\Model\Log;
use Velou\DataFeed\Model\RetryCountFactory as RetryCountFactory;
use Velou\DataFeed\Model\RetryCount;

class Consumer
{
    const IN_STOCK = "InStock";
    const OUT_OF_STOCK = "OutOfStock";
    const JOB_NAME_UPDATE = 'Product Update Sync';
    const JOB_NAME_DELETE = 'Product Delete Sync';
    const TOPIC_NAME = 'velou.queue.product.sync';

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
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var Option
     */
    protected $customOptions;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var Action
     */
    private $productActionInstance;

    /**
     * @var RetryCountFactory
     */
    protected $retryCountFactory;

    /**
     * @param Logger $logger
     * @param Rest $rest
     * @param Json $json
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param Image $imageHelper
     * @param StockItemRepository $stockItemRepository
     * @param PublisherInterface $publisher
     * @param HelperData $helperData
     * @param ProductFactory $productFactory
     * @param Option $customOptions
     * @param Action $productActionInstance
     * @param RetryCountFactory $retryCountFactory
     */
    public function __construct(
        Logger $logger,
        Rest $rest,
        Json $json,
        ProductRepositoryInterface $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        Image $imageHelper,
        StockItemRepository $stockItemRepository,
        PublisherInterface $publisher,
        HelperData $helperData,
        ProductFactory $productFactory,
        Option $customOptions,
        Action $productActionInstance,
        RetryCountFactory $retryCountFactory,
    )
    {
        $this->logger = $logger;
        $this->rest = $rest;
        $this->json = $json;
        $this->productRepository = $productRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->stockItemRepository = $stockItemRepository;
        $this->helperData = $helperData;
        $this->productFactory = $productFactory;
        $this->customOptions = $customOptions;
        $this->publisher = $publisher;
        $this->productActionInstance = $productActionInstance;
        $this->retryCountFactory = $retryCountFactory;
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
                    $product = $this->productFactory->create()->load($productId);

                    $feedData ['id'] = $productId;
                    $feedData ['url'] = $product->getProductUrl();
                    $feedData ['product_type'] = $product->getTypeId();
                    $feedData ['name'] = $product->getName();
                    $feedData ['description'] = $product->getDescription() ? $product->getDescription() : ' ';
                    $feedData ['short_description'] = $product->getShortDescription() ? $product->getShortDescription() : ' ';
                    $feedData ['categories'] = $this->getProductCategoryNames($product->getCategoryIds());
                    $feedData ['media'] = $this->getProductMedia($product);
                    $feedData ['url_key'] = $product->getUrlKey();
                    $feedData ['meta_title'] = $product->getMetaTitle();
                    $feedData ['meta_keywords'] = $product->getMetaKeyword();
                    $feedData ['meta_description'] = $product->getMetaDescription();
                    $feedData ['up_sells'] = $product->getUpSellProductIds();
                    $feedData ['cross_sells'] = $product->getCrossSellProductIds();
                    $feedData ['related_products'] = $product->getRelatedProductIds();
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
                    } else if ($product->getTypeId() === Grouped::TYPE_CODE) {
                        $children = $product->getTypeInstance()->getAssociatedProducts($product);
                        $feedData ['skus'] = $this->getChildrenDetails($children);
                    } else if ($product->getTypeId() === Type::TYPE_BUNDLE) {
                        $bundleOptions = $product->getTypeInstance()->getOptions($product);
                        $bundleSelections = $product->getTypeInstance()
                            ->getSelectionsCollection(
                            $product->getTypeInstance(true)->getOptionsIds($product),
                            $product
                        );
                        $bundleOptionsData = [];
                        $bundleSelectionsData = [];
                        foreach ($bundleOptions as $option) {
                            $optionData = $option->getData();
                            $optionData['option_id'] = $option->getId();
                            $bundleOptionsData[] = $optionData;
                        }
                        foreach ($bundleSelections as $selection) {
                            $selectionData = $selection->getData();
                            $selectionData['option_id'] = $selection->getOptionId();
                            $bundleSelectionsData[$selection->getOptionId()][] = $selectionData;
                        }
                        $feedData ['bundle_options'] = $bundleOptionsData;
                        $feedData ['bundle_selections'] = $bundleSelectionsData;
                        $feedData ['skus'] = $this->getBundleProductSkus($bundleSelectionsData);
                    } else {
                        $feedData ['skus'] = [
                            [
                                'variationId' => $productId,
                                'price' => $this->getPriceByType($product, 'final_price'),
                                'availability' => $this->getStockItem($productId)->getIsInStock() ? self::IN_STOCK : self::OUT_OF_STOCK,
                                'media' => $this->getProductMedia($product),
                            ]
                        ];
                    }
                    $feedData ['custom_options'] = $this->getCustomOptions($product);

                    $customAttributeData = $this->getCustomAttributeOfProduct($product);
                    if ($customAttributeData) {
                        $feedData ['custom_attributes'] = $customAttributeData;
                    } else {
                        $feedData ['custom_attributes'] = [];
                    }

                    $feed[] = $feedData;
                }
                //Post product details to Velou
                $response = $this->rest->doPost($feed, '/products');
                $this->logger->info(print_r($feed,true));
                $this->logger->info($response);
                $this->helperData->addLogMessage(
                    self::JOB_NAME_UPDATE,
                    $response,
                    Log::MESSAGE_TYPE_INFO,
                );
                $status = $this->processResponse($response);
                //Publish the product ids to the queue for retry if service is down
                if(!$status) {
                    //Update the product sync attributes
                    foreach ($productsIds as $productId) {
                        $updateValues = [
                            'velou_last_sync_status' => 'Error',
                            'velou_last_sync_time' => date('Y-m-d H:i:s'),
                            'velou_last_sync_errors' => 'Invalid response from the server',
                        ];
                        $this->updateProductAttribute($productId, $updateValues);
                        //Save the product for retry
                        $retryCount = $this->retryCountFactory->create();
                        $retryCount->setEntityId($productId);
                        $retryCount->setEntity(RetryCount::ENTITY_TYPE_PRODUCT);
                        $retryCount->setRetryCount($retryCount->getRetryCount() + 1);
                        $retryCount->save();
                    }
                } else {
                    //Delete the product from retry table when sync is successful
                    foreach ($productsIds as $productId) {
                        $this->retryCountFactory->create()->load($productId, 'entity_id')->delete();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->helperData->addLogMessage(
                self::JOB_NAME_DELETE,
                $e->getMessage(),
                Log::MESSAGE_TYPE_ERROR,
                $e->getTraceAsString()
            );
            //Publish the product ids to the queue for retry
            $this->publisher->publish(self::TOPIC_NAME, $productsIds);
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
        $galleryImages = $product->getMediaGalleryImages();
        //If no images found, add placeholder image
        if ($galleryImages->getSize() == 0) {
            $media[] = [
                'url' => $this->helperData->getProductPlaceHolderImage(),
            ];
            return $media;
        }
        foreach($galleryImages as $productImage) {
            $childMedia['url'] = $productImage->getUrl();
            $media[] = $childMedia;
        }
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
            $sku['sku'] = $child->getSku();
            $sku['name'] = $child->getName();
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
     * @return array|boolean
     */
    public function getCustomAttributeOfProduct($product)
    {
        $customAttributes = [];
        if (!$this->helperData->getProductCustomAttributesToSync()) {
            return false;
        }
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
        $this->helperData->addLogMessage(
            self::JOB_NAME_DELETE,
            $response,
            Log::MESSAGE_TYPE_INFO,
        );
    }

    /**
     * Get custom options of the product
     * @param $product
     * @return array
     */
    private function getCustomOptions($product)
    {
        $customOptionCollection = $this->customOptions->getProductOptionCollection($product);
        $customOptions = [];
        foreach ($customOptionCollection as $option) {
            $customOption = [
                'option_id' => $option->getId(),
                'option_label' => $option->getTitle(),
                'option_price' => $option->getPrice(),
                'option_price_type' => $option->getPriceType(),
                'option_sku' => $option->getSku(),
                'option_type' => $option->getType(),
                'option_max_characters' => $option->getMaxCharacters(),
                'option_is_require' => $option->getIsRequire(),
                'option_file_extension' => $option->getFileExtension(),
                'option_image_size_x' => $option->getImageSizeX(),
                'option_image_size_y' => $option->getImageSizeY(),
                'option_values' => $option->getValues() ? $this->getOptionValuesAsArray($option->getValues()) : '',
            ];
            $customOptions[] = $customOption;
        }
        return $customOptions;
    }

    /**
     * Get option values as array
     * @param $optionValues
     * @return array
     */
    private function getOptionValuesAsArray($optionValues)
    {
        $optionValuesArray = [];
        foreach ($optionValues as $optionValue) {
            $optionValuesArray[] = [
                'option_id' => $optionValue->getId(),
                'option_value' => $optionValue->getTitle(),
                'option_price' => $optionValue->getPrice(),
                'option_price_type' => $optionValue->getPriceType(),
                'option_sku' => $optionValue->getSku(),
            ];
        }
        return $optionValuesArray;
    }

    private function getBundleProductSkus($bundleSelectionsData)
    {
        $skus = [];
        foreach ($bundleSelectionsData as $selections) {
            foreach ($selections as $selection) {
                $childrenProduct = $this->productRepository->getById($selection['product_id']);
                $skus[] = [
                    'variationId' => $selection['product_id'],
                    'sku' => $selection['sku'],
                    'name' => $selection['name'],
                    'price' => $this->getPriceByType($childrenProduct, 'final_price'),
                    'availability' => $this->getStockItem($childrenProduct->getEntityId())->getIsInStock() ? self::IN_STOCK : self::OUT_OF_STOCK,
                    'media' => $this->getProductMedia($childrenProduct),
                ];
            }
        }
        return $skus;
    }

    private function processResponse($response)
    {
        $response = $this->json->unserialize($response);
        $date = date('Y-m-d H:i:s');
        if (isset($response['results'])) {
            foreach ($response['results'] as $result) {
                if ($result['success']) {
                    $updateValues = [
                        'velou_last_sync_status' => 'Success',
                        'velou_task_id' => $result['taskId'],
                        'velou_last_success_sync_time' => $date,
                        'velou_last_sync_time' => $date,
                        'velou_last_sync_errors' => 'No Errors',
                    ];
                } else {
                    $updateValues = [
                        'velou_last_sync_status' => 'Error',
                        'velou_last_sync_time' => $date,
                        'velou_last_sync_errors' => $result['code'],
                    ];
                }
                $this->updateProductAttribute($result['id'], $updateValues);
                return true;
            }
        } elseif (isset($response['message']) && $response['message']==='An invalid response was received from the upstream server') {
            return false;
        }
    }

    /**
     * Update product attribute
     * @param $productId
     * @param $updateValues
     */
    private function updateProductAttribute($productId,$updateValues)
    {
        $this->productActionInstance->updateAttributes([$productId], $updateValues, 0);
    }
}
