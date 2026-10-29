const notificationBanner = document.getElementById("notificationBanner");
const closeNotification = document.getElementById("closeNotification");

if (notificationBanner && closeNotification) {
    closeNotification.addEventListener("click", () => {
        notificationBanner.classList.add("hidden");
    });
}


/* =========================================================
   MOBILE NAVIGATION
   ========================================================= */

const menuButton = document.getElementById("menuButton");
const navigationMenu = document.getElementById("navigationMenu");

if (menuButton && navigationMenu) {
    menuButton.addEventListener("click", () => {
        navigationMenu.classList.toggle("show");

        const isOpen =
            navigationMenu.classList.contains("show");

        menuButton.setAttribute(
            "aria-expanded",
            isOpen ? "true" : "false"
        );

        menuButton.textContent =
            isOpen ? "✕" : "☰";
    });
}


/* =========================================================
   THEME MANAGEMENT
   ========================================================= */

const themeButton =
    document.getElementById("themeButton");

const themeToggle =
    document.getElementById("themeToggle");

const savedTheme =
    localStorage.getItem("studenthubTheme");


if (savedTheme === "dark") {
    document.body.classList.add("dark-mode");
}


/* Old button-style theme toggle */

function updateThemeButton() {

    if (!themeButton) return;

    themeButton.textContent =
        document.body.classList.contains("dark-mode")
            ? "☀️ Light Mode"
            : "🌙 Dark Mode";
}

updateThemeButton();


if (themeButton) {

    themeButton.addEventListener("click", () => {

        const isDark =
            document.body.classList.toggle("dark-mode");

        localStorage.setItem(
            "studenthubTheme",
            isDark ? "dark" : "light"
        );

        updateThemeButton();

    });
}


/* New checkbox-style theme toggle */

if (themeToggle) {

    themeToggle.checked =
        document.body.classList.contains("dark-mode");

    themeToggle.addEventListener("change", () => {

        const isDark = themeToggle.checked;

        document.body.classList.toggle(
            "dark-mode",
            isDark
        );

        localStorage.setItem(
            "studenthubTheme",
            isDark ? "dark" : "light"
        );

    });
}


/* =========================================================
   IMAGE SLIDER
   ========================================================= */

const slides =
    document.querySelectorAll(".slide");

const previousSlide =
    document.getElementById("previousSlide");

const nextSlide =
    document.getElementById("nextSlide");

const slideNumber =
    document.getElementById("slideNumber");

let currentSlide = 0;


function showSlide(index) {

    if (slides.length === 0) return;

    currentSlide =
        (index + slides.length) %
        slides.length;

    slides.forEach((slide) => {
        slide.classList.remove("active");
    });

    slides[currentSlide].classList.add("active");

    if (slideNumber) {

        slideNumber.textContent =
            `${currentSlide + 1} / ${slides.length}`;

    }
}


if (previousSlide) {

    previousSlide.addEventListener(
        "click",
        () => {
            showSlide(currentSlide - 1);
        }
    );

}


if (nextSlide) {

    nextSlide.addEventListener(
        "click",
        () => {
            showSlide(currentSlide + 1);
        }
    );

}


if (slides.length > 0) {

    showSlide(0);

    setInterval(() => {
        showSlide(currentSlide + 1);
    }, 4000);

}


/* =========================================================
   FAQ ACCORDION
   ========================================================= */

document
    .querySelectorAll(".faq-question")
    .forEach((button) => {

        button.addEventListener("click", () => {

            const item =
                button.closest(".faq-item");

            const isOpen =
                item.classList.toggle("open");

            button.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );

        });

    });


/* =========================================================
   REGISTRATION FORM VALIDATION
   ========================================================= */

const registrationForm =
    document.getElementById("registrationForm");


