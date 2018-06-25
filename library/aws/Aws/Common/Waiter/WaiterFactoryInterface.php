<?php
namespace Aws\Common\Waiter;
interface WaiterFactoryInterface
{
    public function build($waiter);
    public function canBuild($waiter);
}
