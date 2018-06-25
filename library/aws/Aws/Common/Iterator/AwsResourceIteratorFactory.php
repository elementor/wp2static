<?php
namespace Aws\Common\Iterator;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Collection;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Resource\ResourceIteratorFactoryInterface;
class AwsResourceIteratorFactory implements ResourceIteratorFactoryInterface
{
    protected static $defaultIteratorConfig = array(
        'input_token'  => null,
        'output_token' => null,
        'limit_key'    => null,
        'result_key'   => null,
        'more_results' => null,
    );
    private static $legacyConfigOptions = array(
        'token_param' => 'input_token',
        'token_key'   => 'output_token',
        'limit_param' => 'limit_key',
        'more_key'    => 'more_results',
    );
    protected $config;
    protected $primaryIteratorFactory;
    public function __construct(array $config, ResourceIteratorFactoryInterface $primaryIteratorFactory = null)
    {
        $this->primaryIteratorFactory = $primaryIteratorFactory;
        $this->config = array();
        foreach ($config as $name => $operation) {
            $this->config[$name] = $operation + self::$defaultIteratorConfig;
        }
    }
    public function build(CommandInterface $command, array $options = array())
    {
        $commandName = $command->getName();
        $commandSupported = isset($this->config[$commandName]);
        $options = $this->translateLegacyConfigOptions($options);
        $options += $commandSupported ? $this->config[$commandName] : array();
        if ($this->primaryIteratorFactory && $this->primaryIteratorFactory->canBuild($command)) {
            $iterator = $this->primaryIteratorFactory->build($command, $options);
        } elseif (!$commandSupported) {
            throw new InvalidArgumentException("Iterator was not found for {$commandName}.");
        } else {
            $iterator = new AwsResourceIterator($command, $options);
        }
        return $iterator;
    }
    public function canBuild(CommandInterface $command)
    {
        if ($this->primaryIteratorFactory) {
            return $this->primaryIteratorFactory->canBuild($command);
        } else {
            return isset($this->config[$command->getName()]);
        }
    }
    private function translateLegacyConfigOptions($config)
    {
        foreach (self::$legacyConfigOptions as $legacyOption => $newOption) {
            if (isset($config[$legacyOption])) {
                $config[$newOption] = $config[$legacyOption];
                unset($config[$legacyOption]);
            }
        }
        return $config;
    }
}
