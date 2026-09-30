// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #379 — liste publique des inscriptions en page d'accueil.
 *
 * Setup : la compétition de test « E2E Inscriptions » (fenêtre ouverte,
 * démarrage à venir) et une demande en attente (`registrations_volante.php`).
 * Aucune connexion : la liste est publique, et ne doit rien dire des personnes.
 */
test.describe('Issue #379 — inscriptions en cours, en page d\'accueil', () => {
    test.beforeAll(async ({ request }) => {
        expect((await request.get('/e2e/helpers/registrations_setup.php')).status()).toBe(200);
        const volante = await request.get('/e2e/helpers/registrations_volante.php');
        expect(volante.status(), await volante.text()).toBe(200);
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/registrations_teardown.php');
    });

    test('un visiteur voit les équipes inscrites, leur statut et leur type', async ({ page }) => {
        await page.goto('/pages/home.html#/home');
        const bloc = page.getByTestId('public-registrations');
        await expect(bloc).toBeVisible({ timeout: 15000 });

        const competition = bloc.locator('.collapse', { hasText: 'E2E Inscriptions' });
        await expect(competition).toContainText('1 en attente');
        // daisyUI : c'est la case superposée au titre qui ouvre le bloc
        await competition.locator('input[type=checkbox]').check();

        const equipe = competition.getByTestId('public-registration').filter({ hasText: 'E2E Reg Team Volante' });
        await expect(equipe).toBeVisible();
        await expect(equipe).toContainText('E2E Reg Club');
        await expect(equipe).toContainText('nouvelle équipe');
        await expect(equipe.getByTestId('public-registration-status')).toHaveText('en attente');
        // Le responsable de la demande (Paul Volant) n'apparaît nulle part.
        await expect(bloc).not.toContainText('Paul Volant');
        await expect(bloc).not.toContainText('e2e_reg_volante');
        await page.screenshot({ path: 'test-results/issue-379/inscriptions_en_cours.png', fullPage: true });
    });

    test('l\'API publique ne renvoie que club, équipe, statut, type et ancien nom', async ({ playwright }) => {
        const anonyme = await playwright.request.newContext();
        try {
            const res = await anonyme.get('/rest/action.php/register/getPublicRegistrations');
            expect(res.status()).toBe(200);
            const body = await res.text();
            for (const secret of ['e2e_reg_volante', '0600000001', 'leader', 'remarks', 'refusal']) {
                expect(body, secret).not.toContain(secret);
            }
            const competitions = JSON.parse(body);
            const test_competition = competitions.find((c) => c.libelle === 'E2E Inscriptions');
            expect(test_competition).toBeTruthy();
            for (const team of test_competition.teams) {
                expect(Object.keys(team).sort()).toEqual(['ancien_nom', 'club', 'equipe', 'status', 'type']);
            }
        } finally {
            await anonyme.dispose();
        }
    });
});
