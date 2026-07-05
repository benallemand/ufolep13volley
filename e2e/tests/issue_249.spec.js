const { test, expect } = require('@playwright/test');

/**
 * Issue #249 — Inscriptions des équipes par le responsable de club.
 *
 * Parcours : le responsable de club se connecte, dépose une demande
 * d'inscription (statut « en attente »), la modifie, puis — une fois la
 * demande validée par l'admin (helper) — vérifie qu'elle est verrouillée.
 */

let setupData;

async function loginAsClubLeader(page) {
    await page.goto('/pages/home.html#/login');
    await page.fill('input[name="login"]', setupData.login);
    await page.fill('input[name="password"]', setupData.password);
    await Promise.all([
        page.waitForLoadState('networkidle'),
        page.click('form button[type="submit"]'),
    ]);
}

test.describe('Issue #249 — Inscriptions par le responsable de club', () => {
    test.beforeAll(async ({ request }) => {
        const res = await request.get('/e2e/helpers/registrations_setup.php');
        expect(res.status()).toBe(200);
        setupData = await res.json();
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/registrations_teardown.php');
    });

    test('le responsable de club dépose puis modifie une demande', async ({ page }) => {
        await loginAsClubLeader(page);

        await page.goto('/pages/my_page.html#/club_registrations');
        await expect(page.locator('text=Inscriptions aux compétitions')).toBeVisible();
        // la compétition de test (fenêtre ouverte) est proposée
        await expect(page.locator('#id_competition option', { hasText: 'E2E Inscriptions' })).toHaveCount(1);
        await page.screenshot({
            path: 'test-results/issue-249/01_page_inscriptions.png',
            fullPage: true
        });

        // dépôt d'une demande
        await page.selectOption('#id_competition', String(setupData.id_competition));
        await page.fill('#new_team_name', 'E2E Reg Team');
        await page.fill('input[placeholder="Nom"]', 'Dupont');
        await page.fill('input[placeholder="Prénom"]', 'Jean');
        await page.fill('input[placeholder="Email"]', 'e2e_reg_leader@ufolep.test');
        await page.fill('input[placeholder="Téléphone"]', '0600000000');
        await page.click('form button[type="submit"]');

        const card = page.locator('.card', { hasText: 'E2E Reg Team' });
        await expect(card).toBeVisible();
        await expect(card.locator('.badge', { hasText: 'en attente' })).toBeVisible();
        await page.screenshot({
            path: 'test-results/issue-249/02_demande_deposee.png',
            fullPage: true
        });

        // modification tant que la demande n'est pas validée
        await card.locator('button', { hasText: 'modifier' }).click();
        await page.fill('#remarks', 'gymnase indisponible pendant les vacances');
        await page.click('form button[type="submit"]');
        await expect(page.locator('.card', { hasText: 'E2E Reg Team' })).toBeVisible();
        await page.screenshot({
            path: 'test-results/issue-249/03_demande_modifiee.png',
            fullPage: true
        });
    });

    test('une demande validée est verrouillée pour le club', async ({ page, request }) => {
        const res = await request.get('/e2e/helpers/registrations_validate.php');
        expect(res.status()).toBe(200);

        await loginAsClubLeader(page);
        await page.goto('/pages/my_page.html#/club_registrations');

        const card = page.locator('.card', { hasText: 'E2E Reg Team' });
        await expect(card).toBeVisible();
        await expect(card.locator('.badge', { hasText: 'validée' })).toBeVisible();
        await expect(card.locator('button', { hasText: 'modifier' })).toHaveCount(0);
        await expect(card.locator('button', { hasText: 'supprimer' })).toHaveCount(0);
        await page.screenshot({
            path: 'test-results/issue-249/04_validee_verrouillee.png',
            fullPage: true
        });
    });
});
