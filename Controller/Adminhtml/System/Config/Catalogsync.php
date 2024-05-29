<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Controller\Adminhtml\System\Config;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use Velou\DataFeed\Model\Sync;

class Catalogsync extends Action
{
    protected $resultJsonFactory;

    protected $catalogSync;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Sync $catalogSync,
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->catalogSync = $catalogSync;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $this->catalogSync->process();
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => 'Error occurred while publishing catalog to the sync queue.']);
        }
        return $result->setData(['success' => true, 'message' => 'Catalog data published to the sync queue successfully.']);
    }
}
