/**
 * AI Study Hub - Trang đăng ký (Register Page Validation & Interactions)
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    if (!form) return;

    // Form inputs
    const fullname = document.getElementById('fullname');
    const email = document.getElementById('email');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const terms = document.getElementById('terms');
    const submitBtn = document.getElementById('submitBtn');

    // UI elements for error messages
    const fullnameErr = document.getElementById('fullname_error');
    const emailErr = document.getElementById('email_error');
    const usernameErr = document.getElementById('username_error');
    const passwordErr = document.getElementById('password_error');
    const confirmPasswordErr = document.getElementById('confirm_password_error');
    const termsErr = document.getElementById('terms_error');

    // Password requirements elements
    const reqLength = document.getElementById('req_length');
    const reqUpper = document.getElementById('req_uppercase');
    const reqLower = document.getElementById('req_lowercase');
    const reqNumber = document.getElementById('req_number');
    const reqSpecial = document.getElementById('req_special');
    const passwordRequirements = document.querySelector('.password-requirements');

    // Password strength bar and text elements
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    // Toggle button enabling/disabling based on all validation state
    const validationStates = {
        fullname: false,
        email: false,
        username: false,
        password: false,
        confirmPassword: false,
        terms: false
    };

    // Helper functions
    const setError = (input, errorEl, message) => {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        errorEl.textContent = message;
        errorEl.classList.add('active');
        validationStates[input.id === 'confirm_password' ? 'confirmPassword' : input.id] = false;
        checkFormValidity();
    };

    const setSuccess = (input, errorEl) => {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        errorEl.textContent = '';
        errorEl.classList.remove('active');
        validationStates[input.id === 'confirm_password' ? 'confirmPassword' : input.id] = true;
        checkFormValidity();
    };

    const checkFormValidity = () => {
        const isValid = Object.values(validationStates).every(state => state === true);
        submitBtn.disabled = !isValid;
    };

    // Validate Full Name
    const validateFullName = () => {
        const val = fullname.value.trim();
        if (val === '') {
            setError(fullname, fullnameErr, 'Họ và tên không được để trống.');
        } else if (val.length < 2) {
            setError(fullname, fullnameErr, 'Họ và tên phải có ít nhất 2 ký tự.');
        } else {
            setSuccess(fullname, fullnameErr);
        }
    };

    // Validate Email
    const validateEmail = () => {
        const val = email.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (val === '') {
            setError(email, emailErr, 'Email không được để trống.');
        } else if (!emailRegex.test(val)) {
            setError(email, emailErr, 'Email không đúng định dạng (Ví dụ: user@example.com).');
        } else {
            setSuccess(email, emailErr);
        }
    };

    // Validate Username
    const validateUsername = () => {
        const val = username.value.trim();
        const usernameRegex = /^[a-zA-Z0-9_]+$/;
        if (val === '') {
            setError(username, usernameErr, 'Tên đăng nhập không được để trống.');
        } else if (val.length < 4) {
            setError(username, usernameErr, 'Tên đăng nhập phải có ít nhất 4 ký tự.');
        } else if (!usernameRegex.test(val)) {
            setError(username, usernameErr, 'Tên đăng nhập chỉ gồm chữ cái, số và dấu gạch dưới (_).');
        } else {
            setSuccess(username, usernameErr);
        }
    };

    // Validate Password & Strength Meter
    const validatePassword = () => {
        const val = password.value;

        // Show/hide guidelines
        if (val.length > 0) {
            passwordRequirements.classList.add('active');
        } else {
            passwordRequirements.classList.remove('active');
        }

        // Checklist validation
        const checks = {
            length: val.length >= 8,
            uppercase: /[A-Z]/.test(val),
            lowercase: /[a-z]/.test(val),
            number: /[0-9]/.test(val),
            special: /[^A-Za-z0-9]/.test(val)
        };

        // Update Checklist UI
        updateRequirementUI(reqLength, checks.length);
        updateRequirementUI(reqUpper, checks.uppercase);
        updateRequirementUI(reqLower, checks.lowercase);
        updateRequirementUI(reqNumber, checks.number);
        updateRequirementUI(reqSpecial, checks.special);

        // Calculate strength score
        let score = 0;
        if (checks.length) score++;
        if (checks.uppercase && checks.lowercase) score++;
        if (checks.number) score++;
        if (checks.special) score++;

        // Update Strength Bar UI
        updateStrengthUI(score);

        // Final verification for password field
        const isAllRequirementsMet = Object.values(checks).every(c => c === true);

        if (val === '') {
            setError(password, passwordErr, 'Mật khẩu không được để trống.');
        } else if (!isAllRequirementsMet) {
            setError(password, passwordErr, 'Mật khẩu chưa đáp ứng đủ yêu cầu bảo mật.');
        } else {
            setSuccess(password, passwordErr);
        }

        // Real-time update to confirm password too if it has value
        if (confirmPassword.value.length > 0) {
            validateConfirmPassword();
        }
    };

    const updateRequirementUI = (element, isMet) => {
        const icon = element.querySelector('i');
        if (isMet) {
            element.classList.remove('unmet');
            element.classList.add('met');
            icon.className = 'fa-solid fa-circle-check';
        } else {
            element.classList.remove('met');
            element.classList.add('unmet');
            icon.className = 'fa-regular fa-circle';
        }
    };

    const updateStrengthUI = (score) => {
        let percentage = '0%';
        let color = '#e5e7eb';
        let text = 'Chưa nhập';
        let colorText = '#6b7280';

        if (password.value.length > 0) {
            if (score <= 1) {
                percentage = '25%';
                color = '#ef4444'; // Red
                text = 'Yếu';
                colorText = '#ef4444';
            } else if (score === 2) {
                percentage = '50%';
                color = '#f59e0b'; // Amber
                text = 'Trung bình';
                colorText = '#f59e0b';
            } else if (score === 3) {
                percentage = '75%';
                color = '#3b82f6'; // Blue
                text = 'Khá';
                colorText = '#3b82f6';
            } else if (score === 4) {
                percentage = '100%';
                color = '#10b981'; // Green
                text = 'Mạnh';
                colorText = '#10b981';
            }
        }

        strengthFill.style.width = percentage;
        strengthFill.style.backgroundColor = color;
        strengthText.innerHTML = `Độ mạnh: <span style="color: ${colorText}">${text}</span>`;
    };

    // Validate Confirm Password
    const validateConfirmPassword = () => {
        const val = confirmPassword.value;
        const passVal = password.value;

        if (val === '') {
            setError(confirmPassword, confirmPasswordErr, 'Vui lòng xác nhận mật khẩu.');
        } else if (val !== passVal) {
            setError(confirmPassword, confirmPasswordErr, 'Mật khẩu xác nhận không khớp với mật khẩu đã nhập.');
        } else {
            setSuccess(confirmPassword, confirmPasswordErr);
        }
    };

    // Validate Terms & Conditions Checkbox
    const validateTerms = () => {
        if (!terms.checked) {
            termsErr.textContent = 'Bạn cần đồng ý với Điều khoản dịch vụ và Chính sách bảo mật của chúng tôi.';
            termsErr.classList.add('active');
            validationStates.terms = false;
            checkFormValidity();
        } else {
            termsErr.textContent = '';
            termsErr.classList.remove('active');
            validationStates.terms = true;
            checkFormValidity();
        }
    };

    // Event listeners for real-time validation
    fullname.addEventListener('input', validateFullName);
    fullname.addEventListener('blur', validateFullName);

    email.addEventListener('input', validateEmail);
    email.addEventListener('blur', validateEmail);

    username.addEventListener('input', validateUsername);
    username.addEventListener('blur', validateUsername);

    password.addEventListener('input', validatePassword);
    password.addEventListener('focus', () => {
        if (password.value.length > 0) passwordRequirements.classList.add('active');
    });

    confirmPassword.addEventListener('input', validateConfirmPassword);
    confirmPassword.addEventListener('blur', validateConfirmPassword);

    terms.addEventListener('change', validateTerms);

    // Initial check in case form loaded with values
    checkFormValidity();

    // Final Form Submission Verification
    form.addEventListener('submit', (e) => {
        // Run all validations once again
        validateFullName();
        validateEmail();
        validateUsername();
        validatePassword();
        validateConfirmPassword();
        validateTerms();

        const isValid = Object.values(validationStates).every(state => state === true);
        if (!isValid) {
            e.preventDefault();
        }
    });
});