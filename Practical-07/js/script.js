/**
 * StudentHub - Main Application Script
 * Clean, accessible, client-side functionality
 */

document.addEventListener("DOMContentLoaded", () => {
    /* ==========================================================================
       1. Notification Banner
       ========================================================================== */
    const notificationBanner = document.getElementById("notificationBanner");
    const closeNotification = document.getElementById("closeNotification");

    if (notificationBanner && closeNotification) {
        if (sessionStorage.getItem("studenthub_banner_closed") === "true") {
            notificationBanner.classList.add("hidden");
        }

        closeNotification.addEventListener("click", () => {
            notificationBanner.classList.add("hidden");
            sessionStorage.setItem("studenthub_banner_closed", "true");
        });
    }

    /* ==========================================================================
       2. Responsive Mobile Navigation
       ========================================================================== */
    const menuButton = document.getElementById("menuButton");
    const navigationMenu = document.getElementById("navigationMenu");

    if (menuButton && navigationMenu) {
        menuButton.addEventListener("click", (e) => {
            e.stopPropagation();
            const isOpen = navigationMenu.classList.toggle("show");
            menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
            menuButton.textContent = isOpen ? "✕" : "☰";
        });

        // Close when clicking outside
        document.addEventListener("click", (e) => {
            if (!navigationMenu.contains(e.target) && !menuButton.contains(e.target)) {
                if (navigationMenu.classList.contains("show")) {
                    navigationMenu.classList.remove("show");
                    menuButton.setAttribute("aria-expanded", "false");
                    menuButton.textContent = "☰";
                }
            }
        });

        // Close on Escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && navigationMenu.classList.contains("show")) {
                navigationMenu.classList.remove("show");
                menuButton.setAttribute("aria-expanded", "false");
                menuButton.textContent = "☰";
            }
        });
    }

    /* ==========================================================================
       3. Theme Management (Dark Mode)
       ========================================================================== */
    const themeCheckbox = document.getElementById("themeToggle") || document.getElementById("themeButton");
    const savedTheme = localStorage.getItem("studenthubTheme");

    const applyTheme = (isDark) => {
        if (isDark) {
            document.body.classList.add("dark-mode");
        } else {
            document.body.classList.remove("dark-mode");
        }

        if (themeCheckbox) {
            if (themeCheckbox.type === "checkbox") {
                themeCheckbox.checked = isDark;
            } else {
                themeCheckbox.textContent = isDark ? "☀️ Light Mode" : "🌙 Dark Mode";
            }
        }
    };

    // Apply saved theme (or match system preference if none stored)
    const prefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
    const initialThemeIsDark = savedTheme === "dark" || (!savedTheme && prefersDark);
    applyTheme(initialThemeIsDark);

    if (themeCheckbox) {
        const toggleHandler = () => {
            const isDark = document.body.classList.toggle("dark-mode");
            localStorage.setItem("studenthubTheme", isDark ? "dark" : "light");
            applyTheme(isDark);
        };

        if (themeCheckbox.type === "checkbox") {
            themeCheckbox.addEventListener("change", toggleHandler);
        } else {
            themeCheckbox.addEventListener("click", toggleHandler);
        }
    }

    /* ==========================================================================
       4. Campus Image Slider (Homepage)
       ========================================================================== */
    const sliderContainer = document.querySelector(".slider");
    const slides = document.querySelectorAll(".slide");
    const previousSlide = document.getElementById("previousSlide");
    const nextSlide = document.getElementById("nextSlide");
    const slideNumber = document.getElementById("slideNumber");

    if (slides.length > 0) {
        let currentSlide = 0;
        let sliderTimer = null;

        const showSlide = (index) => {
            currentSlide = (index + slides.length) % slides.length;
            slides.forEach((slide) => slide.classList.remove("active"));
            slides[currentSlide].classList.add("active");

            if (slideNumber) {
                slideNumber.textContent = `${currentSlide + 1} / ${slides.length}`;
            }
        };

        const startAutoPlay = () => {
            stopAutoPlay();
            sliderTimer = setInterval(() => {
                showSlide(currentSlide + 1);
            }, 4500);
        };

        const stopAutoPlay = () => {
            if (sliderTimer) {
                clearInterval(sliderTimer);
                sliderTimer = null;
            }
        };

        if (previousSlide) {
            previousSlide.addEventListener("click", () => {
                showSlide(currentSlide - 1);
                startAutoPlay();
            });
        }

        if (nextSlide) {
            nextSlide.addEventListener("click", () => {
                showSlide(currentSlide + 1);
                startAutoPlay();
            });
        }

        if (sliderContainer) {
            sliderContainer.addEventListener("mouseenter", stopAutoPlay);
            sliderContainer.addEventListener("mouseleave", startAutoPlay);
        }

        showSlide(0);
        startAutoPlay();
    }

    /* ==========================================================================
       5. FAQ Accordion & Live Filter
       ========================================================================== */
    const faqButtons = document.querySelectorAll(".faq-question");
    faqButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = button.closest(".faq-item");
            const isOpen = item.classList.toggle("open");
            button.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    });

    const faqSearch = document.getElementById("faqSearch");
    if (faqSearch) {
        faqSearch.addEventListener("input", () => {
            const query = faqSearch.value.toLowerCase().trim();
            const faqItems = document.querySelectorAll(".faq-item");

            faqItems.forEach((item) => {
                const question = item.querySelector(".faq-question")?.textContent.toLowerCase() || "";
                const answer = item.querySelector(".faq-answer")?.textContent.toLowerCase() || "";
                if (question.includes(query) || answer.includes(query)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        });
    }

    /* ==========================================================================
       6. Registration Form Validation & Password Strength
       ========================================================================== */
    const passwordInput = document.getElementById("password");
    const passwordStrength = document.getElementById("passwordStrength");

    if (passwordInput && passwordStrength) {
        passwordInput.addEventListener("input", () => {
            const val = passwordInput.value;
            if (!val) {
                passwordStrength.textContent = "";
                passwordStrength.className = "password-strength";
                return;
            }

            let score = 0;
            if (val.length >= 8) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[@$!%*?&]/.test(val)) score++;

            if (score <= 2) {
                passwordStrength.textContent = "Strength: Weak";
                passwordStrength.className = "password-strength weak";
            } else if (score <= 4) {
                passwordStrength.textContent = "Strength: Medium";
                passwordStrength.className = "password-strength medium";
            } else {
                passwordStrength.textContent = "Strength: Strong";
                passwordStrength.className = "password-strength strong";
            }
        });
    }

    const registrationForm = document.getElementById("registrationForm");
    if (registrationForm) {
        const nameInput = document.getElementById("name");
        const emailInput = document.getElementById("email");
        const mobileInput = document.getElementById("mobile");
        const confirmPasswordInput = document.getElementById("confirmPassword");
        const courseInput = document.getElementById("course");
        const yearInput = document.getElementById("year");
        const termsInput = document.getElementById("terms");

        const showError = (fieldId, message) => {
            const errorElement = document.getElementById(`${fieldId}Error`);
            if (errorElement) errorElement.textContent = message;
        };

        const clearErrors = () => {
            ["name", "email", "mobile", "password", "confirmPassword", "course", "year", "gender", "terms"].forEach(
                (id) => showError(id, "")
            );
        };

        registrationForm.addEventListener("submit", (e) => {
            clearErrors();
            let isValid = true;

            const nameRegex = /^[A-Za-z ]{2,50}$/;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const mobileRegex = /^[6-9]\d{9}$/;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

            if (!nameRegex.test(nameInput.value.trim())) {
                showError("name", "Enter a valid name with 2 to 50 alphabetic characters.");
                isValid = false;
            }

            if (!emailRegex.test(emailInput.value.trim())) {
                showError("email", "Enter a valid email address.");
                isValid = false;
            }

            if (!mobileRegex.test(mobileInput.value.trim())) {
                showError("mobile", "Enter a valid 10-digit mobile number starting with 6-9.");
                isValid = false;
            }

            if (!passwordRegex.test(passwordInput.value)) {
                showError("password", "Password needs 8+ chars with uppercase, lowercase, number & symbol.");
                isValid = false;
            }

            if (!confirmPasswordInput.value || confirmPasswordInput.value !== passwordInput.value) {
                showError("confirmPassword", "Passwords do not match.");
                isValid = false;
            }

            if (!courseInput.value) {
                showError("course", "Please select your course.");
                isValid = false;
            }

            if (!yearInput.value) {
                showError("year", "Please select your academic year.");
                isValid = false;
            }

            if (!document.querySelector('input[name="gender"]:checked')) {
                showError("gender", "Please select your gender.");
                isValid = false;
            }

            if (termsInput && !termsInput.checked) {
                showError("terms", "You must accept the terms and conditions.");
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
            // If valid and form has an action, it will submit normally to the PHP handler
        });
    }

    // Generic demo submit handler for forms without an active server backend
    document.querySelectorAll('form[action="#"]').forEach((form) => {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            alert("Thank you! Your information has been submitted successfully.");
            form.reset();
        });
    });

    /* ==========================================================================
       7. URL Query Feedback Banner (Success / Error)
       ========================================================================== */
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = document.getElementById("successMessage");
    const errorMsg = document.getElementById("errorMessage");

    if (urlParams.get("success") === "1" && successMsg) {
        successMsg.style.display = "block";
        successMsg.classList.add("show");
    }

    if (urlParams.get("error") === "1" && errorMsg) {
        errorMsg.style.display = "block";
    }
});