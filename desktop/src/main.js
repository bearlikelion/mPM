const { invoke } = window.__TAURI__.core;

const setupCard = document.getElementById("setup-card");
const loadingCard = document.getElementById("loading-card");
const serverUrlInput = document.getElementById("server-url");
const connectBtn = document.getElementById("connect-btn");
const changeServerBtn = document.getElementById("change-server-btn");
const connectingToMsg = document.getElementById("connecting-to");

const STORAGE_KEY = "mpm_server_url";
const DEFAULT_URL = "https://mpm.arneman.me";

function getSavedUrl() {
  return localStorage.getItem(STORAGE_KEY);
}

function saveUrl(url) {
  // Ensure URL is valid and has a protocol
  let formattedUrl = url.trim();
  if (!formattedUrl.startsWith("http://") && !formattedUrl.startsWith("https://")) {
    formattedUrl = "https://" + formattedUrl;
  }
  localStorage.setItem(STORAGE_KEY, formattedUrl);
  return formattedUrl;
}

function redirectToUrl(url) {
  connectingToMsg.textContent = url;
  // Small delay to show the loading state
  setTimeout(() => {
    window.location.href = url;
  }, 500);
}

function init() {
  const savedUrl = getSavedUrl();

  if (savedUrl) {
    setupCard.classList.add("hidden");
    loadingCard.classList.remove("hidden");
    redirectToUrl(savedUrl);
  } else {
    // If it's the first run, show the setup screen with the default value
    loadingCard.classList.add("hidden");
    setupCard.classList.remove("hidden");
    serverUrlInput.value = DEFAULT_URL;
  }
}

connectBtn.addEventListener("click", () => {
  const url = serverUrlInput.value;
  if (url) {
    const savedUrl = saveUrl(url);
    setupCard.classList.add("hidden");
    loadingCard.classList.remove("hidden");
    redirectToUrl(savedUrl);
  }
});

changeServerBtn.addEventListener("click", () => {
  localStorage.removeItem(STORAGE_KEY);
  loadingCard.classList.add("hidden");
  setupCard.classList.remove("hidden");
});

// Start the app
init();
