// ---- Trusted domains (built-in) ----
const WHITELIST = [
  "localhost", "127.0.0.1",
  "google.com", "youtube.com", "gmail.com",
  "microsoft.com", "live.com", "office.com", "outlook.com",
  "facebook.com", "instagram.com", "whatsapp.com", "messenger.com",
  "github.com", "linkedin.com", "twitter.com", "x.com", "reddit.com",
  "paypal.com", "amazon.com", "apple.com", "icloud.com",
  "netflix.com", "spotify.com", "dropbox.com", "mozilla.org",
  "wikipedia.org", "cloudflare.com", "microsoftonline.com",
  "stackoverflow.com", "gitlab.com", "bitbucket.org", "medium.com",
  "tiktok.com", "snapchat.com", "pinterest.com", "twitch.tv",
  "openai.com", "chatgpt.com", "anthropic.com", "claude.ai",
  "adobe.com", "salesforce.com", "oracle.com", "ibm.com",
  "bing.com", "duckduckgo.com", "yahoo.com", "yandex.com"
];

function isWhitelisted(hostname, customList) {
  const all = WHITELIST.concat(customList || []);
  return all.some(d => hostname === d || hostname.endsWith("." + d));
}

function analyzeUrl(urlString, customWhitelist) {
  let u;
  try {
    u = new URL(urlString);
  } catch (e) {
    return { url: urlString, score: 0, level: "unknown", reasons: ["Invalid URL"] };
  }

  if (isWhitelisted(u.hostname, customWhitelist)) {
    return {
      url: urlString,
      score: 0,
      level: "low",
      reasons: ["Trusted domain (whitelist)"]
    };
  }

  let score = 0;
  const reasons = [];

  RULES.forEach((rule) => {
    try {
      if (rule.test(u)) {
        score += rule.weight;
        reasons.push(rule.name);
      }
    } catch (e) {}
  });

  score = Math.min(score, 100);

  let level = "low";
  if (score >= 70) level = "high";
  else if (score >= 30) level = "medium";

  return { url: urlString, score, level, reasons };
}