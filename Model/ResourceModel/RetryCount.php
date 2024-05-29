<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RetryCount extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('velou_datafeed_retry_count', 'id');
    }
}
