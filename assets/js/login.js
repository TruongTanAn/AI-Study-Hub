// Hiện / Ẩn mật khẩu
function togglePassword() {

    const password =
        document.getElementById("password");

    const eyeIcon =
        document.getElementById("eyeIcon");

    if (password.type === "password") {

        password.type = "text";

        eyeIcon.classList.remove("fa-eye");
        eyeIcon.classList.add("fa-eye-slash");

    } else {

        password.type = "password";

        eyeIcon.classList.remove("fa-eye-slash");
        eyeIcon.classList.add("fa-eye");
    }
}


document.addEventListener("DOMContentLoaded", function () {

    const inputs = document.querySelectorAll("input");

    inputs.forEach(input => {

        input.addEventListener("focus", function () {
            this.parentElement.style.borderColor = "#4f46e5";
        });

        input.addEventListener("blur", function () {
            this.parentElement.style.borderColor = "#cbd5e1";
        });

    });

});