<?php
namespace Aws\S3\Model;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\OverflowException;
use Guzzle\Common\ToArrayInterface;
use Guzzle\Service\Command\AbstractCommand;
class Acp implements ToArrayInterface, \IteratorAggregate, \Countable
{
    protected $grants = array();
    protected $owner;
    public function __construct(Grantee $owner, $grants = null)
    {
        $this->setOwner($owner);
        $this->setGrants($grants);
    }
    public static function fromArray(array $data)
    {
        $builder = new AcpBuilder();
        $builder->setOwner((string) $data['Owner']['ID'], $data['Owner']['DisplayName']);
        foreach ($data['Grants'] as $grant) {
            $permission = $grant['Permission'];
            if (!isset($grant['Grantee']['Type'])) {
                if (isset($grant['Grantee']['ID'])) {
                    $grant['Grantee']['Type'] = 'CanonicalUser';
                } elseif (isset($grant['Grantee']['URI'])) {
                    $grant['Grantee']['Type'] = 'Group';
                } else {
                    $grant['Grantee']['Type'] = 'AmazonCustomerByEmail';
                }
            }
            switch ($grant['Grantee']['Type']) {
                case 'Group':
                    $builder->addGrantForGroup($permission, $grant['Grantee']['URI']);
                    break;
                case 'AmazonCustomerByEmail':
                    $builder->addGrantForEmail($permission, $grant['Grantee']['EmailAddress']);
                    break;
                case 'CanonicalUser':
                    $builder->addGrantForUser(
                        $permission,
                        $grant['Grantee']['ID'],
                        $grant['Grantee']['DisplayName']
                    );
            }
        }
        return $builder->build();
    }
    public function setOwner(Grantee $owner)
    {
        if (!$owner->isCanonicalUser()) {
            throw new InvalidArgumentException('The owner must have an ID set.');
        }
        $this->owner = $owner;
        return $this;
    }
    public function getOwner()
    {
        return $this->owner;
    }
    public function setGrants($grants = array())
    {
        $this->grants = new \SplObjectStorage();
        if ($grants) {
            if (is_array($grants) || $grants instanceof \Traversable) {
                foreach ($grants as $grant) {
                    $this->addGrant($grant);
                }
            } else {
                throw new InvalidArgumentException('Grants must be passed in as an array or Traversable object.');
            }
        }
        return $this;
    }
    public function getGrants()
    {
        return $this->grants;
    }
    public function addGrant(Grant $grant)
    {
        if (count($this->grants) < 100) {
            $this->grants->attach($grant);
        } else {
            throw new OverflowException('An ACP may contain up to 100 grants.');
        }
        return $this;
    }
    public function count()
    {
        return count($this->grants);
    }
    public function getIterator()
    {
        return $this->grants;
    }
    public function updateCommand(AbstractCommand $command)
    {
        $parameters = array();
        foreach ($this->grants as $grant) {
            $parameters = array_merge_recursive($parameters, $grant->getParameterArray());
        }
        foreach ($parameters as $name => $values) {
            $command->set($name, implode(', ', (array) $values));
        }
        return $this;
    }
    public function toArray()
    {
        $grants = array();
        foreach ($this->grants as $grant) {
            $grants[] = $grant->toArray();
        }
        return array(
            'Owner' => array(
                'ID'          => $this->owner->getId(),
                'DisplayName' => $this->owner->getDisplayName()
            ),
            'Grants' => $grants
        );
    }
}
