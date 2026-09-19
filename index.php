<?php
$pageTitle = 'ParkNexa | Smart Parking Reservation System';
require 'includes/header.php';
?>

<section class="hero">
    <div class="hero-grid">

        <div class="hero-copy">
            <span class="eyebrow">SMART PARKING RESERVATION</span>

            <h1>
                Find a slot before<br>
                you <span>arrive.</span>
            </h1>

            <p>
                Search parking locations, check slot availability, reserve a space
                and manage your vehicle parking online.
            </p>

            <div class="hero-actions">
                <a class="btn" href="search-parking.php">Find Parking</a>
                <a class="btn btn-outline" href="register.php">Create Account</a>
            </div>

            <div class="hero-benefits">

                <div class="hero-benefit">
                    <div class="hero-benefit-icon">✓</div>
                    <div>
                        <strong>Secure &amp; Reliable</strong>
                        <small>Your data is safe with us</small>
                    </div>
                </div>

                <div class="hero-benefit">
                    <div class="hero-benefit-icon">ϟ</div>
                    <div>
                        <strong>Real-time Availability</strong>
                        <small>Updated parking slots</small>
                    </div>
                </div>

                <div class="hero-benefit">
                    <div class="hero-benefit-icon">▣</div>
                    <div>
                        <strong>Access Anywhere</strong>
                        <small>Web, tablet or mobile</small>
                    </div>
                </div>

            </div>
        </div>

        <div class="hero-visual">

            <div class="parking-photo"></div>

            <div class="availability-float">

                <div class="availability-top">

                    <div class="availability-pin">⌖</div>

                    <div class="availability-name">
                        <strong>Metro Central Parking</strong>
                        <small>New Delhi</small>
                    </div>

                </div>

                <div class="availability-row">
                    <span class="open">● 24 slots available</span>
                    <span class="rate">₹40/hr</span>
                </div>

            </div>

            <div class="visual-note">
                Less searching.<br>
                More parking.
            </div>

        </div>

    </div>
</section>


<section class="how-section">

    <div class="how-decoration how-decoration-left"></div>
    <div class="how-decoration how-decoration-right"></div>

    <div class="how-container">

        <div class="how-heading">

            <div class="how-label">
                HOW IT WORKS
            </div>

            <h2>
                Reserve your parking in
                <span>four simple steps</span>
            </h2>

            <p>
                From search to park, it only takes a few clicks.
                Save time, avoid the hassle and get a guaranteed parking space.
            </p>

        </div>


        <div class="steps-flow">

            <!-- STEP 01 -->
            <article class="step-card purple-card">

                <div class="step-card-top">

                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="M16 16l4 4"></path>
                        </svg>
                    </div>

                    <span class="step-number">01</span>

                </div>

                <div class="step-content">

                    <h3>Search</h3>

                    <p>
                        Choose a parking location and city.
                    </p>

                    <a href="search-parking.php" class="step-link purple-link">
                        Get Started
                        <span>→</span>
                    </a>

                </div>

                <div class="step-art">
                    <svg viewBox="0 0 60 60" aria-hidden="true">
                        <path d="M20 45c0-12 7-22 18-28"></path>
                        <circle cx="42" cy="18" r="7"></circle>
                    </svg>
                </div>

            </article>


            <!-- STEP 02 -->
            <article class="step-card orange-card">

                <div class="step-card-top">

                    <div class="step-icon orange-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="4" y="4" width="6" height="6" rx="1"></rect>
                            <rect x="14" y="4" width="6" height="6" rx="1"></rect>
                            <rect x="4" y="14" width="6" height="6" rx="1"></rect>
                            <rect x="14" y="14" width="6" height="6" rx="1"></rect>
                        </svg>
                    </div>

                    <span class="step-number">02</span>

                </div>

                <div class="step-content">

                    <h3>Check Slots</h3>

                    <p>
                        See available spaces and hourly rates.
                    </p>

                    <a href="search-parking.php" class="step-link orange-link">
                        View Availability
                        <span>→</span>
                    </a>

                </div>

                <div class="step-art">
                    <svg viewBox="0 0 60 60" aria-hidden="true">
                        <path d="M12 42h7M26 34h7M40 24h7"></path>
                        <path d="M14 42V27M28 34V20M42 24V14"></path>
                    </svg>
                </div>

            </article>


            <!-- STEP 03 -->
            <article class="step-card purple-card">

                <div class="step-card-top">

                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="4" y="5" width="16" height="15" rx="2"></rect>
                            <path d="M8 3v4M16 3v4M4 9h16"></path>
                            <path d="M8 13h2M12 13h2M16 13h1"></path>
                            <path d="M8 16h2M12 16h2"></path>
                        </svg>
                    </div>

                    <span class="step-number">03</span>

                </div>

                <div class="step-content">

                    <h3>Reserve</h3>

                    <p>
                        Enter vehicle and reservation details.
                    </p>

                    <a href="search-parking.php" class="step-link purple-link">
                        Book Now
                        <span>→</span>
                    </a>

                </div>

                <div class="step-art">
                    <svg viewBox="0 0 60 60" aria-hidden="true">
                        <path d="M30 9v42"></path>
                        <path d="M20 18h20M20 28h20M20 38h20"></path>
                    </svg>
                </div>

            </article>


            <!-- STEP 04 -->
            <article class="step-card orange-card">

                <div class="step-card-top">

                    <div class="step-icon orange-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 16l1.5-5h11L19 16"></path>
                            <path d="M8 11l1.3-3h5.4l1.3 3"></path>
                            <circle cx="7.5" cy="17.5" r="1.5"></circle>
                            <circle cx="16.5" cy="17.5" r="1.5"></circle>
                        </svg>
                    </div>

                    <span class="step-number">04</span>

                </div>

                <div class="step-content">

                    <h3>Park</h3>

                    <p>
                        Use your booking code to manage the reservation.
                    </p>

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <a href="my-reservations.php" class="step-link orange-link">
                            Manage Booking
                            <span>→</span>
                        </a>

                    <?php else: ?>

                        <a href="login.php" class="step-link orange-link">
                            Manage Booking
                            <span>→</span>
                        </a>

                    <?php endif; ?>

                </div>

                <div class="step-art">
                    <svg viewBox="0 0 60 60" aria-hidden="true">
                        <rect x="18" y="13" width="24" height="34" rx="3"></rect>
                        <path d="M24 20h12M24 27h12M24 34h7"></path>
                    </svg>
                </div>

            </article>

        </div>

    </div>

</section>


<?php require 'includes/footer.php'; ?>