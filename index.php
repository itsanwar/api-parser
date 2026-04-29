<?php
include "./includes/header.php";
?>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="json-viewer/jquery.json-viewer.js"></script>
<link href="json-viewer/jquery.json-viewer.css" type="text/css" rel="stylesheet">

<script>
    document.title = `🔴 RAW API PARSER TOOL`;
</script>

<!-- ============================================================ -->
<!-- ============================================================ -->
<!-- FULLSCREEN IDE LAYOUT -->
<!-- ============================================================ -->
<div class="h-screen w-full flex flex-col bg-body p-4 md:p-6 gap-4 overflow-hidden">


    <!-- ============================================================ -->
    <!-- HTML VIEWER MODAL -->
    <!-- ============================================================ -->
    <div class='html-viewer hide fixed inset-0 z-[9999] flex items-center justify-center p-4'>
        <div class='absolute inset-0 bg-black/70 backdrop-blur-sm'></div>
        <div
            class='relative w-[95vw] max-w-5xl h-[85vh] bg-surface rounded-2xl shadow-modal flex flex-col overflow-hidden border border-border'>
            <div class='flex items-center justify-between px-5 py-3 border-b border-border'>
                <div class='flex items-center gap-2'>
                    <i class='fas fa-code text-primary text-sm'></i>
                    <span class='text-txt font-semibold text-sm tracking-wide uppercase'>HTML Viewer</span>
                </div>
                <button onclick='closeDiv(this)'
                    class='close-btn w-8 h-8 flex items-center justify-center rounded-lg bg-card hover:bg-red-500/20 text-muted hover:text-red-400 transition-all cursor-pointer'>
                    <i class='fas fa-times text-sm'></i>
                </button>
            </div>
            <iframe class='html-vw-body flex-1 m-3 rounded-xl bg-white border-none'
                sandbox='allow-same-origin'></iframe>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- JSON PARSER MODAL -->
    <!-- ============================================================ -->
    <div class='json-parser hide fixed inset-0 z-[9999] flex items-center justify-center p-4'>
        <div class='absolute inset-0 bg-black/70 backdrop-blur-sm'></div>
        <div
            class='relative w-[95vw] max-w-5xl h-[85vh] bg-surface rounded-2xl shadow-modal flex flex-col overflow-hidden border border-border'>
            <div class='flex items-center justify-between px-5 py-3 border-b border-border'>
                <div class='flex items-center gap-2'>
                    <i class='fas fa-brackets-curly text-primary text-sm'></i>
                    <span class='text-txt font-semibold text-sm tracking-wide uppercase'>JSON Parser</span>
                </div>
                <button onclick='closeDiv(this)'
                    class='close-btn w-8 h-8 flex items-center justify-center rounded-lg bg-card hover:bg-red-500/20 text-muted hover:text-red-400 transition-all cursor-pointer'>
                    <i class='fas fa-times text-sm'></i>
                </button>
            </div>
            <div class='json-viewer flex items-center gap-3 px-5 py-2 border-b border-border-dim'>
                <div
                    class='json-output flex-1 font-mono text-sm text-json-string bg-card px-4 py-2 rounded-lg truncate'>
                    $data = $json["data"];</div>
                <select id='json-lang-select'
                    class='bg-card text-txt text-sm border border-border rounded-lg px-3 py-2 outline-none cursor-pointer hover:border-primary transition-colors'>
                    <option value='js'>Javascript</option>
                    <option selected='selected' value='php'>Php</option>
                </select>
                <button id='json-copy-btn'
                    class='w-9 h-9 flex items-center justify-center rounded-lg bg-card border border-border text-primary hover:bg-primary-glow transition-all cursor-pointer'>
                    <i class='fas fa-copy text-sm'></i>
                </button>
            </div>
            <pre id='json-renderer'
                class='json-document flex-1 overflow-y-auto overflow-x-hidden mx-4 my-3 bg-card rounded-xl p-5 text-txt text-sm text-left'></pre>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TOP BAR: Language + Options -->
    <!-- ============================================================ -->
    <div
        class='output-box flex-shrink-0 flex items-center justify-between px-6 py-4 rounded-xl bg-surface/40 backdrop-blur-md border border-border shadow-sm'>
        <!-- Left: Language -->
        <div class='lang-select flex items-center gap-3'>
            <span class='text-muted/70 text-xs font-medium uppercase tracking-wider'>Lang</span>
            <select id='lang-select'
                class='bg-card text-txt text-sm border border-border rounded-lg px-3 py-2 outline-none cursor-pointer hover:border-primary focus:border-primary transition-colors'>
                <option value=''>Select</option>
                <option value='js'>Javascript</option>
                <option selected='selected' value='php'>Php</option>
            </select>
        </div>
        <!-- Center: Headers Toggle -->
        <label class='flex items-center gap-2.5 cursor-pointer select-none'>
            <span class='text-xs font-semibold text-muted uppercase tracking-wider'>Return Headers</span>
            <input type='checkbox' id='return-headers' class='toggle-input hidden'>
            <div class='toggle-switch'></div>
        </label>
        <!-- Right: Input Length -->
        <span id='opt-len' class='font-mono text-xs text-muted/60'>Length: 0</span>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN LAYOUT: Input + Arrow + Toolbox Side-by-Side -->
    <!-- ============================================================ -->
    <div class='flex-1 flex gap-4 items-stretch min-h-0 relative'>

        <!-- LEFT: Main Input Panel -->
        <div
            class='flex-1 min-w-0 flex flex-col bg-surface/40 backdrop-blur-md rounded-2xl border border-border shadow-card overflow-hidden'>
            <!-- Header for Editor -->
            <div class='px-4 py-3 bg-surface/80 border-b border-border/50 flex items-center justify-between'>
                <div class='flex items-center gap-2'>
                    <div class='w-3 h-3 rounded-full bg-red-500/80'></div>
                    <div class='w-3 h-3 rounded-full bg-yellow-500/80'></div>
                    <div class='w-3 h-3 rounded-full bg-green-500/80'></div>
                    <span class='ml-3 text-xs font-mono text-muted'>Request Body</span>
                </div>
            </div>
            <div class='input-box relative flex-1 bg-[#0f1219]'>
                <textarea id='main-textarea' spellcheck='false' placeholder='Paste your raw API request here...'
                    class='w-full h-full bg-transparent text-txt/90 text-[13px] font-mono p-5 outline-none resize-none tracking-wide placeholder:text-muted/30 focus:border-primary transition-all custom-scrollbar'></textarea>
                <button id='copy-btn'
                    class='absolute bottom-4 right-4 w-9 h-9 flex items-center justify-center rounded-xl bg-surface/90 border border-border-dim text-primary/70 hover:text-primary hover:bg-primary/10 transition-all cursor-pointer shadow-lg'>
                    <i class='fas fa-copy text-sm'></i>
                </button>
            </div>
            <!-- Action Buttons -->
            <div class='px-5 py-4 bg-surface/80 border-t border-border/50 flex justify-between items-center'>
                <div class='flex gap-3'>
                    <button id='parse-btn'
                        class='main-btn px-6 py-2.5 rounded-lg text-sm font-semibold tracking-wide border border-primary/50 text-primary bg-primary/5 hover:bg-primary/20 hover:border-primary transition-all duration-200 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed'>
                        <i class='fas fa-terminal mr-2 text-xs'></i>Parse Api
                    </button>
                    <button id='run-btn'
                        class='main-btn px-6 py-2.5 rounded-lg text-sm font-semibold tracking-wide border border-emerald-500/50 text-emerald-400 bg-emerald-500/5 hover:bg-emerald-500/20 hover:border-emerald-500 transition-all duration-200 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed shadow-[0_0_15px_rgba(16,185,129,0.15)] hover:shadow-[0_0_20px_rgba(16,185,129,0.3)]'>
                        <i class='fas fa-play mr-2 text-xs'></i>Run Api
                    </button>
                </div>
                <!-- Transfer Button inside Left Panel now -->
                <button id='transfer-btn'
                    class='h-10 px-4 flex items-center justify-center gap-2 rounded-lg bg-card border border-border text-primary/80 hover:text-primary hover:bg-primary-glow hover:border-primary transition-all duration-200 cursor-pointer group'
                    title='Copy to ToolBox'>
                    <span class='text-xs font-semibold uppercase tracking-wider'>Transfer</span>
                    <i class='fas fa-chevron-right text-sm group-hover:translate-x-1 transition-transform'></i>
                </button>
            </div>
        </div>

        <!-- RIGHT: API Toolbox Panel (always visible) -->
        <div
            class='output w-[380px] flex-shrink-0 flex flex-col bg-surface/50 backdrop-blur-xl rounded-2xl border border-border shadow-card overflow-hidden'>
            <!-- Header -->
            <div
                class='output-header flex items-center justify-between px-5 py-3.5 bg-primary/10 border-b border-border/50'>
                <div class='output-title text-primary font-semibold text-sm tracking-wide flex items-center gap-2.5'>
                    ToolBox
                </div>
                <div class='output-header-actions flex items-center gap-2'>
                    <div class='undo-redo flex gap-1 bg-surface rounded-lg p-0.5 border border-border/50'>
                        <button id='output-undo' style='filter: grayscale(100%)'
                            class='w-7 h-7 flex items-center justify-center rounded-md text-muted hover:text-primary hover:bg-primary/10 transition-all cursor-pointer'
                            title='Undo'>
                            <i class='fas fa-undo text-[11px]'></i>
                        </button>
                        <button id='output-redo' style='filter: grayscale(100%)'
                            class='w-7 h-7 flex items-center justify-center rounded-md text-muted hover:text-primary hover:bg-primary/10 transition-all cursor-pointer'
                            title='Redo'>
                            <i class='fas fa-redo text-[11px]'></i>
                        </button>
                    </div>
                    <button id='output-copy-btn'
                        class='w-8 h-8 flex items-center justify-center rounded-lg bg-surface border border-border/50 text-primary/70 hover:text-primary hover:bg-primary/10 transition-all cursor-pointer shadow-sm'
                        title='Copy Output'>
                        <i class='fas fa-copy text-[11px]'></i>
                    </button>
                </div>
            </div>
            <!-- Tool Buttons Grid -->
            <div class='toolbox-buttons grid grid-cols-4 gap-2 px-4 py-4 bg-card/30 border-b border-border/30'>
                <button
                    class='tool-btn bg-surface border border-border/50 text-txt/90 rounded-lg px-2 py-2 text-[11px] font-semibold tracking-wider hover:bg-primary/10 hover:border-primary/50 hover:text-primary hover:shadow-[0_0_10px_rgba(20,184,166,0.15)] transition-all cursor-pointer text-center'
                    data-tool='b64-enc'>B64↑</button>
                <button
                    class='tool-btn bg-surface border border-border/50 text-txt/90 rounded-lg px-2 py-2 text-[11px] font-semibold tracking-wider hover:bg-primary/10 hover:border-primary/50 hover:text-primary hover:shadow-[0_0_10px_rgba(20,184,166,0.15)] transition-all cursor-pointer text-center'
                    data-tool='b64-dec'>B64↓</button>
                <button
                    class='tool-btn bg-surface border border-border/50 text-txt/90 rounded-lg px-2 py-2 text-[11px] font-semibold tracking-wider hover:bg-primary/10 hover:border-primary/50 hover:text-primary hover:shadow-[0_0_10px_rgba(20,184,166,0.15)] transition-all cursor-pointer text-center'
                    data-tool='url-enc'>URL↑</button>
                <button
                    class='tool-btn bg-surface border border-border/50 text-txt/90 rounded-lg px-2 py-2 text-[11px] font-semibold tracking-wider hover:bg-primary/10 hover:border-primary/50 hover:text-primary hover:shadow-[0_0_10px_rgba(20,184,166,0.15)] transition-all cursor-pointer text-center'
                    data-tool='url-dec'>URL↓</button>
                <button
                    class='tool-btn bg-surface border border-border/50 text-txt/90 rounded-lg px-2 py-2 text-[11px] font-semibold tracking-wider hover:bg-primary/10 hover:border-primary/50 hover:text-primary hover:shadow-[0_0_10px_rgba(20,184,166,0.15)] transition-all cursor-pointer text-center'
                    data-tool='md5-enc'>MD5</button>
                <button
                    class='tool-btn bg-surface border border-border/50 text-txt/90 rounded-lg px-2 py-2 text-[11px] font-semibold tracking-wider hover:bg-primary/10 hover:border-primary/50 hover:text-primary hover:shadow-[0_0_10px_rgba(20,184,166,0.15)] transition-all cursor-pointer text-center'
                    data-tool='html-vw'>HTML</button>
                <button
                    class='tool-btn bg-surface border border-primary/30 text-primary rounded-lg px-2 py-2 text-[11px] font-semibold tracking-wider hover:bg-primary hover:text-[#09090b] hover:shadow-[0_0_15px_rgba(20,184,166,0.4)] transition-all cursor-pointer text-center col-span-2'
                    data-tool='json-ps'>JSON Parse</button>
            </div>
            <!-- Output Textarea -->
            <div class='flex-1 flex flex-col bg-[#0f1219] p-4 relative'>
                <span class='absolute top-2 right-4 text-[10px] text-muted/40 font-mono pointer-events-none'>Output
                    Buffer</span>
                <textarea spellcheck='false' placeholder='Output will appear here...'
                    class='output-inner w-full flex-1 bg-transparent text-txt/90 text-[13px] font-mono outline-none resize-none placeholder:text-muted/30 focus:border-primary transition-colors custom-scrollbar'></textarea>
            </div>
        </div>

    </div>

</div>

<!-- Hidden checkbox for JS compat (always checked) -->
<input type='checkbox' id='show-html' class='hidden' checked>

<script defer src='app.js'></script>
<?php
include "./includes/footer.php";
?>