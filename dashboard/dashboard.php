<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: index.php');
  exit;
}

$statsJson = @file_get_contents('http://localhost/phishguard/backend/stats.php');
$statsData = $statsJson ? json_decode($statsJson, true) : null;
$stats      = $statsData['stats']      ?? ['total' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
$daily      = $statsData['daily']      ?? [];
$topDomains = $statsData['topDomains'] ?? [];

$scansJson = @file_get_contents('http://localhost/phishguard/backend/scans.php?limit=50');
$scansData = $scansJson ? json_decode($scansJson, true) : null;
$scans      = $scansData['scans'] ?? [];
$totalScans = $scansData['total'] ?? 0;

function shortenUrl($url, $max = 60)
{
  if (strlen($url) <= $max) return $url;
  return substr($url, 0, $max) . '…';
}

function timeAgo($datetime)
{
  $ts = strtotime($datetime);
  $diff = time() - $ts;
  if ($diff < 60)     return "just now";
  if ($diff < 3600)   return floor($diff / 60) . "m ago";
  if ($diff < 86400)  return floor($diff / 3600) . "h ago";
  if ($diff < 604800) return floor($diff / 86400) . "d ago";
  return date('M d, H:i', $ts);
}

// Compute percentages for stat cards
$total = max(1, (int)$stats['total']);
$highPct   = round((int)$stats['high']   / $total * 100);
$mediumPct = round((int)$stats['medium'] / $total * 100);
$lowPct    = round((int)$stats['low']    / $total * 100);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>PhishGuard — Dashboard</title>
  <link rel="stylesheet" href="style.css?v=5" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
  <div class="container">

    <!-- Topbar -->
    <header class="topbar">
      <div class="brand">
        <div class="logo"><img src="../extension/icons/icon128.png" alt="PhishGuard" /></div>
        <div>
          <h1>PhishGuard</h1>
          <p class="subtitle">Security Overview</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="export.php" class="btn">
          <span>📥</span> Export CSV
        </a>
        <a href="logout.php" class="btn btn-ghost">Logout</a>
      </div>
    </header>

    <!-- Stats -->
    <section class="stats">
      <div class="stat">
        <div class="stat-head">
          <span class="stat-label">Total Scans</span>
          <span class="pill pill-blue">Active</span>
        </div>
        <div class="stat-value"><?= number_format((int)$stats['total']) ?></div>
        <div class="stat-foot">All URLs analyzed</div>
      </div>

      <div class="stat">
        <div class="stat-head">
          <span class="stat-label">High Risk</span>
          <span class="pill pill-red">▲ <?= $highPct ?>%</span>
        </div>
        <div class="stat-value"><?= number_format((int)$stats['high']) ?></div>
        <div class="stat-foot">Threats detected</div>
      </div>

      <div class="stat">
        <div class="stat-head">
          <span class="stat-label">Medium Risk</span>
          <span class="pill pill-orange"><?= $mediumPct ?>%</span>
        </div>
        <div class="stat-value"><?= number_format((int)$stats['medium']) ?></div>
        <div class="stat-foot">Needs attention</div>
      </div>

      <div class="stat">
        <div class="stat-head">
          <span class="stat-label">Low Risk</span>
          <span class="pill pill-green">✓ <?= $lowPct ?>%</span>
        </div>
        <div class="stat-value"><?= number_format((int)$stats['low']) ?></div>
        <div class="stat-foot">Safe URLs</div>
      </div>
    </section>

    <!-- Chart card -->
    <section class="card chart-card">
      <div class="card-head">
        <div>
          <h2 class="card-title">Activity Overview</h2>
          <p class="card-sub">Threats detected over the last 7 days</p>
        </div>
        <div class="segmented">
          <button class="seg-btn" data-range="30">Last 30 days</button>
          <button class="seg-btn active" data-range="7">Last 7 days</button>
          <button class="seg-btn" data-range="today">Today</button>
        </div>
      </div>
      <div class="chart-wrap chart-tall">
        <canvas id="chart-line"></canvas>
      </div>
    </section>

    <!-- Row: Doughnut + Top domains -->
    <section class="row-2col">
      <div class="card">
        <h3 class="card-title">Risk Distribution</h3>
        <div class="chart-wrap chart-donut">
          <canvas id="chart-donut"></canvas>
        </div>
      </div>

      <div class="card">
        <h3 class="card-title">Top Risky Domains</h3>
        <ul class="top-domains">
          <?php if (empty($topDomains)): ?>
            <li class="empty-mini">No high-risk scans yet.</li>
          <?php else: ?>
            <?php foreach ($topDomains as $i => $d): ?>
              <li>
                <span class="rank"><?= $i + 1 ?></span>
                <span class="dom"><?= htmlspecialchars($d['domain']) ?></span>
                <span class="cnt"><?= (int)$d['count'] ?></span>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </section>

    <!-- Filters -->
    <section class="filters">
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" id="search" placeholder="Search URLs..." />
      </div>
      <div class="chips" id="level-chips">
        <button class="chip active" data-level="">All</button>
        <button class="chip" data-level="high"><span class="dot dot-red"></span>High</button>
        <button class="chip" data-level="medium"><span class="dot dot-orange"></span>Medium</button>
        <button class="chip" data-level="low"><span class="dot dot-green"></span>Low</button>
      </div>
      <button id="btn-refresh" class="btn btn-ghost">↻ Refresh</button>
    </section>

    <!-- Table -->
    <section class="table-wrap">
      <table id="scans-table">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>URL</th>
            <th style="width:80px;">Score</th>
            <th style="width:120px;">Level</th>
            <th>Reasons</th>
            <th style="width:130px;">Time</th>
          </tr>
        </thead>
        <tbody id="scans-body">
          <?php if (empty($scans)): ?>
            <tr>
              <td colspan="6" class="empty">No scans yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($scans as $s): ?>
              <tr class="row-<?= htmlspecialchars($s['risk_level']) ?>">
                <td class="id">#<?= (int)$s['id'] ?></td>
                <td class="url" title="<?= htmlspecialchars($s['url']) ?>">
                  <?= htmlspecialchars(shortenUrl($s['url'])) ?>
                </td>
                <td class="score"><?= (int)$s['risk_score'] ?></td>
                <td>
                  <span class="level-pill <?= htmlspecialchars($s['risk_level']) ?>">
                    <span class="dot"></span>
                    <?= ucfirst(htmlspecialchars($s['risk_level'])) ?>
                  </span>
                </td>
                <td class="reasons">
                  <?= htmlspecialchars(implode(', ', $s['reasons'])) ?: '—' ?>
                </td>
                <td class="date"><?= timeAgo($s['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <div class="table-footer">
      <span id="table-info">Showing <?= count($scans) ?> of <?= (int)$totalScans ?> scans</span>
      <button id="btn-load-more" class="btn btn-ghost">Load more</button>
    </div>

  </div>

    <script>
    const stats = {
      low: <?= (int)$stats['low'] ?>,
      medium: <?= (int)$stats['medium'] ?>,
      high: <?= (int)$stats['high'] ?>
    };
    const daily = <?= json_encode($daily) ?>;

    // ---- Chart defaults ----
    Chart.defaults.color = "#a1a1aa";
    Chart.defaults.font.family = "-apple-system, 'Inter', 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.font.size = 12;

    // ---- Doughnut ----
    new Chart(document.getElementById("chart-donut"), {
      type: "doughnut",
      data: {
        labels: ["Low", "Medium", "High"],
        datasets: [{
          data: [stats.low, stats.medium, stats.high],
          backgroundColor: ["#10b981", "#f59e0b", "#ef4444"],
          borderWidth: 0,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "72%",
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              color: "#a1a1aa",
              padding: 14,
              font: { size: 12, weight: "600" },
              usePointStyle: true,
              pointStyle: "circle"
            }
          }
        }
      }
    });

    // ---- Line chart (single declaration) ----
    const ctxLine = document.getElementById("chart-line").getContext("2d");

    function makeGradient(hex) {
      const g = ctxLine.createLinearGradient(0, 0, 0, 320);
      g.addColorStop(0, hex + "59");
      g.addColorStop(1, hex + "00");
      return g;
    }

    const lineChart = new Chart(ctxLine, {
      type: "line",
      data: {
        labels: daily.map(d => d.day),
        datasets: [
          {
            label: "High",
            data: daily.map(d => +d.high),
            borderColor: "#ef4444",
            backgroundColor: makeGradient("#ef4444"),
            tension: 0.4, fill: true, borderWidth: 2,
            pointRadius: 0, pointHoverRadius: 5
          },
          {
            label: "Medium",
            data: daily.map(d => +d.medium),
            borderColor: "#f59e0b",
            backgroundColor: makeGradient("#f59e0b"),
            tension: 0.4, fill: true, borderWidth: 2,
            pointRadius: 0, pointHoverRadius: 5
          },
          {
            label: "Low",
            data: daily.map(d => +d.low),
            borderColor: "#10b981",
            backgroundColor: makeGradient("#10b981"),
            tension: 0.4, fill: true, borderWidth: 2,
            pointRadius: 0, pointHoverRadius: 5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        plugins: {
          legend: {
            position: "bottom",
            align: "center",
            labels: {
              color: "#a1a1aa",
              padding: 18,
              font: { size: 12, weight: "600" },
              usePointStyle: true,
              pointStyle: "circle"
            }
          },
          tooltip: {
            backgroundColor: "#18181b",
            borderColor: "#27272a",
            borderWidth: 1,
            padding: 12,
            titleColor: "#fafafa",
            bodyColor: "#e4e4e7",
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            ticks: { color: "#71717a", font: { size: 11 } },
            grid: { display: false },
            border: { display: false }
          },
          y: {
            ticks: { color: "#71717a", font: { size: 11 }, padding: 8 },
            grid: { color: "rgba(255,255,255,0.05)" },
            border: { display: false },
            beginAtZero: true
          }
        }
      }
    });

    // ---- Segmented control (wired up) ----
    document.querySelectorAll(".seg-btn").forEach(btn => {
      btn.addEventListener("click", async () => {
        document.querySelectorAll(".seg-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const range = btn.dataset.range;
        try {
          const res = await fetch("http://localhost/phishguard/backend/stats.php?range=" + range);
          const data = await res.json();
          if (!data.success) return;

          // Update chart
          lineChart.data.labels = data.daily.map(d => d.day);
          lineChart.data.datasets[0].data = data.daily.map(d => +d.high);
          lineChart.data.datasets[1].data = data.daily.map(d => +d.medium);
          lineChart.data.datasets[2].data = data.daily.map(d => +d.low);
          lineChart.update();

          // Update subtitle
          const sub = document.querySelector(".card-sub");
          if (sub) {
            const labels = {
              "today": "today (hourly)",
              "7": "the last 7 days",
              "30": "the last 30 days"
            };
            sub.textContent = "Threats detected over " + (labels[range] || "the last 7 days");
          }
        } catch (e) {
          console.error("Failed to load range data:", e);
        }
      });
    });

    // ---- Level chips ----
    let currentLevel = "";
    document.querySelectorAll("#level-chips .chip").forEach(chip => {
      chip.addEventListener("click", () => {
        document.querySelectorAll("#level-chips .chip").forEach(c => c.classList.remove("active"));
        chip.classList.add("active");
        currentLevel = chip.dataset.level;
        loadScans(true);
      });
    });

    // ---- Table logic ----
    let offset = <?= count($scans) ?>;
    const LIMIT = 50;
    const tbody = document.getElementById("scans-body");
    const info  = document.getElementById("table-info");

    function timeAgoShort(s) {
      const d = new Date(s.replace(" ", "T"));
      const diff = (Date.now() - d.getTime()) / 1000;
      if (diff < 60)    return "just now";
      if (diff < 3600)  return Math.floor(diff / 60) + "m ago";
      if (diff < 86400) return Math.floor(diff / 3600) + "h ago";
      if (diff < 604800)return Math.floor(diff / 86400) + "d ago";
      return d.toLocaleDateString();
    }

    function shorten(url, max) {
      return url.length > max ? url.slice(0, max) + "…" : url;
    }

    function escapeHtml(s) {
      return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;")
                     .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    function renderRows(scans, append = false) {
      if (!append) tbody.innerHTML = "";
      if (!scans.length && !append) {
        tbody.innerHTML = `<tr><td colspan="6" class="empty">No scans found.</td></tr>`;
        return;
      }
      scans.forEach(s => {
        const tr = document.createElement("tr");
        tr.className = "row-" + s.risk_level;
        tr.innerHTML = `
          <td class="id">#${s.id}</td>
          <td class="url" title="${escapeHtml(s.url)}">${escapeHtml(shorten(s.url, 60))}</td>
          <td class="score">${s.risk_score}</td>
          <td>
            <span class="level-pill ${s.risk_level}">
              <span class="dot"></span>
              ${s.risk_level.charAt(0).toUpperCase() + s.risk_level.slice(1)}
            </span>
          </td>
          <td class="reasons">${escapeHtml(s.reasons.join(", ")) || "—"}</td>
          <td class="date">${timeAgoShort(s.created_at)}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    async function loadScans(reset = false) {
      if (reset) offset = 0;
      const q = document.getElementById("search").value.trim();
      const params = new URLSearchParams({ limit: LIMIT, offset, q, level: currentLevel });
      const res  = await fetch("http://localhost/phishguard/backend/scans.php?" + params);
      const data = await res.json();
      if (!data.success) return;

      renderRows(data.scans, !reset);
      offset += data.scans.length;
      info.textContent = `Showing ${offset} of ${data.total} scans`;
      document.getElementById("btn-load-more").style.display =
        offset >= data.total ? "none" : "inline-flex";
    }

    document.getElementById("btn-refresh").addEventListener("click", () => loadScans(true));

    let searchTimer;
    document.getElementById("search").addEventListener("input", () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadScans(true), 400);
    });

    document.getElementById("btn-load-more").addEventListener("click", () => loadScans(false));
  </script>
</body>

</html>