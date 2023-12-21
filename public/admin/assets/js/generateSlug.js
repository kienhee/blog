const diacriticMap = {
    à: "a",
    á: "a",
    ả: "a",
    ã: "a",
    ạ: "a",
    ă: "a",
    ằ: "a",
    ắ: "a",
    ẳ: "a",
    ẵ: "a",
    ặ: "a",
    â: "a",
    ầ: "a",
    ấ: "a",
    ẩ: "a",
    ẫ: "a",
    ậ: "a",
    è: "e",
    é: "e",
    ẻ: "e",
    ẽ: "e",
    ẹ: "e",
    ê: "e",
    ề: "e",
    ế: "e",
    ể: "e",
    ễ: "e",
    ệ: "e",
    ì: "i",
    í: "i",
    ỉ: "i",
    ĩ: "i",
    ị: "i",
    ò: "o",
    ó: "o",
    ỏ: "o",
    õ: "o",
    ọ: "o",
    ô: "o",
    ồ: "o",
    ố: "o",
    ổ: "o",
    ỗ: "o",
    ộ: "o",
    ơ: "o",
    ờ: "o",
    ớ: "o",
    ở: "o",
    ỡ: "o",
    ợ: "o",
    ù: "u",
    ú: "u",
    ủ: "u",
    ũ: "u",
    ụ: "u",
    ư: "u",
    ừ: "u",
    ứ: "u",
    ử: "u",
    ữ: "u",
    ự: "u",
    ỳ: "y",
    ý: "y",
    ỷ: "y",
    ỹ: "y",
    ỵ: "y",
    đ: "d",
};

function removeDiacritics(inputString) {
    return inputString.replace(
        /[^\u0000-\u007E]/g,
        (char) => diacriticMap[char] || char
    );
}

// Xử lý onchang slug - SEO thân thiện
function generateSlug(inputText) {
    return removeDiacritics(
        inputText
            .toLowerCase() //Chuyển dạng thường
            .replace(/\s+/g, "-") // đổi khoảng trắng thành -
            .replace(/[.,?!'"()`~@#$%^&*;:|||><]/g, "")
            .replace(/^-+|-+$/g, "")
            .trim()
    ); // Loại bỏ dấu gạch ngang ở đầu và cuối chuỗi // xóa kí tự đặc biệt và khoảng trắng
}
function createSlug(title_name, slug_name) {
    let title_input = document.getElementById(title_name);
    let slug_input = document.getElementById(slug_name);
    slug_input.value = generateSlug(title_input.value);
}
