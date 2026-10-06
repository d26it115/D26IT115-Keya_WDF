/* =========================================
   STUDENTHUB - PRACTICAL 6
   FETCH API
   SEARCH
   FILTER
   SORT
   PAGINATION
   ========================================= */


/* =========================================
   EVENTS VARIABLES
   ========================================= */

const eventsContainer =
    document.getElementById("eventsContainer");

const eventSearch =
    document.getElementById("eventSearch");

const categoryFilter =
    document.getElementById("categoryFilter");

const sortEvents =
    document.getElementById("sortEvents");

const pagination =
    document.getElementById("pagination");

const eventsLoading =
    document.getElementById("eventsLoading");

const eventsError =
    document.getElementById("eventsError");

const noEvents =
    document.getElementById("noEvents");


/* =========================================
   DATA VARIABLES
   ========================================= */

let allEvents = [];

let filteredEvents = [];

let currentPage = 1;

const eventsPerPage = 3;


/* =========================================
   FETCH JSON DATA
   ========================================= */

async function fetchEvents() {

    if (!eventsContainer) {
        return;
    }

    try {

        eventsLoading.style.display = "block";

        eventsError.style.display = "none";

        noEvents.style.display = "none";


        const response =
            await fetch("../data/events.json");


        if (!response.ok) {

            throw new Error(
                "Unable to load event data."
            );

        }


        const data =
            await response.json();


        allEvents = data;

        filteredEvents = [...allEvents];

        currentPage = 1;


        applyFiltersAndRender();

    }

    catch (error) {

        console.error(
            "Fetch Error:",
            error
        );


        eventsLoading.style.display = "none";

        eventsError.textContent =
            "Unable to load events. Please make sure the JSON file exists and you are running the project using Live Server.";

        eventsError.style.display = "block";

    }

}


/* =========================================
   SEARCH EVENTS
   ========================================= */

function searchEvents(events) {

    const searchText =
        eventSearch.value
            .trim()
            .toLowerCase();


    if (searchText === "") {

        return events;

    }


    return events.filter(
        function (event) {

            return (

                event.title
                    .toLowerCase()
                    .includes(searchText)

                ||

                event.description
                    .toLowerCase()
                    .includes(searchText)

                ||

                event.organizer
                    .toLowerCase()
                    .includes(searchText)

            );

        }
    );

}


/* =========================================
   FILTER EVENTS BY CATEGORY
   ========================================= */

function filterByCategory(events) {

    const category =
        categoryFilter.value;


    if (category === "all") {

        return events;

    }


    return events.filter(
        function (event) {

            return event.category === category;

        }
    );

}


/* =========================================
   SORT EVENTS
   ========================================= */

function sortEventList(events) {

    const sortType =
        sortEvents.value;


    const sortedEvents =
        [...events];


    if (sortType === "dateAsc") {

        sortedEvents.sort(
            function (a, b) {

                return new Date(a.date)
                    -
                    new Date(b.date);

            }
        );

    }


    else if (sortType === "dateDesc") {

        sortedEvents.sort(
            function (a, b) {

                return new Date(b.date)
                    -
                    new Date(a.date);

            }
        );

    }


    else if (sortType === "titleAsc") {

        sortedEvents.sort(
            function (a, b) {

                return a.title.localeCompare(
                    b.title
                );

            }
        );

    }


    else if (sortType === "titleDesc") {

        sortedEvents.sort(
            function (a, b) {

                return b.title.localeCompare(
                    a.title
                );

            }
        );

    }


    return sortedEvents;

}


/* =========================================
   APPLY SEARCH + FILTER + SORT
   ========================================= */

function applyFiltersAndRender() {

    let result =
        [...allEvents];


    result =
        searchEvents(result);


    result =
        filterByCategory(result);


    result =
        sortEventList(result);


    filteredEvents =
        result;


    currentPage = 1;


    renderEvents();

}


/* =========================================
   RENDER EVENTS
   ========================================= */

function renderEvents() {

    eventsLoading.style.display = "none";

    eventsContainer.innerHTML = "";


    if (filteredEvents.length === 0) {

        noEvents.style.display = "block";

        pagination.innerHTML = "";

        return;

    }


    noEvents.style.display = "none";


    const startIndex =
        (currentPage - 1)
        * eventsPerPage;


    const endIndex =
        startIndex
        + eventsPerPage;


    const pageEvents =
        filteredEvents.slice(
            startIndex,
            endIndex
        );


    pageEvents.forEach(
        function (event) {

            const card =
                document.createElement("article");


            card.className =
                "event-card";


            card.innerHTML = `

                <span class="event-category">
                    ${event.category}
                </span>

                <h2>
                    ${event.title}
                </h2>

                <p class="event-date">
                    📅 ${formatDate(event.date)}
                </p>

                <p class="event-venue">
                    📍 ${event.venue}
                </p>

                <p class="event-organizer">
                    👤 ${event.organizer}
                </p>

                <p class="event-description">
                    ${event.description}
                </p>

            `;


            eventsContainer.appendChild(card);

        }
    );


    renderPagination();

}


/* =========================================
   FORMAT DATE
   ========================================= */

function formatDate(dateString) {

    const date =
        new Date(dateString);


    return date.toLocaleDateString(
        "en-IN",
        {
            day: "2-digit",
            month: "long",
            year: "numeric"
        }
    );

}


/* =========================================
   PAGINATION
   ========================================= */

function renderPagination() {

    pagination.innerHTML = "";


    const totalPages =
        Math.ceil(
            filteredEvents.length
            /
            eventsPerPage
        );


    if (totalPages <= 1) {

        return;

    }


    /* PREVIOUS BUTTON */

    const previousButton =
        document.createElement("button");


    previousButton.textContent =
        "❮ Previous";


    previousButton.disabled =
        currentPage === 1;


    previousButton.addEventListener(
        "click",
        function () {

            if (currentPage > 1) {

                currentPage--;

                renderEvents();

            }

        }
    );


    pagination.appendChild(
        previousButton
    );


    /* PAGE BUTTONS */

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        const pageButton =
            document.createElement("button");


        pageButton.textContent =
            page;


        if (page === currentPage) {

            pageButton.classList.add(
                "active"
            );

        }


        pageButton.addEventListener(
            "click",
            function () {

                currentPage =
                    page;

                renderEvents();

            }
        );


        pagination.appendChild(
            pageButton
        );

    }


    /* NEXT BUTTON */

    const nextButton =
        document.createElement("button");


    nextButton.textContent =
        "Next ❯";


    nextButton.disabled =
        currentPage === totalPages;


    nextButton.addEventListener(
        "click",
        function () {

            if (
                currentPage
                <
                totalPages
            ) {

                currentPage++;

                renderEvents();

            }

        }
    );


    pagination.appendChild(
        nextButton
    );

}


/* =========================================
   SEARCH EVENT LISTENER
   ========================================= */

if (eventSearch) {

    eventSearch.addEventListener(
        "input",
        function () {

            applyFiltersAndRender();

        }
    );

}


/* =========================================
   CATEGORY EVENT LISTENER
   ========================================= */

if (categoryFilter) {

    categoryFilter.addEventListener(
        "change",
        function () {

            applyFiltersAndRender();

        }
    );

}


/* =========================================
   SORT EVENT LISTENER
   ========================================= */

if (sortEvents) {

    sortEvents.addEventListener(
        "change",
        function () {

            applyFiltersAndRender();

        }
    );

}


/* =========================================
   START FETCHING EVENTS
   ========================================= */

if (eventsContainer) {

    fetchEvents();

}