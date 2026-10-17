// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — écran public « Archives des classements » (issue #435).
 *
 * Setup : une saison de test 2001-2002 reconstituée (archives_setup.php) :
 * deux équipes, un match archivé, une pénalité d'un point.
 */
test.describe('Issue #435 — archives des classements', () => {
    test.beforeEach(async ({ request }) => {
        const res = await request.get('/e2e/helpers/archives_setup.php');
        expect(res.ok(), await res.text()).toBe(true);
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/archives_teardown.php');
    });

    test('le menu mène aux archives, qui affichent classement et résultats', async ({ page }) => {
        await page.goto('/pages/home.html#/home');
        await expect(page.locator('a[href="#/archives"]').first()).toBeAttached({ timeout: 15000 });

        await page.goto('/pages/home.html#/archives?saison=2001-2002&demi=1&competition=m&division=Z9');
        await expect(page.getByTestId('archives')).toBeVisible({ timeout: 15000 });
        await expect(page.getByTestId('archives-period')).toHaveValue('2001-2002|1');

        const rank = page.getByTestId('archives-rank');
        await expect(rank.locator('tbody tr')).toHaveCount(2);
        await expect(rank.locator('tbody tr').first()).toContainText('E2E Archives A');
        await expect(rank.locator('tbody tr').nth(1)).toContainText('1 pts de pénalité');
        await expect(page.getByTestId('archives-rebuilt')).toBeVisible();

        const results = page.getByTestId('archives-results');
        await expect(results).toContainText('06/11/2001');
        await expect(results).toContainText('3 - 0');
        await page.screenshot({ path: 'test-results/issue-435/archives.png', fullPage: true });
    });
});
