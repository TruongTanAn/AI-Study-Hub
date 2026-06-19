<<<<<<< HEAD
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
=======
/**
 * AI Study Hub - Profile JavaScript
 * Handles password toggling, avatar preview, and client-side validations
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Avatar Preview Logic
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG, GIF.');
                    avatarInput.value = ''; // Clear selection
                    return;
                }

                // Validate file size (2MB)
                const maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('Kích thước ảnh đại diện không được vượt quá 2MB.');
                    avatarInput.value = ''; // Clear selection
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    avatarPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 2. Profile Form Validation (Passwords matching)
    const profileForm = document.getElementById('profile-edit-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

            // If user enters a new password, they MUST provide current password and passwords must match
            if (newPassword !== '') {
                if (currentPassword === '') {
                    e.preventDefault();
                    alert('Vui lòng nhập Mật khẩu hiện tại để thay đổi mật khẩu.');
                    return;
                }

                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Mật khẩu mới phải từ 6 ký tự trở lên.');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Mật khẩu mới và xác nhận mật khẩu không trùng khớp.');
                    return;
                }
            }
        });
    }
});

/**
 * Toggle Password Visibility
 * @param {string} inputId - The ID of the input element to toggle
 * @param {string} iconId - The ID of the eye icon element
 */
function togglePasswordVisibility(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = document.getElementById(iconId);

    if (passwordInput && eyeIcon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }
}
>>>>>>> origin/hoa-fe
