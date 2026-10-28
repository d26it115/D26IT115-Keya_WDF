const notificationBanner = document.getElementById("notificationBanner");
const closeNotification = document.getElementById("closeNotification");

if (notificationBanner && closeNotification) {
    closeNotification.addEventListener("click", () => {
        notificationBanner.classList.add("hidden");
    });
}

const menuButton = document.getElementById("menuButton");
const navigationMenu = document.getElementById("navigationMenu");

if (menuButton && navigationMenu) {
    menuButton.addEventListener("click", () => {
        navigationMenu.classList.toggle("show");
        const isOpen = navigationMenu.classList.contains("show");
        menuButton.setAttribute("aria-expanded", isOpen);
        menuButton.textContent = isOpen ? "✕" : "☰";
    });
}

const themeButton = document.getElementById("themeButton");
const savedTheme = localStorage.getItem("studenthubTheme");

if (savedTheme === "dark") {
    document.body.classList.add("dark-mode");
}

function updateThemeButton() {
    if (!themeButton) return;
    themeButton.textContent = document.body.classList.contains("dark-mode")
        ? "☀️ Light Mode"
        : "🌙 Dark Mode";
}

updateThemeButton();

if (themeButton) {
    themeButton.addEventListener("click", () => {
        const isDark = document.body.classList.toggle("dark-mode");
        localStorage.setItem("studenthubTheme", isDark ? "dark" : "light");
        updateThemeButton();
    });
}

const slides = document.querySelectorAll(".slide");
const previousSlide = document.getElementById("previousSlide");
const nextSlide = document.getElementById("nextSlide");
const slideNumber = document.getElementById("slideNumber");
let currentSlide = 0;

function showSlide(index) {
    if (slides.length === 0) return;

    currentSlide = (index + slides.length) % slides.length;

    slides.forEach((slide) => slide.classList.remove("active"));
    slides[currentSlide].classList.add("active");

    if (slideNumber) {
        slideNumber.textContent = `${currentSlide + 1} / ${slides.length}`;
    }
}

if (previousSlide) {
    previousSlide.addEventListener("click", () => showSlide(currentSlide - 1));
}

if (nextSlide) {
    nextSlide.addEventListener("click", () => showSlide(currentSlide + 1));
}

if (slides.length > 0) {
    showSlide(0);
    setInterval(() => showSlide(currentSlide + 1), 4000);
}

document.querySelectorAll(".faq-question").forEach((button) => {
    button.addEventListener("click", () => {
        const item = button.closest(".faq-item");
        const isOpen = item.classList.toggle("open");
        button.setAttribute("aria-expanded", isOpen);
    });
});

const registrationForm = document.getElementById("registrationForm");

if (registrationForm) {
    const name = document.getElementById("name");
    const email = document.getElementById("email");
    const mobile = document.getElementById("mobile");
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirmPassword");
    const course = document.getElementById("course");
    const year = document.getElementById("year");
    const terms = document.getElementById("terms");
    const successMessage = document.getElementById("successMessage");

    const showError = (id, message) => {
        const error = document.getElementById(`${id}Error`);
        if (error) error.textContent = message;
    };

    const clearErrors = () => {
        ["name", "email", "mobile", "password", "confirmPassword", "course", "year", "gender", "terms"]
            .forEach((id) => showError(id, ""));
    };

    registrationForm.addEventListener("submit", (event) => {
        event.preventDefault();
        clearErrors();
        let valid = true;

        const nameRegex = /^[A-Za-z ]{2,50}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const mobileRegex = /^[6-9]\d{9}$/;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

        if (!nameRegex.test(name.value.trim())) {
            showError("name", "Enter a valid name using letters and spaces only.");
            valid = false;
        }

        if (!emailRegex.test(email.value.trim())) {
            showError("email", "Enter a valid email address.");
            valid = false;
        }

        if (!mobileRegex.test(mobile.value.trim())) {
            showError("mobile", "Enter a valid 10-digit Indian mobile number.");
            valid = false;
        }

        if (!passwordRegex.test(password.value)) {
            showError("password", "Password must contain 8+ characters, uppercase, lowercase, number and special character.");
            valid = false;
        }

        if (!confirmPassword.value || confirmPassword.value !== password.value) {
            showError("confirmPassword", "Passwords do not match.");
            valid = false;
        }

        if (!course.value) {
            showError("course", "Please select a course.");
            valid = false;
        }

        if (!year.value) {
            showError("year", "Please select your year.");
            valid = false;
        }

        if (!document.querySelector('input[name="gender"]:checked')) {
            showError("gender", "Please select your gender.");
            valid = false;
        }

        if (!terms.checked) {
            showError("terms", "You must accept the terms and conditions.");
            valid = false;
        }

        if (valid) {
            successMessage.textContent = "Registration successful! Your details have been validated.";
            successMessage.classList.add("show");
            registrationForm.reset();
        } else {
            successMessage.classList.remove("show");
        }
    });
}

document.querySelectorAll("form:not(#registrationForm)").forEach((form) => {
    form.addEventListener("submit", (event) => {
        if (form.getAttribute("action") === "#") {
            event.preventDefault();
            alert("Form submitted successfully for demonstration.");
        }
    });
});
