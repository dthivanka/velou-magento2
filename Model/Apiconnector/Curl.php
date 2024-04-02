<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Model\Apiconnector;

use Magento\Framework\HTTP\Client\Curl as CurlLibrary;

/**
 * Class Curl extends the Magento Default Curl Library
 *
 * @package Velou\DataFeed\Model\Apiconnector
 */
class Curl extends CurlLibrary
{
    /**
     * Make DELETE request
     *
     * The Magento Default curl library doesn't support delete method
     *
     * @param string $uri
     * @return void
     */
    public function delete($uri)
    {
        $this->makeRequest("DELETE", $uri);
    }
}
