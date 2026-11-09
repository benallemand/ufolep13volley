// @ts-check
const { test, expect } = require('@playwright/test');
const { iphoneFacePhoto, inspect } = require('./photo_fixtures');

/**
 * Issue #461 — la photo d'un joueur passe par un recadrage 3:4 avant l'envoi,
 * et une photo d'iPhone (couchée, orientation EXIF 6) arrive à l'endroit.
 *
 * La photo de test (`photo_fixtures.js`) montre, à l'endroit, une bande rouge
 * en haut, un visage, une bande bleue en bas. Elle est enregistrée couchée avec
 * l'étiquette « orientation = 6 ». Si l'étiquette était ignorée, le rouge
 * serait à gauche, et la détection de visage (#471) refuserait la photo.
 */

function expectRedTopBlueBottom(image) {
    expect(image.top[0], `haut rouge : ${image.top}`).toBeGreaterThan(150);
    expect(image.top[2], `haut rouge : ${image.top}`).toBeLessThan(100);
    expect(image.bottom[2], `bas bleu : ${image.bottom}`).toBeGreaterThan(150);
    expect(image.bottom[0], `bas bleu : ${image.bottom}`).toBeLessThan(100);
}

test.describe('Issue #461 — recadrage de la photo d\'un joueur', () => {
    let player;

    test.beforeEach(async ({ page }) => {
        await page.goto('/e2e/helpers/player_photo_setup.php');
        player = JSON.parse(await page.locator('body').innerText());
        if (player.error) throw new Error(`Setup failed: ${player.error}`);
    });

    test.afterEach(async ({ page }) => {
        await page.request.get(`/e2e/helpers/player_photo_setup.php?teardown=1&id=${player.id}&id_photo=${player.id_photo ?? ''}`);
    });

    test("une photo d'iPhone est recadrée en 3:4 et arrive à l'endroit", async ({ page }) => {
        const errors = [];
        page.on('pageerror', (e) => errors.push(e.message));
        page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
        page.on('dialog', (d) => { errors.push(`dialog: ${d.message()}`); d.dismiss(); });
        await page.goto('/pages/my_page.html#/players');
        const card = page.locator('li.card', { hasText: `${player.prenom} ${player.nom}` }).first();
        await expect(card).toBeVisible({ timeout: 15000 });

        await card.getByTestId('player-photo-input').setInputFiles({
            name: 'iphone.jpg', mimeType: 'image/jpeg', buffer: await iphoneFacePhoto(page),
        });
        const modal = page.getByTestId('photo-crop-modal');
        await expect(modal).toBeVisible();
        await expect(modal).toContainText(`Photo de ${player.prenom} ${player.nom}`);
        const validate = modal.getByRole('button', { name: /Valider la photo/ });
        await expect(validate).toBeEnabled();
        await page.screenshot({ path: 'test-results/issue-461/recadrage.png' });

        const [upload] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/player/uploadPhoto'), { timeout: 30000 })
                .catch((e) => { throw new Error(`pas d'envoi (${errors.join(' | ') || 'aucune erreur'}) : ${e.message}`); }),
            validate.click(),
        ]);
        expect(upload.status(), await upload.text()).toBe(200);
        await expect(modal).toBeHidden();

        const players = await (await page.request.get('/rest/action.php/player/getMyPlayers')).json();
        const saved = players.find((p) => Number(p.id) === player.id);
        expect(saved.path_photo).toMatch(/^players_pics\//);

        const photo = await inspect(page, saved.path_photo);
        expect([photo.width, photo.height]).toEqual([600, 800]);
        expectRedTopBlueBottom(photo);

        const low = await inspect(page, saved.path_photo_low);
        expect([low.width, low.height]).toEqual([300, 400]);
        expectRedTopBlueBottom(low);
    });

    test('annuler le recadrage n\'envoie rien', async ({ page }) => {
        let uploads = 0;
        page.on('request', (r) => { if (r.url().includes('/player/uploadPhoto')) uploads++; });
        await page.goto('/pages/my_page.html#/players');
        const card = page.locator('li.card', { hasText: `${player.prenom} ${player.nom}` }).first();
        await expect(card).toBeVisible({ timeout: 15000 });
        await card.getByTestId('player-photo-input').setInputFiles({
            name: 'iphone.jpg', mimeType: 'image/jpeg', buffer: await iphoneFacePhoto(page),
        });
        const modal = page.getByTestId('photo-crop-modal');
        await expect(modal).toBeVisible();
        await modal.getByRole('button', { name: 'Annuler' }).click();
        await expect(modal).toBeHidden();
        expect(uploads).toBe(0);
    });
});
