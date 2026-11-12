
// ============================================================
// NOC HTTP TRIGGER
// Universal Response Handling + n8n Integration
// ============================================================

// Configuration
const CONFIG = {
    webhookUrl: "http://localhost:5678/webhook/noc-http-trigger",
    requestTimeout: 30000,
    maxCharacters: 5000
};

// ============================================================
// STATE
// ============================================================

let latestResponse = null;
let pendingRequest = null;
let isSubmitting = false;

// ============================================================
// DOM ELEMENTS
// ============================================================

const inputText = document.getElementById("inputText");
const submitBtn = document.getElementById("submitBtn");
const btnText = document.getElementById("btn-text");
const btnLoading = document.getElementById("btn-loading");
const loadingIndicator = document.getElementById("loadingIndicator");

const responsePanel = document.getElementById("responsePanel");
const responseStatus = document.getElementById("response-status");
const responseTimestamp = document.getElementById("response-timestamp");

const getResult = document.getElementById("getResult");
const postResult = document.getElementById("postResult");

const getStatus = document.getElementById("get-status");
const postStatus = document.getElementById("post-status");

const getJson = document.getElementById("get-json");
const postJson = document.getElementById("post-json");

const executionTimestamp = document.getElementById("execution-timestamp");
const charCount = document.getElementById("charCount");

const n8nStatus = document.getElementById("n8n-status");
const n8nStatusText = document.getElementById("n8n-status-text");

// ============================================================
// CHARACTER COUNTER
// ============================================================

function updateCharCounter() {
    if (!inputText || !charCount) return;

    const length = inputText.value.length;

    charCount.textContent = length;

    charCount.style.color =
        length > CONFIG.maxCharacters
            ? "var(--noc-error)"
            : "var(--noc-text-muted)";
}

// ============================================================
// UNIVERSAL JSON RESPONSE PARSER
// ============================================================

function parseJSONResponse(text) {
    try {
        const parsed = JSON.parse(text);

        if (
            parsed &&
            typeof parsed === "object" &&
            !Array.isArray(parsed)
        ) {
            if (parsed.success === false || parsed.error) {
                return {
                    error: parsed.error || "Unknown server error",
                    status: "error",
                    data: parsed
                };
            }
        }

        return {
            status: "success",
            data: parsed
        };

    } catch (error) {
        return {
            error: "Invalid JSON: " + error.message,
            rawText: text,
            status: "parse_error"
        };
    }
}

// ============================================================
// ERROR NOTIFICATION
// ============================================================

