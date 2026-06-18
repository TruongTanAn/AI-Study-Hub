document.addEventListener("DOMContentLoaded", () => {

    const avatarInput = document.getElementById("avatarInput");
    const avatarPreview = document.getElementById("avatarPreview");

    if (avatarInput && avatarPreview) {

        avatarInput.addEventListener("change", function () {

            const file = this.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function (e) {
                avatarPreview.src = e.target.result;
            };

            reader.readAsDataURL(file);

        });

    }

});