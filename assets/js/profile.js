document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG, GIF, WEBP.');
                    avatarInput.value = '';
                    return;
                }

                const maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('Kích thước ảnh đại diện không được vượt quá 2MB.');
                    avatarInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    avatarPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    const profileForm = document.getElementById('profile-edit-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

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
