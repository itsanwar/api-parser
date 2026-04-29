/**
 * API Parser - Application JavaScript
 * 
 * Handles UI interactions for the raw API parser tool:
 * - Parse/Run button actions
 * - Output toolbox (encode/decode/view)
 * - JSON viewer integration
 * - Undo/Redo state management
 * - Copy to clipboard
 */

// --- DOM References ---
const buttons = document.querySelectorAll(".main-btn");
const selectLang = document.querySelector("#lang-select");
const parseBtn = document.querySelector("#parse-btn");
const runBtn = document.querySelector("#run-btn");
const copyBtn = document.querySelector("#copy-btn");
const textArea = document.querySelector("#main-textarea");
const outputBox = document.querySelector(".output-box");
const transferBtn = document.querySelector("#transfer-btn");
const outputInner = document.querySelector(".output-inner");
const undoBtn = document.querySelector("#output-undo");
const redoBtn = document.querySelector("#output-redo");
const copyBtn2 = document.querySelector("#output-copy-btn");
const copyBtn3 = document.querySelector("#json-copy-btn");

// --- State ---
let next = false;
let previousInput = ""; // Single-level undo for main textarea
let outputStates = [];
let undoIndex = 0;
let parentGArr = ['data'];



// --- Button validation (shared for Parse & Run) ---
buttons.forEach((button) => {
  button.addEventListener("click", function () {
    if (textArea.value.trim() === "") {
      sontam("error", "Fill the input first!");
      next = false;
      return;
    }
    if (selectLang.value === "") {
      sontam("error", "Select language first!");
      next = false;
      return;
    }
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    next = true;
    outputBox.classList.add("hide");
  });
});


// --- Keyboard shortcuts ---
window.addEventListener("keydown", (e) => {
  if (e.ctrlKey && e.key === "r") {
    e.preventDefault();
    runBtn.click();
  }
  if (e.ctrlKey && e.key === "q") {
    e.preventDefault();
    parseBtn.click();
  }
  // Ctrl+Z: Undo main textarea
  if (e.ctrlKey && e.key === "z" && document.activeElement !== outputInner) {
    e.preventDefault();
    const temp = textArea.value;
    textArea.value = previousInput;
    previousInput = temp;
  }
  // Esc: Close popups
  if (e.key === "Escape") {
    document.querySelectorAll('.html-viewer:not(.hide) .close-btn, .json-parser:not(.hide) .close-btn').forEach(btn => btn.click());
  }
});

// --- Transfer Arrow: Copy main textarea → toolbox ---
transferBtn.addEventListener('click', function () {
  if (!textArea.value.trim()) {
    sontam("warning", "Nothing to transfer", "Enter some data first!");
    return;
  }
  outputInner.value = textArea.value;
  // Visual feedback
  this.classList.add('scale-125', 'text-primary');
  setTimeout(() => this.classList.remove('scale-125'), 200);
  sontam("success", "Transferred!", "Data copied to ToolBox");
});

// --- Input character limit ---
const MAX_INPUT_LENGTH = 10000;

textArea.addEventListener('paste', function () {
  setTimeout(() => {
    if (this.value.length > MAX_INPUT_LENGTH) {
      sontam("error", "Max Limit Reached!", `Only ${MAX_INPUT_LENGTH} characters allowed!`);
      this.value = this.value.substring(0, MAX_INPUT_LENGTH);
    }
  }, 0);
});

textArea.addEventListener('input', function () {
  if (this.value.length > MAX_INPUT_LENGTH) {
    sontam("error", "Max Limit Reached!", `Only ${MAX_INPUT_LENGTH} characters allowed!`);
    this.value = this.value.substring(0, MAX_INPUT_LENGTH);
  }
  outputInner.value = this.value;
  document.querySelector("#opt-len").innerHTML = `Input Length : ${this.value.length}`;
  parseBtn.disabled = false;
  runBtn.disabled = false;
  runBtn.innerHTML = `<i class="fas fa-play mr-2 text-xs"></i>Run Api`;
});

