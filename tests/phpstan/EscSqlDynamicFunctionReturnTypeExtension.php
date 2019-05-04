<?php declare(strict_types = 1);

namespace PHPStan\WordPress;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Type;

class EscSqlDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'esc_sql';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$argsCount = count($functionCall->args);
		if ($argsCount < 1) {
			return new NullType();
		}
		$dataArg = $functionCall->args[0]->value;
		$dataArgType = $scope->getType($dataArg);
		if ($dataArgType instanceof ArrayType) {
			$keyType = $dataArgType->getIterableKeyType();
			$itemType = $dataArgType->getIterableValueType();
			return new ArrayType($keyType, $itemType);
		}
		return new StringType();
	}
}
