
// ============================================================
// CONTINUOUS BINANCE MONITORING DASHBOARD
// Reads the latest snapshot saved by monitor-receiver.php.
// Separate from the existing HTTP Trigger Tester.
// ============================================================

const MONITOR_DATA_URL = "monitor-data.php";
const MONITOR_REFRESH_MS = 3000;

let previousBtcPrice = null;

function formatMonitorDate(value) {
    if (!value) return "Never";

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? "Unknown"
        : date.toLocaleString();
}

function formatBtcPrice(value) {
    const price = Number(value);

    if (!Number.isFinite(price)) return "--.--";

    return price.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function renderMonitorEvents(events) {
    const container = document.getElementById("monitor-events");

    if (!container) return;

    container.replaceChildren();

    if (!Array.isArray(events) || events.length === 0) {
        const empty = document.createElement("div");

        empty.className = "monitor-event text-sm monitor-muted";
        empty.textContent = "No Binance price data received yet.";

        container.appendChild(empty);
        return;
    }

    events.forEach(event => {
        const card = document.createElement("article");
        card.className = "monitor-event";

        const header = document.createElement("div");
        header.className =
            "flex items-center justify-between gap-3 mb-2";

        const symbol = document.createElement("strong");
        symbol.textContent = event.symbol || "Unknown symbol";

        const source = document.createElement("span");
        source.className = "monitor-pill";
        source.textContent = event.source || "Unknown source";

        header.append(symbol, source);

        const price = document.createElement("div");
        price.className = "text-xl font-semibold noc-accent";

        price.textContent =
            `${formatBtcPrice(event.price)} ${event.currency || "USDT"}`;

        const details = document.createElement("p");
        details.className = "text-xs monitor-muted mt-2";

        details.textContent =
            "Price received: " + formatMonitorDate(event.updated_at);

        card.append(header, price, details);
        container.appendChild(card);
    });
}

async function refreshMonitorDashboard() {
    const badge = document.getElementById("monitor-connection");

    try {
        const response = await fetch(
            `${MONITOR_DATA_URL}?_=${Date.now()}`,
            {
                method: "GET",
                cache: "no-store",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        if (!response.ok) {
            throw new Error(
                `Monitor endpoint returned HTTP ${response.status}`
            );
        }

        const snapshot = await response.json();

        const events = Array.isArray(snapshot.events)
            ? snapshot.events
            : [];

        const latest = events[0] || null;

        // Update number of records
        const countElement = document.getElementById("monitor-count");

        if (countElement) {
            countElement.textContent = snapshot.count ?? events.length;
        }

        // Update last received timestamp
        const updatedElement = document.getElementById("monitor-updated");

        if (updatedElement) {
            updatedElement.textContent =
                formatMonitorDate(snapshot.updated_at);
        }

        // Update main Bitcoin price
        const priceElement = document.getElementById("btc-price");

        if (priceElement && latest) {
            const currentPrice = Number(latest.price);

            priceElement.textContent =
                Number.isFinite(currentPrice)
                    ? `${formatBtcPrice(currentPrice)} ${latest.currency || "USDT"}`
                    : "--.--";

            // Track the previous price for future comparison
            if (Number.isFinite(currentPrice)) {
                previousBtcPrice = currentPrice;
            }
        }

        // Render monitoring records
        renderMonitorEvents(events);

        // Update connection status
        if (badge) {
            if (snapshot.updated_at && latest) {
                badge.textContent = "RECEIVING DATA";
                badge.style.background = "var(--noc-accent-dim)";
                badge.style.color = "var(--noc-accent)";
            } else {
                badge.textContent = "WAITING FOR DATA";
                badge.style.background = "var(--noc-border)";
                badge.style.color = "var(--noc-text-muted)";
            }
        }

    } catch (error) {
        console.error(
            "Monitoring dashboard refresh failed:",
            error
        );

        if (badge) {
            badge.textContent = "CONNECTION ERROR";
            badge.style.background = "rgba(255, 71, 87, 0.15)";
            badge.style.color = "var(--noc-error)";
        }
    }
}

// Start monitoring independently from the HTTP Trigger form
document.addEventListener("DOMContentLoaded", () => {
    refreshMonitorDashboard();

    window.setInterval(
        refreshMonitorDashboard,
        MONITOR_REFRESH_MS
    );
});
