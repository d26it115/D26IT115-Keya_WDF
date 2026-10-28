/**
 * StudentHub - Events Page Script
 * Dynamic fetch, search, sorting, and pagination
 */

document.addEventListener("DOMContentLoaded", () => {
    const eventGrid = document.getElementById("eventGrid");
    const eventMessage = document.getElementById("eventMessage");
    const eventError = document.getElementById("eventError");
    const eventSearch = document.getElementById("eventSearch");
    const eventSort = document.getElementById("eventSort");
    const paginationContainer = document.getElementById("pagination");

    if (!eventGrid) return;

    let allEvents = [];
    let filteredEvents = [];
    let currentPage = 1;
    const itemsPerPage = 6;

    // Format YYYY-MM-DD into readable date (e.g., Oct 15, 2026)
    const formatDate = (dateStr) => {
        try {
            const parts = dateStr.split("-");
            if (parts.length === 3) {
                const date = new Date(parts[0], parts[1] - 1, parts[2]);
                return date.toLocaleDateString("en-US", {
                    month: "short",
                    day: "numeric",
                    year: "numeric"
                });
            }
            return dateStr;
        } catch {
            return dateStr;
        }
    };

    const renderEvents = () => {
        eventGrid.innerHTML = "";

        if (filteredEvents.length === 0) {
            eventGrid.innerHTML = `
                <div class="events-message" style="grid-column: 1 / -1;">
                    No events found matching your search. Try different keywords.
                </div>
            `;
            if (paginationContainer) paginationContainer.innerHTML = "";
            return;
        }

        const totalPages = Math.ceil(filteredEvents.length / itemsPerPage);
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const pageItems = filteredEvents.slice(startIndex, startIndex + itemsPerPage);

        pageItems.forEach((event) => {
            const card = document.createElement("article");
            card.className = "event-card";
            card.innerHTML = `
                <div class="event-header">
                    <h2>${event.title}</h2>
                </div>
                <span class="event-category">${event.category}</span>
                <div class="event-meta">
                    <p class="event-date">📅 ${formatDate(event.date)}</p>
                    <p class="event-venue">📍 ${event.venue}</p>
                    <p class="event-organizer">Organized by: ${event.organizer}</p>
                </div>
                <p class="event-description">${event.description}</p>
            `;
            eventGrid.appendChild(card);
        });

        renderPagination(totalPages);
    };

    const renderPagination = (totalPages) => {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = "";

        if (totalPages <= 1) return;

        // Previous button
        const prevBtn = document.createElement("button");
        prevBtn.type = "button";
        prevBtn.textContent = "❮ Prev";
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener("click", () => {
            if (currentPage > 1) {
                currentPage--;
                renderEvents();
                eventGrid.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
        paginationContainer.appendChild(prevBtn);

        // Page number buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement("button");
            pageBtn.type = "button";
            pageBtn.textContent = i;
            if (i === currentPage) {
                pageBtn.classList.add("active");
            }
            pageBtn.addEventListener("click", () => {
                currentPage = i;
                renderEvents();
                eventGrid.scrollIntoView({ behavior: "smooth", block: "start" });
            });
            paginationContainer.appendChild(pageBtn);
        }

        // Next button
        const nextBtn = document.createElement("button");
        nextBtn.type = "button";
        nextBtn.textContent = "Next ❯";
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener("click", () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderEvents();
                eventGrid.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
        paginationContainer.appendChild(nextBtn);
    };

    const filterAndSort = () => {
        const query = eventSearch ? eventSearch.value.toLowerCase().trim() : "";
        const sortMode = eventSort ? eventSort.value : "date-asc";

        filteredEvents = allEvents.filter((item) => {
            if (!query) return true;
            return (
                item.title.toLowerCase().includes(query) ||
                item.category.toLowerCase().includes(query) ||
                item.venue.toLowerCase().includes(query) ||
                item.organizer.toLowerCase().includes(query) ||
                item.description.toLowerCase().includes(query)
            );
        });

        filteredEvents.sort((a, b) => {
            switch (sortMode) {
                case "date-desc":
                    return new Date(b.date) - new Date(a.date);
                case "name-asc":
                    return a.title.localeCompare(b.title);
                case "name-desc":
                    return b.title.localeCompare(a.title);
                case "date-asc":
                default:
                    return new Date(a.date) - new Date(b.date);
            }
        });

        currentPage = 1;
        renderEvents();
    };

    // Attach search and sort listeners
    if (eventSearch) {
        eventSearch.addEventListener("input", filterAndSort);
    }

    if (eventSort) {
        eventSort.addEventListener("change", filterAndSort);
    }

    // Fetch events JSON data
    fetch("../data/events.json")
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Failed to load events: HTTP ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            allEvents = Array.isArray(data) ? data : [];
            if (eventMessage) eventMessage.style.display = "none";
            filterAndSort();
        })
        .catch((err) => {
            console.error("Error loading events:", err);
            if (eventMessage) eventMessage.style.display = "none";
            if (eventError) {
                eventError.textContent = "Unable to load events at this moment. Please try again later.";
                eventError.style.display = "block";
            }
        });
});