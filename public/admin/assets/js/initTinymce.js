var editor_config = {
    path_absolute: "/",
    relative_urls: false,
    selector: "textarea#content",
    codesample_global_prismjs: true,
    plugins:
        "codesample fullscreen preview anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount",
    toolbar:
        "codesample fullscreen preview undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat",
    // codesample_languages: [
    //     { text: "HTML/XML", value: "markup" },
    //     { text: "JavaScript", value: "javascript" },
    //     { text: "CSS", value: "css" },
    //     { text: "PHP", value: "php" },
    //     { text: "Ruby", value: "ruby" },
    //     { text: "Python", value: "python" },
    //     { text: "Java", value: "java" },
    //     { text: "C", value: "c" },
    //     { text: "C#", value: "csharp" },
    //     { text: "C++", value: "cpp" },
    // ],
    file_picker_callback: function (callback, value, meta) {
        var x =
            window.innerWidth ||
            document.documentElement.clientWidth ||
            document.getElementsByTagName("body")[0].clientWidth;
        var y =
            window.innerHeight ||
            document.documentElement.clientHeight ||
            document.getElementsByTagName("body")[0].clientHeight;

        var cmsURL =
            editor_config.path_absolute +
            "laravel-filemanager?editor=" +
            meta.fieldname;
        if (meta.filetype == "image") {
            cmsURL = cmsURL + "&type=Images";
        } else {
            cmsURL = cmsURL + "&type=Files";
        }

        tinyMCE.activeEditor.windowManager.openUrl({
            url: cmsURL,
            title: "Filemanager",
            width: x * 0.8,
            height: y * 0.8,
            resizable: "yes",
            close_previous: "no",
            onMessage: (api, message) => {
                callback(message.content);
            },
        });
    },
};

tinymce.init(editor_config);
