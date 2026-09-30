// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #377 — l'écran admin « Divisions / poules » enregistre à nouveau.
 *
 * `Rank::saveRank` exigeait `$dirtyFields`, que l'admin Vue n'envoie pas :
 * toute édition finissait en « Paramètres manquants pour cette action ! ».
 * Setup : un engagement de test en division 77 (`ranks_setup.php`).
 */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: baseURL }]);
}

test.describe('Issue #377 — Divisions / poules', () => {
    test.beforeEach(async ({ request }) => {
        const res = await request.get('/e2e/helpers/ranks_setup.php');
        const body = await res.json();
        expect(body.error, `Setup failed: ${body.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/ranks_setup.php?teardown=1');
    });

    test('éditer un engagement puis enregistrer fonctionne', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/ranks');
        await page.getByPlaceholder(/Rechercher/).fill('E2E Poule Team');
        const row = page.locator('tbody tr', { hasText: 'E2E Poule Team' });
        await expect(row).toHaveCount(1, { timeout: 15000 });
        await row.locator('input[type=checkbox]').check();
        await page.getByRole('button', { name: /Éditer/ }).click();

        const dialog = page.locator('.modal.modal-open, dialog[open]').first();
        const rang = dialog.locator('input[type=number]').first();
        await expect(rang).toHaveValue('5');
        await rang.fill('2');

        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/rest/action.php/rank/saveRank')),
            dialog.getByRole('button', { name: /Enregistrer/ }).click(),
        ]);
        expect(response.status(), await response.text()).toBe(200);
        await expect(row.locator('td', { hasText: /^2$/ })).toHaveCount(1, { timeout: 10000 });
        await page.screenshot({ path: 'test-results/issue-377/divisions_poules_enregistre.png', fullPage: true });
    });
});
