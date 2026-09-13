// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Tiroir de détail des grilles d'administration (issue #308)
 *
 * Le tiroir appartient au socle générique `AdminGrid`, donc il est automatisé,
 * comme la recherche et le tri — contrairement aux écrans CRUD eux-mêmes, qui
 * relèvent de `admin/RECETTE.md`.
 *
 * Il faut malgré tout passer par l'écran des joueurs : c'est le seul à déclarer
 * un tiroir pour l'instant, et l'écran des gymnases utilisé par le socle n'en a
 * pas. `player/getPlayers` renvoie plusieurs Mo, d'où les délais généreux.
 *
 * Ce qui est vérifié ici est ce que la grille garantit, pas ce que l'écran
 * affiche : le clic ouvre, les chevrons naviguent, la croix ferme, et — le
 * point qui a motivé le changement de comportement du clic — consulter une
 * ligne ne la sélectionne pas.
 */

/**
 * Ouvre une session admin et la pose dans le contexte du navigateur.
 *
 * Le cookie est pose AVANT toute navigation, sur `baseURL`. La variante des
 * autres specs charge d'abord la page d'accueil pour en deduire l'origine :
 * celle-ci verifie sa session au chargement, et sa reponse peut reecrire
 * PHPSESSID juste apres le `addCookies`. Le test partait alors sur l'ecran de
 * connexion une fois sur plusieurs.
 */
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

test.describe('Issue #308 — tiroir de détail', () => {

    test('le clic sur une ligne ouvre le tiroir, sans sélectionner la ligne', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/players');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 60000 });

        const drawer = page.getByRole('dialog');
        await expect(drawer).toHaveCount(0);

        await rows.nth(0).click();
        await expect(drawer).toBeVisible({ timeout: 15000 });

        // Les champs que la grille ne montre plus depuis l'allègement des
        // colonnes : c'est la raison d'être du tiroir.
        await expect(drawer.getByText('N° de licence')).toBeVisible();
        await expect(drawer.getByText('Téléphone', { exact: true })).toBeVisible();

        // Consulter n'est pas sélectionner : sinon la barre d'outils agirait
        // sur des lignes qu'on n'a fait que regarder.
        await expect(page.getByRole('button', { name: /Supprimer/ })).toBeDisabled();
        expect(await rows.nth(0).locator('input[type=checkbox]').isChecked()).toBe(false);
    });

    test('les chevrons passent à la ligne suivante, la croix referme', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/players');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 60000 });

        await rows.nth(0).click();
        const drawer = page.getByRole('dialog');
        await expect(drawer).toBeVisible({ timeout: 15000 });

        const first = await drawer.getAttribute('aria-label');
        expect(first, 'Le tiroir doit porter le nom de la ligne ouverte').toBeTruthy();

        await drawer.getByTitle('Ligne suivante').click();
        await expect.poll(async () => drawer.getAttribute('aria-label')).not.toBe(first);

        await drawer.getByTitle('Ligne précédente').click();
        await expect.poll(async () => drawer.getAttribute('aria-label')).toBe(first);

        await drawer.getByTitle('Fermer').click();
        await expect(drawer).toHaveCount(0);
    });

    test('sur une longue page, les boutons du tiroir restent à l\'écran', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/players');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 60000 });

        // 100 lignes suffisent à rendre le tableau bien plus haut que la
        // fenêtre, ce qui est le cas signalé : le tiroir s'étirait jusqu'en bas
        // du tableau et son pied partait hors de portée.
        await page.locator('label:has-text("par page") select').selectOption('100');
        await expect.poll(async () => rows.count()).toBe(100);

        await rows.nth(0).click();
        const drawer = page.getByRole('dialog');
        await expect(drawer).toBeVisible({ timeout: 15000 });

        const edit = drawer.getByRole('button', { name: /Éditer/ });
        await expect(edit).toBeInViewport();

        // Et il y reste une fois qu'on a fait défiler la page.
        await page.mouse.wheel(0, 3000);
        await expect(edit).toBeInViewport();
    });

    test('la case à cocher sélectionne sans ouvrir le tiroir', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/players');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 60000 });

        await rows.nth(0).locator('input[type=checkbox]').click();
        await expect(page.getByRole('button', { name: /Supprimer/ })).toBeEnabled();
        await expect(page.getByRole('dialog')).toHaveCount(0);
    });
});
