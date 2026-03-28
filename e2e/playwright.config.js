const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests',
    timeout: 60000,
    expect: { timeout: 10000 },
    workers: 1,
    retries: 1,
    reporter: [['list'], ['html', { outputFolder: 'report', open: 'never' }]],
    use: {
        baseURL: process.env.BASE_URL || 'http://localhost',
        headless: true,
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    globalSetup: './global-setup.js',
});
