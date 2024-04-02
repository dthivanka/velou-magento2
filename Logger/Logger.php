<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Logger;

class Logger extends \Monolog\Logger
{
    /**
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
    }
}
