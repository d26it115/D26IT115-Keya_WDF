
"use strict";

(async function initializeDynamicMenu() {
    const menu =
        document.getElementById("navigationMenu") ||
        document.querySelector(".navigation-menu");

    if (!menu) {
        console.warn(
            "StudentHub: navigation menu not found on this page."
        );
        return;
    }

    // Preserve the existing theme toggle before rebuilding the menu.
    const themeToggleItem =
        menu.querySelector("#themeToggle")?.closest("li");

    const currentPage = window.location.pathname.split("/").pop();

    try {
        const response = await fetch("../php/session_status.php", {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store",
            headers: {
                "Accept": "application/json"
            }
        });

        if (!response.ok) {
            throw new Error(
                "Session check failed with status " + response.status
            );
        }

        const session = await response.json();

        const isLoggedIn = session.logged_in === true;
        const role = isLoggedIn
            ? String(session.role || "").trim().toLowerCase()
            : "guest";

        const commonLinks = [
            { text: "Home", href: "index.html" },
            { text: "About", href: "about.html" },
            { text: "Events", href: "events.html" },
            { text: "FAQ", href: "faq.html" },
            { text: "Contact", href: "contact.html" }
        ];

        let links = [...commonLinks];

        if (role === "admin") {
            links.push(
                { text: "Profile", href: "profile.html" },
                { text: "Admin Dashboard", href: "admin_dashboard.html" },
                { text: "Manage Students", href: "student_management.html" },
                { text: "Manage Events", href: "event_management.html" },
                { text: "Registrations", href: "../php/view_registrations.php" },
                { text: "Audit Logs", href: "admin_audit_logs.html" }
            );
        } else if (role === "student") {
            links.push(
                { text: "Feedback", href: "feedback.html" },
                { text: "Profile", href: "profile.html" },
                { text: "Dashboard", href: "dashboard.html" }
            );
        } else {
            links.push(
                { text: "Login", href: "login.html" },
                { text: "Register", href: "register.html" }
            );
        }

        // Build the navigation safely.
        const fragment = document.createDocumentFragment();

        links.forEach(function (item) {
            const li = document.createElement("li");
            const anchor = document.createElement("a");

            anchor.textContent = item.text;
            anchor.href = item.href;

            if (item.href.split("/").pop() === currentPage) {
                anchor.setAttribute("aria-current", "page");
            }

            li.appendChild(anchor);
            fragment.appendChild(li);
        });

        if (isLoggedIn) {
            const logoutItem = document.createElement("li");
            const logoutLink = document.createElement("a");

            logoutLink.href = "../php/logout.php";
            logoutLink.className = "logout-link";
            logoutLink.textContent = "Logout";

            logoutItem.appendChild(logoutLink);
            fragment.appendChild(logoutItem);
        }

        if (themeToggleItem) {
            fragment.appendChild(themeToggleItem);
        }

        menu.replaceChildren(fragment);

        // Ensure the mobile menu reflects its current state.
        const menuButton = document.getElementById("menuButton");

        if (menuButton) {
            menuButton.setAttribute("aria-expanded", "false");
        }

    } catch (error) {
        console.error("StudentHub navigation error:", error);

        // Do not falsely display an authenticated admin menu if
        // the session request fails.
        const themeItem =
            menu.querySelector("#themeToggle")?.closest("li");

        menu.replaceChildren();

        const message = document.createElement("li");
        message.textContent = "Navigation unavailable. Refresh the page.";
        menu.appendChild(message);

        if (themeItem) {
            menu.appendChild(themeItem);
        }
    }
})();
