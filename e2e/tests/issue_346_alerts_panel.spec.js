// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Encart d'alertes à la connexion (issue #346)
 *
 * Setup : le match de test de #240 (match joué sans données, donc une action
 * en attente), en vrai responsable de l'équipe à domicile (`?role=leader`).
 */
test.describe('Issue #346 — encart d\'alertes', () => {
    let setup = null;

    test.beforeEach(async ({ page }) => {
        await page.goto('/e2e/helpers/match_actions_setup.php?role=leader');
        const body = JSON.parse(await page.locator('body').innerText());
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        setup = body;
    });

    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/match_actions_teardown.php');
        setup = null;
    });

    test("l'action de match en attente apparaît dans l'encart, avec son lien", async ({ page }) => {
        await page.goto('/pages/my_page.html#/dashboard');
        const encart = page.getByTestId('alerts-panel');
        await expect(encart).toBeVisible({ timeout: 15000 });

        // Ciblée par son lien : d'autres matchs contre la même équipe peuvent
        // porter la même action en attente (le seed de CI en a un, id 1).
        const carte = encart.getByTestId('alert-card')
            .filter({ has: page.locator(`a[href="${setup.expected_url}"]`) });
        await expect(carte).toBeVisible();
        await expect(carte.getByRole('link', { name: 'Corriger' })).toHaveAttribute('href', setup.expected_url);
        await expect(carte).toContainText(setup.equipe_adverse);
        await expect(carte).toContainText(setup.expected_label);
        await page.screenshot({ path: 'test-results/issue-346/encart-alertes.png', fullPage: true });
    });

    test("l'API ne renvoie que des alertes structurées", async ({ page }) => {
        const alertes = await (await page.request.get('/rest/action.php/alerts/getAlerts')).json();
        expect(Array.isArray(alertes)).toBe(true);
        expect(alertes.length).toBeGreaterThan(0);
        for (const a of alertes) {
            expect(['error', 'warning', 'info']).toContain(a.criticity);
            expect(typeof a.issue).toBe('string');
            expect(a).toHaveProperty('link');
            expect(a).toHaveProperty('team');
        }
    });
});
