const API_URL = "http://localhost/phishguard/backend/scan.php";

let currentResult = null;

document.addEventListener("DOMContentLoaded", () => {
  chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
    if (!tabs || !tabs[0]) return;
    const url = tabs[0].url;

    if (url.startsWith("chrome://") || url.startsWith("brave://") || url.startsWith("about:")) {
      showError("Cannot analyze internal browser pages.");
      return;
    }

    chrome.storage.local.get(["customWhitelist"], (res) => {
      const customWhitelist = res.customWhitelist || [];
      const result = analyzeUrl(url, customWhitelist);
      currentResult = result;
      render(result);
      sendToBackend(result);
      wireActions(url);
    });
  });
});

// ---------- Render ----------
function render(r) {
  document.getElementById("loading").style.display = "none";
  document.getElementById("content").style.display = "block";

  let domain = r.url;
  try { domain = new URL(r.url).hostname; } catch (e) {}
  document.getElementById("domain").textContent = domain;
  document.getElementById("full-url").textContent = r.url;
  document.getElementById("full-url").title = r.url;

  const card = document.getElementById("score-card");
  card.className = "score-card " + r.level;
  document.getElementById("score").textContent = r.score;
  document.getElementById("level-badge").textContent = r.level + " RISK";

  const fill = document.getElementById("meter-fill");
  setTimeout(() => { fill.style.width = Math.min(r.score, 100) + "%"; }, 50);

  // Chips
  renderChips(r);

  // Reasons
  const list = document.getElementById("reasons-list");
  const count = document.getElementById("reasons-count");

  if (!r.reasons.length) {
    list.innerHTML = `<li class="safe"><span class="dot"></span>No suspicious patterns detected.</li>`;
    count.textContent = "0";
  } else {
    list.innerHTML = r.reasons
      .map(reason => `<li><span class="dot"></span>${escapeHtml(reason)}</li>`)
      .join("");
    count.textContent = r.reasons.length;
  }

  // Sound alert on high risk
  if (r.level === "high") {
    const audio = document.getElementById("alert-sound");
    if (audio) {
      audio.volume = 0.4;
      audio.play().catch(() => {});
    }
  }

  setFooter("Sending...", "");
}

// ---------- Chips ----------
function renderChips(r) {
  const chips = [];
  try {
    const u = new URL(r.url);
    chips.push({
      text: u.protocol === "https:" ? "🔒 HTTPS" : "🔓 HTTP",
      cls: u.protocol === "https:" ? "ok" : "bad"
    });
    chips.push({
      text: r.level === "low" ? "🌐 Legit" : "🌐 Suspicious",
      cls: r.level === "low" ? "ok" : (r.level === "medium" ? "warn" : "bad")
    });
    chips.push({
      text: u.href.length > 75 ? "📏 Long URL" : "📏 Normal",
      cls: u.href.length > 75 ? "warn" : "ok"
    });
  } catch (e) {}

  document.getElementById("chips").innerHTML = chips
    .map(c => `<span class="chip ${c.cls}">${c.text}</span>`)
    .join("");
}

// ---------- Error / Footer ----------
function showError(msg) {
  document.getElementById("loading").innerHTML =
    `<p style="color:#ef4444;padding:20px 0;">${escapeHtml(msg)}</p>`;
}

function setFooter(text, cls) {
  const el = document.getElementById("footer-status");
  el.textContent = text;
  el.className = cls || "";
}

// ---------- Backend ----------
function sendToBackend(r) {
  fetch(API_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(r)
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) setFooter("✓ Saved to server", "ok");
      else setFooter("⚠ Server rejected", "err");
    })
    .catch(() => setFooter("⚠ Server offline", "err"));
}

// ---------- Actions ----------
function wireActions(url) {
  // Rescan
  document.getElementById("btn-rescan").addEventListener("click", () => {
    location.reload();
  });

  // Copy URL (single click) + Copy JSON (double click)
  const copyBtn = document.getElementById("btn-copy");
  copyBtn.addEventListener("click", () => {
    navigator.clipboard.writeText(url).then(() => {
      const original = copyBtn.innerHTML;
      copyBtn.innerHTML = "<span>✓</span> Copied!";
      copyBtn.classList.add("active");
      setTimeout(() => {
        copyBtn.innerHTML = original;
        copyBtn.classList.remove("active");
      }, 1200);
    });
  });
  copyBtn.addEventListener("dblclick", () => {
    navigator.clipboard.writeText(JSON.stringify(currentResult, null, 2)).then(() => {
      const original = copyBtn.innerHTML;
      copyBtn.innerHTML = "<span>✓</span> JSON copied";
      copyBtn.classList.add("active");
      setTimeout(() => {
        copyBtn.innerHTML = original;
        copyBtn.classList.remove("active");
      }, 1200);
    });
  });

  // Trust Site
  const trustBtn = document.getElementById("btn-trust");
  let domain = "";
  try { domain = new URL(url).hostname; } catch (e) {}

  chrome.storage.local.get(["customWhitelist"], (res) => {
    const wl = res.customWhitelist || [];
    if (wl.includes(domain)) {
      trustBtn.classList.add("active");
      trustBtn.innerHTML = "<span>✓</span> Trusted";
    }
  });

  trustBtn.addEventListener("click", () => {
    if (!domain) return;

    chrome.storage.local.get(["customWhitelist"], (res) => {
      let wl = res.customWhitelist || [];

      if (wl.includes(domain)) {
        wl = wl.filter(d => d !== domain);
        trustBtn.classList.remove("active");
        trustBtn.innerHTML = "<span>✓</span> Trust Site";
        // Re-analyze with updated whitelist
        const newResult = analyzeUrl(url, wl);
        currentResult = newResult;
        render(newResult);
      } else {
        wl.push(domain);
        trustBtn.classList.add("active");
        trustBtn.innerHTML = "<span>✓</span> Trusted";

        const newResult = analyzeUrl(url, wl);
        currentResult = newResult;
        render(newResult);
        setFooter("✓ Marked as trusted", "ok");
      }

      chrome.storage.local.set({ customWhitelist: wl });
    });
  });
}

// ---------- Helpers ----------
function escapeHtml(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}