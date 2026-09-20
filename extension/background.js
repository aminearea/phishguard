// PhishGuard background service worker

// ---- Icon generator (shield + circular badge) ----
async function generateIcon(level) {
  try {
    const SIZE = 128;
    const canvas = new OffscreenCanvas(SIZE, SIZE);
    const ctx = canvas.getContext("2d");

    const url = chrome.runtime.getURL("icons/icon128.png");
    const response = await fetch(url);
    const blob = await response.blob();
    const base = await createImageBitmap(blob);
    ctx.drawImage(base, 0, 0, SIZE, SIZE);

    if (!level) return ctx.getImageData(0, 0, SIZE, SIZE);

    const colors  = { high: "#ef4444", medium: "#f59e0b", low: "#10b981" };
    const symbols = { high: "!",       medium: "!",       low: "✓" };

    const cx = SIZE * 0.70;
    const cy = SIZE * 0.70;
    const r  = SIZE * 0.24;

    ctx.beginPath();
    ctx.arc(cx, cy, r + 4, 0, Math.PI * 2);
    ctx.fillStyle = "#ffffff";
    ctx.fill();

    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.fillStyle = colors[level] || colors.low;
    ctx.fill();

    ctx.fillStyle = "#0f172a";
    ctx.font = `900 ${Math.round(r * 1.35)}px Arial, sans-serif`;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(symbols[level] || "", cx, cy + r * 0.05);

    return ctx.getImageData(0, 0, SIZE, SIZE);
  } catch (e) {
    console.error("[PhishGuard] generateIcon failed:", e);
    return null;
  }
}

// ---- Apply icon to a tab ----
async function applyIconToTab(tabId, level, url) {
  const imageData = await generateIcon(level);

  if (!imageData) {
    chrome.action.setIcon({ tabId, path: "icons/icon128.png" });
    return;
  }

  try {
    chrome.action.setIcon({ tabId, imageData });
    chrome.action.setBadgeText({ tabId, text: "" });
  } catch (e) {
    console.error("[PhishGuard] setIcon failed:", e);
  }

  // Desktop notification on high risk
  if (level === "high") {
    notifyHighRisk(url);
  }
}

// ---- Desktop notification ----
let notifiedUrls = {};

function notifyHighRisk(url) {
  if (notifiedUrls[url]) return;
  notifiedUrls[url] = true;
  setTimeout(() => { delete notifiedUrls[url]; }, 60000);

  let domain = url;
  try { domain = new URL(url).hostname; } catch (e) {}

  chrome.notifications.create({
    type: "basic",
    iconUrl: "icons/icon128.png",
    title: "⚠ PhishGuard Warning",
    message: `Suspicious site detected:\n${domain}\nDo not enter personal information.`,
    priority: 2
  });
}

// ---- Analyze a tab ----
function analyzeTab(tabId, url) {
  if (!url || !url.startsWith("http")) {
    chrome.action.setIcon({ tabId, path: "icons/icon128.png" });
    chrome.action.setBadgeText({ tabId, text: "" });
    return;
  }

  chrome.storage.local.get(["customWhitelist"], (res) => {
    const customWhitelist = res.customWhitelist || [];

    chrome.scripting.executeScript(
      { target: { tabId }, files: ["rules.js", "analyzer.js"] },
      () => {
        if (chrome.runtime.lastError) return;

        chrome.scripting.executeScript(
          {
            target: { tabId },
            func: (wl) => {
              try { return analyzeUrl(window.location.href, wl); }
              catch (e) { return null; }
            },
            args: [customWhitelist]
          },
          (results) => {
            if (chrome.runtime.lastError || !results || !results[0]) return;
            const r = results[0].result;
            if (!r) return;
            applyIconToTab(tabId, r.level, r.url);
          }
        );
      }
    );
  });
}

// ---- Events ----
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.status !== "complete") return;
  analyzeTab(tabId, tab.url);
});

chrome.tabs.onActivated.addListener((activeInfo) => {
  chrome.tabs.get(activeInfo.tabId, (tab) => {
    if (!tab) return;
    analyzeTab(activeInfo.tabId, tab.url);
  });
});

chrome.runtime.onInstalled.addListener(() => {
  chrome.action.setBadgeText({ text: "" });
});