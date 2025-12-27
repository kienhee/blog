"use strict";

$(function () {
    // Form validation
    const form = document.getElementById("form_role");
    if (form) {
        // Basic validation
        form.addEventListener("submit", function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    }
});

