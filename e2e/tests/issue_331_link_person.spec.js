// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #331 — rattacher à la main une personne à un compte.
 *
 * Setup : un compte dont l'email est porté par deux personnes
 * (`users_link_setup.php`) — l'automatisme refuse de choisir, l'admin tranche.
 */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: baseURL }]);
}

test.describe('Issue #331 — personne rattachée à un compte', () => {
    test.beforeEach(async ({ request }) => {
        const body = await (await request.get('/e2e/helpers/users_link_setup.php')).json();
        expect(body.error, `Setup failed: ${body.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/users_link_setup.php?teardown=1');
    });

    test('l\'admin choisit la personne, puis la détache', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/users');
        await page.getByPlaceholder(/Rechercher/).fill('e2e_link_account');
        const row = page.locator('tbody tr', { hasText: 'e2e_link_account' });
        await expect(row).toHaveCount(1, { timeout: 15000 });
        await expect(row).toContainText('—');
        await row.locator('input[type=checkbox]').check();

        await page.getByTestId('link-person').click();
        const picker = page.locator('dialog.modal-open');
        await expect(picker).toContainText('Personne rattachée à e2e_link_account');
        await picker.getByPlaceholder('Rechercher…').fill('E2ELINK');
        // Les deux homonymes d'email sont suggérés (★) : c'est à l'admin de choisir.
        await expect(picker.locator('label', { hasText: '★ E2ELINK' })).toHaveCount(2);
        await picker.locator('label', { hasText: 'E2ELINK Bruno' }).click();
        await picker.getByRole('button', { name: 'Rattacher' }).click();

        await expect(row).toContainText('E2ELINK Bruno', { timeout: 10000 });
        await page.screenshot({ path: 'test-results/issue-331/personne_rattachee.png', fullPage: true });

        // Détacher : première ligne du sélecteur
        await row.locator('input[type=checkbox]').check();
        await page.getByTestId('link-person').click();
        await picker.locator('label', { hasText: 'aucune personne (détacher)' }).click();
        await picker.getByRole('button', { name: 'Rattacher' }).click();
        await expect(row).not.toContainText('E2ELINK Bruno', { timeout: 10000 });
    });
});
