/**
 * La personne est-elle identifiable sur la photo recadrée ? (issue #471)
 *
 * Détection de visage dans le navigateur avec MediaPipe Tasks Vision (Google,
 * Apache 2.0) et son modèle BlazeFace « short range », fait pour les visages
 * proches de l'objectif, comme une photo d'identité. Le modèle et le moteur
 * WebAssembly sont servis par le site : la photo ne quitte pas le navigateur
 * pour cette vérification.
 *
 * BlazeFace donne, pour chaque visage, un score et six points : œil droit, œil
 * gauche, nez, bouche, oreilles. On exige :
 *  - exactement un visage, avec un score suffisant ;
 *  - les deux yeux et la bouche dans le cadre ;
 *  - un visage assez grand pour être reconnu (pas un joueur au loin) ;
 *  - la bouche sous les yeux (photo à l'endroit).
 *
 * Le moteur (~13 Mo) n'est chargé qu'à la première vérification, puis reste
 * en cache.
 */
import {FaceDetector} from '@mediapipe/tasks-vision';
import wasmLoaderUrl from '@mediapipe/tasks-vision/vision_wasm_internal.js?url';
import wasmBinaryUrl from '@mediapipe/tasks-vision/vision_wasm_internal.wasm?url';
import modelUrl from '../../../src/assets/models/blaze_face_short_range.tflite?url';

/** Score minimal d'un visage. */
export const MIN_SCORE = 0.6;
/** Largeur minimale du visage, en part de la largeur de la photo. */
export const MIN_FACE_WIDTH = 0.25;

const RIGHT_EYE = 0;
const LEFT_EYE = 1;
const MOUTH = 3;

let detectorPromise = null;

/** Charge le détecteur une seule fois ; à appeler tôt pour gagner du temps. */
export function loadFaceDetector() {
    if (!detectorPromise) {
        detectorPromise = FaceDetector.createFromOptions(
            {wasmLoaderPath: wasmLoaderUrl, wasmBinaryPath: wasmBinaryUrl},
            {
                baseOptions: {modelAssetPath: modelUrl},
                runningMode: 'IMAGE',
                minDetectionConfidence: 0.5,
            },
        ).catch((error) => {
            detectorPromise = null;
            throw error;
        });
    }
    return detectorPromise;
}

const inside = (p) => p && p.x >= 0 && p.x <= 1 && p.y >= 0 && p.y <= 1;

/**
 * Applique les règles aux détections MediaPipe d'une image de largeur `width`.
 * Séparée de la détection pour être testable seule.
 *
 * @returns {{ok: boolean, reason: string|null, message: string|null}}
 */
export function judgeDetections(detections, width) {
    const faces = (detections || []).filter((d) => (d.categories?.[0]?.score ?? 0) >= MIN_SCORE);
    if (faces.length === 0) {
        return refuse('none', "Aucun visage n'est reconnu : les deux yeux et la bouche doivent être visibles, de face, sans lunettes de soleil.");
    }
    if (faces.length > 1) {
        return refuse('several', "Plusieurs visages sont dans le cadre : ne gardez que celui du joueur.");
    }
    const face = faces[0];
    const points = face.keypoints || [];
    const eyes = [points[RIGHT_EYE], points[LEFT_EYE]];
    const mouth = points[MOUTH];
    if (!eyes.every(inside) || !inside(mouth)) {
        return refuse('cut', "Le visage est coupé par le cadre : les deux yeux et la bouche doivent y être.");
    }
    if (mouth.y <= Math.max(eyes[0].y, eyes[1].y)) {
        return refuse('upside_down', "La photo semble à l'envers ou couchée : tournez-la avec les flèches.");
    }
    if ((face.boundingBox?.width ?? 0) < MIN_FACE_WIDTH * width) {
        return refuse('small', "Le visage est trop petit : zoomez pour qu'il remplisse le cadre.");
    }
    return {ok: true, reason: null, message: null};
}

function refuse(reason, message) {
    return {ok: false, reason, message};
}

/**
 * Vérifie un canvas (la photo recadrée).
 * @param {HTMLCanvasElement} canvas
 */
export async function checkFace(canvas) {
    const detector = await loadFaceDetector();
    const {detections} = detector.detect(canvas);
    return judgeDetections(detections, canvas.width);
}
