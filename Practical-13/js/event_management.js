

document.addEventListener("DOMContentLoaded", () => {
    const eventTableBody = document.getElementById("eventTableBody");
    const eventMessage = document.getElementById("eventApiMessage");
    const refreshButton = document.getElementById("refreshEvents");

    // This script should run only on the admin event management page.
    if (!eventTableBody) {
        return;
    }

    const API = {
        list: "../php/events.php",
        add: "../php/add_event.php",
        edit: "../php/edit_event.php",
        delete: "../php/delete_event.php"
    };

    let events = [];
    let loading = false;

    // Display status messages.
    function showMessage(message, type = "info") {
        if (!eventMessage) {
            console.log(message);
            return;
        }

        eventMessage.textContent = message;
        eventMessage.className = `api-message ${type}`;
        eventMessage.style.display = "block";
        eventMessage.setAttribute("role", "status");
    }

    function clearMessage() {
        if (eventMessage) {
            eventMessage.textContent = "";
            eventMessage.style.display = "none";
        }
    }

    // Safely create table cells.
    function createCell(value) {
        const cell = document.createElement("td");
        cell.textContent =
            value === null || value === undefined || value === ""
                ? "—"
                : String(value);

        return cell;
    }

    function formatDate(dateValue) {
        if (!dateValue) {
            return "—";
        }

        const parts = String(dateValue).split("-");

        if (parts.length !== 3) {
            return dateValue;
        }

        const date = new Date(
            Number(parts[0]),
            Number(parts[1]) - 1,
            Number(parts[2])
        );

        return date.toLocaleDateString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric"
        });
    }

    // Render event records.
    function renderEvents() {
        eventTableBody.replaceChildren();

        if (events.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");

            cell.colSpan = 10;
            cell.textContent = "No events found.";
            row.appendChild(cell);
            eventTableBody.appendChild(row);

            return;
        }

        events.forEach((event) => {
            const row = document.createElement("tr");

            row.appendChild(createCell(event.event_id));

            const posterCell = document.createElement("td");

            if (event.poster) {
                const image = document.createElement("img");

                image.src =
                    `../images/event_posters/${encodeURIComponent(event.poster)}`;

                image.alt = `Poster for ${event.title}`;
                image.width = 70;
                image.loading = "lazy";

                posterCell.appendChild(image);
            } else {
                posterCell.textContent = "No poster";
            }

            row.appendChild(posterCell);
            row.appendChild(createCell(event.title));
            row.appendChild(createCell(event.category));
            row.appendChild(createCell(formatDate(event.event_date)));
            row.appendChild(createCell(event.venue));
            row.appendChild(createCell(event.organizer));
            row.appendChild(createCell(event.status));
            row.appendChild(createCell(event.description));

            const actionsCell = document.createElement("td");

            const editButton = document.createElement("button");
            editButton.type = "button";
            editButton.textContent = "Edit";
            editButton.addEventListener("click", () => {
                showMessage(
                    "Connect this button to your existing Edit Event form.",
                    "info"
                );

                // The edit form will be connected in the next step.
                const editForm = document.getElementById("editEventForm");

                if (editForm) {
                    editForm.elements["event_id"].value = event.event_id;
                    editForm.elements["title"].value = event.title;
                    editForm.elements["category"].value = event.category;
                    editForm.elements["event_date"].value = event.event_date;
                    editForm.elements["venue"].value = event.venue;
                    editForm.elements["organizer"].value = event.organizer;
                    editForm.elements["description"].value =
                        event.description || "";
                    editForm.elements["status"].value = event.status;

                    editForm.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                }
            });

            const deleteButton = document.createElement("button");
            deleteButton.type = "button";
            deleteButton.textContent = "Delete";
            deleteButton.addEventListener("click", async () => {
                await deleteEvent(event.event_id, event.title);
            });

            actionsCell.append(editButton, document.createTextNode(" "), deleteButton);
            row.appendChild(actionsCell);

            eventTableBody.appendChild(row);
        });
    }

    // GET: retrieve events from PHP.
    async function loadEvents() {
        if (loading) {
            return;
        }

        loading = true;
        clearMessage();
        showMessage("Loading events...", "info");

        if (refreshButton) {
            refreshButton.disabled = true;
        }

        try {
            const response = await fetch(API.list, {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json"
                },
                cache: "no-store"
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || `Request failed (${response.status}).`
                );
            }

            events = Array.isArray(data.events) ? data.events : [];
            renderEvents();

            showMessage(
                `Loaded ${events.length} event(s) successfully.`,
                "success"
            );
        } catch (error) {
            console.error("Unable to load events:", error);

            showMessage(
                error.message || "Unable to load events.",
                "error"
            );
        } finally {
            loading = false;

            if (refreshButton) {
                refreshButton.disabled = false;
            }
        }
    }

    // DELETE: delete an event without reloading the page.
    async function deleteEvent(eventId, eventTitle) {
        const confirmed = window.confirm(
            `Are you sure you want to delete "${eventTitle}"?`
        );

        if (!confirmed) {
            return;
        }

        const formData = new FormData();
        formData.append("event_id", eventId);

        showMessage("Deleting event...", "info");

        try {
            const response = await fetch(API.delete, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || `Delete failed (${response.status}).`
                );
            }

            showMessage(data.message || "Event deleted successfully.", "success");

            // Refresh table data without refreshing the entire page.
            await loadEvents();
        } catch (error) {
            console.error("Unable to delete event:", error);

            showMessage(
                error.message || "Unable to delete event.",
                "error"
            );
        }
    }

    // POST: add an event without reloading the page.
    async function addEvent(form) {
        const formData = new FormData(form);

        showMessage("Adding event...", "info");

        try {
            const response = await fetch(API.add, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || `Add failed (${response.status}).`
                );
            }

            form.reset();

            showMessage(data.message || "Event added successfully.", "success");

            await loadEvents();
        } catch (error) {
            console.error("Unable to add event:", error);

            showMessage(
                error.message || "Unable to add event.",
                "error"
            );
        }
    }

    // POST: update an event without reloading the page.
    async function editEvent(form) {
        const formData = new FormData(form);

        showMessage("Updating event...", "info");

        try {
            const response = await fetch(API.edit, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || `Update failed (${response.status}).`
                );
            }

            form.reset();

            showMessage(data.message || "Event updated successfully.", "success");

            await loadEvents();
        } catch (error) {
            console.error("Unable to update event:", error);

            showMessage(
                error.message || "Unable to update event.",
                "error"
            );
        }
    }

    // Attach to forms if they exist on the page.
    const addForm = document.getElementById("addEventForm");
    const editForm = document.getElementById("editEventForm");

    if (addForm) {
        addForm.addEventListener("submit", (event) => {
            event.preventDefault();

            if (!addForm.reportValidity()) {
                return;
            }

            addEvent(addForm);
        });
    }

    if (editForm) {
        editForm.addEventListener("submit", (event) => {
            event.preventDefault();

            if (!editForm.reportValidity()) {
                return;
            }

            editEvent(editForm);
        });
    }

    if (refreshButton) {
        refreshButton.addEventListener("click", loadEvents);
    }

    // Initial asynchronous GET request.
    loadEvents();
});