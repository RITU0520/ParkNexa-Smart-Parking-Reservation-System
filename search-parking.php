<?php
declare(strict_types=1);

require 'config/database.php';

$pageTitle = 'Find Parking | ParkNexa';

$city = trim($_GET['city'] ?? '');
$max = (float)($_GET['max_rate'] ?? 0);
$sort = $_GET['sort'] ?? 'recommended';

$sql = "SELECT * FROM parking_locations WHERE status = 'Active'";
$params = [];

if ($city !== '') {
    $sql .= " AND city = ?";
    $params[] = $city;
}

if ($max > 0) {
    $sql .= " AND hourly_rate <= ?";
    $params[] = $max;
}

switch ($sort) {
    case 'price':
        $sql .= " ORDER BY hourly_rate ASC, available_slots DESC";
        break;

    case 'availability':
        $sql .= " ORDER BY available_slots DESC, hourly_rate ASC";
        break;

    default:
        $sql .= " ORDER BY available_slots DESC, hourly_rate ASC";
        $sort = 'recommended';
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$locations = $stmt->fetchAll();

$cities = $pdo->query("
    SELECT DISTINCT city
    FROM parking_locations
    WHERE status = 'Active'
    ORDER BY city
")->fetchAll();

require 'includes/header.php';
?>

<section class="find-page">

    <!-- HERO -->
    <section class="find-hero">
        <div class="find-hero-inner">

            <div class="find-breadcrumb">
                <a href="index.php">Home</a>
                <span>›</span>
                <strong>Find Parking</strong>
            </div>

            <div class="find-hero-copy">
                <span class="eyebrow">FIND A PARKING SPACE</span>

                <h1>
                    Find the <span>perfect</span><br>
                    parking spot.
                </h1>

                <p>
                    Search available parking locations, compare hourly rates
                    and reserve a space before you arrive.
                </p>
            </div>

            <div class="find-hero-art" aria-hidden="true">
                <div class="find-hero-city"></div>

                

                <div class="find-hero-car">
                    <svg viewBox="0 0 190 95">
                        <path d="M26 60 40 31c3-7 8-11 16-11h62c7 0 13 4 17 11l14 29"/>
                        <path d="M17 60h156v10H17z"/>
                        <circle cx="48" cy="70" r="10"/>
                        <circle cx="140" cy="70" r="10"/>
                        <path d="M56 30h49M48 40h65"/>
                    </svg>
                </div>
            </div>

        </div>
    </section>


    <!-- SEARCH BAR -->
    <div class="find-search-wrap">

        <form class="find-search-form" method="get">

            <label class="find-field">

                <span class="find-field-icon plum">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 21s7-6.1 7-12A7 7 0 0 0 5 9c0 5.9 7 12 7 12Z"/>
                        <circle cx="12" cy="9" r="2.4"/>
                    </svg>
                </span>

                <span class="find-field-copy">

                    <small>Location / City</small>

                    <select name="city">
                        <option value="">All cities</option>

                        <?php foreach ($cities as $c): ?>
                            <option
                                value="<?= htmlspecialchars($c['city']) ?>"
                                <?= $city === $c['city'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($c['city']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                </span>

            </label>


            <label class="find-field">

                <span class="find-field-icon orange">₹</span>

                <span class="find-field-copy">

                    <small>Maximum hourly rate</small>

                    <input
                        type="number"
                        name="max_rate"
                        min="1"
                        step="1"
                        placeholder="Any price"
                        value="<?= htmlspecialchars($_GET['max_rate'] ?? '') ?>"
                    >

                </span>

            </label>


            <button class="btn find-search-button" type="submit">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="6.5"/>
                    <path d="m16 16 4 4"/>
                </svg>

                <span>Search Parking</span>

            </button>

        </form>

    </div>


    <!-- RESULTS -->
    <section class="find-results">

        <div class="find-results-heading">

            <div>
                <span class="eyebrow">AVAILABLE PARKING</span>

                <h2>
                    <?= count($locations) ?>
                    <?= count($locations) === 1 ? 'parking location' : 'parking locations' ?>
                    found
                </h2>
            </div>


            <div class="find-controls">

                <label class="sort-label">
                    Sort by:

                    <select
                        name="sort"
                        onchange="this.form.submit()"
                        form="sortForm"
                    >
                        <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>
                            Recommended
                        </option>

                        <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>
                            Lowest price
                        </option>

                        <option value="availability" <?= $sort === 'availability' ? 'selected' : '' ?>>
                            Most available
                        </option>
                    </select>
                </label>

                <button
                    type="button"
                    class="view-toggle active"
                    data-view="both"
                    aria-pressed="true"
                >
                    ▦ List
                </button>

                <button
                    type="button"
                    class="view-toggle"
                    data-view="map"
                    aria-pressed="false"
                >
                    ⌖ Map
                </button>

            </div>

        </div>


        <form id="sortForm" method="get">
            <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>">
            <input type="hidden" name="max_rate" value="<?= htmlspecialchars($_GET['max_rate'] ?? '') ?>">
        </form>


        <?php if (!$locations): ?>

            <div class="find-empty">

                <div class="find-empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5"/>
                        <path d="m16 16 4 4"/>
                    </svg>
                </div>

                <h3>No parking locations found</h3>

                <p>
                    Try another city or increase the maximum hourly rate.
                </p>

                <a class="btn btn-sm" href="search-parking.php">
                    Clear Search
                </a>

            </div>

        <?php else: ?>

            <div class="find-content-grid">

                <!-- FILTERS -->
                <aside class="find-filter">

                    <div class="find-filter-title">

                        <div class="find-filter-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 6h16M7 12h10M10 18h4"/>
                            </svg>
                        </div>

                        <div>
                            <strong>Filters</strong>
                            <span>Refine your search</span>
                        </div>

                    </div>


                    <form method="get" class="find-filter-form">

                        <div class="find-filter-group">

                            <label for="filterCity">City</label>

                            <select id="filterCity" name="city">

                                <option value="">All cities</option>

                                <?php foreach ($cities as $c): ?>

                                    <option
                                        value="<?= htmlspecialchars($c['city']) ?>"
                                        <?= $city === $c['city'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($c['city']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="find-filter-group">

                            <label for="filterRate">
                                Maximum hourly rate
                            </label>

                            <div class="filter-rate-field">
                                <span>₹</span>

                                <input
                                    id="filterRate"
                                    type="number"
                                    name="max_rate"
                                    min="1"
                                    step="1"
                                    placeholder="Any price"
                                    value="<?= htmlspecialchars($_GET['max_rate'] ?? '') ?>"
                                >
                            </div>

                        </div>


                        <div class="find-filter-group">

                            <label>Availability</label>

                            <div class="filter-available">
                                <span class="filter-check">✓</span>

                                <span>
                                    <strong>Active locations</strong>
                                    <small>
                                        <?= count($locations) ?> available result<?= count($locations) === 1 ? '' : 's' ?>
                                    </small>
                                </span>
                            </div>

                        </div>


                        <div class="find-filter-group">

                            <label>Current sort</label>

                            <select name="sort">

                                <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>
                                    Recommended
                                </option>

                                <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>
                                    Lowest price
                                </option>

                                <option value="availability" <?= $sort === 'availability' ? 'selected' : '' ?>>
                                    Most available
                                </option>

                            </select>

                        </div>


                        <button class="btn filter-apply" type="submit">
                            Apply Filters
                        </button>

                    </form>


                    <a
                        class="find-reset"
                        href="search-parking.php"
                    >
                        ↻ Reset all
                    </a>

                </aside>


                <!-- LISTINGS -->
                <div class="find-listings">

                    <?php foreach ($locations as $index => $p): ?>

                        <?php
                        $available = (int)$p['available_slots'];
                        $total = max((int)$p['total_slots'], 1);
                        $percent = min(
                            100,
                            max(0, (int)round(($available / $total) * 100))
                        );
                        ?>

                        <article
                            class="find-listing"
                            data-location-id="<?= (int)$p['id'] ?>"
                        >

                            <div class="listing-thumb thumb-<?= (($index % 4) + 1) ?>">

                                <div class="thumb-building"></div>

                                <div class="thumb-sign">P</div>

                                <span class="listing-badge">
                                    <?= $available > 0 ? 'Available' : 'Full' ?>
                                </span>

                            </div>


                            <div class="listing-info">

                                <div class="listing-topline">

                                    <span class="listing-city">
                                        <?= htmlspecialchars($p['city']) ?>
                                    </span>

                                    <span class="listing-open">
                                        ● Active
                                    </span>

                                </div>


                                <h3>
                                    <?= htmlspecialchars($p['name']) ?>
                                </h3>


                                <p class="listing-address">

                                    <span>⌖</span>

                                    <?= htmlspecialchars($p['address']) ?>

                                </p>


                                <div class="listing-meta">

                                    <span>
                                        <b>🚗</b>
                                        <strong><?= $available ?></strong>
                                        / <?= (int)$p['total_slots'] ?> slots
                                    </span>

                                    <span>
                                        <b>◷</b>
                                        <?= htmlspecialchars(substr($p['opening_time'], 0, 5)) ?>
                                        –
                                        <?= htmlspecialchars(substr($p['closing_time'], 0, 5)) ?>
                                    </span>

                                </div>


                                <div class="listing-progress">

                                    <div class="progress-track">

                                        <span style="width:<?= $percent ?>%;"></span>

                                    </div>

                                    <small>
                                        <?= $percent ?>% capacity available
                                    </small>

                                </div>

                            </div>


                            <div class="listing-price-action">

                                <div class="listing-price">

                                    <small>From</small>

                                    <strong>
                                        ₹<?= number_format((float)$p['hourly_rate'], 0) ?>
                                    </strong>

                                    <span>per hour</span>

                                </div>


                                <a
                                    href="parking-details.php?id=<?= (int)$p['id'] ?>"
                                    class="btn view-slot-btn"
                                >
                                    View Slots
                                    <span>→</span>
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <!-- ARTIFICIAL MAP PREVIEW -->
                <aside class="find-map-panel" id="mapPanel">

                    <div class="google-map-card artificial-map-card">

                        <div class="artificial-map" aria-label="Artificial preview map showing available parking locations">
                            <div class="artificial-map-grid"></div>

                            <div class="artificial-road road-main"></div>
                            <div class="artificial-road road-secondary"></div>
                            <div class="artificial-road road-diagonal"></div>
                            <div class="artificial-road road-cross"></div>

                            <div class="artificial-area area-one">CENTRAL</div>
                            <div class="artificial-area area-two">CITY CENTRE</div>
                            <div class="artificial-area area-three">TECH DISTRICT</div>
                            <div class="artificial-area area-four">MALL AREA</div>

                            <?php foreach ($locations as $index => $p): ?>
                                <?php
                                $mapLeft = [18, 56, 76, 42][$index % 4];
                                $mapTop = [28, 20, 63, 72][$index % 4];
                                ?>
                                <a
                                    class="artificial-map-marker marker-<?= ($index % 4) + 1 ?>"
                                    style="left:<?= $mapLeft ?>%; top:<?= $mapTop ?>%;"
                                    href="parking-details.php?id=<?= (int)$p['id'] ?>"
                                    title="<?= htmlspecialchars($p['name']) ?>"
                                >
                                    ₹<?= number_format((float)$p['hourly_rate'], 0) ?>
                                </a>
                            <?php endforeach; ?>

                            <div class="artificial-map-label label-top">Parking zones</div>
                            <div class="artificial-map-label label-right">Main Road</div>
                            <div class="artificial-map-label label-bottom">Metro / Transit</div>

                            <div class="artificial-map-compass">N</div>
                            <div class="artificial-map-zoom"><span>+</span><span>−</span></div>

                            <div class="artificial-map-note">
                                <span class="preview-dot"></span>
                                <span>Interactive preview — Google Maps can be enabled later.</span>
                            </div>
                        </div>

                        <div class="map-count">
                            <div class="map-count-icon">⌖</div>
                            <div>
                                <strong>
                                    <?= count($locations) ?> location<?= count($locations) === 1 ? '' : 's' ?> shown
                                </strong>
                                <span>Markers represent your current parking results.</span>
                            </div>
                        </div>

                    </div>

                </aside>


        <?php endif; ?>

    </section>

</section>

<?php require 'includes/footer.php'; ?>
