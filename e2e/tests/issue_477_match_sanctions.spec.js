// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #477 — sur la page publique d'une division, un match soumis à une
 * sanction l'affiche, avec l'équipe et la raison : « sanction possible » tant
 * que la commission n'a pas décidé, « sanction appliquée » ensuite. Une
 * sanction levée disparaît.
 *
 * Setup : `sanctions_setup.php` (match fictif `E2E475`, deux sanctions
 * constatées, une par équipe).
 */
test.describe('Issue #477 — sanctions sur les listes de matchs', () => {
    let setup;

    test.beforeEach(async ({ request }) => {
        setup = await (await request.get('/e2e/helpers/sanctions_setup.php')).json();
        expect(setup.error, `Setup failed: ${setup.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/sanctions_setup.php?teardown=1');
    });

    test('le match affiche la sanction possible et sa raison', async ({ page }) => {
        await page.goto(`/pages/home.html#/divisions/m/${setup.division}`);
        const row = page.locator('tr', { hasText: 'E2E475' });
        await expect(row).toBeVisible({ timeout: 15000 });
        const sanctions = row.getByTestId('match-sanction');
        await expect(sanctions).toHaveCount(2);
        for (const s of setup.sanctions) {
            await expect(row).toContainText(s.equipe);
        }
        await expect(sanctions.first()).toContainText('sanction possible');
        await expect(sanctions.first()).toContainText('Feuille de match non signée');
        await expect(sanctions.first()).toContainText('en attente de décision de la commission');
        await row.scrollIntoViewIfNeeded();
        await page.screenshot({ path: 'test-results/issue-477/division.png' });
    });
});
