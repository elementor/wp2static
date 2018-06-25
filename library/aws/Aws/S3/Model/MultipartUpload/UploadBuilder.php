<?php
namespace Aws\S3\Model\MultipartUpload;
use Aws\Common\Enum\UaString as Ua;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Model\MultipartUpload\AbstractUploadBuilder;
use Aws\S3\Model\Acp;
class UploadBuilder extends AbstractUploadBuilder
{
    protected $concurrency = 1;
    protected $minPartSize = AbstractTransfer::MIN_PART_SIZE;
    protected $md5;
    protected $calculateEntireMd5 = false;
    protected $calculatePartMd5 = true;
    protected $commandOptions = array();
    protected $transferOptions = array();
    public function setBucket($bucket)
    {
        return $this->setOption('Bucket', $bucket);
    }
    public function setKey($key)
    {
        return $this->setOption('Key', $key);
    }
    public function setMinPartSize($minSize)
    {
        $this->minPartSize = (int) max((int) $minSize, AbstractTransfer::MIN_PART_SIZE);
        return $this;
    }
    public function setConcurrency($concurrency)
    {
        $this->concurrency = $concurrency;
        return $this;
    }
    public function setMd5($md5)
    {
        $this->md5 = $md5;
        return $this;
    }
    public function calculateMd5($calculateMd5)
    {
        $this->calculateEntireMd5 = (bool) $calculateMd5;
        return $this;
    }
    public function calculatePartMd5($usePartMd5)
    {
        $this->calculatePartMd5 = (bool) $usePartMd5;
        return $this;
    }
    public function setAcp(Acp $acp)
    {
        return $this->setOption('ACP', $acp);
    }
    public function setOption($name, $value)
    {
        $this->commandOptions[$name] = $value;
        return $this;
    }
    public function addOptions(array $options)
    {
        $this->commandOptions = array_replace($this->commandOptions, $options);
        return $this;
    }
    public function setTransferOptions(array $options)
    {
        $this->transferOptions = $options;
        return $this;
    }
    public function build()
    {
        if ($this->state instanceof TransferState) {
            $this->commandOptions = array_replace($this->commandOptions, $this->state->getUploadId()->toParams());
        }
        if (!isset($this->commandOptions['Bucket']) || !isset($this->commandOptions['Key'])
            || !$this->client || !$this->source
        ) {
            throw new InvalidArgumentException('You must specify a Bucket, Key, client, and source.');
        }
        if ($this->state && !$this->source->isSeekable()) {
            throw new InvalidArgumentException('You cannot resume a transfer using a non-seekable source.');
        }
        if (is_string($this->state)) {
            $this->state = TransferState::fromUploadId($this->client, UploadId::fromParams(array(
                'Bucket'   => $this->commandOptions['Bucket'],
                'Key'      => $this->commandOptions['Key'],
                'UploadId' => $this->state
            )));
        } elseif (!$this->state) {
            $this->state = $this->initiateMultipartUpload();
        }
        $options = array_replace(array(
            'min_part_size' => $this->minPartSize,
            'part_md5'      => (bool) $this->calculatePartMd5,
            'concurrency'   => $this->concurrency
        ), $this->transferOptions);
        return $this->concurrency > 1
            ? new ParallelTransfer($this->client, $this->state, $this->source, $options)
            : new SerialTransfer($this->client, $this->state, $this->source, $options);
    }
    protected function initiateMultipartUpload()
    {
        if (!isset($this->commandOptions['ContentType'])) {
            if ($mimeType = $this->source->getContentType()) {
                $this->commandOptions['ContentType'] = $mimeType;
            }
        }
        $params = array_replace(array(
            Ua::OPTION        => Ua::MULTIPART_UPLOAD,
            'command.headers' => $this->headers,
            'Metadata'        => array()
        ), $this->commandOptions);
        if ($this->calculateEntireMd5) {
            $this->md5 = $this->source->getContentMd5();
        }
        if ($this->md5) {
            $params['Metadata']['x-amz-Content-MD5'] = $this->md5;
        }
        $result = $this->client->getCommand('CreateMultipartUpload', $params)->execute();
        $params['UploadId'] = $result['UploadId'];
        return new TransferState(UploadId::fromParams($params));
    }
}
