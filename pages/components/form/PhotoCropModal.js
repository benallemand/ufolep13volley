import Cropper from 'cropperjs';

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
          <div class="modal-action">
            <button type="button" class="btn btn-ghost" @click="$emit('cancel')">Annuler</button>
            <button type="button" class="btn btn-primary" :disabled="!ready || busy" @click="validate">
              <i class="fas fa-check"></i> Valider la photo
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
        });
    },
    beforeUnmount() {
        if (this.cropper) {
            this.cropper.destroy();
        }
        URL.revokeObjectURL(this.url);
    },
    methods: {
        rotate(degrees) {
            this.cropper.rotate(degrees);
        },
        zoom(ratio) {
            this.cropper.zoom(ratio);
        },
        validate() {
            const canvas = this.cropper.getCroppedCanvas({
                width: CROP_WIDTH,
                height: CROP_HEIGHT,
                fillColor: '#fff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            if (!canvas) {
                return;
            }
            this.busy = true;
            canvas.toBlob((blob) => {
                this.busy = false;
                if (blob) {
                    this.$emit('cropped', blob);
                }
            }, 'image/jpeg', 0.85);
        },
    },
};
