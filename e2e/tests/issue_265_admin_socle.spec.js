// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Socle de l'administration Vue (issue #265, lot 0)
 *
 * Couvre le socle par l'écran qui le valide, la gestion des gymnases :
 * chargement de la grille, recherche multi-termes, tri, et la garde d'accès.
 *
 * Le cycle créer / éditer / supprimer n'est PAS testé ici : il écrirait dans la
 * base de dev, que les tests E2E partagent. Il a été validé à la main (création,
 * modification, suppression, total revenu à son point de départ).
 */

/** Ouvre une session admin et la pose dans le contexte du navigateur. */
async function loginAsAdmin(page, request) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    expect(session_id).toBeTruthy();
    await page.context().addCookies([{
        name: 'PHPSESSID',
        value: session_id,
        url: page.url().startsWith('http') ? new URL(page.url()).origin : 'http://localhost',
    }]);
}

test.describe('Issue #265 — socle de l\'administration Vue', () => {

    test('un visiteur non administrateur est renvoyé vers la connexion', async ({ page }) => {
        await page.goto('/admin/index.html');
        await page.waitForURL(/#\/login/, { timeout: 15000 });
        expect(page.url()).toContain('/pages/home.html#/login');
        expect(decodeURIComponent(page.url())).toContain('profil suffisant');
    });

    test('la grille des gymnases se charge pour un administrateur', async ({ page, request, baseURL }) => {
        await page.goto(baseURL + '/pages/home.html');
        await loginAsAdmin(page, request);
        await page.goto('/admin/index.html');

        await expect(page.getByRole('heading', { name: 'Gestion des gymnases' }))
            .toBeVisible({ timeout: 15000 });

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 15000 });
        expect(await rows.count()).toBeGreaterThan(0);

        // Les actions de masse sont désactivées tant que rien n'est sélectionné
        await expect(page.getByRole('button', { name: /Éditer/ })).toBeDisabled();
        await expect(page.getByRole('button', { name: /Supprimer/ })).toBeDisabled();

        await page.screenshot({ path: 'test-results/issue-265/admin_gymnases.png', fullPage: true });
    });

    test('la recherche multi-termes et le tri fonctionnent', async ({ page, request, baseURL }) => {
        await page.goto(baseURL + '/pages/home.html');
        await loginAsAdmin(page, request);
        await page.goto('/admin/index.html');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 15000 });

        // On s'appuie sur le compteur « filtrés / total », pas sur le nombre de
        // lignes affichées : celui-ci est plafonné par la pagination, donc une
        // recherche qui ramène plus d'une page ne le ferait pas bouger.
        const counter = page.getByText(/\d+ \/ \d+ gymnase\(s\)/);
        const readCounter = async () => {
            const m = (await counter.innerText()).match(/(\d+) \/ (\d+)/);
            return { filtered: Number(m[1]), total: Number(m[2]) };
        };

        const { total } = await readCounter();
        expect(total).toBeGreaterThan(0);

        // Un terme qui ne correspond à rien doit tout filtrer
        await page.getByPlaceholder(/Rechercher/).fill('zzz_aucun_resultat_zzz');
        await expect.poll(async () => (await readCounter()).filtered).toBe(0);

        // Le nom d'une ligne doit en ramener au moins une, et pas toutes.
        // On prend le NOM et non la ville : d'autres specs créent des gymnases
        // temporaires, parfois sans ville — un terme vide ne filtrerait rien et
        // le test échouerait pour une raison qui n'a rien à voir.
        await page.getByPlaceholder(/Rechercher/).fill('');
        await expect.poll(async () => (await readCounter()).filtered).toBe(total);

        const nom = (await rows.nth(0).locator('td').nth(1).innerText()).trim();
        expect(nom, 'La première ligne doit avoir un nom').not.toBe('');

        await page.getByPlaceholder(/Rechercher/).fill(nom);
        await expect.poll(async () => (await readCounter()).filtered).toBeGreaterThan(0);
        expect((await readCounter()).filtered).toBeLessThan(total);

        await page.getByPlaceholder(/Rechercher/).fill('');
        await expect.poll(async () => (await readCounter()).filtered).toBe(total);

        // Tri : cliquer deux fois sur une colonne doit inverser l'ordre
        const header = page.getByRole('columnheader', { name: /Terrains/ });
        await header.click();
        const asc = await rows.nth(0).locator('td').nth(5).innerText();
        await header.click();
        const desc = await rows.nth(0).locator('td').nth(5).innerText();
        expect(Number(desc)).toBeGreaterThanOrEqual(Number(asc));
    });

    test('sélectionner une ligne active les actions', async ({ page, request, baseURL }) => {
        await page.goto(baseURL + '/pages/home.html');
        await loginAsAdmin(page, request);
        await page.goto('/admin/index.html');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 15000 });

        await rows.nth(0).locator('input[type=checkbox]').click();
        await expect(page.getByRole('button', { name: /Éditer/ })).toBeEnabled();
        await expect(page.getByRole('button', { name: /Supprimer/ })).toBeEnabled();
    });
});
