import Cropper from 'cropperjs';
import {checkFace, loadFaceDetector} from './faceCheck.js';

/**
 * Recadrage d'une photo de joueur avant l'envoi (issue #461).
 *
 * Cadre imposé au format photo d'identité (3:4), que l'on déplace et zoome ;
 * boutons pour tourner d'un quart de tour. Cropper.js lit l'orientation EXIF
 * d'une photo d'iPhone et la redresse lui-même (`checkOrientation`).
 *
 * Seule la zone recadrée est envoyée, en JPEG de 600 × 800 : quelques dizaines
 * de Ko au lieu des 2 à 5 Mo d'une photo de téléphone. Le serveur la redresse
 * et la réduit de toute façon (`PhotoImage`), pour les photos qui ne passent
 * pas par ici.
 *
 * Avant l'envoi, la photo recadrée doit montrer un visage identifiable : deux
 * yeux et une bouche détectés (issue #471, `faceCheck.js`). Sinon un message
 * dit quoi corriger. Un administrateur peut forcer l'envoi en cas de faux
 * refus ; ce contrôle n'existe que dans le navigateur.
 *
 * Émet `cropped` (Blob JPEG) ou `cancel`.
 */
export const CROP_WIDTH = 600;
export const CROP_HEIGHT = 800;

export default {
    props: {
        file: { type: File, required: true },
        title: { type: String, default: 'Recadrer la photo' },
    },
    emits: ['cropped', 'cancel'],
    template: `
      <dialog class="modal modal-open" data-testid="photo-crop-modal">
        <div class="modal-box max-w-2xl">
          <h3 class="font-bold text-lg">{{ title }}</h3>
          <p class="text-sm text-base-content/70 mt-1">
            Placez le visage dans le cadre : déplacez la photo, zoomez avec la molette ou les boutons.
          </p>
          <div class="mt-3 bg-base-200 rounded-box overflow-hidden" style="height: 60vh; max-height: 480px;">
            <img ref="image" :src="url" alt="Photo à recadrer" class="block max-w-full"/>
          </div>
          <div class="flex flex-wrap justify-center gap-2 mt-3">
            <button type="button" class="btn btn-sm" title="Tourner vers la gauche" @click="rotate(-90)">
              <i class="fas fa-rotate-left"></i>
            </button>
            <button type="button" class="btn btn-sm" title="Tourner vers la droite" @click="rotate(90)">
              <i class="fas fa-rotate-right"></i>
            </button>
            <button type="button" class="btn btn-sm" title="Zoomer" @click="zoom(0.1)">
              <i class="fas fa-magnifying-glass-plus"></i>
            </button>
            <button type="button" class="btn btn-sm" title="Dézoomer" @click="zoom(-0.1)">
              <i class="fas fa-magnifying-glass-minus"></i>
            </button>
          </div>
          <div v-if="refusal" role="alert" class="alert alert-warning mt-3" data-testid="photo-face-refusal">
            <i class="fas fa-user-slash"></i>
            <span>{{ refusal }}</span>
          </div>
          <div class="modal-action">
            <button type="button" class="btn btn-ghost" @click="$emit('cancel')">Annuler</button>
            <button v-if="refusal && isAdmin" type="button" class="btn btn-warning" :disabled="busy"
                    data-testid="photo-force" @click="force">
              <i class="fas fa-triangle-exclamation"></i> Envoyer quand même (admin)
            </button>
            <button type="button" class="btn btn-primary" :disabled="!ready || busy" @click="validate">
              <span v-if="checking" class="loading loading-spinner loading-sm"></span>
              <i v-else class="fas fa-check"></i>
              {{ checking ? 'Vérification du visage…' : 'Valider la photo' }}
            </button>
          </div>
        </div>
      </dialog>
    `,
    data() {
        return {
            url: URL.createObjectURL(this.file),
            ready: false,
            busy: false,
            checking: false,
            refusal: null,
            isAdmin: false,
        };
    },
    mounted() {
        this.cropper = new Cropper(this.$refs.image, {
            aspectRatio: 3 / 4,
            viewMode: 1,          // le cadre reste dans la photo
            dragMode: 'move',     // glisser déplace la photo, pas le cadre
            autoCropArea: 1,
            checkOrientation: true,
            background: false,
            ready: () => { this.ready = true; },
            // un nouveau cadrage efface le refus précédent
            cropend: () => { this.refusal = null; },
            zoom: () => { this.refusal = null; },
        });
        // le moteur de détection se charge pendant que l'on recadre
        loadFaceDetector().catch(() => {});
        axios.get(`/session_user.php?_dc=${Date.now()}`)
            .then((response) => { this.isAdmin = !!response.data?.is_admin; })
            .catch(() => {});
    },
    beforeUnmount() {
        if (this.cropper) {
            this.cropper.destroy();
        }
        URL.revokeObjectURL(this.url);
    },
    methods: {
        rotate(degrees) {
            this.refusal = null;
            this.cropper.rotate(degrees);
        },
        zoom(ratio) {
            this.cropper.zoom(ratio);
        },
        croppedCanvas() {
            return this.cropper.getCroppedCanvas({
                width: CROP_WIDTH,
                height: CROP_HEIGHT,
                fillColor: '#fff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
        },
        async validate() {
            const canvas = this.croppedCanvas();
            if (!canvas) {
                return;
            }
            this.busy = true;
            this.checking = true;
            this.refusal = null;
            let result;
            try {
                result = await checkFace(canvas);
            } catch (error) {
                console.error('Détection du visage impossible :', error);
                result = {ok: false, message: "La vérification du visage n'a pas pu se faire. Réessayez, ou utilisez un navigateur à jour."};
            }
            this.checking = false;
            if (!result.ok) {
                this.busy = false;
                this.refusal = result.message;
                return;
            }
            this.send(canvas);
        },
        // faux refus (lunettes, contre-jour…) : l'admin envoie quand même
        force() {
            const canvas = this.croppedCanvas();
            if (canvas && this.isAdmin) {
                this.busy = true;
                this.send(canvas);
            }
        },
        send(canvas) {
            canvas.toBlob((blob) => {
                this.busy = false;
                if (blob) {
                    this.$emit('cropped', blob);
                }
            }, 'image/jpeg', 0.85);
        },
    },
};
