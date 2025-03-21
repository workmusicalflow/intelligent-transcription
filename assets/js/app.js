// Script principal pour l'application

document.addEventListener("DOMContentLoaded", function () {
  // Initialisation des onglets
  initTabs();

  // Initialisation du gestionnaire de fichiers
  initFileUpload();

  // Initialisation de l'overlay de chargement
  initLoadingOverlay();
});

/**
 * Initialise la gestion des onglets
 */
function initTabs() {
  const tabButtons = document.querySelectorAll(".tab-button");

  tabButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      // Extraire l'ID du tab depuis l'attribut data-tab
      const tabId = this.getAttribute("data-tab");
      if (tabId) {
        showTab(tabId);
      }
    });
  });

  // Activer l'onglet Fichier par défaut
  showTab("file-tab");
}

/**
 * Affiche l'onglet spécifié et masque les autres
 *
 * @param {string} tabId - ID de l'onglet à afficher
 */
function showTab(tabId) {
  console.log("Switching to tab:", tabId);

  // Masquer tous les contenus d'onglets
  const tabContents = document.querySelectorAll(".tab-content");
  tabContents.forEach((content) => {
    content.classList.remove("active");
  });

  // Désactiver tous les boutons d'onglets
  const tabButtons = document.querySelectorAll(".tab-button");
  tabButtons.forEach((button) => {
    button.classList.remove("active");
  });

  // Afficher le contenu de l'onglet sélectionné
  const selectedTab = document.getElementById(tabId);
  if (selectedTab) {
    selectedTab.classList.add("active");
  } else {
    console.error("Tab content not found:", tabId);
  }

  // Activer le bouton d'onglet sélectionné
  const selector = `.tab-button[data-tab="${tabId}"]`;
  const activeButton = document.querySelector(selector);
  if (activeButton) {
    activeButton.classList.add("active");
  } else {
    console.error("Tab button not found for:", tabId);
  }
}

/**
 * Initialise les fonctionnalités de téléchargement de fichiers
 */
function initFileUpload() {
  const fileInput = document.getElementById("audio_file");
  const uploadArea = document.getElementById("file-upload-area");
  const fileNameDisplay = document.getElementById("selected-file-name");

  if (!fileInput || !uploadArea) {
    return;
  }

  // Mise à jour du nom du fichier
  fileInput.addEventListener("change", function () {
    if (this.files.length > 0) {
      fileNameDisplay.textContent = this.files[0].name;
      fileNameDisplay.classList.remove("hidden");
    } else {
      fileNameDisplay.classList.add("hidden");
    }
  });

  // Effets visuels pour le drag & drop
  ["dragenter", "dragover"].forEach((eventName) => {
    uploadArea.addEventListener(
      eventName,
      function (e) {
        e.preventDefault();
        uploadArea.classList.add("active");
      },
      false
    );
  });

  ["dragleave", "drop"].forEach((eventName) => {
    uploadArea.addEventListener(
      eventName,
      function (e) {
        e.preventDefault();
        uploadArea.classList.remove("active");

        if (eventName === "drop") {
          fileInput.files = e.dataTransfer.files;
          if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
            fileNameDisplay.classList.remove("hidden");
          }
        }
      },
      false
    );
  });
}

/**
 * Initialise l'overlay de chargement
 */
function initLoadingOverlay() {
  // Ajouter des écouteurs d'événements aux formulaires
  const forms = document.querySelectorAll("form");

  forms.forEach((form) => {
    form.addEventListener("submit", function () {
      showLoadingOverlay();
    });
  });
}

/**
 * Affiche l'overlay de chargement
 */
function showLoadingOverlay() {
  console.log("Showing loading overlay");
  const overlay = document.getElementById("loading-overlay");

  if (overlay) {
    overlay.classList.remove("hidden");
    overlay.classList.add("flex");
  }

  return true;
}
