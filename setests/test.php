<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverBy;
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

    public function testAdminWorkInProgress() {
        $this->webDriver->get($this->url);
		$this->assertContains('Log In ‹ wp plugindev — WordPress', $this->webDriver->getTitle());
    }    

	public function logInToAdmin() {
        $this->webDriver->get($this->url);

		// insert username #user_login

		// insert password #user_pass

		// submit and wait for dashboard to be visible submit #loginform
	}	

	public function resetPluginSettingsToDefault() {
		$this->logInToAdmin();

        $this->webDriver->get('http://172.17.0.3/wp-admin/tools.php?page=wp-static-html-output-options');
	
		// .resetDefaultSettingsButton
	}	

    public function testSavedDeploymentMethodIsRetained() {

		$this->resetPluginSettingsToDefault();

		$deployment_chooser = $this->webDriver->findElement(WebDriverBy::name('selected_deployment_option'));

		$deployment_chooser_select = new WebDriverSelect($deployment_chooser);

		$this->assertContains(
			'Log In ‹ wp plugindev — WordPress',
			$deployment_chooser_select->getFirstSelectedOption()->getAttribute('value'));

		$this->assertContains(
			'Log In ‹ wp plugindev — WordPress',
			$deployment_chooser_select->getFirstSelectedOption()->getText());
    }    

	public function tearDown() {

        $this->webDriver->close();
        $this->webDriver->quit();
		
	}

}

