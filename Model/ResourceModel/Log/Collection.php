<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Model\ResourceModel\Log;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Velou\DataFeed\Model\Log;
use Velou\DataFeed\Model\ResourceModel\Log as LogResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'velou_datafeed_log_collection';
    protected $_eventObject = 'log_collection';

    protected function _construct()
    {
        $this->_init(Log::class, LogResourceModel::class);
    }
}
