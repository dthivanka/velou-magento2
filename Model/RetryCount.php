<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model;

use \Magento\Framework\Model\AbstractModel;
use Velou\DataFeed\Model\ResourceModel\RetryCount as RetryCountResourceModel;

class RetryCount extends AbstractModel
{
    const ENTITY_TYPE_PRODUCT = 'product';
    const ENTITY_TYPE_ORDER = 'order';

    protected function _construct()
    {
        $this->_init(RetryCountResourceModel::class);
    }
}
