<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
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

		$options = new ChromeOptions();
        $options->setBinary("/usr/bin/chromium-browser");
//        $options->addArguments(["--headless","--disable-gpu", "--no-sandbox"]);

		// For Chrome
		$capabilities = DesiredCapabilities::chrome();
		$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
		$capabilities->setCapability( 'acceptSslCerts', true );
		$capabilities->setPlatform("Linux");

		// // For Firefox
		// //$capabilities = DesiredCapabilities::firefox();
		// //$capabilities->setCapability( 'acceptInsecureCerts', true );

        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
    }

    protected $url = 'http://172.17.0.3/wp-admin/';

    public function testAdminWorkInProgress()
    {

        $this->webDriver->get($this->url);
		//$driver->get( 'http://172.17.0.3/wp-admin/' );
		$this->assertContains('Log In ‹ wp plugindev — WordPress', $this->webDriver->getTitle());
    }    

	public function tearDown() {

        $this->webDriver->close();
        $this->webDriver->quit();
		
	}

}

