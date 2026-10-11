// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Responsable de plusieurs équipes : la modification de date d'un match
 * s'affiche pour chacune de ses équipes, pas seulement l'équipe courante
 * (issue #421).
 *
 * Setup : un compte responsable de deux équipes, dont l'équipe courante ne
 * joue pas le match de test (non confirmé, celui de son autre équipe).
 */
test.describe('Issue #421 — responsable de plusieurs équipes', () => {
    let setup = null;

    test.beforeEach(async ({ page }) => {
        await page.goto('/e2e/helpers/multi_team_setup.php');
        const body = JSON.parse(await page.locator('body').innerText());
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        setup = body;
    });

    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/multi_team_teardown.php');
        setup = null;
    });

    test('la date se modifie sur le match d\'une équipe qui n\'est pas l\'équipe courante', async ({ page }) => {
        const session = await (await page.request.get('/session_user.php')).json();
        expect(session.id_equipe).toBe(setup.id_equipe_courante);
        expect(session.team_ids).toContain(setup.id_equipe_match);

        await page.goto(`/match.html?id_match=${setup.id_match}`);
        const bloc = page.getByTestId('date-modification');
        await expect(bloc).toBeVisible({ timeout: 15000 });
        await expect(bloc.getByRole('button', { name: 'Modifier la date' })).toBeVisible();
        await page.screenshot({ path: 'test-results/issue-421/modification-date.png', fullPage: true });
    });
});
