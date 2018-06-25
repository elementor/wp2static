<?php
namespace Aws\S3\Model;
use Aws\S3\Enum\Permission;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Common\ToArrayInterface;
class Grant implements ToArrayInterface
{
    protected static $parameterMap = array(
        Permission::READ         => 'GrantRead',
        Permission::WRITE        => 'GrantWrite',
        Permission::READ_ACP     => 'GrantReadACP',
        Permission::WRITE_ACP    => 'GrantWriteACP',
        Permission::FULL_CONTROL => 'GrantFullControl'
    );
    protected $grantee;
    protected $permission;
    public function __construct(Grantee $grantee, $permission)
    {
        $this->setGrantee($grantee);
        $this->setPermission($permission);
    }
    public function setGrantee(Grantee $grantee)
    {
        $this->grantee = $grantee;
        return $this;
    }
    public function getGrantee()
    {
        return $this->grantee;
    }
    public function setPermission($permission)
    {
        $valid = Permission::values();
        if (!in_array($permission, $valid)) {
            throw new InvalidArgumentException('The permission must be one of '
                . 'the following: ' . implode(', ', $valid) . '.');
        }
        $this->permission = $permission;
        return $this;
    }
    public function getPermission()
    {
        return $this->permission;
    }
    public function getParameterArray()
    {
        return array(
            self::$parameterMap[$this->permission] => $this->grantee->getHeaderValue()
        );
    }
    public function toArray()
    {
        return array(
            'Grantee'    => $this->grantee->toArray(),
            'Permission' => $this->permission
        );
    }
}