// --- Save previous input for undo ---
function pushInputData() {
  previousInput = textArea.value;
}

// --- Parse Button ---
parseBtn.addEventListener('click', function () {
  if (!next) return;
  pushInputData();
  let mainData = textArea.value.replace(/\n/g, "<br>");
  mainData = escapeHtml(mainData);
  const returnHeaders = document.querySelector("#return-headers").checked ? 1 : 0;
  http_call(
    `work.php?action=parse&lang=${selectLang.value}&headers=${returnHeaders}`,
    `data=${mainData}`,
    (res) => {
      flash(textArea);
      textArea.value = res;
      parseBtn.innerHTML = `<i class="fas fa-terminal mr-2 text-xs"></i>Parse Api`;
      parseBtn.disabled = false;
      outputBox.classList.remove("hide");
      document.querySelector("#opt-len").innerHTML = `Output Length : ${res.length}`;
    },
    "POST",
    "html"
  );
});

// --- Run Button ---
runBtn.addEventListener('click', function () {
  if (!next) return;
  pushInputData();
  let mainData = textArea.value.replace(/\n/g, "<br>");
  mainData = escapeHtml(mainData);
  const returnHeaders = document.querySelector("#return-headers").checked ? 1 : 0;
  http_call(
    `work.php?action=run&lang=${selectLang.value}&headers=${returnHeaders}`,
    `data=${mainData}`,
    (res) => {
      outputBox.classList.remove("hide");
      document.querySelector("#opt-len").innerHTML = `Output Length : ${res.length}`;

      if (selectLang.value === "js") {
        runJS(res);
        return;
      }
      if (!res || res.trim() === "") {
        sontam("error", "No output found!", "Enter correct raw data!");
        runBtn.disabled = false;
        runBtn.innerHTML = `<i class="fas fa-play mr-2 text-xs"></i>Run Api`;
        return;
      }
      printRes(res);
    },
    "POST",
    "html"
  );
});

// --- Close overlay div (HTML viewer / JSON parser) ---
function closeDiv(elem) {
  // Walk up to find the modal root (.html-viewer or .json-parser)
  let modal = elem.closest('.html-viewer') || elem.closest('.json-parser');
  if (modal) modal.classList.add("hide");
  
  // Clean up iframe when closing html viewer
  if (modal && modal.classList.contains('html-viewer')) {
    document.querySelector(".html-vw-body").srcdoc = "";
  }
}

// --- Undo/Redo state management for output panel ---
function pushOutputStates(data) {
  undoIndex++;
  undoBtn.style.filter = "grayscale(100%)";
  redoBtn.style.filter = "grayscale(100%)";
  if (undoIndex === 1) outputStates[0] = outputInner.value;
  if (undoIndex > 0) undoBtn.style.filter = "grayscale(0%)";
  outputStates[undoIndex] = data;
  outputStates.length = undoIndex + 1; // Trim forward history
}

undoBtn.addEventListener('click', function () {
  if (undoIndex === 0) return;
  redoBtn.style.filter = "grayscale(0%)";
  undoIndex--;
  if (undoIndex === 0) this.style.filter = "grayscale(100%)";
  outputStates[undoIndex + 1] = outputInner.value;
  outputInner.value = outputStates[undoIndex];
});

redoBtn.addEventListener('click', function () {
  if (undoIndex === outputStates.length - 1) return;
  undoBtn.style.filter = "grayscale(0%)";
  undoIndex++;
  if (undoIndex === outputStates.length - 1) this.style.filter = "grayscale(100%)";
  outputStates[undoIndex - 1] = outputInner.value;
  outputInner.value = outputStates[undoIndex];
});

