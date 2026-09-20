// Detection rules: each rule returns true if the URL matches.
const RULES = [
  {
    name: "No HTTPS",
    weight: 30,
    test: (u) => u.protocol !== "https:"
  },
  {
    name: "Uses raw IP address",
    weight: 30,
    test: (u) => /^\d{1,3}(\.\d{1,3}){3}$/.test(u.hostname)
  },
  {
    name: "Suspicious keyword in URL",
    weight: 20,
    test: (u) => /(login|verify|secure|update|account|signin|password)/i.test(u.href)
  },
  {
    name: "Very long URL",
    weight: 10,
    test: (u) => u.href.length > 75
  },
  {
    name: "Too many subdomains",
    weight: 15,
    test: (u) => u.hostname.split(".").length > 3
  },
  {
    name: "URL shortener",
    weight: 15,
    test: (u) => /(^|\.)(bit\.ly|tinyurl\.com|t\.co|goo\.gl|ow\.ly)$/i.test(u.hostname)
  },
  {
    name: "Contains '@' in URL",
    weight: 20,
    test: (u) => u.href.includes("@")
  },
  {
    name: "Punycode hostname",
    weight: 25,
    test: (u) => u.hostname.startsWith("xn--")
  },
  {
    name: "Suspicious tunneling service",
    weight: 40,
    test: (u) => /(trycloudflare\.com|ngrok\.io|ngrok-free\.app|localtunnel\.me|loca\.lt|serveo\.net|localxpose\.io|bore\.pub|pagekite\.me)/i.test(u.hostname)
  },
  {
    name: "Auto-generated subdomain",
    weight: 15,
    test: (u) => /^[a-z]+-[a-z]+-[a-z]+-[a-z]+/.test(u.hostname)
  },
  {
    name: "HTML file in path",
    weight: 10,
    test: (u) => {
      if (!/\.(html?|php)$/i.test(u.pathname)) return false;
      const hyphens = (u.hostname.match(/-/g) || []).length;
      const weirdTld = /\.(xyz|tk|top|gq|ml|cf|ga|work|click|link|zip)$/i.test(u.hostname);
      return hyphens >= 2 || weirdTld;
    }
  }
];