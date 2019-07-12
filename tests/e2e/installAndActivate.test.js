describe('Homepage sanity check', () => {
  beforeAll(async () => {
    await page.goto('http://localhost:81');
  });

  it('should be titled "WP2Static Test Site"', async () => {
    await expect(page.title()).resolves.toMatch('WP2Static Test Site');
  });
});