function showError(message) {
    const existingError = document.querySelector(".noc-error-toast");

    if (existingError) {
        existingError.remove();
    }

    const errorToast = document.createElement("div");

    errorToast.className = "noc-error-toast";

    const container = document.createElement("div");
    container.className = "flex items-center gap-2 p-3 noc-error rounded";

    const icon = document.createElement("span");
    icon.textContent = "✕";
    icon.setAttribute("aria-hidden", "true");

    const messageElement = document.createElement("span");
    messageElement.textContent = String(message);

    container.append(icon, messageElement);
    errorToast.appendChild(container);

    errorToast.style.cssText = `
        position: fixed;
        top: 2rem;
        left: 50%;
        transform: translateX(-50%);
        background: var(--noc-bg);
        border: 1px solid var(--noc-error);
        padding: 1rem 1.5rem;
        border-radius: 6px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(errorToast);

    setTimeout(() => {
        errorToast.remove();
    }, 5000);
}

// ============================================================
// HTTP RESPONSE VALIDATION
// ============================================================

async function readHTTPResponse(response) {
    const raw = await response.text();

    const contentType =
        response.headers.get("content-type") || "";

    console.log("HTTP Status:", response.status);
    console.log("Content-Type:", contentType);
    console.log("Raw Response:", raw.slice(0, 1000));

    if (!response.ok) {
        throw new Error(
            `HTTP ${response.status}: ${raw.slice(0, 500)}`
        );
    }

    if (!raw.trim()) {
        throw new Error("Server returned an empty response.");
    }

    const isHTML =
        contentType.toLowerCase().includes("text/html") ||
        /^\s*<!doctype html|^\s*<html[\s>]/i.test(raw);

    if (isHTML) {
        throw new Error(
            "Server returned HTML instead of JSON. " +
            raw.slice(0, 300)
        );
    }

    const parsed = parseJSONResponse(raw);

    if (parsed.status === "parse_error") {
        throw new Error(
            parsed.error +
            "\nResponse: " +
            raw.slice(0, 500)
        );
    }

    if (parsed.status === "error") {
        throw new Error(
            typeof parsed.error === "string"
                ? parsed.error
                : JSON.stringify(parsed.error)
        );
    }

    return parsed.data;
}

// ============================================================
// SUBMIT FORM
// ============================================================

async function submitForm() {

    // Prevent duplicate submissions
    if (isSubmitting) {
        console.warn("Request already in progress.");
        return;
    }

    if (!inputText) {
        showError("Input field was not found.");
        return;
    }

    const text = inputText.value.trim();

    if (!text) {
        showError("Input text cannot be empty.");
        return;
    }

    if (text.length > CONFIG.maxCharacters) {
        showError(
            `Input exceeds the ${CONFIG.maxCharacters} character limit.`
        );
        return;
    }

    isSubmitting = true;

    const controller = new AbortController();
    pendingRequest = controller;

    const timeoutId = setTimeout(() => {
        controller.abort();
    }, CONFIG.requestTimeout);

    // Loading UI
    submitBtn?.classList.add("noc-submit-pending");

    if (btnText) btnText.style.display = "none";
    if (btnLoading) btnLoading.style.display = "inline";

    loadingIndicator?.classList.add("active");

    if (submitBtn) submitBtn.disabled = true;

    try {

        // Build PHP request
        const formData = new FormData();

        formData.append("submit_text", "1");
        formData.append("input_text", text);


        const inputText = document.getElementById("inputText");
        
        const params = new URLSearchParams();

        params.append("submit_text", "1");
        params.append("input_text", inputText.value.trim());

        const response = await fetch(
            `func.php?${params.toString()}`,
            {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                signal: controller.signal
            }
        );
        // Convert FormData into URL parameters
        // const params = new URLSearchParams();

        // for (const [key, value] of formData.entries()) {
        //     params.append(key, value);
        // }

        // // Send request to PHP backend using GET parameters
        // const response = await fetch(`func.php?${params.toString()}`, {
        //     method: "POST",
        //     headers: {
        //         "X-Requested-With": "XMLHttpRequest"
        //     },
        //     signal: controller.signal
        // });

        // // Send request to PHP backend
        // const response = await fetch("func.php", {
        //     method: "POST",
        //     body: formData,
        //     headers: {
        //         "X-Requested-With": "XMLHttpRequest"
        //     },
        //     signal: controller.signal
        // });

        // Read and validate response
        const result = await readHTTPResponse(response);

        latestResponse = result;

        // Update UI
        updateResponseUI(latestResponse);
        showResponsePanel();

    } catch (error) {

        console.error("Submission error:", error);
        console.error(
            "Error stack:",
            error?.stack || "No stack trace available"
        );

        if (error.name === "AbortError") {
            showError(
                `Request timed out after ${CONFIG.requestTimeout / 1000
                } seconds or was cancelled.`
            );
        } else {
            showError(error.message || "Unexpected error occurred.");
        }

        if (responsePanel) {
            responsePanel.style.display = "block";
        }

        if (responseStatus) {
            responseStatus.textContent = "Error";
            responseStatus.className =
                "noc-response-status status-error";
        }

        if (responseTimestamp) {
            responseTimestamp.textContent = "Request failed";
        }

    } finally {

        clearTimeout(timeoutId);

        if (pendingRequest === controller) {
            pendingRequest = null;
        }

        isSubmitting = false;

        submitBtn?.classList.remove("noc-submit-pending");

        if (btnText) btnText.style.display = "";
        if (btnLoading) btnLoading.style.display = "none";

        loadingIndicator?.classList.remove("active");

        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }
}

// ============================================================
// DOWNSTREAM REQUEST STATUS VALIDATION
// ============================================================

function isOperationSuccessful(operation) {

    if (!operation || operation.status !== "success") {
        return false;
    }

    const payload = operation.response;

    if (payload && typeof payload === "object") {

        const statusCode = Number(
            payload.code ??
            payload.statusCode ??
            payload.status_code ??
            0
        );

        if (statusCode >= 400) {
            return false;
        }
    }

    return true;
}

// ============================================================
// SAFE JSON DISPLAY
// ============================================================

function displayJson(element, value) {

    if (!element) return;

    const pre = document.createElement("pre");

    if (value === undefined || value === null) {
        pre.textContent = "No response data";

    } else if (typeof value === "string") {
        pre.textContent = value;

    } else {
        try {
            pre.textContent = JSON.stringify(value, null, 2);
        } catch (error) {
            pre.textContent = "Unable to display response: " + error.message;
        }
    }

    element.replaceChildren(pre);
}

// ============================================================
// UPDATE RESPONSE UI
// ============================================================

function updateResponseUI(response) {

    if (!response || typeof response !== "object") {
        throw new Error("Backend returned an invalid response object.");
    }

    // Execution timestamp
    if (executionTimestamp) {
        executionTimestamp.textContent =
            response.execution_time || "--:--";
    }

    // --------------------------------------------------------
    // GET REQUEST RESULT
    // --------------------------------------------------------

    if (response.get_request) {

        const getOk = isOperationSuccessful(
            response.get_request
        );

        if (getResult) {
            getResult.style.display = "block";
        }

        if (getStatus) {
            getStatus.textContent = getOk ? "Success" : "Error";

            getStatus.className =
                "noc-result-status " +
                (getOk
                    ? "result-status-get"
                    : "result-status-error");
        }

        displayJson(
            getJson,
            response.get_request.response ??
            response.get_request.error ??
            null
        );

    } else if (getResult) {
        getResult.style.display = "none";
    }

    // --------------------------------------------------------
    // POST REQUEST RESULT
    // --------------------------------------------------------

    if (response.post_request) {

        const postOk = isOperationSuccessful(
            response.post_request
        );

        if (postResult) {
            postResult.style.display = "block";
        }

        if (postStatus) {
            postStatus.textContent = postOk ? "Success" : "Error";

            postStatus.className =
                "noc-result-status " +
                (postOk
                    ? "result-status-post"
                    : "result-status-error");
        }

        displayJson(
            postJson,
            response.post_request.response ??
            response.post_request.error ??
            null
        );

    } else if (postResult) {
        postResult.style.display = "none";
    }

    // --------------------------------------------------------
    // INPUT DISPLAY
    // --------------------------------------------------------

    // Use the actual DOM element if it exists.
    // Do not reference an undefined responseData variable.

    const inputDisplay =
        document.getElementById("inputDisplay");

    if (inputDisplay) {
        inputDisplay.textContent = response.input || "";
    }
}

// ============================================================
// SHOW RESPONSE PANEL
// ============================================================

function showResponsePanel() {

    if (!latestResponse) return;

    const getOk = latestResponse.get_request
        ? isOperationSuccessful(latestResponse.get_request)
        : true;

    const postOk = latestResponse.post_request
        ? isOperationSuccessful(latestResponse.post_request)
        : true;

    const overallOk =
        latestResponse.success !== false &&
        getOk &&
        postOk;

    if (responsePanel) {
        responsePanel.style.display = "block";
    }

    if (responseStatus) {

        responseStatus.textContent = overallOk
            ? "Success"
            : "Completed with errors";

        responseStatus.className =
            "noc-response-status " +
            (overallOk
                ? "status-success"
                : "status-error");
    }

    if (responseTimestamp) {

        responseTimestamp.textContent =
            "Executed " +
            (
                latestResponse.execution_time
                    ? latestExecutionTime(
                        latestResponse.execution_time
                    )
                    : "just now"
            );
    }
}

// ============================================================
// EXECUTION TIME FORMATTER
// ============================================================

function latestExecutionTime(timeStr) {

    const date = new Date(timeStr);

    if (Number.isNaN(date.getTime())) {
        return "just now";
    }

    const now = new Date();
    const diff = now - date;

    if (diff < 1000) {
        return "just now";

    } else if (diff < 60000) {
        return Math.round(diff / 1000) + "s ago";

    } else if (diff < 3600000) {
        return Math.round(diff / 60000) + "m ago";

    } else {
        return date.toLocaleTimeString();
    }
}

// ============================================================
// CLEAR INPUT
// ============================================================

function clearInput() {

    if (inputText) {
        inputText.value = "";
    }

    updateCharCounter();
    hideResponsePanel();
}

// ============================================================
// HIDE RESPONSE PANEL
// ============================================================

function hideResponsePanel() {

    if (responsePanel) {
        responsePanel.style.display = "none";
    }

    if (getResult) {
        getResult.style.display = "none";
    }

    if (postResult) {
        postResult.style.display = "none";
    }

    latestResponse = null;
}

// ============================================================
// INITIALIZATION
// ============================================================

document.addEventListener("DOMContentLoaded", function () {

    updateCharCounter();

    const form =
        submitBtn?.closest("form") ||
        document.querySelector("form");

    // Only one submission handler.
    if (form) {

        form.addEventListener("submit", function (event) {
            event.preventDefault();
            submitForm();
        });

    } else if (submitBtn) {

        submitBtn.addEventListener("click", function (event) {
            event.preventDefault();
            submitForm();
        });
    }

    // Update character counter while typing.
    inputText?.addEventListener("input", updateCharCounter);

    // Automatically submit after pasting text.
    inputText?.addEventListener("paste", function () {

        setTimeout(() => {

            updateCharCounter();

            if (
                !isSubmitting &&
                inputText.value.trim()
            ) {
                submitForm();
            }

        }, 500);
    });

    // Clear button.
    const clearBtn = document.querySelector(
        'button[type="button"]'
    );

    if (clearBtn) {
        clearBtn.addEventListener("click", clearInput);
    }

});
