function showAlert(type = 'success' , message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-fixed`;
    alertDiv.style.display = 'none';
    alertDiv.innerHTML = message;

    document.body.appendChild(alertDiv);

    $(alertDiv).fadeIn();
    setTimeout(() => {
        $(alertDiv).fadeOut();
    }, 3000);
}