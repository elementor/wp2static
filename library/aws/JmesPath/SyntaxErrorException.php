<?php
namespace JmesPath;
class SyntaxErrorException extends \InvalidArgumentException
{
    public function __construct(
        $expectedTypesOrMessage,
        array $token,
        $expression
    ) {
        $message = "Syntax error at character {$token['pos']}\n"
            . $expression . "\n" . str_repeat(' ', $token['pos']) . "^\n";
        $message .= !is_array($expectedTypesOrMessage)
            ? $expectedTypesOrMessage
            : $this->createTokenMessage($token, $expectedTypesOrMessage);
        parent::__construct($message);
    }
    private function createTokenMessage(array $token, array $valid)
    {
        return sprintf(
            'Expected one of the following: %s; found %s "%s"',
            implode(', ', array_keys($valid)),
            $token['type'],
            $token['value']
        );
    }
}
