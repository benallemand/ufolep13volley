const { test, expect } = require('@playwright/test');

// Codes de compétition à tester
const COMPETITIONS = ['kf', 'cf'];

// Setup global : crée les matchs de test 1/8 avec date et gymnase
test.beforeAll(async ({ request }) => {
    const res = await request.get('/e2e/helpers/finals_setup.php');
    expect(res.status(), 'finals_setup.php doit répondre 200').toBe(200);
    const body = await res.json();
    expect(body.error, `Erreur setup finals : ${body.error}`).toBeUndefined();
});

// Teardown global : supprime les matchs de test
test.afterAll(async ({ request }) => {
    await request.get('/e2e/helpers/finals_teardown.php');
});

for (const code of COMPETITIONS) {
    test.describe(`Phases finales — ${code.toUpperCase()}`, () => {

        test.beforeEach(async ({ page }) => {
            await page.goto(`/pages/home.html#/finals/${code}`);
            // Attendre que le spinner disparaisse
            await expect(page.locator('.loading.loading-spinner')).toHaveCount(0, { timeout: 10000 });
        });

        test('affiche l\'arbre du tournoi complet (1/8 → Finale)', async ({ page }) => {
            // Le container brackets-viewer doit être présent et non vide
            const bracket = page.locator('.brackets-viewer');
            await expect(bracket).toBeVisible({ timeout: 15000 });
            await expect(bracket).not.toBeEmpty();

            // Les 4 rounds doivent être représentés
            const roundLabels = bracket.locator('.round-label, .round-name, h3, h4');
            const labelsText = await roundLabels.allTextContents();
            const joined = labelsText.join(' ').toLowerCase();
            expect(joined).toContain('1/8');
            expect(joined).toContain('1/4');
            expect(joined).toContain('1/2');
            expect(joined).toContain('finale');

            await page.screenshot({ path: `test-results/issue-215/proof-${code}-arbre-complet.png` });
        });

        test('les matchs de 1/8 affichent des équipes réelles (pas des labels de tirage)', async ({ page }) => {
            const bracket = page.locator('.brackets-viewer');
            await expect(bracket).toBeVisible({ timeout: 15000 });

            // Récupérer tous les noms de participants
            const names = await bracket.locator('.participant .name').allTextContents();
            expect(names.length).toBeGreaterThan(0);

            // Aucun nom de 1/8 ne doit ressembler à un placeholder de type "Vainqueur 1/8 #X"
            // (les placeholders 1/4, 1/2, finale sont acceptés pour les rounds futurs)
            const huitiemesNames = names.slice(0, 16); // 2 équipes × 8 matchs
            for (const name of huitiemesNames) {
                expect(name.trim()).not.toMatch(/^Vainqueur/i);
            }

            await page.screenshot({ path: `test-results/issue-215/proof-${code}-equipes-reelles-huitiemes.png` });
        });

        test('les matchs de 1/8 affichent la date directement dans l\'arbre', async ({ page }) => {
            const bracket = page.locator('.brackets-viewer');
            await expect(bracket).toBeVisible({ timeout: 15000 });

            // Au moins un élément .match-date-display doit être visible dans le bracket
            const dateBadges = bracket.locator('.match-date-display');
            await expect(dateBadges.first()).toBeVisible({ timeout: 5000 });

            // Les dates affichées doivent être au format dd/mm/yyyy
            const firstDate = await dateBadges.first().textContent();
            expect(firstDate).toMatch(/^\d{2}\/\d{2}\/\d{4}$/);

            await page.screenshot({ path: `test-results/issue-215/proof-${code}-dates-dans-arbre.png` });
        });

        test('un clic sur un match de 1/8 ouvre la modal avec date et gymnase', async ({ page }) => {
            const bracket = page.locator('.brackets-viewer');
            await expect(bracket).toBeVisible({ timeout: 15000 });

            // Cibler un match du premier round (1/8) — le premier round contient
            // les vrais matchs insérés par le setup avec date_reception
            const firstRound = bracket.locator('.round, [class*="round"]').first();
            const firstMatch = firstRound.locator('.match, [class*="match"]').first();
            await firstMatch.click();

            // La modal doit s'ouvrir
            const modal = page.locator('.modal.modal-open');
            await expect(modal).toBeVisible({ timeout: 5000 });

            // La date doit être présente
            await expect(modal.locator('strong:has-text("Date")')).toBeVisible();

            // Le gymnase doit être présent
            await expect(modal.locator('strong:has-text("Lieu")')).toBeVisible();

            await page.screenshot({ path: `test-results/issue-215/proof-${code}-modal-details-match.png` });

            // Fermer
            await modal.locator('button.btn').click();
            await expect(modal).not.toBeVisible();
        });

        test('la vue liste des matchs est également accessible', async ({ page }) => {
            // Cliquer sur l'onglet "Liste des matchs"
            await page.locator('a.tab', { hasText: 'Liste des matchs' }).click();
            // Le composant Matchs.js rend des filtres + des match-cards
            // On attend que les checkboxes de filtre soient visibles
            await expect(page.locator('input[type="checkbox"]').first()).toBeVisible({ timeout: 10000 });

            await page.screenshot({ path: `test-results/issue-215/proof-${code}-liste-matchs.png` });
        });
    });
}