if (registrationForm) {

    const name =
        document.getElementById("name");

    const email =
        document.getElementById("email");

    const mobile =
        document.getElementById("mobile");

    const password =
        document.getElementById("password");

    const confirmPassword =
        document.getElementById("confirmPassword");

    const course =
        document.getElementById("course");

    const year =
        document.getElementById("year");

    const terms =
        document.getElementById("terms");


    const showError = (id, message) => {

        const error =
            document.getElementById(
                `${id}Error`
            );

        if (error) {
            error.textContent = message;
        }

    };


    const clearErrors = () => {

        [
            "name",
            "email",
            "mobile",
            "password",
            "confirmPassword",
            "course",
            "year",
            "gender",
            "terms"
        ].forEach((id) => {

            showError(id, "");

        });

    };


    registrationForm.addEventListener(
        "submit",
        (event) => {

            clearErrors();

            let valid = true;


            /* -------------------------
               REGEX
               ------------------------- */

            const nameRegex =
                /^[A-Za-z ]{2,50}$/;

            const emailRegex =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            const mobileRegex =
                /^[6-9]\d{9}$/;

            const passwordRegex =
                /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;


            /* -------------------------
               NAME
               ------------------------- */

            if (
                !nameRegex.test(
                    name.value.trim()
                )
            ) {

                showError(
                    "name",
                    "Enter a valid name using letters and spaces only."
                );

                valid = false;

            }


            /* -------------------------
               EMAIL
               ------------------------- */

            if (
                !emailRegex.test(
                    email.value.trim()
                )
            ) {

                showError(
                    "email",
                    "Enter a valid email address."
                );

                valid = false;

            }


            /* -------------------------
               MOBILE
               ------------------------- */

            if (
                !mobileRegex.test(
                    mobile.value.trim()
                )
            ) {

                showError(
                    "mobile",
                    "Enter a valid 10-digit Indian mobile number."
                );

                valid = false;

            }


            /* -------------------------
               PASSWORD
               ------------------------- */

            if (
                !passwordRegex.test(
                    password.value
                )
            ) {

                showError(
                    "password",
                    "Password must contain 8+ characters, uppercase, lowercase, number and special character."
                );

                valid = false;

            }


            /* -------------------------
               CONFIRM PASSWORD
               ------------------------- */

            if (
                !confirmPassword.value ||
                confirmPassword.value !==
                    password.value
            ) {

                showError(
                    "confirmPassword",
                    "Passwords do not match."
                );

                valid = false;

            }


            /* -------------------------
               COURSE
               ------------------------- */

            if (!course.value) {

                showError(
                    "course",
                    "Please select a course."
                );

                valid = false;

            }


            /* -------------------------
               YEAR
               ------------------------- */

            if (!year.value) {

                showError(
                    "year",
                    "Please select your year."
                );

                valid = false;

            }


            /* -------------------------
               GENDER
               ------------------------- */

            if (
                !document.querySelector(
                    'input[name="gender"]:checked'
                )
            ) {

                showError(
                    "gender",
                    "Please select your gender."
                );

                valid = false;

            }


            /* -------------------------
               TERMS
               ------------------------- */

            if (
                terms &&
                !terms.checked
            ) {

                showError(
                    "terms",
                    "You must accept the terms and conditions."
                );

                valid = false;

            }


            /* =================================================
               IMPORTANT
               =================================================

               If validation fails:
               STOP the form.

               If validation succeeds:
               DO NOT preventDefault().

               The form will be submitted normally to:

               ../php/process_registration.php

               PHP will then:
               - validate again
               - check duplicate email
               - hash password
               - insert into MySQL
               ================================================= */

            if (!valid) {

                event.preventDefault();

            }

        }
    );

}


/* =========================================================
   PASSWORD STRENGTH CHECKER
   ========================================================= */

const passwordInput =
    document.getElementById("password");

const passwordStrength =
    document.getElementById("passwordStrength");


if (
    passwordInput &&
    passwordStrength
) {

    passwordInput.addEventListener(
        "input",
        function () {

            const password =
                passwordInput.value;


            if (password.length === 0) {

                passwordStrength.textContent =
                    "";

                passwordStrength.className =
                    "password-strength";

                return;

            }


            let score = 0;


            /* Length */

            if (password.length >= 8) {
                score++;
            }


            /* Lowercase */

            if (/[a-z]/.test(password)) {
                score++;
            }


            /* Uppercase */

            if (/[A-Z]/.test(password)) {
                score++;
            }


            /* Number */

            if (/[0-9]/.test(password)) {
                score++;
            }


            /* Special character */

            if (
                /[@$!%*?&]/.test(password)
            ) {
                score++;
            }


            if (score <= 2) {

                passwordStrength.textContent =
                    "Weak";

                passwordStrength.className =
                    "password-strength weak";

            }

            else if (score <= 4) {

                passwordStrength.textContent =
                    "Medium";

                passwordStrength.className =
                    "password-strength medium";

            }

            else {

                passwordStrength.textContent =
                    "Strong";

                passwordStrength.className =
                    "password-strength strong";

            }

        }
    );

}


/* =========================================================
   FAQ SEARCH
   ========================================================= */

const faqSearch =
    document.getElementById("faqSearch");


if (faqSearch) {

    faqSearch.addEventListener(
        "input",
        function () {

            const searchText =
                faqSearch.value
                    .toLowerCase()
                    .trim();


            const faqItems =
                document.querySelectorAll(
                    ".faq-item"
                );


            faqItems.forEach(
                function (item) {

                    const question =
                        item
                            .querySelector(
                                ".faq-question"
                            )
                            .textContent
                            .toLowerCase();


                    const answer =
                        item
                            .querySelector(
                                ".faq-answer"
                            )
                            .textContent
                            .toLowerCase();


                    if (
                        question.includes(searchText) ||
                        answer.includes(searchText)
                    ) {

                        item.style.display = "";

                    }

                    else {

                        item.style.display = "none";

                    }

                }
            );

        }
    );

}


/* =========================================================
   DEMO FORMS
   ========================================================= */

document
    .querySelectorAll(
        "form:not(#registrationForm)"
    )
    .forEach((form) => {

        form.addEventListener(
            "submit",
            (event) => {

                if (
                    form.getAttribute("action") === "#"
                ) {

                    event.preventDefault();

                    alert(
                        "Form submitted successfully for demonstration."
                    );

                }

            }
        );

    });


/* =========================================================
   PHP SUCCESS / ERROR MESSAGES
   ========================================================= */

const urlParams =
    new URLSearchParams(
        window.location.search
    );


const successMessage =
    document.getElementById(
        "successMessage"
    );


const errorMessage =
    document.getElementById(
        "errorMessage"
    );


/* -------------------------
   SUCCESS
   ------------------------- */

if (
    urlParams.get("success") === "1" &&
    successMessage
) {

    successMessage.textContent =
        "🎉 Registration completed successfully! Your account details have been recorded.";

    successMessage.style.display =
        "block";

    successMessage.classList.add(
        "show"
    );

}


/* -------------------------
   ERROR
   ------------------------- */

const errorText =
    urlParams.get("error");


if (
    errorText &&
    errorMessage
) {

    errorMessage.textContent =
        "⚠️ " + errorText;

    errorMessage.style.display =
        "block";

}