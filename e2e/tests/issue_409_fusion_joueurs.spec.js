// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Fusionner deux fiches joueur (issue #409, lot 2)
 *
 * Deux fiches de test (helper player_merge_setup) : la vraie, dans une équipe,
 * et un doublon de saisie portant le numéro de licence. On ouvre l'écran
 * Joueurs sur ces deux fiches, comme le fait « Corriger » de l'alerte, on les
 * sélectionne et on fusionne : il n'en reste qu'une, qui a récupéré la licence.
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

test.describe('Issue #409 — fusion de deux fiches joueur', () => {
    let ids;

    test.beforeAll(async ({ request }) => {
        const res = await request.get('/e2e/helpers/player_merge_setup.php');
        expect(res.status(), await res.text()).toBe(200);
        ids = await res.json();
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/player_merge_setup.php?teardown=1');
    });

    test('sélectionner deux fiches, garder la vraie, fusionner', async ({ page, request, baseURL }) => {
        test.setTimeout(90000);
        await loginAsAdmin(page, request, baseURL);
        await page.goto(`/admin/index.html#/players?ids=${ids.real},${ids.duplicate}`);
        const rows = page.locator('tbody tr');
        await expect(rows).toHaveCount(2, { timeout: 60000 });

        const open = page.getByTestId('player-merge-open');
        await expect(open).toBeDisabled();
        for (const row of await rows.all()) {
            await row.locator('input[type="checkbox"]').check();
        }
        await expect(open).toBeEnabled();
        await open.click();

        const modal = page.getByTestId('player-merge');
        await expect(modal).toBeVisible();
        // La fiche proposée par défaut est celle qui a une équipe.
        await expect(modal.getByTestId('player-merge-' + ids.real)).toContainText('gardée');
        await expect(modal.getByTestId('player-merge-' + ids.duplicate)).toContainText('supprimée');
        await page.screenshot({ path: 'test-results/issue-409/fusion.png' });

        await modal.getByTestId('player-merge-confirm').click();
        await expect(modal).toHaveCount(0, { timeout: 15000 });
        await expect(rows).toHaveCount(1, { timeout: 30000 });
        await expect(rows.first()).toContainText('Vraie');

        // La licence du doublon a été reportée sur la fiche gardée.
        await rows.first().click();
        await expect(page.getByRole('dialog')).toContainText('E2E0000001');
    });
});
