/**
 * Generate slug from hashtag name with max length 20.
 */
$(document).ready(function () {
    const $nameInput = $("#inputSlug");
    const $slugInput = $("#outputSlug");
    const MAX_LEN = 20;

    if (!$nameInput.length || !$slugInput.length) {
        return;
    }

    // Ensure maxlength attributes
    if (!$nameInput.attr("maxlength")) {
        $nameInput.attr("maxlength", MAX_LEN);
    }
    if (!$slugInput.attr("maxlength")) {
        $slugInput.attr("maxlength", MAX_LEN);
    }

    const slugify = (value) => {
        const cleaned = value
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "")
            .replace(/-+/g, "-");
        return cleaned.slice(0, MAX_LEN);
    };

    let isInternalUpdate = false;

    const applySlug = () => {
        const nameVal = $nameInput.val() || "";
        const slugVal = slugify(nameVal);

        // Chỉ cập nhật và bắn sự kiện khi thay đổi giá trị thực sự
        if ($slugInput.val() !== slugVal) {
            isInternalUpdate = true;
            $slugInput.val(slugVal);
            // Kích hoạt lại sự kiện input để bộ đếm ký tự cập nhật
            $slugInput.trigger("input");
            isInternalUpdate = false;
        }
    };

    // Auto-generate when typing name
    $nameInput.on("input", applySlug);

    // Enforce max length and cleanup when manually editing slug
    $slugInput.on("input", () => {
        if (isInternalUpdate) {
            return;
        }
        const current = $slugInput.val() || "";
        const trimmed = slugify(current);
        if (current !== trimmed) {
            isInternalUpdate = true;
            $slugInput.val(trimmed);
            $slugInput.trigger("input");
            isInternalUpdate = false;
        }
    });

    // Initial run
    applySlug();
});
