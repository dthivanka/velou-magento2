<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Console\Command;

use Exception;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Velou\DataFeed\Model\Sync;
use Velou\DataFeed\Helper\Data as HelperData;
use Velou\DataFeed\Model\LogFactory as LogFactory;
use Velou\DataFeed\Model\Log;
use Velou\DataFeed\Model\ResourceModel\LogFactory as LogResourceModelFactory;

/**
 * Command to sync entire product collection to Velou
 * Class ProductSync
 * @package Velou\DataFeed\Console\Command
 */
class ProductSync extends Command
{
    const PRODUCT_SYNC_NAME = 'Bulk Product Sync';
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Sync
     */
    protected $sync;

    protected $helperData;

    /**
     * ProductSync constructor.
     *
     * @param State $appState
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Sync $sync
     * @param HelperData $helperData
     */
    public function __construct(
        State $appState,
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Sync $sync,
        HelperData $helperData,
    ) {
        $this->appState = $appState;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->sync = $sync;
        $this->helperData = $helperData;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('velou:product:sync')
            ->setDescription('Sync all products with Velou');
        parent::configure();
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);

        if(!$this->helperData->isDataFeedModuleEnabled()){
            $this->helperData->addLogMessage(
                self::PRODUCT_SYNC_NAME,
                "Velou DataFeed module is disabled",
                Log::MESSAGE_TYPE_WARNING
            );
            $output->writeln("Velou DataFeed module is disabled");
            return Cli::RETURN_FAILURE;
        }
        if(!$this->helperData->isCatalogSyncEnabled()){
            $this->helperData->addLogMessage(
                self::PRODUCT_SYNC_NAME,
                "Catalog sync is disabled",
                Log::MESSAGE_TYPE_WARNING
            );
            return Cli::RETURN_FAILURE;
        }
        $output->writeln("Starting product sync");
        $this->helperData->addLogMessage(
            self::PRODUCT_SYNC_NAME,
            "Starting product sync",
            Log::MESSAGE_TYPE_INFO
        );
        $this->sync->process();
        $output->writeln("Product sync finished");
        $this->helperData->addLogMessage(
            self::PRODUCT_SYNC_NAME,
            "Product sync finished",
            Log::MESSAGE_TYPE_SUCCESS
        );
        return Cli::RETURN_SUCCESS;
    }
}
