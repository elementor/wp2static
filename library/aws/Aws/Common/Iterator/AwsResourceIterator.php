<?php
namespace Aws\Common\Iterator;
use Aws\Common\Enum\UaString as Ua;
use Aws\Common\Exception\RuntimeException;
Use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIterator;
class AwsResourceIterator extends ResourceIterator
{
    protected $lastResult = null;
    public function getLastResult()
    {
        return $this->lastResult;
    }
    protected function sendRequest()
    {
        do {
            $this->prepareRequest();
            if ($this->nextToken) {
                $this->applyNextToken();
            }
            $this->command->add(Ua::OPTION, Ua::ITERATOR);
            $this->lastResult = $this->command->getResult();
            $resources = $this->handleResults($this->lastResult);
            $this->determineNextToken($this->lastResult);
            if ($reiterate = empty($resources) && $this->nextToken) {
                $this->command = clone $this->originalCommand;
            }
        } while ($reiterate);
        return $resources;
    }
    protected function prepareRequest()
    {
        $limitKey = $this->get('limit_key');
        if ($limitKey && ($limit = $this->command->get($limitKey))) {
            $pageSize = $this->calculatePageSize();
            if ($limit && $pageSize) {
                $realLimit = min($limit, $pageSize);
                $this->command->set($limitKey, $realLimit);
            }
        }
    }
    protected function handleResults(Model $result)
    {
        $results = array();
        if ($resultKey = $this->get('result_key')) {
            $results = $this->getValueFromResult($result, $resultKey) ?: array();
        }
        return $results;
    }
    protected function applyNextToken()
    {
        if ($tokenParam = $this->get('input_token')) {
            if (is_array($tokenParam)) {
                if (is_array($this->nextToken) && count($tokenParam) === count($this->nextToken)) {
                    foreach (array_combine($tokenParam, $this->nextToken) as $param => $token) {
                        $this->command->set($param, $token);
                    }
                } else {
                    throw new RuntimeException('The definition of the iterator\'s token parameter and the actual token '
                        . 'value are not compatible.');
                }
            } else {
                $this->command->set($tokenParam, $this->nextToken);
            }
        }
    }
    protected function determineNextToken(Model $result)
    {
        $this->nextToken = null;
        $moreKey = $this->get('more_results');
        if ($moreKey === null || $this->getValueFromResult($result, $moreKey)) {
            if ($tokenKey = $this->get('output_token')) {
                if (is_array($tokenKey)) {
                    $this->nextToken = array();
                    foreach ($tokenKey as $key) {
                        $this->nextToken[] = $this->getValueFromResult($result, $key);
                    }
                } else {
                    $this->nextToken = $this->getValueFromResult($result, $tokenKey);
                }
            }
        }
    }
    protected function getValueFromResult(Model $result, $key)
    {
        if (strpos($key, '#') !== false) {
            $keyParts = explode('#', $key, 2);
            $items = $result->getPath(trim($keyParts[0], '/'));
            if ($items && is_array($items)) {
                $index = count($items) - 1;
                $key = strtr($key, array('#' => $index));
            }
        }
        return $result->getPath($key);
    }
}