// --- Toolbox actions ---
document.querySelectorAll('.tool-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const data = outputInner;
    const option = this.dataset.tool;
    const rawValue = data.value;

    if (!rawValue) {
        sontam("error", "No data to process!");
        return;
    }

    // HTML Viewer
    if (option === "html-vw") {
      const preStyle = `<style>
        body{
            background: #aaa5;
        }
        body::-webkit-scrollbar {
            border-radius: 30px;
            width: .2em;
        }
        body::-webkit-scrollbar-track {
            background: #000;
            box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
            border-radius: 30px;
        }
        body::-webkit-scrollbar-thumb {
            background-color: #00ff00;
            border: 3px solid #00ff00;
            border-radius: 10px;
        }
        </style>`;

      document.querySelector(".html-viewer").classList.remove("hide");
      const iframe = document.querySelector(".html-vw-body");
      iframe.srcdoc = preStyle + rawValue;
      return;
    }

    // JSON Parser
    if (option === "json-ps") {
      pushOutputStates(rawValue);
      document.querySelector(".json-parser").classList.remove("hide");
      try {
        // Try strict JSON parse first
        var input = JSON.parse(rawValue);
      } catch (error) {
        try {
          // Fallback to relaxed JS object parsing (common in raw Fiddler dumps)
          var input = new Function('return (' + rawValue + ')')();
        } catch (err2) {
          document.querySelector(`#json-renderer`).innerHTML = "<div style='color: #ff5555; padding: 20px; font-size: 16px; font-family: monospace;'>Error parsing JSON/JS Object:<br>" + error.message + "<br><br>Make sure the input is not empty.</div>";
          return;
        }
      }
      const options = {
        collapsed: false,
        rootCollapsable: true,
        withQuotes: false,
        withLinks: true
      };
      $('#json-renderer').jsonViewer(input, options);
      jsonParserEvent();
      return;
    }

    // Encoding/Decoding tools natively in JS
    let result = "";
    try {
        switch (option) {
            case 'b64-enc':
                // Base64 Encode (Unicode safe)
                result = btoa(unescape(encodeURIComponent(rawValue)));
                break;
            case 'b64-dec':
                // Base64 Decode (Unicode safe)
                result = decodeURIComponent(escape(atob(rawValue)));
                break;
            case 'url-enc':
                result = encodeURIComponent(rawValue);
                break;
            case 'url-dec':
                result = decodeURIComponent(rawValue);
                break;
            case 'md5-enc':
                // Using CryptoJS
                if (typeof CryptoJS !== 'undefined') {
                    result = CryptoJS.MD5(rawValue).toString();
                } else {
                    sontam("error", "MD5 library not loaded");
                    return;
                }
                break;
            default:
                return;
        }
    } catch (e) {
        sontam("error", "Processing Error", e.message);
        result = "Error: " + e.message;
    }

    pushOutputStates(result);
    data.value = result;
  });
});

// --- Print result to main textarea ---
function printRes(res) {
  flash(textArea);
  textArea.value = res;
  runBtn.innerHTML = `<i class="fas fa-play mr-2 text-xs"></i>Run Api`;
  runBtn.disabled = false;
}

// --- Run JS-parsed API directly ---
function runJS(res) {
  const parts = res.split("|");
  if (parts.length < 5) {
    sontam("error", "Invalid JS response format");
    runBtn.disabled = false;
    runBtn.innerHTML = `<i class="fas fa-play mr-2 text-xs"></i>Run Api`;
    return;
  }
  const url = parts[0];
  const fData = parts[1];
  let headers;
  try {
    headers = JSON.parse(parts[2]);
  } catch (e) {
    sontam("error", "Failed to parse headers", e.message);
    runBtn.disabled = false;
    runBtn.innerHTML = `Run Api`;
    return;
  }
  const method = parts[3];
  const dType = parts[4];

  http_call(
    url,
    fData,
    (res) => {
      printRes(typeof res === 'object' ? JSON.stringify(res, null, 2) : res);
    },
    0,
    method,
    headers,
    dType
  );
}

// --- JSON output display ---
document.querySelector("#json-lang-select").addEventListener('change', function () {
  jsonOutput(parentGArr);
});

function getUppercaseChars(str) {
  let result = '';
  for (let i = 0; i < str.length; i++) {
    if (str[i] === str[i].toUpperCase() && /[A-Z]/.test(str[i])) {
      result += str[i];
    }
  }
  return result;
}

