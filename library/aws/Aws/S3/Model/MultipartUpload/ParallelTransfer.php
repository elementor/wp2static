<?php
namespace Aws\S3\Model\MultipartUpload;
use Aws\Common\Exception\RuntimeException;
use Aws\Common\Enum\DateFormat;
use Aws\Common\Enum\UaString as Ua;
use Guzzle\Http\EntityBody;
use Guzzle\Http\ReadLimitEntityBody;
class ParallelTransfer extends AbstractTransfer
{
    protected function init()
    {
        parent::init();
        if (!$this->source->isLocal() || $this->source->getWrapper() != 'plainfile') {
            throw new RuntimeException('The source data must be a local file stream when uploading in parallel.');
        }
        if (empty($this->options['concurrency'])) {
            throw new RuntimeException('The `concurrency` option must be specified when instantiating.');
        }
    }
    protected function transfer()
    {
        $totalParts  = (int) ceil($this->source->getContentLength() / $this->partSize);
        $concurrency = min($totalParts, $this->options['concurrency']);
        $partsToSend = $this->prepareParts($concurrency);
        $eventData   = $this->getEventData();
        while (!$this->stopped && count($this->state) < $totalParts) {
            $currentTotal = count($this->state);
            $commands = array();
            for ($i = 0; $i < $concurrency && $i + $currentTotal < $totalParts; $i++) {
                $partsToSend[$i]->setOffset(($currentTotal + $i) * $this->partSize);
                if ($partsToSend[$i]->getContentLength() == 0) {
                    break;
                }
                $params = $this->state->getUploadId()->toParams();
                $eventData['command'] = $this->client->getCommand('UploadPart', array_replace($params, array(
                    'PartNumber' => count($this->state) + 1 + $i,
                    'Body'       => $partsToSend[$i],
                    'ContentMD5' => (bool) $this->options['part_md5'],
                    Ua::OPTION   => Ua::MULTIPART_UPLOAD
                )));
                $commands[] = $eventData['command'];
                $this->dispatch(self::BEFORE_PART_UPLOAD, $eventData);
            }
            if ($this->stopped) {
                break;
            }
            foreach ($this->client->execute($commands) as $command) {
                $this->state->addPart(UploadPart::fromArray(array(
                    'PartNumber'   => $command['PartNumber'],
                    'ETag'         => $command->getResponse()->getEtag(),
                    'Size'         => (int) $command->getRequest()->getBody()->getContentLength(),
                    'LastModified' => gmdate(DateFormat::RFC2822)
                )));
                $eventData['command'] = $command;
                $this->dispatch(self::AFTER_PART_UPLOAD, $eventData);
            }
        }
    }
    protected function prepareParts($concurrency)
    {
        $url = $this->source->getUri();
        $parts = array(new ReadLimitEntityBody($this->source, $this->partSize));
        for ($i = 1; $i < $concurrency; $i++) {
            $parts[] = new ReadLimitEntityBody(new EntityBody(fopen($url, 'r')), $this->partSize);
        }
        return $parts;
    }
}
