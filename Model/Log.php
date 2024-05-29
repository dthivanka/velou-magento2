<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */

namespace Velou\DataFeed\Model;

use \Magento\Framework\Model\AbstractModel;
use Velou\DataFeed\Model\ResourceModel\Log as LogResourceModel;

class Log extends AbstractModel
{
    const MESSAGE_TYPE_INFO = 'info';
    const MESSAGE_TYPE_ERROR = 'error';
    const MESSAGE_TYPE_WARNING = 'warning';
    const MESSAGE_TYPE_SUCCESS = 'success';

    protected function _construct()
    {
        $this->_init(LogResourceModel::class);
    }
}
