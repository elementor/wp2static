<?php
/**
 * CloudFront
 *
 * @package WP2Static
 */

require_once 'Request2.php';

class CloudFront {

	private $serviceUrl;
	private $accessKeyId;
	private $responseCode;
	private $distributionId;
    private $responseMessage;
	function __construct($accessKeyId, $secretKey, $distributionId, $serviceUrl="https://cloudfront.amazonaws.com/"){
		$this->accessKeyId    = $accessKeyId;
		$this->secretKey      = $secretKey;
		$this->distributionId = $distributionId;
		$this->serviceUrl     = $serviceUrl;
	}
	function invalidate($keys){
        if (!is_array($keys)) {
            $keys = array($keys);
        }

        $requestUrl = $this->serviceUrl . "2012-07-01/distribution/" . $this->distributionId . "/invalidation";
        $body = $this->makeRequestBody($keys);
        $req = new HTTP_Request2($requestUrl, HTTP_Request2::METHOD_POST, array('ssl_verify_peer' => false));
        $this->setRequestHeaders($req);
        $req->setBody($body);

        try {
            $response = $req->send();
            $this->responseCode = $response->getStatus();

            switch ($this->responseCode) {
                case 201:
                    $this->responseMessage = '201: Request accepted';
                    return true;
                case 400:
                    $this->responseMessage = '400: Too many invalidations in progress. Retry in some time';
                    return false;
                case 403:
                    $this->responseMessage = '403: Forbidden. Please check your security settings.';
                    return false;
                default:
                    $this->responseMessage = $response->getStatus() . ': ' . $response->getReasonPhrase();
                    return false;
            }
        } catch (HTTP_Request2_Exception $e) {
            $this->responseMessage = 'Error: ' . $e->getMessage();
            return false;
        }
	}
    private function setRequestHeaders(HTTP_Request2 $req)
    {
        $date = gmdate("D, d M Y G:i:s T");
        $req->setHeader("Host", 'cloudfront.amazonaws.com');
        $req->setHeader("Date", $date);
        $req->setHeader("Authorization", $this->generateAuthKey($date));
        $req->setHeader("Content-Type", "text/xml");
    }
    private function makeRequestBody($objects)
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>';
        $body .= '<InvalidationBatch xmlns="http://cloudfront.amazonaws.com/doc/2012-07-01/">';
        $body .= '<Paths>';
        $body .= '<Quantity>' . count($objects) . '</Quantity>';
        $body .= '<Items>';
        foreach ($objects as $object) {
            $object = (preg_match("/^\//", $object)) ? $object : "/" . $object;
            $body .= "<Path>" . $object . "</Path>";
        }
        $body .= '</Items>';
        $body .= '</Paths>';
        $body .= "<CallerReference>" . time() . "</CallerReference>";
        $body .= "</InvalidationBatch>";
        return $body;
    }
    private function generateAuthKey($date)
    {
        $signature = base64_encode(hash_hmac('sha1', $date, $this->secretKey, true));
        return "AWS " . $this->accessKeyId . ":" . $signature;
    }
    public function getResponseCode()
    {
        return $this->responseCode;
    }
    public function getResponseMessage()
    {
        return $this->responseMessage;
    }

}
?>
