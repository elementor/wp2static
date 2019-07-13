describe('Plugin page renders and filelist is generated', () => {
  beforeAll(async () => {
    // change timeout to 10 seconds
    jest.setTimeout(10000);
    await page.goto('http://localhost:81/wp-login.php');
    await page.type('#user_login', 'admin');
    await page.type('#user_pass', 'banana');
    await page.click('#wp-submit');

    await page.goto('http://localhost:81/wp-admin/admin.php?page=wp2static');
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
});
