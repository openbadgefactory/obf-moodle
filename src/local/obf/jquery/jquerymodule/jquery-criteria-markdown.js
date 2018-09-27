jQuery(document).ready(function($) {
    var simplemde = new SimpleMDE(
            {
                autoDownloadFontAwesome: false,
                element: jQuery('#id_criteriaaddendum')[0],
                renderingConfig: {singleLineBreaks: false},
                toolbar: [
                    "bold", "italic", "heading", "|", 
                    "link", "image" , "|",
                    "unordered-list", "ordered-list", "quote", "|",
                    "preview"
                ],
                spellChecker: false,
            }
    );
);