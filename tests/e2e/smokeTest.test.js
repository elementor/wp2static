const querystring = require('querystring')

describe('Plugin page renders and filelist is generated', () => {
  beforeAll(async () => {
    jest.setTimeout(10000);

    await page.setViewport({
      width: 1920,
      height: 1080,
      deviceScaleFactor: 1,
    });

    const testServerUrl = process.env.WP2STATIC_E2E_TEST_URL
    const testServerUser = process.env.WP2STATIC_E2E_TEST_USER
    const testServerPass = process.env.WP2STATIC_E2E_TEST_PASS

    await page.goto(testServerUrl + '/wp-login.php');
    await page.type('#user_login', testServerUser);
    await page.type('#user_pass', testServerPass);
    await page.click('#wp-submit');

    await page.goto(testServerUrl + '/wp-admin/admin.php?page=wp2static');
    await browser.newPage();

    // wait for filelist preview to complete:
    await page.waitForFunction(
      `document.querySelector('#current_action').innerHTML.includes('URLs were detected')`
    );

  });

  it('should be titled "WP2Static Test Site"', async () => {
    await expect(page.title()).resolves.toMatch('WP2Static');
  });

  it('generate button should not be disabled ', async () => {
    const is_disabled = await page.evaluate(() => document.querySelector('#wp2staticGenerateButton[disabled]') !== null);

    await expect(is_disabled).toBeFalsy();
  });

  it('Resetting default settings sets staging deploy method to "folder"', async () => {
    const navigationPromise =  page.waitForNavigation({ waitUntil: 'load' })
    await page.$eval('#wp2staticResetDefaultsButton', el => el.click())
    await navigationPromise

    // check staging deploy method reset to folder
    const stagingDeployMethod2 =
      await page.evaluate(() => document.querySelector('#deploymentMethodStaging').innerText);

    await expect(stagingDeployMethod2).toMatch('Deployment Method folder');
  });

  it('Set staging deploy method to "zip" and saving persists', async () => {
    await page.$eval('#staging_deploy', el => el.click());
    await page.select('#selected_deployment_method', 'zip')

    await page.$eval('#wp2staticSaveButton', el => el.click());

    await browser.newPage();

    // wait for filelist preview to complete:
    await page.waitForFunction(
      `document.querySelector('#current_action').innerHTML.includes('URLs were detected')`
    );

    const stagingDeployMethod = await page.evaluate(() => document.querySelector('#deploymentMethodStaging').innerText);

    await expect(stagingDeployMethod).toMatch('Deployment Method zip');
  });
});
