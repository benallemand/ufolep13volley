// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #458 — annuler une pénalité automatique depuis l'administration.
 *
 * Setup : un match fictif `E2E458` et ses deux pénalités
 * (`match_penalties_setup.php`). On annule celle d'une équipe : elle sort de la
 * liste des pénalités actives et de l'indicateur, l'autre reste.
 */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: baseURL }]);
}

async function indicatorRows(request) {
    const list = (await (await request.get('/ajax/indicators.php?mode=list')).json()).results || [];
    const indicator = list.find((i) => i.fieldLabel === 'Pénalités automatiques');
    if (!indicator) {
        return [];
    }
    const detail = await (await request.get(`/ajax/indicators.php?mode=detail&id=${indicator.id}`)).json();
    return (detail.details || []).filter((row) => row.match_ === 'E2E458');
}

test.describe('Issue #458 — annuler une pénalité automatique', () => {
    let setup;

    test.beforeEach(async ({ request }) => {
        setup = await (await request.get('/e2e/helpers/match_penalties_setup.php')).json();
        expect(setup.error, `Setup failed: ${setup.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/match_penalties_setup.php?teardown=1');
    });

    test("l'admin annule la pénalité d'une équipe, l'autre reste active", async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        expect(await indicatorRows(request)).toHaveLength(2);

        await page.goto('/admin/index.html#/match-penalties');
        await page.getByPlaceholder(/Rechercher/).fill('E2E458');
        const rows = page.locator('tbody tr', { hasText: 'E2E458' });
        await expect(rows).toHaveCount(2, { timeout: 15000 });

        const [first] = setup.penalties;
        const row = page.locator('tbody tr', { hasText: first.equipe }).filter({ hasText: 'E2E458' });
        await row.locator('input[type="checkbox"]').check();

        page.once('dialog', (confirm) => confirm.accept());
        const [cancel] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/matchpenalty/cancelPenalties')),
            page.getByRole('button', { name: /Annuler la pénalité/ }).click(),
        ]);
        expect(cancel.status(), await cancel.text()).toBe(200);

        // la pénalité annulée sort de la liste des actives…
        await expect(rows).toHaveCount(1);
        await expect(rows).not.toContainText(first.equipe);
        // …et reste visible parmi les annulées
        await page.getByLabel('Voir aussi les annulées').check();
        await expect(page.getByLabel('Voir aussi les annulées')).toBeChecked();
        await expect(rows).toHaveCount(2);
        await expect(page.locator('tbody tr', { hasText: first.equipe }).filter({ hasText: 'E2E458' }))
            .toContainText('annulée');
        await page.screenshot({ path: 'test-results/issue-458/penalite_annulee.png', fullPage: true });

        // l'indicateur ne compte plus que la pénalité active
        const remaining = await indicatorRows(request);
        expect(remaining).toHaveLength(1);
        expect(remaining[0].equipe).toBe(setup.penalties[1].equipe);
    });
});
