<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RetryFrequency implements OptionSourceInterface {

    const CRON_HOURLY = "0 * * * *";
    const CRON_EVERY_5_MINUTE = "*/5 * * * *";
    const CRON_EVERY_10_MINUTE = "*/10 * * * *";
    const CRON_EVERY_15_MINUTE = "*/15 * * * *";
    const CRON_EVERY_30_MINUTE = "*/30 * * * *";

    /**
     * @var string[]
     */
    private $options = [
        'Every 5 Minute' => self::CRON_EVERY_5_MINUTE,
        'Every 10 Minute' => self::CRON_EVERY_10_MINUTE,
        'Every 15 Minute' => self::CRON_EVERY_15_MINUTE,
        'Every 30 Minute' => self::CRON_EVERY_30_MINUTE,
        'Hourly' => self::CRON_HOURLY,
    ];

    /**
     * Frequency constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        if (null !== $options) {
            $this->options = [];
            array_walk($options, function ($crontab, $label) {
                if ($crontab) {
                    $this->options[(string)$label] = (string)$crontab;
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return array_map(
            static function ($value, $label) {
                return [
                    'value' => $value,
                    'label' => __($label),
                ];
            },
            array_values($this->options),
            array_keys($this->options)
        );
    }
}
