<?php
namespace Aws;
interface HashInterface
{
    public function update($data);
    public function complete();
    public function reset();
}
