// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #461 — la photo d'un joueur passe par un recadrage 3:4 avant l'envoi,
 * et une photo d'iPhone (couchée, orientation EXIF 6) arrive à l'endroit.
 *
 * La photo de test est fabriquée ici : 400 × 300, moitié haute rouge, moitié
 * basse bleue, avec l'étiquette « orientation = 6 » (à tourner de 90° dans le
 * sens horaire). À l'endroit, le rouge est à droite et le bleu à gauche. Si
 * l'étiquette était ignorée, le rouge resterait en haut.
 */

/** Ajoute à un JPEG un bloc EXIF qui ne contient que l'orientation. */
function withOrientation(jpeg, orientation) {
    const tiff = Buffer.from([
        0x4d, 0x4d, 0x00, 0x2a, 0x00, 0x00, 0x00, 0x08, // « MM », 42, premier répertoire en 8
        0x00, 0x01,                                     // une entrée
        0x01, 0x12, 0x00, 0x03, 0x00, 0x00, 0x00, 0x01, // orientation, SHORT, 1 valeur
        0x00, orientation, 0x00, 0x00,
        0x00, 0x00, 0x00, 0x00,                         // pas d'autre répertoire
    ]);
    const app1 = Buffer.concat([Buffer.from('Exif\0\0', 'binary'), tiff]);
    const header = Buffer.from([0xff, 0xe1, (app1.length + 2) >> 8, (app1.length + 2) & 0xff]);
    return Buffer.concat([jpeg.subarray(0, 2), header, app1, jpeg.subarray(2)]);
}

async function iphonePhoto(page) {
    const dataUrl = await page.evaluate(() => {
        const canvas = document.createElement('canvas');
        canvas.width = 400;
        canvas.height = 300;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'rgb(220, 30, 30)';
        ctx.fillRect(0, 0, 400, 150);
        ctx.fillStyle = 'rgb(30, 30, 220)';
        ctx.fillRect(0, 150, 400, 150);
        return canvas.toDataURL('image/jpeg', 0.9);
    });
    return withOrientation(Buffer.from(dataUrl.split(',')[1], 'base64'), 6);
}

/** Dimensions et couleurs, à gauche et à droite, d'une image servie par le site. */
async function inspect(page, path) {
    return page.evaluate(async (src) => {
        const img = new Image();
        img.src = src;
        await img.decode();
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        const at = (x) => Array.from(ctx.getImageData(Math.floor(img.naturalWidth * x), Math.floor(img.naturalHeight / 2), 1, 1).data.slice(0, 3));
        return { width: img.naturalWidth, height: img.naturalHeight, left: at(0.25), right: at(0.75) };
    }, `/${path}?t=${Date.now()}`);
}

function expectBlueLeftRedRight(image) {
    expect(image.left[2], `gauche bleue : ${image.left}`).toBeGreaterThan(150);
    expect(image.left[0], `gauche bleue : ${image.left}`).toBeLessThan(100);
    expect(image.right[0], `droite rouge : ${image.right}`).toBeGreaterThan(150);
    expect(image.right[2], `droite rouge : ${image.right}`).toBeLessThan(100);
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
            name: 'iphone.jpg', mimeType: 'image/jpeg', buffer: await iphonePhoto(page),
        });
        const modal = page.getByTestId('photo-crop-modal');
        await expect(modal).toBeVisible();
        await expect(modal).toContainText(`Photo de ${player.prenom} ${player.nom}`);
        // Cropper.js a redressé la photo : le cadre 3:4 tient dans une image portrait
        const validate = modal.getByRole('button', { name: /Valider la photo/ });
        await expect(validate).toBeEnabled();
        await page.screenshot({ path: 'test-results/issue-461/recadrage.png' });

        const [upload] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/player/uploadPhoto'), { timeout: 15000 })
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
        expectBlueLeftRedRight(photo);

        const low = await inspect(page, saved.path_photo_low);
        expect([low.width, low.height]).toEqual([300, 400]);
        expectBlueLeftRedRight(low);
    });

    test('annuler le recadrage n\'envoie rien', async ({ page }) => {
        let uploads = 0;
        page.on('request', (r) => { if (r.url().includes('/player/uploadPhoto')) uploads++; });
        await page.goto('/pages/my_page.html#/players');
        const card = page.locator('li.card', { hasText: `${player.prenom} ${player.nom}` }).first();
        await expect(card).toBeVisible({ timeout: 15000 });
        await card.getByTestId('player-photo-input').setInputFiles({
            name: 'iphone.jpg', mimeType: 'image/jpeg', buffer: await iphonePhoto(page),
        });
        const modal = page.getByTestId('photo-crop-modal');
        await expect(modal).toBeVisible();
        await modal.getByRole('button', { name: 'Annuler' }).click();
        await expect(modal).toBeHidden();
        expect(uploads).toBe(0);
    });
});
