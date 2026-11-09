// Photos de test pour le recadrage et la détection de visage (issues #461, #471).
//
// `e2e/fixtures/visage_genere.jpg` est un visage GÉNÉRÉ (StyleGAN2), du domaine
// public : https://commons.wikimedia.org/wiki/File:This_Person_Does_Not_Exist_example.jpg
// (vignette de 500 px). Ce n'est la photo de personne : aucune donnée personnelle.
const fs = require('fs');
const path = require('path');

const FACE = fs.readFileSync(path.join(__dirname, '..', 'fixtures', 'visage_genere.jpg')).toString('base64');

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

/**
 * Photo « d'iPhone » : à l'endroit, une bande rouge en haut, le visage, une
 * bande bleue en bas (500 × 660). Elle est enregistrée couchée (tournée d'un
 * quart de tour à gauche) avec l'étiquette « orientation = 6 », qui demande
 * de la tourner d'un quart de tour à droite pour l'afficher.
 */
async function iphoneFacePhoto(page) {
    const dataUrl = await page.evaluate(async (face) => {
        const img = new Image();
        img.src = 'data:image/jpeg;base64,' + face;
        await img.decode();
        const upright = document.createElement('canvas');
        upright.width = 500;
        upright.height = 660;
        const u = upright.getContext('2d');
        u.fillStyle = 'rgb(220, 30, 30)';
        u.fillRect(0, 0, 500, 80);
        u.drawImage(img, 0, 80, 500, 500);
        u.fillStyle = 'rgb(30, 30, 220)';
        u.fillRect(0, 580, 500, 80);
        const lying = document.createElement('canvas');
        lying.width = 660;
        lying.height = 500;
        const l = lying.getContext('2d');
        l.translate(0, 500);
        l.rotate(-Math.PI / 2);
        l.drawImage(upright, 0, 0);
        return lying.toDataURL('image/jpeg', 0.9);
    }, FACE);
    return withOrientation(Buffer.from(dataUrl.split(',')[1], 'base64'), 6);
}

/** Photo sans visage : deux aplats de couleur. */
async function noFacePhoto(page) {
    const dataUrl = await page.evaluate(() => {
        const canvas = document.createElement('canvas');
        canvas.width = 300;
        canvas.height = 400;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'rgb(220, 30, 30)';
        ctx.fillRect(0, 0, 300, 200);
        ctx.fillStyle = 'rgb(30, 30, 220)';
        ctx.fillRect(0, 200, 300, 200);
        return canvas.toDataURL('image/jpeg', 0.9);
    });
    return Buffer.from(dataUrl.split(',')[1], 'base64');
}

/** Dimensions et couleurs en haut et en bas d'une image servie par le site. */
async function inspect(page, src) {
    return page.evaluate(async (url) => {
        const img = new Image();
        img.src = url;
        await img.decode();
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        const at = (y) => Array.from(ctx.getImageData(Math.floor(img.naturalWidth / 2), Math.floor(img.naturalHeight * y), 1, 1).data.slice(0, 3));
        return {width: img.naturalWidth, height: img.naturalHeight, top: at(0.03), bottom: at(0.97)};
    }, `/${src}?t=${Date.now()}`);
}

module.exports = {iphoneFacePhoto, noFacePhoto, inspect};
