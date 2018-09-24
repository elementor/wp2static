<?php
/**
 * WPStaticHtmlOutputPluginTest
 *
 * @package WP2Static
 */

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverExpectedCondition;
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

    protected $url = 'http://172.18.0.3/wp-admin/';

    public function testAdminWorkInProgress() {
        $this->webDriver->get($this->url);
		$this->assertContains('Log In ‹ wp plugindev — WordPress', $this->webDriver->getTitle());
    }

	public function debugWithScreenshot() {
		// appears in setests folder
		$this->webDriver->takeScreenshot('screenshot.png');
	}

	public function logInToAdmin() {
        $this->webDriver->get($this->url);

		$this->webDriver->findElement( WebDriverBy::id('user_login'))->sendKeys('admin');

		$this->webDriver->findElement( WebDriverBy::id('user_pass'))->sendKeys('admin');

		$this->webDriver->findElement( WebDriverBy::id('user_pass'))->submit();

		$this->webDriver->wait()->until(
			WebDriverExpectedCondition::titleContains('Dashboard ‹ wp plugindev — WordPress')
		);
	}

	public function resetPluginSettingsToDefault() {
		$this->goToPluginSettingsPage();
	
		$this->webDriver->findElement(WebDriverBy::className('resetDefaultSettingsButton'))->click();

		$this->webDriver->wait()->until(
			WebDriverExpectedCondition::alertIsPresent(),
			'Settings have been reset to default, the page will now be reloaded.'
		);

		$this->webDriver->switchTo()->alert()->accept();

		// TODO: replace browser popups with on page notifications and WP messages

		// note, the reload happens after settings reset has happened, so don't need to wait to continue
	}

	public function goToPluginSettingsPage() {
		// TODO: handle case when license needs entering

        $this->webDriver->get('http://172.18.0.3/wp-admin/tools.php?page=wp-static-html-output-options');

		$this->webDriver->wait()->until(
			WebDriverExpectedCondition::titleContains('WP Static HTML Output ‹ wp plugindev — WordPress')
		);
	}

	public function savePluginOptions() {
		$save_button = $this->webDriver->findElement(
			WebDriverBy::className('saveSettingsButton')
		);
		$save_button->click();

		$this->webDriver->wait()->until(
			WebDriverExpectedCondition::alertIsPresent(),
			'Options have been saved'
		);

		$this->webDriver->switchTo()->alert()->accept();
	}

	public function setDeploymentMethod($value) {
		$deployment_chooser = $this->webDriver->findElement(WebDriverBy::name('selected_deployment_option'));

		$deployment_chooser_select = new WebDriverSelect($deployment_chooser);

		$deployment_chooser_select->selectByValue($value);
	}

	public function getSelectedDeploymentMethod() {
		$deployment_chooser = $this->webDriver->findElement(WebDriverBy::name('selected_deployment_option'));

		$deployment_chooser_select = new WebDriverSelect($deployment_chooser);

		return array(
			'value' => $deployment_chooser_select->getFirstSelectedOption()->getAttribute('value'),
			'text' => $deployment_chooser_select->getFirstSelectedOption()->getText()
		);
	}

    public function testSavedDeploymentMethodIsRetained() {

		$this->logInToAdmin();

		// TODO: this needs to reset the interface, also, then no need to reload page to start again
		$this->resetPluginSettingsToDefault();

		$this->goToPluginSettingsPage();


		$this->setDeploymentMethod('s3');

		$this->savePluginOptions();

		$this->goToPluginSettingsPage();

		$this->assertContains(
			"S3 - Amazon's Simple Storage Service",
			$this->getSelectedDeploymentMethod()['text']);

		$this->assertContains(
			's3',
			$this->getSelectedDeploymentMethod()['value']);
    }

	public function setTargetFolder($target_folder) {
		$this->webDriver->findElement( WebDriverBy::id('targetFolder'))->sendKeys($target_folder);
	}

	public function setBaseURL($base_url) {
		$this->webDriver->findElement( WebDriverBy::id('baseUrl-folder'))->sendKeys($base_url);
	}

	public function doTheExport() {
		$this->webDriver->findElement(WebDriverBy::id('startExportButton'))->click();

		$driver = $this->webDriver;
	
		$this->webDriver->wait(30)->until(
			function () use ($driver) {
				$progress_indicator = $this->webDriver->findElement(WebDriverBy::id('progress'));

				$progress_icon_src = $progress_indicator->getAttribute('src');
				
				return $progress_icon_src == 'http://172.18.0.3/wp-content/plugins/wordpress-static-html-output/images/greentick.png';
			},
			'Error waiting for progress icon to show success'
		);
	}

    public function testFolderDeployment() {

		$timestamp = (string) time();

		$this->logInToAdmin();

		// TODO: this needs to reset the interface, also, then no need to reload page to start again
		$this->resetPluginSettingsToDefault();

		$this->goToPluginSettingsPage();

		$this->setDeploymentMethod('folder');

		$this->setBaseURL('http://google.com');

		$this->setTargetFolder($timestamp);

		$this->doTheExport();

		$this->assertContains(
			"This feature is yet to be released into the official version",
			file_get_contents('http://172.18.0.3/' . $timestamp . '/'));
    }

    public function testFolderDeploymentDoesntOverwriteRoot() {

		$timestamp = (string) time();

		$this->logInToAdmin();

		// TODO: this needs to reset the interface, also, then no need to reload page to start again
		$this->resetPluginSettingsToDefault();

		$this->goToPluginSettingsPage();

		$this->setDeploymentMethod('folder');

		$this->setBaseURL('http://google.com');

		$this->setTargetFolder('');

		$this->doTheExport();

		$ch = curl_init("http://172.18.0.3/index.html");

		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// $retcode >= 400 -> not found, $retcode = 200, found.
		curl_close($ch);

		$this->assertEquals(
			"404",
			$retcode);
    }

	public function tearDown() {

		// TODO: remove any test files

        $this->webDriver->close();
        $this->webDriver->quit();
		
	}

}

