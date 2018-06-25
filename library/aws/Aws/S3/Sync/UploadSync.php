<?php
namespace Aws\S3\Sync;
use Aws\Common\Exception\RuntimeException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\S3\Model\MultipartUpload\AbstractTransfer;
use Guzzle\Http\EntityBody;
class UploadSync extends AbstractSync
{
    const BEFORE_MULTIPART_BUILD = 's3.sync.before_multipart_build';
    protected function init()
    {
        if (null == $this->options['multipart_upload_size']) {
            $this->options['multipart_upload_size'] = AbstractTransfer::MIN_PART_SIZE;
        }
    }
    protected function createTransferAction(\SplFileInfo $file)
    {
        $filename = $file->getRealPath() ?: $file->getPathName();
        if (!($resource = fopen($filename, 'r'))) {
            throw new RuntimeException('Could not open ' . $file->getPathname() . ' for reading');
        }
        $key = $this->options['source_converter']->convert($filename);
        $body = EntityBody::factory($resource);
        if ($acl = $this->options['acl']) {
            $aclType = is_string($this->options['acl']) ? 'ACL' : 'ACP';
        } else {
            $acl = 'private';
            $aclType = 'ACL';
        }
        if ($body->getWrapper() == 'plainfile' && $file->getSize() >= $this->options['multipart_upload_size']) {
            $builder = UploadBuilder::newInstance()
                ->setBucket($this->options['bucket'])
                ->setKey($key)
                ->setMinPartSize($this->options['multipart_upload_size'])
                ->setOption($aclType, $acl)
                ->setClient($this->options['client'])
                ->setSource($body)
                ->setConcurrency($this->options['concurrency']);
            $this->dispatch(
                self::BEFORE_MULTIPART_BUILD,
                array('builder' => $builder, 'file' => $file)
            );
            return $builder->build();
        }
        return $this->options['client']->getCommand('PutObject', array(
            'Bucket' => $this->options['bucket'],
            'Key'    => $key,
            'Body'   => $body,
            $aclType => $acl
        ));
    }
}
