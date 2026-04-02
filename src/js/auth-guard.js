/**
 * auth-guard.js
 *
 * Included in the <head> of every protected page via header-dashboard.php.
 * Requires `var BASE_URL` to be declared just before this script is loaded.
 *
 * Responsibilities:
 *   1. Redirect to index.html immediately if no valid session is found.
 *   2. Expose window.currentUser  ({ name, username, homeAccountId }).
 *   3. Expose window.signOut()    (clears session, redirects to login.html).
 *   4. Populate #nav-username on DOMContentLoaded.
 */
(function () {
  "use strict";

  var raw = localStorage.getItem("cuenta");

  if (!raw) {
    window.location.replace(BASE_URL + "/");
    return;
  }

  try {
    var user = JSON.parse(raw);
    // Basic sanity check — a valid MSAL account always has homeAccountId.
    if (!user || typeof user.homeAccountId !== "string") {
      throw new Error("malformed");
    }
    window.currentUser = user;
  } catch (e) {
    localStorage.removeItem("cuenta");
    window.location.replace(BASE_URL + "/");
    return;
  }

  // Populate the navbar username badge once the DOM is ready.
  document.addEventListener("DOMContentLoaded", function () {
    var el = document.getElementById("nav-username");
    if (el && window.currentUser) {
      el.textContent = window.currentUser.name || window.currentUser.username;
    }
  });
})();

/**
 * Clear the local session and return to the login page.
 * Called by the sign-out button in header-dashboard.php.
 */
window.signOut = function () {
  localStorage.removeItem("cuenta");
  fetch(BASE_URL + "/api/auth.php?action=logout", { method: "POST" })
    .finally(function () {
      window.location.href = BASE_URL + "/";
    });
};
