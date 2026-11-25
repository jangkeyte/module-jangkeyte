<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/adminlte.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
@stack('scripts')

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sidebarWrapper = document.querySelector(".sidebar-wrapper");
        if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== "undefined") {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                scrollbars: {
                    theme: "os-theme-light",
                    autoHide: "leave",
                    clickScroll: true,
                },
            });
        }

        if (typeof Quill !== "undefined") {
            document.querySelectorAll("[data-quill-editor]").forEach(function (editorEl) {
                if (editorEl.dataset.quillInitialized === "1") {
                    return;
                }

                const targetId = editorEl.dataset.quillTarget;
                const hiddenInput = targetId ? document.getElementById(targetId) : null;
                if (!hiddenInput) {
                    return;
                }

                editorEl.dataset.quillInitialized = "1";

                const quill = new Quill(editorEl, {
                    theme: "snow",
                    modules: {
                        toolbar: [
                            [{ header: [1, 2, 3, false] }],
                            ["bold", "italic", "underline", "strike"],
                            [{ list: "ordered" }, { list: "bullet" }],
                            ["link", "blockquote", "code-block"],
                            ["clean"],
                        ],
                    },
                });

                if (hiddenInput.value) {
                    const delta = quill.clipboard.convert({ html: hiddenInput.value });
                    quill.setContents(delta, "silent");
                }

                const syncValue = function () {
                    const hasEmbed = Boolean(quill.root.querySelector("img, video, iframe"));
                    const isEmpty = quill.getText().trim() === "" && !hasEmbed;
                    if (isEmpty) {
                        hiddenInput.value = "";
                    } else {
                        hiddenInput.value = typeof quill.getSemanticHTML === "function"
                            ? quill.getSemanticHTML()
                            : quill.root.innerHTML;
                    }
                };

                quill.on("text-change", syncValue);

                const parentForm = editorEl.closest("form");
                if (parentForm) {
                    parentForm.addEventListener("submit", syncValue);
                }
            });
        }

        document.querySelectorAll("[data-media-file-input]").forEach(function (fileInput) {
            fileInput.addEventListener("change", function () {
                const file = fileInput.files && fileInput.files[0];
                const pathInputId = fileInput.dataset.mediaPathInput;
                const previewId = fileInput.dataset.mediaPreview;
                const labelId = fileInput.dataset.mediaLabel;

                if (pathInputId) {
                    const pathInput = document.getElementById(pathInputId);
                    if (pathInput) {
                        pathInput.value = "";
                    }
                }

                if (labelId) {
                    const labelEl = document.getElementById(labelId);
                    const wrapEl = document.getElementById(labelId + "_wrap");
                    if (labelEl) {
                        labelEl.textContent = "";
                    }
                    if (wrapEl) {
                        wrapEl.classList.add("d-none");
                    }
                }

                if (file && previewId && file.type && file.type.startsWith("image/")) {
                    const previewEl = document.getElementById(previewId);
                    if (previewEl && previewEl.tagName === "IMG") {
                        previewEl.src = URL.createObjectURL(file);
                        previewEl.classList.remove("d-none", "opacity-50");
                    }
                }
            });
        });

        document.addEventListener("jangkeyte:media-selected", function (event) {
            const pathInput = event.target;
            if (!pathInput || !pathInput.id) {
                return;
            }

            const baseField = pathInput.id.replace(/_media_path$/, "");
            const fileInput = document.getElementById(baseField);
            if (fileInput && fileInput.type === "file") {
                fileInput.value = "";
            }

            const labelEl = document.getElementById(baseField + "_media_label");
            const wrapEl = document.getElementById(baseField + "_media_label_wrap");
            const relPath = event.detail && event.detail.relative_path ? event.detail.relative_path : pathInput.value;

            if (labelEl) {
                labelEl.textContent = relPath || "";
            }
            if (wrapEl) {
                wrapEl.classList.toggle("d-none", !relPath);
            }
        });
    });
</script>

@if($errors->any())
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            @foreach($errors->all() as $error)
                iziToast.error({ position: 'topCenter', message: @json($error) });
            @endforeach
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            iziToast.error({ position: 'topRight', message: @json(session('error')) });
        });
    </script>
@endif

@if(session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            iziToast.success({ position: 'topRight', message: @json(session('success')) });
        });
    </script>
@endif
