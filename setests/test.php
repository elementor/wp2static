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

		$this->webDriver->findElement( WebDriverBy::id('user_login'))->sendKeys('admin');

		$this->webDriver->findElement( WebDriverBy::id('user_pass'))->click('admin');

		$this->webDriver->findElement( WebDriverBy::id('user_pass'))->submit();

		//wait for dashboard to be visible submit #loginform
	}	

	public function resetPluginSettingsToDefault() {
		$this->logInToAdmin();

        $this->webDriver->get('http://172.17.0.3/wp-admin/tools.php?page=wp-static-html-output-options');
	
		// .resetDefaultSettingsButton
	}	

	public function savePluginOptions() {
		 

		$save_button = $this->webDriver->findElement(
			WebDriverBy::class('saveSettingsButton')
		);
		$save_button->click();

		$this->webDriver->wait()->until(
			WebDriverExpectedCondition::alertIsPresent(),
			'Options have been saved'
		);

		$this->webDriver->switchTo()->alert()->accept();
	}

    public function testSavedDeploymentMethodIsRetained() {

		$this->logInToAdmin();

		$this->resetPluginSettingsToDefault();

		$deployment_chooser = $this->webDriver->findElement(WebDriverBy::name('selected_deployment_option'));

		$deployment_chooser_select = new WebDriverSelect($deployment_chooser);

		$deployment_chooser_select->selectByValue('s3'); 

		$this->savePluginOptions();


		// reload page

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

