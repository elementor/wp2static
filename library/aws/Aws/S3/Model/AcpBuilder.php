<?php
namespace Aws\S3\Model;
use Aws\S3\Enum\GranteeType;
class AcpBuilder
{
    protected $owner;
    protected $grants = array();
    public static function newInstance()
    {
        return new static;
    }
    public function setOwner($id, $displayName = null)
    {
        $this->owner = new Grantee($id, $displayName ?: $id, GranteeType::USER);
        return $this;
    }
    public function addGrantForUser($permission, $id, $displayName = null)
    {
        $grantee = new Grantee($id, $displayName ?: $id, GranteeType::USER);
        $this->addGrant($permission, $grantee);
        return $this;
    }
    public function addGrantForEmail($permission, $email)
    {
        $grantee = new Grantee($email, null, GranteeType::EMAIL);
        $this->addGrant($permission, $grantee);
        return $this;
    }
    public function addGrantForGroup($permission, $group)
    {
        $grantee = new Grantee($group, null, GranteeType::GROUP);
        $this->addGrant($permission, $grantee);
        return $this;
    }
    public function addGrant($permission, Grantee $grantee)
    {
        $this->grants[] = new Grant($grantee, $permission);
        return $this;
    }
    public function build()
    {
        return new Acp($this->owner, $this->grants);
    }
}
