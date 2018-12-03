<?php
/**
 * EndpointSignatureInterface
 *
 * @package WP2Static
 */

namespace Aws\Common\Signature;
interface EndpointSignatureInterface extends SignatureInterface
{
    public function setServiceName($service);
    public function setRegionName($region);
}
