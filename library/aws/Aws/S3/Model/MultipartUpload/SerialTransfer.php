<?php
namespace Aws\S3\Model\MultipartUpload;
use Aws\Common\Enum\DateFormat;
use Aws\Common\Enum\Size;
use Aws\Common\Enum\UaString as Ua;
use Guzzle\Http\EntityBody;
use Guzzle\Http\ReadLimitEntityBody;
class SerialTransfer extends AbstractTransfer
{
    protected function transfer()
    {
        while (!$this->stopped && !$this->source->isConsumed()) {
            if ($this->source->getContentLength() && $this->source->isSeekable()) {
                $body = new ReadLimitEntityBody($this->source, $this->partSize, $this->source->ftell());
            } else {
                $body = EntityBody::factory();
                while ($body->getContentLength() < $this->partSize
                    && $body->write(
                        $this->source->read(max(1, min(10 * Size::KB, $this->partSize - $body->getContentLength())))
                    ));
            }
            if ($body->getContentLength() == 0) {
                break;
            }
            $params = $this->state->getUploadId()->toParams();
            $command = $this->client->getCommand('UploadPart', array_replace($params, array(
                'PartNumber' => count($this->state) + 1,
                'Body'       => $body,
                'ContentMD5' => (bool) $this->options['part_md5'],
                Ua::OPTION   => Ua::MULTIPART_UPLOAD
            )));
            $eventData = $this->getEventData();
            $eventData['command'] = $command;
            $this->dispatch(self::BEFORE_PART_UPLOAD, $eventData);
            if ($this->stopped) {
                break;
            }
            $response = $command->getResponse();
            $this->state->addPart(UploadPart::fromArray(array(
                'PartNumber'   => $command['PartNumber'],
                'ETag'         => $response->getEtag(),
                'Size'         => $body->getContentLength(),
                'LastModified' => gmdate(DateFormat::RFC2822)
            )));
            $this->dispatch(self::AFTER_PART_UPLOAD, $eventData);
        }
    }
}
