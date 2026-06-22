// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Toasts d'actions en attente pour le responsable d'équipe (issue #240)
 *
 * Setup : ouvre une session responsable + crée un match déjà joué (date passée)
 * sans données saisies => une action est en attente ("Remplir les joueurs présents").
 * On vérifie qu'un toast apparaît à la connexion (espace responsable ET home
 * publique), et qu'un clic mène à la page de l'action.
 */
test.describe("Issue #240 — Toasts actions en attente (responsable)", () => {
    let setup = null;

    test.beforeEach(async ({ page }) => {
        // Le goto() permet à PHP de poser le cookie PHPSESSID via Set-Cookie
        await page.goto('/e2e/helpers/match_actions_setup.php');
        const body = JSON.parse(await page.locator('body').innerText());
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        setup = body;
    });

    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/match_actions_teardown.php');
        setup = null;
    });

    test("un toast d'action en attente apparaît sur l'espace responsable", async ({ page }) => {
        await page.goto('/pages/my_page.html');
        // Le toast contient l'équipe adverse + le libellé de l'action en attente
        const toast = page.locator('.toastify').filter({ hasText: setup.equipe_adverse });
        await expect(toast.first()).toBeVisible({ timeout: 10000 });
        await expect(toast.first()).toContainText(setup.expected_label);
        await page.screenshot({ path: 'test-results/issue-240/toast_my_page.png', fullPage: true });
    });

    test("le toast apparaît aussi sur la page d'accueil publique", async ({ page }) => {
        await page.goto('/pages/home.html');
        const toast = page.locator('.toastify').filter({ hasText: setup.equipe_adverse });
        await expect(toast.first()).toBeVisible({ timeout: 10000 });
        await page.screenshot({ path: 'test-results/issue-240/toast_home.png', fullPage: true });
    });

    test("cliquer le toast mène à la page de l'action", async ({ page }) => {
        await page.goto('/pages/my_page.html');
        const toast = page.locator('.toastify').filter({ hasText: setup.equipe_adverse }).first();
        await expect(toast).toBeVisible({ timeout: 10000 });
        await toast.click();
        // Le clic ouvre la page de l'action (team_sheets / match / survey) du bon match
        await expect(page).toHaveURL(new RegExp('(team_sheets|match|survey)\\.html\\?id_match=' + setup.id_match), { timeout: 10000 });
        await page.screenshot({ path: 'test-results/issue-240/toast_click_navigates.png', fullPage: true });
    });
});
