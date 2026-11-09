// @ts-check
const { test, expect } = require('@playwright/test');
const { iphoneFacePhoto, noFacePhoto } = require('./photo_fixtures');

/**
 * Issue #471 — la photo d'un joueur n'est acceptée que si son visage est
 * identifiable : deux yeux et une bouche détectés dans le cadre (MediaPipe,
 * dans le navigateur). Sinon un message dit quoi corriger ; seul un admin peut
 * forcer l'envoi.
 */

async function openCrop(page, player, buffer) {
    await page.goto('/pages/my_page.html#/players');
    const card = page.locator('li.card', { hasText: `${player.prenom} ${player.nom}` }).first();
    await expect(card).toBeVisible({ timeout: 15000 });
    await card.getByTestId('player-photo-input').setInputFiles({ name: 'photo.jpg', mimeType: 'image/jpeg', buffer });
    const modal = page.getByTestId('photo-crop-modal');
    await expect(modal).toBeVisible();
    const validate = modal.getByRole('button', { name: /Valider la photo/ });
    await expect(validate).toBeEnabled();
    return { modal, validate };
}

for (const admin of [false, true]) {
    test.describe(`Issue #471 — visage identifiable (${admin ? 'admin' : 'responsable'})`, () => {
        let player;
        let uploads;

        test.beforeEach(async ({ page }) => {
            await page.goto(`/e2e/helpers/player_photo_setup.php?admin=${admin ? 1 : 0}`);
            player = JSON.parse(await page.locator('body').innerText());
            if (player.error) throw new Error(`Setup failed: ${player.error}`);
            uploads = 0;
            page.on('request', (r) => { if (r.url().includes('/player/uploadPhoto')) uploads++; });
        });

        test.afterEach(async ({ page }) => {
            await page.request.get(`/e2e/helpers/player_photo_setup.php?teardown=1&id=${player.id}&id_photo=${player.id_photo ?? ''}`);
        });

        test('une photo sans visage est refusée', async ({ page }) => {
            const { modal, validate } = await openCrop(page, player, await noFacePhoto(page));
            await validate.click();
            const refusal = modal.getByTestId('photo-face-refusal');
            await expect(refusal).toBeVisible({ timeout: 30000 });
            await expect(refusal).toContainText('Aucun visage');
            await page.screenshot({ path: `test-results/issue-471/refus-${admin ? 'admin' : 'responsable'}.png` });
            expect(uploads).toBe(0);

            const force = modal.getByTestId('photo-force');
            if (!admin) {
                await expect(force).toHaveCount(0);
                return;
            }
            // l'admin peut passer outre un faux refus
            const [upload] = await Promise.all([
                page.waitForResponse((r) => r.url().includes('/player/uploadPhoto'), { timeout: 15000 }),
                force.click(),
            ]);
            expect(upload.status(), await upload.text()).toBe(200);
            await expect(modal).toBeHidden();
        });

        test('une photo avec un visage identifiable est acceptée', async ({ page }) => {
            const { modal, validate } = await openCrop(page, player, await iphoneFacePhoto(page));
            const [upload] = await Promise.all([
                page.waitForResponse((r) => r.url().includes('/player/uploadPhoto'), { timeout: 30000 }),
                validate.click(),
            ]);
            expect(upload.status(), await upload.text()).toBe(200);
            await expect(modal).toBeHidden();
        });
    });
}
