<?php
/**
 * @copyright   Velou, 2024
 * @package     Velou_DataFeed
 * @author      Duleep Thivanka <duleepthivanka@gmail.com>
 *
 */
namespace Velou\DataFeed\Model\Apiconnector;

use Velou\DataFeed\Helper\Data as HelperData;

class Rest
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @param Curl $curl
     */
    public function __construct(
        Curl $curl,
        HelperData $helperData
    ) {
        $this->curl = $curl;
        $this->helperData = $helperData;
    }

    /**
     * Make POST request
     *
     * @param string $endPoint
     * @return string
     */
    public function doPost(Array $data, $endPoint) {
        $jsonData = json_encode($data);
        $this->curl->addHeader("X-Velou-API-Key", $this->getApiKey());
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post($this->getApiUrl().$endPoint, $jsonData);

        return $this->curl->getBody();
    }

    /**
     * Make DELETE request
     *
     * @param string $endPoint
     * @return string
     */
    public function doDelete($productId,$endPoint) {
        $this->curl->addHeader("X-Velou-API-Key", $this->getApiKey());
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->delete($this->getApiUrl().$endPoint.'/'.$productId);

        return $this->curl->getBody();
    }

    /**
     * Get API Key
     *
     * @return string
     */
    private function getApiKey()
    {
        return $this->helperData->getApiKey();
    }

    /**
     * Get API URL
     *
     * @return string
     */
    private function getApiUrl()
    {
        return $this->helperData->getApiUrl();
    }
}
