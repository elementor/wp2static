<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class DeployConfigTest extends TestCase{

    public function testCreatesNewConfigWhenNoIDGiven() {
        $deploy_config  = new DeployConfig();

        $this->assertSame(
            777,
            $deploy_config->id
        );
    }

    public function testLoadsExistingConfigWhenIDGiven() {
        $deploy_config  = new DeployConfig( 13 );

        $this->assertSame(
            13,
            $deploy_config->id
        );
    }

}