function shortVar(longName) {
  longName = longName.charAt(0).toUpperCase() + longName.substring(1);
  let shortName = getUppercaseChars(longName);
  if (shortName.length < 3) {
    shortName = longName.charAt(0) +
      longName.charAt(Math.floor(longName.length / 2) - 1) +
      longName.substring(longName.length - 2);
  }
  return shortName.toLowerCase();
}

function jsonOutput(array) {
  if (!array || array.length === 0) return;
  flash(document.querySelector(".json-output"));
  let mainText = array[array.length - 1];
  if (mainText && mainText.length > 7) mainText = shortVar(mainText);

  const isPhp = document.querySelector("#json-lang-select").value === "php";
  let str = isPhp ? `$${mainText} = $json` : `let ${mainText} = resultJson`;

  array.forEach((arr) => {
    str += isPhp ? `['${arr}']` : `.${arr}`;
  });

  document.querySelector(".json-output").innerHTML = str + ";";
}

// --- JSON parser click events ---
function jsonParserEvent() {
  const lis = document.querySelectorAll("#json-renderer li");
  lis.forEach((li) => {
    li.addEventListener("click", (e) => {
      e.stopPropagation();
      const dataText = e.target.innerText.split(":")[0];
      let parentElem = e.target.parentElement;
      const parentArr = [dataText];

      while (parentElem && parentElem.id !== "json-renderer") {
        const a = parentElem.querySelector("a");
        if (a && a.innerHTML !== "" && parentElem.className !== "json-dict") {
          parentArr.push(a.innerHTML);
        }
        parentElem = parentElem.parentElement;
      }

      jsonOutput(parentArr.reverse());
      parentGArr = parentArr;
    }, true);
  });
}

// --- Copy buttons ---
copyBtn.addEventListener('click', function () {
  const mainData = textArea.value;
  if (!mainData) return;
  flash(textArea);
  navigator.clipboard.writeText(mainData).then(() => {
    sontam("success", "Copied to clipboard!");
  }).catch(() => {
    sontam("error", "Failed to copy!");
  });
});

copyBtn2.addEventListener('click', function () {
  const mainData = outputInner.value;
  if (!mainData) return;
  flash(document.querySelector(".output"));
  flash(document.querySelector(".output-title"));
  navigator.clipboard.writeText(mainData).then(() => {
    sontam("success", "Copied to clipboard!");
  }).catch(() => {
    sontam("error", "Failed to copy!");
  });
});

copyBtn3.addEventListener('click', function () {
  const mainData = document.querySelector(".json-output").innerHTML;
  if (!mainData) return;
  flash(document.querySelector(".json-output"));
  navigator.clipboard.writeText(mainData).then(() => {
    sontam("success", "Copied to clipboard!");
  }).catch(() => {
    sontam("error", "Failed to copy!");
  });
});

// --- Flash animation utility ---
function flash(elem) {
  if (!elem) return;
  elem.classList.add("flash");
  setTimeout(() => {
    elem.classList.remove("flash");
  }, 300);
}

// --- HTML entity escape for & character ---
function escapeHtml(text) {
  return text.replace(/&/g, "andder");
}

// --- Global Escape key handler for modals ---
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const htmlViewer = document.querySelector('.html-viewer:not(.hide)');
    const jsonParser = document.querySelector('.json-parser:not(.hide)');
    if (htmlViewer) {
      const btn = htmlViewer.querySelector('.close-btn');
      if (btn) btn.click();
    }
    if (jsonParser) {
      const btn = jsonParser.querySelector('.close-btn');
      if (btn) btn.click();
    }
  }

  // Ctrl+C with no selection → copy entire textarea content
  if (e.key === 'c' && (e.ctrlKey || e.metaKey)) {
    const active = document.activeElement;
    if (active === textArea || active === outputInner) {
      // Only intercept if nothing is selected (let normal copy work otherwise)
      if (active.selectionStart === active.selectionEnd) {
        e.preventDefault();
        navigator.clipboard.writeText(active.value).then(() => {
          sontam("success", "Copied!", "Full content copied to clipboard");
        });
      }
    }
  }
});