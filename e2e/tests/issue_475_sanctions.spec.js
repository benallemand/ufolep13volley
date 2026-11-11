// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #475 — sanctions : un manquement constaté n'a aucun effet ; la
 * commission l'applique ou le lève depuis l'écran « Sanctions ».
 *
 * Setup : un match fictif `E2E475` et deux sanctions constatées (1 point en
 * moins chacune, `sanctions_setup.php`). On applique la première (le point
 * est retiré), on lève la seconde (clémence), puis on remet la première à
 * décider (le point est rendu).
 */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: baseURL }]);
}

async function indicatorRows(request) {
    const list = (await (await request.get('/ajax/indicators.php?mode=list')).json()).results || [];
    const indicator = list.find((i) => i.fieldLabel === 'Sanctions à décider');
    if (!indicator) {
        return [];
    }
    const detail = await (await request.get(`/ajax/indicators.php?mode=detail&id=${indicator.id}`)).json();
    return (detail.details || []).filter((row) => row.match_ === 'E2E475');
}

async function penalite(request, division, id_equipe) {
    const rows = await (await request.get(`/rest/action.php/rank/getRank?competition=m&division=${division}`)).json();
    return Number(rows.find((r) => Number(r.id_equipe) === id_equipe).penalites);
}

test.describe('Issue #475 — sanctions décidées en réunion', () => {
    let setup;

    test.beforeEach(async ({ request }) => {
        setup = await (await request.get('/e2e/helpers/sanctions_setup.php')).json();
        expect(setup.error, `Setup failed: ${setup.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/sanctions_setup.php?teardown=1');
    });

    test('appliquer, lever, puis remettre à décider', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        const [first, second] = setup.sanctions;
        expect(await indicatorRows(request)).toHaveLength(2);
        // constatée : aucun effet sur le classement
        expect(await penalite(request, setup.division, first.id_equipe)).toBe(first.penalite);

        await page.goto('/admin/index.html#/sanctions');
        await page.getByPlaceholder(/Rechercher/).fill('E2E475');
        const rows = page.locator('tbody tr', { hasText: 'E2E475' });
        await expect(rows).toHaveCount(2, { timeout: 15000 });
        await expect(rows.first()).toContainText('à décider');
        const rowOf = (s) => page.locator('tbody tr', { hasText: s.equipe }).filter({ hasText: 'E2E475' });

        // appliquer la première : son point est retiré
        await rowOf(first).locator('input[type="checkbox"]').check();
        page.once('dialog', (prompt) => prompt.accept('réunion E2E'));
        const [applied] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/sanction/applySanctions')),
            page.getByTestId('sanctions-apply').click(),
        ]);
        expect(applied.status(), await applied.text()).toBe(200);
        await expect(rows).toHaveCount(1);
        expect(await penalite(request, setup.division, first.id_equipe)).toBe(first.penalite + 1);

        // lever la seconde (clémence) : aucun point retiré
        await rowOf(second).locator('input[type="checkbox"]').check();
        page.once('dialog', (prompt) => prompt.accept('clémence'));
        const [lifted] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/sanction/liftSanctions')),
            page.getByTestId('sanctions-lift').click(),
        ]);
        expect(lifted.status(), await lifted.text()).toBe(200);
        await expect(rows).toHaveCount(0);
        expect(await penalite(request, setup.division, second.id_equipe)).toBe(second.penalite);
        expect(await indicatorRows(request)).toHaveLength(0);

        // les deux restent visibles parmi les décidées
        await page.getByTestId('sanctions-show-decided').check();
        await expect(rows).toHaveCount(2);
        await expect(rowOf(first)).toContainText('appliquée');
        await expect(rowOf(first)).toContainText('réunion E2E');
        await expect(rowOf(second)).toContainText('levée');
        await page.screenshot({ path: 'test-results/issue-475/sanctions.png' });

        // remettre la première à décider : le point est rendu
        await rowOf(first).locator('input[type="checkbox"]').check();
        page.once('dialog', (confirm) => confirm.accept());
        const [reopened] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/sanction/reopenSanctions')),
            page.getByTestId('sanctions-reopen').click(),
        ]);
        expect(reopened.status(), await reopened.text()).toBe(200);
        await expect(rowOf(first)).toContainText('à décider');
        expect(await penalite(request, setup.division, first.id_equipe)).toBe(first.penalite);
    });

    test('la commission constate un manquement depuis le formulaire', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        const [first] = setup.sanctions;
        await page.goto('/admin/index.html#/sanctions');
        await page.getByRole('button', { name: 'Créer' }).click();
        await page.locator('#f-team_key').selectOption(`m|${first.id_equipe}`);
        await page.locator('#f-motif').selectOption('joueur_non_licencie');
        await page.locator('#f-sanction_type').selectOption('forfait');
        await page.locator('#f-code_match').fill('E2E475');
        await page.locator('#f-comment').fill('joueur sans licence le soir du match');
        await page.screenshot({ path: 'test-results/issue-475/formulaire.png' });
        const [saved] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/sanction/saveSanction')),
            page.locator('form button[type="submit"]').click(),
        ]);
        expect(saved.status(), await saved.text()).toBe(200);

        await page.getByPlaceholder(/Rechercher/).fill('licence');
        const row = page.locator('tbody tr', { hasText: 'E2E475' }).filter({ hasText: 'Joueur sans licence' });
        await expect(row).toHaveCount(1, { timeout: 15000 });
        await expect(row).toContainText('à décider');
        await expect(row).toContainText('Match perdu par forfait');
        // constatée : le match n'est pas touché
        expect(await penalite(request, setup.division, first.id_equipe)).toBe(first.penalite);
    });
});
