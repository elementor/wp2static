<?php
namespace Aws\S3\Model;
use Aws\S3\Enum\Group;
use Aws\S3\Enum\GranteeType;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\UnexpectedValueException;
use Aws\Common\Exception\LogicException;
use Guzzle\Common\ToArrayInterface;
class Grantee implements ToArrayInterface
{
    protected static $headerMap = array(
        GranteeType::USER  => 'id',
        GranteeType::EMAIL => 'emailAddress',
        GranteeType::GROUP => 'uri'
    );
    protected $id;
    protected $displayName;
    protected $type;
    public function __construct($id, $displayName = null, $expectedType = null)
    {
        $this->type = GranteeType::USER;
        $this->setId($id, $expectedType);
        $this->setDisplayName($displayName);
    }
    public function setId($id, $expectedType = null)
    {
        if (in_array($id, Group::values())) {
            $this->type = GranteeType::GROUP;
        } elseif (!is_string($id)) {
            throw new InvalidArgumentException('The grantee ID must be provided as a string value.');
        }
        if (strpos($id, '@') !== false) {
            $this->type = GranteeType::EMAIL;
        }
        if ($expectedType && $expectedType !== $this->type) {
            throw new UnexpectedValueException('The type of the grantee after '
                . 'setting the ID did not match the specified, expected type "'
                . $expectedType . '" but received "' . $this->type . '".');
        }
        $this->id = $id;
        return $this;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getEmailAddress()
    {
        return $this->isAmazonCustomerByEmail() ? $this->id : null;
    }
    public function getGroupUri()
    {
        return $this->isGroup() ? $this->id : null;
    }
    public function setDisplayName($displayName)
    {
        if ($this->type === GranteeType::USER) {
            if (empty($displayName) || !is_string($displayName)) {
                $displayName = $this->id;
            }
            $this->displayName = $displayName;
        } else {
            if ($displayName) {
                throw new LogicException('The display name can only be set '
                    . 'for grantees specified by ID.');
            }
        }
        return $this;
    }
    public function getDisplayName()
    {
        return $this->displayName;
    }
    public function getType()
    {
        return $this->type;
    }
    public function isCanonicalUser()
    {
        return ($this->type === GranteeType::USER);
    }
    public function isAmazonCustomerByEmail()
    {
        return ($this->type === GranteeType::EMAIL);
    }
    public function isGroup()
    {
        return ($this->type === GranteeType::GROUP);
    }
    public function getHeaderValue()
    {
        $key = static::$headerMap[$this->type];
        return "{$key}=\"{$this->id}\"";
    }
    public function toArray()
    {
        $result = array(
            'Type' => $this->type
        );
        switch ($this->type) {
            case GranteeType::USER:
                $result['ID'] = $this->id;
                $result['DisplayName'] = $this->displayName;
                break;
            case GranteeType::EMAIL:
                $result['EmailAddress'] = $this->id;
                break;
            case GranteeType::GROUP:
                $result['URI'] = $this->id;
        }
        return $result;
    }
}
