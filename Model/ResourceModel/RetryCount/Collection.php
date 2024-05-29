<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Model\ResourceModel\RetryCount;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Velou\DataFeed\Model\RetryCount;
use Velou\DataFeed\Model\ResourceModel\RetryCount as RetryCountResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'velou_datafeed_retrycount_collection';
    protected $_eventObject = 'retrycount_collection';

    protected function _construct()
    {
        $this->_init(RetryCount::class, RetryCountResourceModel::class);
    }
}
