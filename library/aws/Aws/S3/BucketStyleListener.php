<?php
namespace Aws\S3;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class BucketStyleListener implements EventSubscriberInterface
{
    private static $exclusions = array('GetBucketLocation' => true);
    public static function getSubscribedEvents()
    {
        return array('command.after_prepare' => array('onCommandAfterPrepare', -255));
    }
    public function onCommandAfterPrepare(Event $event)
    {
        $command = $event['command'];
        $bucket = $command['Bucket'];
        $request = $command->getRequest();
        $pathStyle = false;
        if (isset(self::$exclusions[$command->getName()])) {
            return;
        }
        if ($key = $command['Key']) {
            if (is_array($key)) {
                $command['Key'] = $key = implode('/', $key);
            }
        }
        $request->getParams()->set('bucket', $bucket)->set('key', $key);
        if (!$command['PathStyle'] && $command->getClient()->isValidBucketName($bucket)
            && !($command->getRequest()->getScheme() == 'https' && strpos($bucket, '.'))
        ) {
            $request->setHost($bucket . '.' . $request->getHost());
            $request->setPath(preg_replace("#^/{$bucket}#", '', $request->getPath()));
        } else {
            $pathStyle = true;
        }
        if (!$bucket) {
            $request->getParams()->set('s3.resource', '/');
        } elseif ($pathStyle) {
            $request->getParams()->set(
                's3.resource',
                '/' . rawurlencode($bucket) . ($key ? ('/' . S3Client::encodeKey($key)) : '')
            );
        } else {
            $request->getParams()->set(
                's3.resource',
                '/' . rawurlencode($bucket) . ($key ? ('/' . S3Client::encodeKey($key)) : '/')
            );
        }
    }
}
