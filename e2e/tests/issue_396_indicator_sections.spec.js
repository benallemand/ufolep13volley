// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Tableau de bord des indicateurs rangé par sections (issue #396)
 *
 * Les indicateurs tournent sur la base réelle : le contenu des tuiles n'est
 * pas connu d'avance. On vérifie la forme : l'API déclare les sections et en
 * range chaque indicateur, l'écran affiche les sections non vides dans
 * l'ordre déclaré, et chaque tuile se trouve dans celle de son indicateur.
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

test.describe('Issue #396 — indicateurs par sections', () => {

    test("l'API range chaque indicateur dans une section déclarée", async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        const liste = await (await request.get('/ajax/indicators.php?mode=list')).json();
        const cles = liste.categories.map((c) => c.key);
        expect(cles).toEqual(['inscriptions', 'calendrier', 'equipes', 'joueurs', 'saison', 'statistiques']);
        expect(liste.results).toHaveLength(44);
        for (const ind of liste.results) {
            expect(cles, ind.fieldLabel).toContain(ind.category);
        }
        // Retirés par #396.
        const libelles = liste.results.map((i) => i.fieldLabel);
        for (const retire of ['Comptes', 'Evènements', "Proposition d'organisation", 'Nouvelles équipes',
            'Nombre de matches par date et par gymnase']) {
            expect(libelles).not.toContain(retire);
        }
    });

    test("l'écran affiche les sections dans l'ordre, chaque tuile dans la sienne", async ({ page, request, baseURL }) => {
        test.setTimeout(120000);
        await loginAsAdmin(page, request, baseURL);
        const liste = await (await request.get('/ajax/indicators.php?mode=list')).json();
        const sectionDe = Object.fromEntries(liste.results.map((i) => [i.fieldLabel, i.category]));
        const ordre = liste.categories.map((c) => c.key);

        await page.goto('/admin/index.html#/indicators');
        // Une première section, puis plus aucune tuile grise : le calcul est
        // terminé. Attendre seulement l'absence de tuiles grises passerait
        // avant même que la liste soit chargée.
        const sections = page.locator('section[data-testid^="indicators-section-"]');
        await expect(sections.first()).toBeVisible({ timeout: 90000 });
        await expect(page.locator('.animate-pulse')).toHaveCount(0, { timeout: 90000 });

        const affichees = (await sections.evaluateAll((els) => els.map((e) => e.dataset.testid)))
            .map((t) => t.replace('indicators-section-', ''));
        // Les sections affichées suivent l'ordre déclaré, sans doublon.
        expect(affichees).toEqual(ordre.filter((k) => affichees.includes(k)));

        for (const cle of affichees) {
            const section = page.getByTestId('indicators-section-' + cle);
            const libelles = await section.locator('button.card .text-xs').allTextContents();
            expect(libelles.length).toBeGreaterThan(0);
            for (const libelle of libelles) {
                expect(sectionDe[libelle.trim()], libelle).toBe(cle);
            }
        }
        await page.screenshot({ path: 'test-results/issue-396/sections.png', fullPage: true });
    });
});
