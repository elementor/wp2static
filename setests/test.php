<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

require_once('vendor/autoload.php');

class WPStaticHtmlOutputPluginTest extends TestCase {

    /**
     * @var \RemoteWebDriver
     */
    protected $webDriver;

	public function setUp()
    {
		$host = 'http://localhost:4444/wd/hub';

		// For Chrome
		$capabilities = DesiredCapabilities::chrome();
		$capabilities->setCapability( 'acceptSslCerts', true );

		// // For Firefox
		// //$capabilities = DesiredCapabilities::firefox();
		// //$capabilities->setCapability( 'acceptInsecureCerts', true );

        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
    }

    protected $url = 'http://172.17.0.3/wp-admin/';

    public function testGitHubHome()
    {

        $this->webDriver->get($this->url);
		//$driver->get( 'http://172.17.0.3/wp-admin/' );
		$this->assertContains('Admin screen', $this->webDriver->getTitle());
    }    

}

