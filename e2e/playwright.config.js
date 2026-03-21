const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests',
    timeout: 30000,
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
