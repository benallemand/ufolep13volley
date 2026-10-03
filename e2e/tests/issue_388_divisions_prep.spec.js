// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Préparation de saison dans « Réorganiser les divisions » (issue #388)
 *
 *   - « Non affectées » n'affiche que les équipes inscrites ; une case remet
 *     celles des anciennes saisons ;
 *   - la division X vient en tête et se signale « à placer ».
 *
 * Tout vit dans une compétition de test « dx » (helper divisions_prep_setup).
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

test.describe('Issue #388 — préparation de saison, réorganisation des divisions', () => {
    test.beforeAll(async ({ request }) => {
        const res = await request.get('/e2e/helpers/divisions_prep_setup.php');
        expect(res.status(), await res.text()).toBe(200);
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/divisions_prep_setup.php?teardown=1');
    });

    test('« Non affectées » limité aux inscrites, division X en tête', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/divisions');
        await page.getByRole('combobox').selectOption({ label: 'E2E Préparation' });

        const unassigned = page.getByTestId('divisions-col-unassigned');
        await expect(unassigned).toContainText('E2E Prep Inscrite', { timeout: 15000 });
        await expect(unassigned).not.toContainText('E2E Prep Ancienne');
        await expect(page.getByTestId('divisions-hidden-count')).toHaveText(/1 équipe\(s\) non inscrite\(s\) masquée\(s\)/);

        // La division à placer vient juste après « Non affectées ».
        const columns = page.locator('[data-testid^="divisions-col-"]');
        await expect(columns.nth(1)).toHaveAttribute('data-testid', 'divisions-col-X');
        await expect(columns.nth(2)).toHaveAttribute('data-testid', 'divisions-col-1');
        const toPlace = page.getByTestId('divisions-col-X');
        await expect(toPlace).toContainText('à placer');
        await expect(toPlace).toContainText('E2E Prep A placer');

        // Une équipe classée sans inscription est signalée à retirer.
        const division1 = page.getByTestId('divisions-col-1');
        await expect(page.getByTestId('divisions-leaving-1')).toHaveText(/1 à retirer/);
        await expect(division1.locator('li', { hasText: 'E2E Prep Partante' })
            .getByTestId('divisions-not-registered')).toBeVisible();
        await expect(division1.locator('li', { hasText: 'E2E Prep Classee' })
            .getByTestId('divisions-not-registered')).toHaveCount(0);
        await expect(page.getByTestId('divisions-leaving-X')).toHaveCount(0);

        // Une inscription dont l'équipe n'est pas encore créée est montrée,
        // badge « équipe à créer », et ne se déplace pas.
        const aCreer = unassigned.locator('li', { hasText: 'E2E Prep A creer' });
        await expect(aCreer.getByTestId('divisions-to-create')).toHaveText('équipe à créer');
        await expect(aCreer).toHaveAttribute('draggable', 'false');

        await page.screenshot({ path: 'test-results/issue-388/01_inscrites_seules.png', fullPage: true });

        // La case remet les équipes des anciennes saisons, et les retire.
        const showAll = page.getByTestId('divisions-show-all');
        await showAll.check();
        await expect(unassigned).toContainText('E2E Prep Ancienne');
        await expect(page.getByTestId('divisions-hidden-count')).toHaveCount(0);
        await showAll.uncheck();
        await expect(unassigned).not.toContainText('E2E Prep Ancienne');
        await expect(unassigned).toContainText('E2E Prep Inscrite');
    });

    test('les colonnes passent à la ligne, sans barre de défilement horizontale', async ({ page, request, baseURL }) => {
        // Trois colonnes de 16 rem ne tiennent pas sur 700 px.
        await page.setViewportSize({ width: 700, height: 900 });
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/divisions');
        await page.getByRole('combobox').selectOption({ label: 'E2E Préparation' });
        await expect(page.getByTestId('divisions-col-1')).toBeVisible({ timeout: 15000 });

        const board = page.getByTestId('divisions-board');
        expect(await board.evaluate((el) => el.scrollWidth <= el.clientWidth)).toBe(true);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        // La dernière colonne est passée sous la première.
        const first = await page.getByTestId('divisions-col-unassigned').boundingBox();
        const last = await page.getByTestId('divisions-col-1').boundingBox();
        expect(last.y).toBeGreaterThan(first.y);
    });
});
