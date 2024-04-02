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
use Velou\DataFeed\Model\OrderSync as OrderSyncModel;

/**
 * Command to sync all orders to Velou
 * Class ProductSync
 * @package Velou\DataFeed\Console\Command
 */
class OrderSync extends Command
{
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
     * @var OrderSyncModel
     */
    protected $orderSync;


    /**
     * ProductSync constructor.
     *
     * @param State $appState
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param OrderSyncModel $sync
     */
    public function __construct(
        State $appState,
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        OrderSyncModel $sync,
    ) {
        $this->appState = $appState;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->orderSync = $sync;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('velou:order:sync')
            ->setDescription('Sync all orders with Velou');
        parent::configure();
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        $output->writeln("Starting order sync");
        $this->orderSync->process();
        $output->writeln("Order sync finished");
        return Cli::RETURN_SUCCESS;
    }



}
