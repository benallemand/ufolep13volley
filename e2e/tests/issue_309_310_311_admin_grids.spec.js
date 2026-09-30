// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Grilles d'administration (issues #309, #310, #311), sur l'écran Divisions /
 * poules : filtres par colonne, mémoire de la vue, édition en masse.
 *
 * Setup : trois engagements dans la division de test 77 (`ranks_setup.php?lot=1`),
 * classements de départ 5, 6 et 7, tous « se réengage : oui ».
 */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: baseURL }]);
}

/**
 * Le type de filtre se déduit des données (#310) : liste s'il y a peu de
 * valeurs distinctes (seed de CI), texte sinon (base de dev).
 */
async function setFilter(page, key, value) {
    const control = page.getByTestId('grid-filter-' + key);
    if (await control.evaluate((el) => el.tagName === 'SELECT')) {
        await control.selectOption(value);
    } else {
        await control.fill(value);
    }
}

/** Lignes de la division de test, par la colonne Division filtrée à 77. */
async function filterDivision77(page) {
    await page.getByTestId('grid-column-filters').click();
    await setFilter(page, 'division', '77');
    const rows = page.locator('tbody tr');
    await expect(rows).toHaveCount(3, { timeout: 15000 });
    return rows;
}

test.describe('Grilles d\'administration — #309, #310, #311', () => {
    test.beforeEach(async ({ page, request, baseURL }) => {
        const body = await (await request.get('/e2e/helpers/ranks_setup.php?lot=1')).json();
        expect(body.error, `Setup failed: ${body.error}`).toBeUndefined();
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/ranks');
        await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 15000 });
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/ranks_setup.php?teardown=1');
    });

    test('#310 — un filtre par colonne ne retient que sa colonne', async ({ page }) => {
        const rows = await filterDivision77(page);
        // « 77 » dans la colonne Division : ni un 77 dans un nom, ni la 177e
        await expect(rows.filter({ hasText: 'E2E' })).toHaveCount(3);
        await expect(page.getByTestId('grid-column-filters')).toContainText('Filtres (1)');

        // Deuxième critère, en liste : le classement de départ
        await setFilter(page, 'rank_start', '6');
        await expect(rows).toHaveCount(1);
        await expect(rows.first()).toContainText('E2E Lot Team B');
        await page.screenshot({ path: 'test-results/issue-310/filtres_colonne.png', fullPage: true });
    });

    test('#311 — la vue est retrouvée au retour, et se réinitialise', async ({ page }) => {
        await filterDivision77(page);
        await page.getByRole('columnheader', { name: /Classement initial/ }).click();

        await page.goto('/admin/index.html#/clubs');
        await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 15000 });
        await page.goto('/admin/index.html#/ranks');

        await expect(page.getByTestId('grid-filter-division')).toHaveValue('77', { timeout: 15000 });
        const rows = page.locator('tbody tr');
        await expect(rows).toHaveCount(3);
        await expect(rows.nth(0)).toContainText('E2E Poule Team'); // tri conservé : 5, 6, 7

        await page.getByTestId('grid-reset-view').click();
        await expect(page.getByTestId('grid-filter-row')).toHaveCount(0);
        await expect(page.getByTestId('grid-reset-view')).toHaveCount(0);
        expect(await rows.count()).toBeGreaterThan(3);
    });

    test('#309 — éditer en masse ne change que le champ coché', async ({ page }) => {
        const rows = await filterDivision77(page);
        await page.locator('thead input[type=checkbox]').first().check();
        const edit = page.getByTestId('grid-edit');
        await expect(edit).toContainText('Éditer (3)');
        await edit.click();

        const dialog = page.locator('dialog.modal-open');
        await expect(dialog).toContainText('Modifier 3');
        await dialog.getByTestId('bulk-apply-will_register_again').check();
        // valeur : décochée = « non »
        await expect(dialog.getByTestId('bulk-value-will_register_again')).not.toBeChecked();
        page.once('dialog', (confirm) => confirm.accept());
        await dialog.getByTestId('bulk-submit').click();
        await expect(dialog).toHaveCount(0, { timeout: 15000 });

        await expect(rows).toHaveCount(3);
        for (const [team, rank] of [['E2E Poule Team', '5'], ['E2E Lot Team B', '6'], ['E2E Lot Team C', '7']]) {
            const row = rows.filter({ hasText: team });
            await expect(row).toContainText('non');
            // Le classement de départ, non coché, n'a pas été écrasé
            await expect(row.locator('td', { hasText: new RegExp(`^${rank}$`) })).toHaveCount(1);
        }
        await page.screenshot({ path: 'test-results/issue-309/edition_en_masse.png', fullPage: true });
    });
});
