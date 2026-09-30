// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — En-tête figé des grilles d'administration (issue #386)
 *
 * Sur desktop, la grille défile dans sa propre zone : titre, compteur et
 * actions restent en haut, les en-têtes de colonnes collent en haut de la
 * zone, et les filtres de l'écran et la recherche partent avec les lignes.
 * Sur mobile, seuls les en-têtes de colonnes restent figés.
 *
 * L'écran des joueurs sert de grande grille : avec « tout » par page, la base
 * de développement a 3 650 lignes et le jeu de la CI une soixantaine, les deux
 * débordent largement de la fenêtre.
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

/** Écran des joueurs, toutes les lignes sur une page. */
async function openAllPlayers(page) {
    await page.goto('/admin/index.html#/players');
    const rows = page.locator('tbody tr');
    await expect(rows.first()).toBeVisible({ timeout: 60000 });
    await page.locator('label:has-text("par page") select').selectOption({ label: 'tout' });
    return rows;
}

test.describe('Issue #386 — en-tête figé des grilles d\'administration', () => {
    test.beforeEach(async ({ page }) => {
        // La vue est mémorisée par écran (#311) : on repart d'une vue vierge.
        await page.addInitScript(() => {
            try { window.localStorage.clear(); } catch (e) { /* navigation privée */ }
        });
    });

    test('desktop : titre, actions et en-têtes restent visibles en bas de la grille', async ({ page, request, baseURL }) => {
        await page.setViewportSize({ width: 1440, height: 800 });
        await loginAsAdmin(page, request, baseURL);
        const rows = await openAllPlayers(page);

        const scroller = page.getByTestId('grid-scroller');
        await expect.poll(
            async () => scroller.evaluate(el => el.scrollHeight > el.clientHeight * 2),
            { message: 'La grille doit être nettement plus haute que sa zone' }
        ).toBe(true);

        // La page elle-même ne défile pas : c'est la zone de la grille.
        expect(await page.evaluate(() => document.documentElement.scrollHeight <= window.innerHeight + 1)).toBe(true);

        await scroller.evaluate(el => el.scrollTo(0, el.scrollHeight));
        const last = rows.last();
        await expect(last).toBeInViewport();

        await expect(page.getByRole('heading', { name: 'Gestion des joueurs' })).toBeInViewport();
        await expect(page.getByTestId('grid-count')).toBeInViewport();
        await expect(page.getByTestId('grid-edit')).toBeInViewport();
        await expect(page.getByTestId('grid-thead')).toBeInViewport();
        await expect(page.getByRole('columnheader', { name: 'Nom', exact: true })).toBeInViewport();

        // Recherche et filtres de l'écran, eux, sont partis avec les lignes.
        await expect(page.getByPlaceholder(/Rechercher/)).not.toBeInViewport();

        // Le thead colle en haut de la zone, sous la barre d'outils.
        const scrollerBox = await scroller.boundingBox();
        const theadBox = await page.getByTestId('grid-thead').boundingBox();
        const toolbarBox = await page.getByTestId('grid-toolbar').boundingBox();
        expect(Math.abs(theadBox.y - scrollerBox.y)).toBeLessThanOrEqual(2);
        expect(theadBox.y).toBeGreaterThanOrEqual(toolbarBox.y + toolbarBox.height - 1);

        // On agit sur une ligne cochée tout en bas sans remonter.
        await last.locator('input[type=checkbox]').check();
        await expect(page.getByTestId('grid-edit')).toBeEnabled();

        // La ligne des filtres de colonnes (#310) colle avec les en-têtes.
        await page.getByTestId('grid-column-filters').click();
        await scroller.evaluate(el => el.scrollTo(0, el.scrollHeight));
        await expect(page.getByTestId('grid-filter-row')).toBeInViewport();

        await page.screenshot({ path: 'test-results/issue-386/01_desktop_bas_de_grille.png' });
    });

    test('desktop : une grille courte ne fait pas apparaître de barre de défilement', async ({ page, request, baseURL }) => {
        await page.setViewportSize({ width: 1440, height: 800 });
        await loginAsAdmin(page, request, baseURL);
        const rows = await openAllPlayers(page);

        await page.getByPlaceholder(/Rechercher/).fill('zz-aucun-joueur-386');
        await expect(rows).toHaveCount(0);

        const scroller = page.getByTestId('grid-scroller');
        expect(await scroller.evaluate(el => el.scrollHeight <= el.clientHeight + 1)).toBe(true);
        expect(await page.evaluate(() => document.documentElement.scrollHeight <= window.innerHeight + 1)).toBe(true);
    });

    test('mobile : seuls les en-têtes de colonnes restent figés', async ({ page, request, baseURL }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await loginAsAdmin(page, request, baseURL);
        const rows = await openAllPlayers(page);

        const root = page.getByTestId('grid-root');
        await root.evaluate(el => el.scrollTo(0, el.scrollHeight));
        await expect(rows.last()).toBeInViewport();

        await expect(page.getByTestId('grid-thead')).toBeInViewport();
        await expect(page.getByRole('heading', { name: 'Gestion des joueurs' })).not.toBeInViewport();

        // Pas de défilement horizontal de la page : la table large défile
        // dans la grille.
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);

        await page.screenshot({ path: 'test-results/issue-386/02_mobile_bas_de_grille.png' });
    });
});
