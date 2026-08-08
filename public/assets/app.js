(function () {
  "use strict";

  function setupNavToggle() {
    var toggles = document.querySelectorAll("[data-nav-toggle]");
    toggles.forEach(function (toggle) {
      var targetId = toggle.getAttribute("data-nav-toggle") || "mainMenu";
      var menu = document.getElementById(targetId);
      if (!menu) return;

      var setOpen = function (open) {
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        if (open) menu.classList.add("is-open"); else menu.classList.remove("is-open");
      };

      toggle.addEventListener("click", function (event) {
        event.preventDefault();
        var expanded = toggle.getAttribute("aria-expanded") === "true";
        setOpen(!expanded);
      });

      document.addEventListener("click", function (event) {
        if (!menu.classList.contains("is-open")) return;
        var target = event.target;
        if (!(target instanceof Node)) return;
        if (menu.contains(target) || toggle.contains(target)) return;
        setOpen(false);
      });

      document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") setOpen(false);
      });
    });
  }

  function maskDigits(value, pattern) {
    var clean = String(value || "").replace(/\D/g, "");
    if (!pattern) return clean;
    if (pattern === "cpf") {
      if (clean.length > 11) clean = clean.slice(0, 11);
      if (clean.length > 9) return clean.slice(0, 3) + "." + clean.slice(3, 6) + "." + clean.slice(6, 9) + "-" + clean.slice(9);
      if (clean.length > 6) return clean.slice(0, 3) + "." + clean.slice(3, 6) + "." + clean.slice(6);
      if (clean.length > 3) return clean.slice(0, 3) + "." + clean.slice(3);
      return clean;
    }
    if (pattern === "phone") {
      if (clean.length > 11) clean = clean.slice(0, 11);
      if (clean.length > 7) return "(" + clean.slice(0, 2) + ") " + clean.slice(2, 7) + "-" + clean.slice(7);
      if (clean.length > 2) return "(" + clean.slice(0, 2) + ") " + clean.slice(2);
      if (clean.length > 0) return "(" + clean;
      return "";
    }
    if (pattern === "cep") {
      if (clean.length > 8) clean = clean.slice(0, 8);
      if (clean.length > 5) return clean.slice(0, 5) + "-" + clean.slice(5);
      return clean;
    }
    if (pattern === "date") {
      if (clean.length > 8) clean = clean.slice(0, 8);
      if (clean.length > 4) return clean.slice(0, 2) + "/" + clean.slice(2, 4) + "/" + clean.slice(4);
      if (clean.length > 2) return clean.slice(0, 2) + "/" + clean.slice(2);
      return clean;
    }
    return clean;
  }

  function setupMaskedInputs() {
    var map = [
      ["data-cpf-input", "cpf"],
      ["data-phone-input", "phone"],
      ["data-cep-input", "cep"],
      ["data-date-input", "date"]
    ];
    map.forEach(function (pair) {
      var sel = pair[0];
      var pattern = pair[1];
      var list = document.querySelectorAll("[" + sel + "]");
      list.forEach(function (input) {
        if (!(input instanceof HTMLInputElement)) return;
        var handler = function () {
          input.value = maskDigits(input.value, pattern);
        };
        input.addEventListener("input", handler);
        input.addEventListener("paste", function () { setTimeout(handler, 0); });
      });
    });
  }

  function setupCandidateCards() {
    var cards = document.querySelectorAll(".candidate-card");
    cards.forEach(function (card) {
      var input = card.querySelector("input[type=radio], input[type=checkbox]");
      if (!input) return;
      var sync = function () {
        if (input.checked) card.classList.add("is-selected"); else card.classList.remove("is-selected");
      };
      input.addEventListener("change", function () {
        if (input.type === "radio") {
          var sameName = document.querySelectorAll('input[type=radio][name="' + input.name + '"]');
          sameName.forEach(function (r) {
            var parent = r.closest && r.closest(".candidate-card");
            if (parent) parent.classList.remove("is-selected");
          });
        }
        sync();
      });
      card.addEventListener("click", function (event) {
        if (event.target === input || (event.target instanceof HTMLElement && event.target.closest("a, button"))) return;
        if (input.disabled) return;
        input.checked = input.type === "radio" ? true : !input.checked;
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
      sync();
    });
  }

  function setupConfirmButtons() {
    document.querySelectorAll("[data-confirm]").forEach(function (el) {
      var handler = function (event) {
        var msg = el.getAttribute("data-confirm") || "Tem certeza?";
        if (!window.confirm(msg)) {
          event.preventDefault();
          event.stopPropagation();
          return false;
        }
        return true;
      };
      el.addEventListener("submit", handler);
      el.addEventListener("click", handler);
    });
  }

  function setupAutoDismiss() {
    var list = document.querySelectorAll("[data-auto-dismiss]");
    list.forEach(function (el) {
      var raw = Number(el.getAttribute("data-auto-dismiss"));
      var ms = Number.isFinite(raw) && raw > 0 ? raw : 4500;
      setTimeout(function () {
        try {
          if (el.classList) {
            el.style.transition = "opacity .25s ease, transform .25s ease";
            el.style.opacity = "0";
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
          } else if (el.parentNode) {
            el.parentNode.removeChild(el);
          }
        } catch (e) {}
      }, ms);
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    setupNavToggle();
    setupMaskedInputs();
    setupCandidateCards();
    setupConfirmButtons();
    setupAutoDismiss();
  });
})();
