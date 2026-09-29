// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Barème FFVB pour les championnats (issue #347)
 *
 * Les championnats (m, f, mo) se classent au barème FFVB et affichent les
 * quotients ; les coupes gardent le barème UFOLEP et la différence de sets.
 * Le bouton admin « Voir en mode FFVB » n'existe plus.
 */
test.describe('Issue #347 — barème de classement', () => {

    test('un championnat affiche le barème FFVB et les quotients', async ({ page, request }) => {
        const rows = await (await request.get('/rest/action.php/rank/getRank',
            { params: { competition: 'm', division: '1' } })).json();
        test.skip(!Array.isArray(rows) || rows.length === 0, 'pas de division 1 masculine dans cette base');
        expect(rows[0]).toHaveProperty('quotient_sets');

        await page.goto('/pages/home.html#/divisions/m/1');
        await expect(page.getByTestId('rank-scale')).toContainText('Barème FFVB', { timeout: 15000 });
        await expect(page.getByRole('columnheader', { name: 'Quot. sets' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Diff. Sets' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: /mode FFVB/ })).toHaveCount(0);
        await page.screenshot({ path: 'test-results/issue-347/championnat.png', fullPage: true });
    });

    test('une coupe garde la différence de sets', async ({ page, request }) => {
        const rows = await (await request.get('/rest/action.php/rank/getRank',
            { params: { competition: 'c', division: '1' } })).json();
        test.skip(!Array.isArray(rows) || rows.length === 0, 'pas de poule 1 en coupe Isoardi dans cette base');
        expect(rows[0]).not.toHaveProperty('quotient_sets');

        await page.goto('/pages/home.html#/divisions/c/1');
        await expect(page.getByRole('columnheader', { name: 'Diff. Sets' })).toBeVisible({ timeout: 15000 });
        await expect(page.getByTestId('rank-scale')).toHaveCount(0);
    });

    test("l'aperçu FFVB admin n'est plus routé", async ({ request }) => {
        const res = await request.get('/rest/action.php/rank/getRankFFVB',
            { params: { competition: 'm', division: '1' } });
        expect(res.status()).toBe(403);
    });
});
