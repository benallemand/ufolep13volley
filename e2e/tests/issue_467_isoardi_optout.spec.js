// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #467 — le responsable d'une équipe du championnat masculin peut la
 * désinscrire de la coupe Isoardi (et l'y réinscrire) depuis « Mon équipe »,
 * pendant la période d'inscription à la coupe Khoury Hanna seulement.
 */

async function setup(page, state) {
    await page.goto(`/e2e/helpers/isoardi_optout_setup.php?state=${state}`);
    const data = JSON.parse(await page.locator('body').innerText());
    if (data.error) throw new Error(`Setup failed: ${data.error}`);
    return data;
}

async function teardown(page, team) {
    const params = new URLSearchParams({
        teardown: '1', id_equipe: String(team.id_equipe), cup: String(team.cup),
        start: team.start, limit: team.limit,
    });
    await page.request.get(`/e2e/helpers/isoardi_optout_setup.php?${params}`);
}

async function cupState(page) {
    return (await page.request.get('/rest/action.php/team/getMyCupRegistration')).json();
}

test.describe('Issue #467 — désinscription de la coupe Isoardi', () => {
    let team;

    test.afterEach(async ({ page }) => {
        if (team) await teardown(page, team);
        team = undefined;
    });

    test('pendant la période, le responsable désinscrit puis réinscrit son équipe', async ({ page }) => {
        team = await setup(page, 'open');
        await page.goto('/pages/my_page.html#/team');
        const row = page.getByTestId('isoardi-registration');
        await expect(row).toBeVisible({ timeout: 15000 });
        await expect(row.getByTestId('isoardi-status')).toHaveText('inscrite');
        await expect(row).toContainText('Choix modifiable jusqu');

        await row.getByTestId('isoardi-toggle').click();
        await expect(row.getByTestId('isoardi-status')).toHaveText('désinscrite');
        await expect(row.getByTestId('isoardi-toggle')).toContainText('Réinscrire');
        expect((await cupState(page)).is_cup_registered).toBe(false);
        await page.screenshot({ path: 'test-results/issue-467/desinscrite.png' });

        await row.getByTestId('isoardi-toggle').click();
        await expect(row.getByTestId('isoardi-status')).toHaveText('inscrite');
        expect((await cupState(page)).is_cup_registered).toBe(true);
    });

    test('hors période, le choix est affiché sans bouton et le serveur refuse', async ({ page }) => {
        team = await setup(page, 'closed');
        await page.goto('/pages/my_page.html#/team');
        const row = page.getByTestId('isoardi-registration');
        await expect(row).toBeVisible({ timeout: 15000 });
        await expect(row.getByTestId('isoardi-status')).toHaveText('inscrite');
        await expect(row).toContainText('Choix clos');
        await expect(row.getByTestId('isoardi-toggle')).toHaveCount(0);

        const response = await page.request.post('/rest/action.php/team/setMyCupRegistration', {
            multipart: { is_cup_registered: '0' },
        });
        expect(response.status()).toBe(403);
        expect((await response.json()).message).toContain('ne peut plus être changée');
        expect((await cupState(page)).is_cup_registered).toBe(true);
    });
});
