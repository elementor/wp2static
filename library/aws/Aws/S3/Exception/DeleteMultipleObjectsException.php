<?php
namespace Aws\S3\Exception;
class DeleteMultipleObjectsException extends \Exception
{
    private $deleted = [];
    private $errors = [];
    public function __construct(array $deleted, array $errors)
    {
        $this->deleted = array_values($deleted);
        $this->errors = array_values($errors);
        parent::__construct('Unable to delete certain keys when executing a'
            . ' DeleteMultipleObjects request: '
            . self::createMessageFromErrors($errors));
    }
    public static function createMessageFromErrors(array $errors)
    {
        return "\n- " . implode("\n- ", array_map(function ($key) {
            return json_encode($key);
        }, $errors));
    }
    public function getErrors()
    {
        return $this->errors;
    }
    public function getDeleted()
    {
        return $this->deleted;
    }
}
