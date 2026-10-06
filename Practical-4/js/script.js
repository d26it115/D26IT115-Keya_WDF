/* =========================================
   STUDENTHUB
   PRACTICAL 4
   JAVASCRIPT DOM MANIPULATION
   ========================================= */


/* =========================================
   1. HAMBURGER MENU
   ========================================= */

const menuButton =
    document.getElementById("menuButton");

const navigationMenu =
    document.getElementById("navigationMenu");


if (menuButton && navigationMenu) {

    menuButton.addEventListener(
        "click",
        function () {

            navigationMenu.classList.toggle("show");


            const menuIsOpen =
                navigationMenu.classList.contains("show");


            menuButton.setAttribute(
                "aria-expanded",
                menuIsOpen
            );


            if (menuIsOpen) {

                menuButton.setAttribute(
                    "aria-label",
                    "Close navigation menu"
                );

                menuButton.textContent = "✕";

            }

            else {

                menuButton.setAttribute(
                    "aria-label",
                    "Open navigation menu"
                );

                menuButton.textContent = "☰";

            }

        }
    );

}



/* =========================================
   2. LIGHT / DARK THEME SWITCHER
   ========================================= */

const themeButton =
    document.getElementById("themeButton");


function applyTheme() {

    const savedTheme =
        localStorage.getItem("studenthubTheme");


    if (savedTheme === "dark") {

        document.body.classList.add("dark-mode");

        if (themeButton) {

            themeButton.textContent =
                "☀️ Light Mode";

        }

    }

    else {

        document.body.classList.remove(
            "dark-mode"
        );

        if (themeButton) {

            themeButton.textContent =
                "🌙 Dark Mode";

        }

    }

}


applyTheme();


if (themeButton) {

    themeButton.addEventListener(
        "click",
        function () {

            const darkMode =
                document.body.classList.toggle(
                    "dark-mode"
                );


            if (darkMode) {

                localStorage.setItem(
                    "studenthubTheme",
                    "dark"
                );

                themeButton.textContent =
                    "☀️ Light Mode";

            }

            else {

                localStorage.setItem(
                    "studenthubTheme",
                    "light"
                );

                themeButton.textContent =
                    "🌙 Dark Mode";

            }

        }
    );

}



/* =========================================
   3. COLLAPSIBLE FAQ
   ========================================= */

const faqQuestions =
    document.querySelectorAll(
        ".faq-question"
    );


faqQuestions.forEach(
    function (question) {

        question.addEventListener(
            "click",
            function () {


                const answer =
                    question.nextElementSibling;


                const icon =
                    question.querySelector(
                        ".faq-icon"
                    );


                const isCurrentlyOpen =
                    answer.classList.contains(
                        "show"
                    );


                /*
                 * Close all FAQ answers
                 */

                document
                    .querySelectorAll(".faq-answer")
                    .forEach(
                        function (item) {

                            item.classList.remove(
                                "show"
                            );

                        }
                    );


                /*
                 * Reset all buttons
                 */

                document
                    .querySelectorAll(
                        ".faq-question"
                    )
                    .forEach(
                        function (item) {

                            item.setAttribute(
                                "aria-expanded",
                                "false"
                            );

                            const itemIcon =
                                item.querySelector(
                                    ".faq-icon"
                                );

                            if (itemIcon) {

                                itemIcon.textContent =
                                    "+";

                            }

                        }
                    );


                /*
                 * Open selected FAQ
                 */

                if (!isCurrentlyOpen) {

                    answer.classList.add(
                        "show"
                    );


                    question.setAttribute(
                        "aria-expanded",
                        "true"
                    );


                    if (icon) {

                        icon.textContent = "−";

                    }

                }

            }
        );

    }
);



/* =========================================
   4. MODAL POPUP
   ========================================= */

const modal =
    document.getElementById("modal");


const openModal =
    document.getElementById("openModal");


const closeModal =
    document.getElementById("closeModal");


const modalOkay =
    document.getElementById("modalOkay");


function showModal() {

    if (!modal) {
        return;
    }


    modal.classList.add("show");


    document.body.style.overflow =
        "hidden";

}


function hideModal() {

    if (!modal) {
        return;
    }


    modal.classList.remove("show");


    document.body.style.overflow =
        "";

}


if (openModal) {

    openModal.addEventListener(
        "click",
        showModal
    );

}


