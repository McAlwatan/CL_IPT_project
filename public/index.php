<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Julius+Sans+One&family=Valley+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/styles.css">
    <script src="https://kit.fontawesome.com/387650f35b.js" crossorigin="anonymous"></script>
    <title>CampusLink — Your campus, online</title>
</head>
<body>
    <?php require_once __DIR__ . '/../app/includes/navbar.php' ?>

    <main>
        <section class="hero" id="home">
            <div class="hero-inner">
                <div class="hero-copy">
                    <h1>The internet,<br>but only for<br>your university.</h1>
                    <p class="hero-sub">Chat, shared notes, a marketplace, and campus
                    groups, all in one place. Every account is a verified student, so
                    it's just the people you actually study with.</p>
                    <div class="hero-actions">
                        <a class="btn-primary" href="/IPT_WEB_PROJECT/CampusLink/public/auth/register.php">Create your account</a>
                    </div>
                    <p class="hero-note">Free for verified students at your university.</p>
                </div>

                <div class="hero-panel" aria-hidden="true">
                    <div class="id-card">
                        <div class="id-card-top">
                            <span class="id-card-brand">CampusLink</span>
                            <span class="id-card-status"><span class="panel-dot"></span>Verified</span>
                        </div>

                        <div class="id-card-body">
                            <span class="id-card-avatar">AJ</span>
                            <div class="id-card-info">
                                <p class="id-card-name">Amina Juma</p>
                                <p class="id-card-course">Computer Engineering, Year 3</p>
                                <p class="id-card-university">University of Dodoma</p>
                            </div>
                        </div>

                        <div class="id-card-bottom">
                            <span class="id-card-number">T23-03-22634</span>
                            <span class="id-card-barcode">
                                <span class="b3"></span><span class="b1"></span><span class="b2"></span>
                                <span class="b4"></span><span class="b1"></span><span class="b3"></span>
                                <span class="b1"></span><span class="b2"></span><span class="b4"></span>
                                <span class="b1"></span><span class="b3"></span><span class="b1"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats">
            <div class="stats-inner">
                <div class="stat">One login for campus</div>
                <div class="stat">Zero strangers</div>
                <div class="stat">Always free for students</div>
            </div>
        </section>

        <section class="features" id="features">
            <div class="features-inner">
                <div class="features-heading">
                    <h2>Everything campus life needs, in one login.</h2>
                    <p>No more scattered WhatsApp groups, expired Drive links, or
                    guessing who's actually a student.</p>
                </div>

                <div class="manifest">
                    <div class="manifest-row">
                        <span class="manifest-code"><i class="fa-solid fa-message"></i></span>
                        <div class="manifest-body">
                            <h3>Chat</h3>
                            <p>Class groups and direct messages, without the noise of
                            an open group chat app.</p>
                        </div>
                    </div>
                    <div class="manifest-row">
                        <span class="manifest-code"><i class="fa-solid fa-file"></i></span>
                        <div class="manifest-body">
                            <h3>Documents</h3>
                            <p>Lecture notes and past papers, uploaded by students,
                            organised by course.</p>
                        </div>
                    </div>
                    <div class="manifest-row">
                        <span class="manifest-code"><i class="fa-solid fa-user-group"></i></span>
                        <div class="manifest-body">
                            <h3>Groups</h3>
                            <p>Study groups and clubs, with posting rules admins
                            actually control.</p>
                        </div>
                    </div>
                    <div class="manifest-row">
                        <span class="manifest-code"><i class="fa-solid fa-store"></i></span>
                        <div class="manifest-body">
                            <h3>Marketplace</h3>
                            <p>Buy and sell textbooks and kit with people who go to
                            your own university.</p>
                        </div>
                    </div>
                    <div class="manifest-row">
                        <span class="manifest-code"><i class="fa-solid fa-id-card"></i></span>
                        <div class="manifest-body">
                            <h3>Verified identity</h3>
                            <p>Every account is tied to a real university email — it's
                            your campus, not the whole internet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-band">
            <div class="cta-inner">
                <div class="cta-copy">
                    <h2>Bring your campus online.</h2>
                    <p class="cta-sub">Set up takes a few minutes. No ads, no
                    strangers, nothing to pay.</p>
                    <ul class="cta-points">
                        <li><i class="fa-solid fa-paper-plane"></i> Verified with your university email</li>
                        <li><i class="fa-solid fa-paper-plane"></i> Free for every student, always</li>
                        <li><i class="fa-solid fa-paper-plane"></i> Live for your class the same day</li>
                    </ul>
                </div>
                <a class="btn-primary btn-inverse" href="/IPT_WEB_PROJECT/CampusLink/public/auth/register.php">Create your account</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-inner">
            <span class="footer-brand">CampusLink</span>
            <nav class="footer-links">
                <a href="#home">Home</a>
                <a href="#features">Features</a>
                <a href="/IPT_WEB_PROJECT/CampusLink/public/auth/login.php">Sign in</a>
            </nav>
            <span class="footer-copy">&copy; <?= date('Y') ?> CampusLink</span>
        </div>
    </footer>

    <script src="./js/main.js"></script>
</body>
</html>