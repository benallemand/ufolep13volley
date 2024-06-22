function onSuccess(response) {
    this.isLoading = false;
    Toastify({
        text: response.data.message,
        duration: 3000,
        close: true,
        gravity: "bottom",
        backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)", // Couleur de fond du toast
    }).showToast();
}
function onError(error) {
    this.isLoading = false;
    Toastify({
        text: error.response.data.message,
        duration: 3000,
        close: true,
        gravity: "bottom",
        backgroundColor: "linear-gradient(to right, #ff0000, #ff6666)",
    }).showToast();
    console.error('Erreur lors du chargement des données:', error);
}