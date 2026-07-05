// Un seul toast par événement : Toastify est la librairie de référence
// (Notyf reste chargée par certaines entrées mais n'est plus doublonnée ici).
// Les messages sont du texte brut — pas de HTML dans response.data.message.
export function onSuccess(controller, response) {
    controller.isLoading = false;
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: response.data.message,
            duration: 5000,
            close: true,
            gravity: "bottom",
            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)", // Couleur de fond du toast
        }).showToast();
    }
}
export function onError(controller, error) {
    controller.isLoading = false;
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: error.response.data.message,
            duration: 5000,
            close: true,
            gravity: "bottom",
            backgroundColor: "linear-gradient(to right, #ff0000, #ff6666)",
        }).showToast();
    }
    console.error('Erreur lors du chargement des données:', error);
}
