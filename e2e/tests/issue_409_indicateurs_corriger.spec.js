// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Un bouton « Corriger » sur toutes les alertes (issue #409, lot 1)
 *
 *   - chaque alerte non vide déclare un écran cible et des lignes à corriger ;
 *   - l'écran ouvert sur ces lignes les montre même quand son propre filtre
 *     les masquerait (matchs archivés, par exemple).
 */

/** Même principe que issue_308 : cookie posé sur `baseURL` avant de naviguer. */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    expect(session_id).toBeTruthy();
    await page.context().addCookies([{
        name: 'PHPSESSID',
        value: session_id,
        url: baseURL || 'http://localhost',
    }]);
}

test.describe('Issue #409 — « Corriger » sur toutes les alertes', () => {

    test('chaque alerte non vide désigne ses lignes dans un écran', async ({ page, request, baseURL }) => {
        test.setTimeout(180000);
        await loginAsAdmin(page, request, baseURL);
        const liste = await (await request.get('/ajax/indicators.php?mode=list')).json();
        const alertes = liste.results.filter((i) => i.type === 'alert');
        expect(alertes.length).toBeGreaterThan(20);
        let nonVides = 0;
        for (const alerte of alertes) {
            const detail = await (await request.get(`/ajax/indicators.php?mode=detail&id=${alerte.id}`)).json();
            expect(detail.target, alerte.fieldLabel).toBeTruthy();
            if (detail.value > 0) {
                nonVides += 1;
                expect(detail.ids.length, `${alerte.fieldLabel} : lignes à corriger`).toBeGreaterThan(0);
                expect(detail.details[0], alerte.fieldLabel).not.toHaveProperty('indicator_id');
            }
        }
        test.info().annotations.push({ type: 'alertes non vides', description: String(nonVides) });
    });

    test("un match archivé désigné par l'URL est montré malgré le préréglage", async ({ page, request, baseURL }) => {
        test.setTimeout(90000);
        await loginAsAdmin(page, request, baseURL);
        const response = page.waitForResponse((r) => r.url().includes('/matchmgr/getMatches'), { timeout: 60000 });
        await page.goto('/admin/index.html#/matches');
        const matches = await (await response).json();
        const archived = matches.find((m) => m.match_status === 'ARCHIVED');
        test.skip(!archived, 'aucun match archivé dans cette base');

        await page.goto(`/admin/index.html#/matches?ids=${archived.id_match}`);
        const rows = page.locator('tbody tr');
        await expect(rows).toHaveCount(1, { timeout: 30000 });
        await expect(rows.first()).toContainText(archived.code_match);
    });
});