if (closeModal) {

    closeModal.addEventListener(
        "click",
        hideModal
    );

}


if (modalOkay) {

    modalOkay.addEventListener(
        "click",
        hideModal
    );

}


/*
 * Close modal when clicking
 * outside the modal content
 */

if (modal) {

    modal.addEventListener(
        "click",
        function (event) {

            if (
                event.target === modal
            ) {

                hideModal();

            }

        }
    );

}


/*
 * Close modal using Escape key
 */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Escape" &&
            modal &&
            modal.classList.contains("show")
        ) {

            hideModal();

        }

    }
);



/* =========================================
   5. HOME PAGE CONTENT SLIDER
   ========================================= */

const homeSlides =
    document.querySelectorAll(
        ".slide"
    );


const previousSlide =
    document.getElementById(
        "previousSlide"
    );


const nextSlide =
    document.getElementById(
        "nextSlide"
    );


const slideNumber =
    document.getElementById(
        "slideNumber"
    );


let currentSlide = 0;


function displayHomeSlide(index) {

    if (homeSlides.length === 0) {

        return;

    }


    if (index >= homeSlides.length) {

        currentSlide = 0;

    }

    else if (index < 0) {

        currentSlide =
            homeSlides.length - 1;

    }

    else {

        currentSlide = index;

    }


    homeSlides.forEach(
        function (slide, index) {

            slide.classList.remove(
                "active"
            );


            if (index === currentSlide) {

                slide.classList.add(
                    "active"
                );

            }

        }
    );


    if (slideNumber) {

        slideNumber.textContent =
            (currentSlide + 1) +
            " / " +
            homeSlides.length;

    }

}


if (previousSlide) {

    previousSlide.addEventListener(
        "click",
        function () {

            displayHomeSlide(
                currentSlide - 1
            );

        }
    );

}


if (nextSlide) {

    nextSlide.addEventListener(
        "click",
        function () {

            displayHomeSlide(
                currentSlide + 1
            );

        }
    );

}


/*
 * Automatic home slider
 */

if (homeSlides.length > 0) {

    setInterval(
        function () {

            displayHomeSlide(
                currentSlide + 1
            );

        },
        5000
    );

}



/* =========================================
   6. EVENTS PAGE CONTENT SLIDER
   ========================================= */

const eventSlides =
    document.querySelectorAll(
        ".event-slide"
    );


const previousEvent =
    document.getElementById(
        "previousEvent"
    );


const nextEvent =
    document.getElementById(
        "nextEvent"
    );


const eventNumber =
    document.getElementById(
        "eventNumber"
    );


let currentEvent = 0;


function displayEvent(index) {

    if (eventSlides.length === 0) {

        return;

    }


    if (index >= eventSlides.length) {

        currentEvent = 0;

    }

    else if (index < 0) {

        currentEvent =
            eventSlides.length - 1;

    }

    else {

        currentEvent = index;

    }


    eventSlides.forEach(
        function (slide, index) {

            slide.classList.remove(
                "active"
            );


            if (index === currentEvent) {

                slide.classList.add(
                    "active"
                );

            }

        }
    );


    if (eventNumber) {

        eventNumber.textContent =
            (currentEvent + 1) +
            " / " +
            eventSlides.length;

    }

}


if (previousEvent) {

    previousEvent.addEventListener(
        "click",
        function () {

            displayEvent(
                currentEvent - 1
            );

        }
    );

}


if (nextEvent) {

    nextEvent.addEventListener(
        "click",
        function () {

            displayEvent(
                currentEvent + 1
            );

        }
    );

}


/*
 * Automatic event slider
 */

if (eventSlides.length > 0) {

    setInterval(
        function () {

            displayEvent(
                currentEvent + 1
            );

        },
        5000
    );

}



/* =========================================
   7. NOTIFICATION BANNER
   ========================================= */

const notificationBanner =
    document.getElementById(
        "notificationBanner"
    );


const closeNotification =
    document.getElementById(
        "closeNotification"
    );


if (notificationBanner) {

    /*
     * Show notification after
     * page loads
     */

    setTimeout(
        function () {

            notificationBanner.classList.add(
                "show"
            );

        },
        500
    );

}


if (closeNotification) {

    closeNotification.addEventListener(
        "click",
        function () {

            notificationBanner.classList.remove(
                "show"
            );

        }
    );

}