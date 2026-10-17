// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — « Figer le classement » dans l'écran Compétitions (issue #435).
 *
 * Le contenu de la copie est vérifié par PHPUnit (RankFreezeTest). Ici : le
 * bouton, sa confirmation et la réponse du serveur. Sans match joué dans la
 * période en cours, rien n'est écrit (« sans match joué ») : le test ne
 * modifie pas la base de dev.
 */
async function loginAsAdmin(page, request) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status()).toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: new URL(page.url()).origin }]);
}

test.describe('Issue #435 — figer le classement', () => {
    test("le bouton fige la compétition choisie et rend compte", async ({ page, request, baseURL }) => {
        await page.goto(baseURL + '/pages/home.html');
        await loginAsAdmin(page, request);
        await page.goto('/admin/index.html#/competitions');

        const freeze = page.getByTestId('freeze-ranks');
        await expect(freeze).toBeVisible({ timeout: 15000 });
        await expect(freeze).toBeDisabled();

        const row = page.locator('tbody tr', { hasText: 'Championnat Masculin' }).first();
        await row.locator('input[type="checkbox"]').check();
        await expect(freeze).toBeEnabled();

        page.once('dialog', (dialog) => {
            expect(dialog.message()).toContain('Figer le classement de la demi-saison en cours');
            dialog.accept();
        });
        const answer = page.waitForResponse((r) => r.url().includes('/rank/freezeRanks'));
        await freeze.click();
        const response = await answer;
        expect(response.ok(), await response.text()).toBe(true);
        const body = await response.json();
        expect(body.message).toMatch(/classement\(s\) figé\(s\)/);
        expect(body.report).toHaveLength(1);
        expect(body.report[0].competition).toBe('Championnat Masculin');
        await page.screenshot({ path: 'test-results/issue-435/figer.png', fullPage: true });
    });
});
