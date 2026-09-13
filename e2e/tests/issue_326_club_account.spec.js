// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Le référent d'un club, c'est son compte (issue #326)
 *
 * Le backend est couvert par `unit_tests/ClubAccountTest.php`. Ce qui reste ici
 * est le chemin que suit l'administrateur pendant le rattrapage : une tuile du
 * tableau de bord qui ouvre l'écran des clubs filtré sur les seuls clubs sans
 * compte (le contrat de #312), puis l'action qui propose les adresses connues.
 *
 * Aucun test ne crée de compte : la création envoie des identifiants par email
 * et modifie la base. On s'arrête au sélecteur, qui est le point où le chemin
 * pouvait encore être cassé.
 */

/** Voir `issue_308_detail_drawer.spec.js` : cookie posé avant toute navigation. */
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

test.describe('Issue #326 — compte de club', () => {

    test("la tuile « Clubs engagés sans compte de club » ouvre l'écran filtré", async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);

        // `ajax/indicators.php` fonctionne en deux temps : `mode=list` rend les
        // libellés, `mode=detail&id=N` la requête d'un seul indicateur.
        const liste = await request.get('/ajax/indicators.php?mode=list');
        expect(liste.ok(), 'les indicateurs doivent répondre').toBeTruthy();
        const declare = ((await liste.json()).results || [])
            .find((i) => i.fieldLabel === 'Clubs engagés sans compte de club');
        expect(declare, "l'indicateur doit être déclaré").toBeTruthy();

        const detail = await request.get(`/ajax/indicators.php?mode=detail&id=${declare.id}`);
        expect(detail.ok()).toBeTruthy();
        const tuile = await detail.json();

        test.skip(
            !tuile.details || tuile.details.length === 0,
            'tous les clubs engagés ont un compte : rien à corriger'
        );
        // Le contrat de #312 : un écran cible et des identifiants.
        expect(tuile.target).toBe('clubs');
        expect(tuile.ids.length).toBeGreaterThan(0);

        await page.goto('/admin/index.html#/indicators');
        await page.getByRole('button', { name: /Clubs engagés sans compte de club/ })
            .click({ timeout: 60000 });

        const modal = page.locator('dialog.modal-open');
        await expect(modal).toBeVisible({ timeout: 15000 });
        await modal.getByRole('button', { name: /Corriger ces \d+ ligne/ }).click();

        await expect(page).toHaveURL(/#\/clubs\?ids=/, { timeout: 15000 });
        // La grille ne montre plus que ces clubs-là.
        await expect(page.locator('tbody tr')).toHaveCount(tuile.ids.length, { timeout: 60000 });
    });

    test("l'action propose les adresses connues du club", async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/clubs');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 60000 });
        // La colonne ajoutée par #326 : le compte du club, avant les
        // coordonnées libres.
        await expect(page.getByRole('columnheader', { name: 'Compte(s)' })).toBeVisible();

        await rows.first().locator('input[type="checkbox"]').check();
        await page.getByRole('button', { name: /Créer le compte du club/ }).click();

        const modal = page.locator('dialog.modal-open');
        await expect(modal).toBeVisible({ timeout: 15000 });
        await expect(modal.getByRole('radio').first()).toBeVisible();

        // On referme sans rien créer : ce test n'envoie aucun email.
        await modal.getByRole('button', { name: 'Annuler' }).click();
        await expect(modal).toHaveCount(0);
    });
});
