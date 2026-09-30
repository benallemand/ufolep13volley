// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #376 — refus motivé d'une demande d'inscription.
 *
 * Parcours : une demande « volante » (sans gymnase) est en attente ; l'admin
 * la refuse avec un motif ; le club voit le refus et son motif, corrige sa
 * demande (ajoute un gymnase) : elle repasse en attente.
 */

let setupData;

async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: baseURL }]);
}

async function loginAsClubLeader(page) {
    await page.goto('/pages/home.html#/login');
    await page.fill('input[name="login"]', setupData.login);
    await page.fill('input[name="password"]', setupData.password);
    // Attendre la page de retour de /login.php (redirection vers le referer),
    // sinon elle interrompt le page.goto suivant.
    await Promise.all([
        page.waitForURL(/\/pages\/home\.html(?!#\/login)/),
        page.click('form button[type="submit"]'),
    ]);
    await page.waitForLoadState('networkidle');
}

test.describe.configure({ mode: 'serial' });

test.describe('Issue #376 — refus d\'une demande d\'inscription', () => {
    test.beforeAll(async ({ request }) => {
        const res = await request.get('/e2e/helpers/registrations_setup.php');
        expect(res.status()).toBe(200);
        setupData = await res.json();
        const volante = await request.get('/e2e/helpers/registrations_volante.php');
        expect(volante.status(), await volante.text()).toBe(200);
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/registrations_teardown.php');
    });

    test('l\'admin refuse une demande en attente, avec un motif', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/registrations');
        await page.getByPlaceholder(/Rechercher/).fill('E2E Reg Team Volante');
        const row = page.locator('tbody tr', { hasText: 'E2E Reg Team Volante' });
        await expect(row).toHaveCount(1, { timeout: 15000 });
        await expect(row.locator('.badge', { hasText: 'en attente' })).toBeVisible();

        await row.locator('input[type=checkbox]').check();
        // une demande en attente ne se dévalide pas : le bouton reste inactif
        await expect(page.getByTestId('registration-unvalidate')).toBeDisabled();
        await expect(page.getByTestId('registration-refuse')).toBeEnabled();

        page.once('dialog', (dialog) => dialog.accept('Équipe volante interdite : un gymnase de réception est obligatoire.'));
        await page.getByTestId('registration-refuse').click();

        await expect(row.locator('.badge', { hasText: 'refusée' })).toBeVisible({ timeout: 10000 });
        await expect(row).toContainText('Équipe volante interdite');
        await page.screenshot({ path: 'test-results/issue-376/01_admin_refus.png', fullPage: true });
    });

    test('le club voit le motif, corrige sa demande, qui repasse en attente', async ({ page }) => {
        await loginAsClubLeader(page);
        await page.goto('/pages/my_page.html#/club_registrations');

        const card = page.locator('.card', { hasText: 'E2E Reg Team Volante' });
        await expect(card.locator('.badge', { hasText: 'refusée' })).toBeVisible({ timeout: 15000 });
        await expect(card.getByTestId('refusal-reason')).toContainText('un gymnase de réception est obligatoire');
        await page.screenshot({ path: 'test-results/issue-376/02_club_refus.png', fullPage: true });

        await card.locator('button', { hasText: 'modifier' }).click();
        const principal = page.locator('select', { has: page.locator('option', { hasText: 'Gymnase principal' }) });
        // n'importe quel gymnase proposé : la demande n'est plus « volante »
        await expect(principal.locator('option')).not.toHaveCount(1, { timeout: 10000 });
        await principal.selectOption({ index: 1 });
        await page.locator('select', { has: page.locator('option', { hasText: 'Jour' }) }).first().selectOption('Mardi');
        await page.click('form button[type="submit"]');

        const corrigee = page.locator('.card', { hasText: 'E2E Reg Team Volante' });
        await expect(corrigee.locator('.badge', { hasText: 'en attente' })).toBeVisible({ timeout: 10000 });
        await expect(corrigee.getByTestId('refusal-reason')).toHaveCount(0);
        await page.screenshot({ path: 'test-results/issue-376/03_club_corrigee.png', fullPage: true });
    });
});
