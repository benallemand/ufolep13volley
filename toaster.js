export function onSuccess(controller, response) {
    controller.isLoading = false;
    if(typeof Toastify !== 'undefined') {
        Toastify({
            text: response.data.message,
            duration: 3000,
            close: true,
            gravity: "bottom",
            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)", // Couleur de fond du toast
        }).showToast();
    }
    if(typeof Notyf !== 'undefined') {
        const notyf = new Notyf();
        notyf.success(response.data.message);
    }
}
export function onError(controller, error) {
    controller.isLoading = false;
    if(typeof Toastify !== 'undefined') {
        Toastify({
            text: error.response.data.message,
            duration: 3000,
            close: true,
            gravity: "bottom",
            backgroundColor: "linear-gradient(to right, #ff0000, #ff6666)",
        }).showToast();
    }
    if(typeof Notyf !== 'undefined') {
        const notyf = new Notyf();
        notyf.error(response.data.message);
    }
    console.error('Erreur lors du chargement des données:', error);
}