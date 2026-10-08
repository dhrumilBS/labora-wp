<?php
/**
 * Homepage (front page). Markup converted unchanged from the HTML site's index.html by a script, so the design,
 * animations, and interactions match it exactly. Editable fields are added section by section once approved.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

$labora_assets = esc_url( LABORA_URI . '/assets/' );

// Preload the hero screenshot (phone crop or desktop sizes), as the HTML site does, for a fast first paint
add_action( 'wp_head', function () use ( $labora_assets ) {
	echo '<link rel="preload" as="image" type="image/webp" media="(max-width: 640px)" href="' . $labora_assets . 'img/screens/dashboard-m-640.webp" imagesrcset="' . $labora_assets . 'img/screens/dashboard-m-640.webp 640w, ' . $labora_assets . 'img/screens/dashboard-m-800.webp 800w, ' . $labora_assets . 'img/screens/dashboard-m-1080.webp 985w" imagesizes="calc(100vw - 40px)" fetchpriority="high">' . "\n";
	echo '<link rel="preload" as="image" type="image/webp" media="(min-width: 641px)" href="' . $labora_assets . 'img/screens/dashboard-1200.webp" imagesrcset="' . $labora_assets . 'img/screens/dashboard-800.webp 800w, ' . $labora_assets . 'img/screens/dashboard-1200.webp 1200w, ' . $labora_assets . 'img/screens/dashboard-1600.webp 1600w, ' . $labora_assets . 'img/screens/dashboard-2320.webp 2320w" imagesizes="(max-width: 1024px) calc(100vw - 80px), 58vw" fetchpriority="high">' . "\n";
}, 3 );

get_header();
?>

<main id="main">


<section class="hero" aria-labelledby="hero-title">
  <div class="container hero-grid">
    <div class="hero-copy">
      <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Laboratory management software</p>
      <h1 id="hero-title">Run your laboratory from first sample to signed report</h1>
      <p class="lead">Pathology, imaging, home collection, and billing in one connected platform. Track every sample, stay ahead of turnaround targets, and send signed reports in one click.</p>
      <div class="hero-ctas">
        <a class="btn btn--primary btn--lg" href="#demo">Book a demo</a>
        <a class="btn btn--ghost btn--lg" href="#product-tour">Watch the 1-min tour</a>
      </div>
      <ul class="hero-assurances">
        <li><svg class="icon" aria-hidden="true"><use href="#i-scan"/></svg>Lab and imaging in one system</li>
        <li><svg class="icon" aria-hidden="true"><use href="#i-building"/></svg>Built for multi-center labs</li>
        <li><svg class="icon" aria-hidden="true"><use href="#i-cloud"/></svg>Secure cloud platform</li>
        <li><svg class="icon" aria-hidden="true"><use href="#i-sliders"/></svg>Configurable to your workflows</li>
      </ul>
    </div>
    <div class="hero-media">
      <div class="shot shot--hero"><picture><source media="(max-width: 640px)" type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/dashboard-m-640.webp 640w, <?php echo $labora_assets; ?>img/screens/dashboard-m-800.webp 800w, <?php echo $labora_assets; ?>img/screens/dashboard-m-1080.webp 985w" sizes="calc(100vw - 40px)" width="640" height="684"><source type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/dashboard-800.webp 800w, <?php echo $labora_assets; ?>img/screens/dashboard-1200.webp 1200w, <?php echo $labora_assets; ?>img/screens/dashboard-1600.webp 1600w, <?php echo $labora_assets; ?>img/screens/dashboard-2320.webp 2320w" sizes="(max-width: 1024px) calc(100vw - 80px), 58vw" width="2320" height="2000"><img src="<?php echo $labora_assets; ?>img/screens/dashboard-1200.webp" width="2320" height="2000" alt="Labora dashboard showing 248 cases today, 18,420 dollars collected, 2 hours 41 minutes average turnaround, 42 reports ready to sign, a patient-case trend, tests by department, recent cases, and items that need attention." fetchpriority="high" decoding="async"></picture></div>
    </div>
  </div>
</section>

<!-- Trusted brands. PLACEHOLDER logos: replace each <li> with the approved customer logo (SVG preferred) before launch. -->
<section class="brands" aria-labelledby="brands-title">
  <div class="container">
    <h2 id="brands-title" class="brands-title">Trusted by diagnostic labs and imaging centers</h2>
  </div>
  <div class="marquee" data-marquee>
    <ul class="marquee-track">
      <li class="brand w-wide">Northfield Diagnostics</li>
      <li class="brand w-light">cedar pathology</li>
      <li class="brand w-heavy">Meridian</li>
      <li class="brand w-split">Summit<span>Labs</span></li>
      <li class="brand w-wide">Riverside Clinical</li>
      <li class="brand w-light">oakline radiology</li>
      <li class="brand w-heavy">Harbor Dx</li>
      <li class="brand w-split">Crestview<span>Care</span></li>
    </ul>
    <ul class="marquee-track" aria-hidden="true">
      <li class="brand w-wide">Northfield Diagnostics</li>
      <li class="brand w-light">cedar pathology</li>
      <li class="brand w-heavy">Meridian</li>
      <li class="brand w-split">Summit<span>Labs</span></li>
      <li class="brand w-wide">Riverside Clinical</li>
      <li class="brand w-light">oakline radiology</li>
      <li class="brand w-heavy">Harbor Dx</li>
      <li class="brand w-split">Crestview<span>Care</span></li>
    </ul>
  </div>
</section>

<section class="section video-sec" id="product-tour" aria-labelledby="video-title">
  <div class="container">
    <div class="split-head reveal">
      <h2 id="video-title">One patient, registration to signed report</h2>
      <p>A real case moving through Labora: the front desk registers a CBC, the tube is scanned into Rack B-04, the ultrasound is reported from a template, and the doctor gets the signed PDF. No sound needed.</p>
    </div>
    <!-- Autoplays muted and looped once on screen. Before that only the lazy poster loads; the video file is
         requested after page load, when the section scrolls into view (full 1080p on desktop and tablet, 720p on phones). -->
    <div class="video-frame reveal" data-video data-src-720="<?php echo $labora_assets; ?>video/labora-tour-v3-720.mp4" data-src-1080="<?php echo $labora_assets; ?>video/labora-tour-v3-1080.mp4">
      <picture class="video-poster"><source type="image/webp" srcset="<?php echo $labora_assets; ?>video/labora-tour-v3-poster-640.webp 640w, <?php echo $labora_assets; ?>video/labora-tour-v3-poster-960.webp 960w, <?php echo $labora_assets; ?>video/labora-tour-v3-poster-1600.webp 1600w" sizes="(max-width: 1240px) calc(100vw - 40px), 1200px"><img src="<?php echo $labora_assets; ?>video/labora-tour-v3-poster-1600.webp" width="1600" height="900" alt="Labora dashboard: cases today, amount collected, average turnaround, recent cases, and home collection on a live map" loading="lazy" decoding="async"></picture>
      <video muted loop playsinline disablepictureinpicture preload="none" width="1600" height="900" aria-hidden="true"></video>
      <button class="video-toggle" type="button" aria-label="Play product tour" hidden><svg class="icon i-play" aria-hidden="true"><use href="#i-play"/></svg><svg class="icon i-pause" aria-hidden="true"><use href="#i-pause"/></svg></button>
    </div>
    <ol class="video-steps">
      <li><b>Register</b> a patient and tests</li>
      <li><b>Collect</b> and barcode the tube</li>
      <li><b>Report</b> lab and imaging</li>
      <li><b>Deliver</b> the signed PDF</li>
    </ol>
  </div>
</section>




<section class="section hm-who" id="who-its-for" aria-labelledby="who-title">
  <div class="container hm-split">
    <div class="hm-copy reveal">
      <p class="hm-label">Who it's for</p>
      <h2 class="hm-h2" id="who-title">Made for diagnostic labs, not research benches</h2>
      <p>A single test passes through five pairs of hands before a patient sees a result: the front desk, a phlebotomist, a technician, the reporting doctor, and whoever sends it. Labora is built around that patient case, so the handoffs stop being where the hours go.</p>
    </div>
    <ul class="hm-labs reveal">
      <li><svg class="icon" aria-hidden="true"><use href="#i-flask"/></svg><div><h3>Independent pathology labs</h3><p>Barcode tracking, validation, and signed reports.</p></div></li><li><svg class="icon" aria-hidden="true"><use href="#i-scan"/></svg><div><h3>Imaging and diagnostic centers</h3><p>Ultrasound, X-ray, CT, and MRI templates beside lab results.</p></div></li><li><svg class="icon" aria-hidden="true"><use href="#i-building"/></svg><div><h3>Multi-center lab chains</h3><p>Every branch and collection point on one setup.</p></div></li><li><svg class="icon" aria-hidden="true"><use href="#i-hospital"/></svg><div><h3>Hospital laboratories</h3><p>Urgent cases on their own turnaround targets.</p></div></li><li><svg class="icon" aria-hidden="true"><use href="#i-home"/></svg><div><h3>Home collection services</h3><p>Bookings, live ETA, and automatic patient updates.</p></div></li><li><svg class="icon" aria-hidden="true"><use href="#i-heart"/></svg><div><h3>Cardiology and ECG clinics</h3><p>ECG, 2D echo, and treadmill reports in the same record.</p></div></li>
    </ul>
  </div>
</section>

<section class="section section--feature" id="home-collection" aria-labelledby="home-collection-title">
  <div class="container feature">
    <div class="feature-copy reveal">
      <p class="step-tag"><span class="step-num">01</span>Collect<span class="step-mod">Home collection</span></p>
      <h2 id="home-collection-title">Run home collection without the phone calls</h2>
      <p>Assign bookings to phlebotomists, follow every visit on a live map, and keep patients updated automatically, so your front desk stops answering “where is the rider?” calls.</p>
      <ul class="checks"><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Bookings by status: pending, en route, in progress, done</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Live phlebotomist location and ETA</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Automatic SMS updates to patients</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Who is on duty, at a glance</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Address and tests on every visit card</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Collected samples move straight into tracking</li></ul>
      <a class="link-arrow" href="<?php echo esc_url( home_url( '/platform/home-collection/' ) ); ?>">See home collection <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <figure class="feature-media reveal">
      <div class="shot"><picture><source media="(max-width: 640px)" type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/home-collection-m-640.webp 618w" sizes="calc(100vw - 40px)" width="618" height="1098"><source type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/home-collection-800.webp 800w, <?php echo $labora_assets; ?>img/screens/home-collection-1200.webp 1200w, <?php echo $labora_assets; ?>img/screens/home-collection-1600.webp 1600w, <?php echo $labora_assets; ?>img/screens/home-collection-2320.webp 2320w" sizes="(max-width: 1024px) calc(100vw - 80px), 58vw" width="2320" height="2000"><img src="<?php echo $labora_assets; ?>img/screens/home-collection-1200.webp" width="2320" height="2000" alt="Home collection board listing patient bookings by status with addresses, tests, and arrival times, next to a live map tracking phlebotomist Marcus Lee 2.1 miles away." loading="lazy" decoding="async"></picture></div>
      <figcaption>Every visit, its tests, and its ETA in one list, with the phlebotomist on a live map.</figcaption>
    </figure>
  </div>
</section>

<section class="section section--feature" id="sample-tracking" aria-labelledby="sample-tracking-title">
  <div class="container feature feature--reverse">
    <div class="feature-copy reveal">
      <p class="step-tag"><span class="step-num">02</span>Track<span class="step-mod">Sample tracking</span></p>
      <h2 id="sample-tracking-title">Every tube traced, from collection to rack to report</h2>
      <p>Scan a barcode to match the tube to the right patient and test, see every step it has passed, and know exactly which rack and slot it sits in. No more searching refrigerators for a sample.</p>
      <ul class="checks"><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Barcode scan with instant patient match</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Timeline from registration to report</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Rack maps color-coded by tube type</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Hold or reject with the reason recorded</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Urgent samples flagged</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Live accession queue</li></ul>
      <a class="link-arrow" href="<?php echo esc_url( home_url( '/platform/sample-tracking/' ) ); ?>">See sample tracking <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <figure class="feature-media reveal">
      <div class="shot"><picture><source media="(max-width: 640px)" type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/sample-tracking-m-640.webp 636w" sizes="calc(100vw - 40px)" width="636" height="952"><source type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/sample-tracking-800.webp 800w, <?php echo $labora_assets; ?>img/screens/sample-tracking-1200.webp 1200w, <?php echo $labora_assets; ?>img/screens/sample-tracking-1600.webp 1600w, <?php echo $labora_assets; ?>img/screens/sample-tracking-2320.webp 2320w" sizes="(max-width: 1024px) calc(100vw - 80px), 58vw" width="2320" height="2000"><img src="<?php echo $labora_assets; ?>img/screens/sample-tracking-1200.webp" width="2320" height="2000" alt="Sample tracking screen: barcode LB-24817-02 matched to patient Emily Carter, her sample timeline from registration to testing, its slot in hematology rack B-04, and a list of received and rejected samples." loading="lazy" decoding="async"></picture></div>
      <figcaption>Scan one tube to see the patient, the test, each handoff, and its exact rack slot.</figcaption>
    </figure>
  </div>
</section>

<section class="section section--feature" id="turnaround-tracking" aria-labelledby="turnaround-tracking-title">
  <div class="container feature">
    <div class="feature-copy reveal">
      <p class="step-tag"><span class="step-num">03</span>Monitor<span class="step-mod">Turnaround (TAT)</span></p>
      <h2 id="turnaround-tracking-title">Catch turnaround delays before the doctor calls</h2>
      <p>Every case carries a target time. Labora shows elapsed time against that target live, highlights what is at risk, and alerts the right people the moment a report runs late.</p>
      <ul class="checks"><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Target time for every case</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Elapsed vs. target, live</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>At-risk and overshoot views</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Automatic alerts when a target is missed</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>A reason logged for every delay</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>On-time delivery rate at a glance</li></ul>
      <a class="link-arrow" href="<?php echo esc_url( home_url( '/platform/turnaround-tracking/' ) ); ?>">See turnaround tracking <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <figure class="feature-media reveal">
      <div class="shot"><picture><source media="(max-width: 640px)" type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/turnaround-tracking-m-640.webp 640w, <?php echo $labora_assets; ?>img/screens/turnaround-tracking-m-1080.webp 824w" sizes="calc(100vw - 40px)" width="640" height="770"><source type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/turnaround-tracking-800.webp 800w, <?php echo $labora_assets; ?>img/screens/turnaround-tracking-1200.webp 1200w, <?php echo $labora_assets; ?>img/screens/turnaround-tracking-1600.webp 1600w, <?php echo $labora_assets; ?>img/screens/turnaround-tracking-2320.webp 2320w" sizes="(max-width: 1024px) calc(100vw - 80px), 58vw" width="2320" height="2000"><img src="<?php echo $labora_assets; ?>img/screens/turnaround-tracking-1200.webp" width="2320" height="2000" alt="Turnaround tracker showing 96.4% of reports delivered on time, 5 cases at risk, and each case’s elapsed time against its target, with an alert sent to Dr. Patel for a Troponin-T test past target." loading="lazy" decoding="async"></picture></div>
      <figcaption>Green is on time, amber is at risk, red is past target, so the team knows what to do next.</figcaption>
    </figure>
  </div>
</section>

<section class="section section--feature" id="reporting" aria-labelledby="reporting-title">
  <div class="container feature feature--reverse">
    <div class="feature-copy reveal">
      <p class="step-tag"><span class="step-num">04</span>Report<span class="step-mod">Pathology &amp; imaging</span></p>
      <h2 id="reporting-title">Pathology and imaging reports in one system</h2>
      <p>Report lab tests, ultrasound, X-ray, CT, MRI, ECG, 2D echo, and treadmill studies from ready templates. Drafts save automatically, and doctors sign and send in one click.</p>
      <ul class="checks"><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>One workspace for every department</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Template library for each modality</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Drafts saved automatically</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Structured findings and impression</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Preview before release</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Digital signature, then send</li></ul>
      <a class="link-arrow" href="<?php echo esc_url( home_url( '/platform/radiology-reporting/' ) ); ?>">See reporting <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <figure class="feature-media reveal">
      <div class="shot"><picture><source media="(max-width: 640px)" type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-m-640.webp 640w, <?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-m-800.webp 800w, <?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-m-1080.webp 1080w" sizes="calc(100vw - 40px)" width="640" height="625"><source type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-800.webp 800w, <?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-1200.webp 1200w, <?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-1600.webp 1600w, <?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-2320.webp 2320w" sizes="(max-width: 1024px) calc(100vw - 80px), 58vw" width="2320" height="2000"><img src="<?php echo $labora_assets; ?>img/screens/pathology-imaging-reporting-1200.webp" width="2320" height="2000" alt="Radiology reporting screen with tabs for pathology, ultrasound, X-ray, CT, MRI, ECG, 2D echo, and treadmill, a list of 58 ultrasound templates, and a drafted abdomen and pelvis report ready to sign and send." loading="lazy" decoding="async"></picture></div>
      <figcaption>Pick a template, complete the findings, then sign and send. Lab and imaging share the same patient case.</figcaption>
    </figure>
  </div>
</section>

<section class="section section--feature" id="business-insights" aria-labelledby="business-insights-title">
  <div class="container feature">
    <div class="feature-copy reveal">
      <p class="step-tag"><span class="step-num">05</span>Grow<span class="step-mod">Business insights</span></p>
      <h2 id="business-insights-title">See what every center, day, and doctor brings in</h2>
      <p>Track billing, collections, and outstanding dues across all your centers, compare this week with last, and see which referring doctors and partner labs drive your business.</p>
      <ul class="checks"><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Billed, collected, and outstanding</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Revenue by day and by center</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Home collection and B2B revenue</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Dues by patient and partner lab</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Top referring doctors with trends</li><li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Week-over-week comparison</li></ul>
      <a class="link-arrow" href="<?php echo esc_url( home_url( '/platform/business-insights/' ) ); ?>">See business insights <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <figure class="feature-media reveal">
      <div class="shot"><picture><source media="(max-width: 640px)" type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/business-insights-m-640.webp 640w, <?php echo $labora_assets; ?>img/screens/business-insights-m-1080.webp 747w" sizes="calc(100vw - 40px)" width="640" height="559"><source type="image/webp" srcset="<?php echo $labora_assets; ?>img/screens/business-insights-800.webp 800w, <?php echo $labora_assets; ?>img/screens/business-insights-1200.webp 1200w, <?php echo $labora_assets; ?>img/screens/business-insights-1600.webp 1600w, <?php echo $labora_assets; ?>img/screens/business-insights-2320.webp 2320w" sizes="(max-width: 1024px) calc(100vw - 80px), 58vw" width="2320" height="2000"><img src="<?php echo $labora_assets; ?>img/screens/business-insights-1200.webp" width="2320" height="2000" alt="Business insights showing 64,280 dollars billed this week, 58,910 collected, 5,370 outstanding, revenue by day and by center, and top referring doctors." loading="lazy" decoding="async"></picture></div>
      <figcaption>Billing, collections, and referral business across every center, updated as cases are billed.</figcaption>
    </figure>
  </div>
</section>







<!-- Client testimonials. PLACEHOLDER quotes and names: replace with verified, approved customer quotes before launch.
     Do not add Review/AggregateRating schema for these. -->
<section class="section hm-compare" id="what-changes" aria-labelledby="compare-title">
  <div class="container">
    <div class="hm-head reveal">
      <p class="hm-label">What changes</p>
      <h2 class="hm-h2" id="compare-title">The same day in your lab, without the chasing</h2>
    </div>
    <!-- [html-validate-disable-block no-redundant-role: the rows are laid out with CSS grid, so explicit roles keep table semantics in Safari] -->
    <table class="hm-table reveal" role="table">
      <caption class="sr-only">A lab day today and with Labora</caption>
      <thead role="rowgroup"><tr class="hm-tr hm-th" role="row"><th scope="col" role="columnheader">Task</th><th scope="col" role="columnheader">Today</th><th scope="col" role="columnheader">With Labora</th></tr></thead>
      <tbody role="rowgroup"><tr class="hm-tr" role="row"><th scope="row" class="hm-task" role="rowheader">Finding a sample</th><td class="hm-old" role="cell" data-label="Today">Someone searches two refrigerators</td><td class="hm-new" role="cell" data-label="With Labora">Scan the tube to see the patient, test, rack, and slot</td></tr><tr class="hm-tr" role="row"><th scope="row" class="hm-task" role="rowheader">Knowing a report will be late</th><td class="hm-old" role="cell" data-label="Today">A referring doctor calls to ask</td><td class="hm-new" role="cell" data-label="With Labora">Cases at risk are flagged while there is still time to act</td></tr><tr class="hm-tr" role="row"><th scope="row" class="hm-task" role="rowheader">Writing an imaging report</th><td class="hm-old" role="cell" data-label="Today">Typed from scratch in a separate system</td><td class="hm-new" role="cell" data-label="With Labora">A template for the modality, next to the lab results</td></tr><tr class="hm-tr" role="row"><th scope="row" class="hm-task" role="rowheader">Releasing a report</th><td class="hm-old" role="cell" data-label="Today">Printed, signed, and sent by hand</td><td class="hm-new" role="cell" data-label="With Labora">Signed by an authorized doctor and sent in one click</td></tr><tr class="hm-tr" role="row"><th scope="row" class="hm-task" role="rowheader">Keeping home-collection patients informed</th><td class="hm-old" role="cell" data-label="Today">The front desk takes “where is the rider?” calls</td><td class="hm-new" role="cell" data-label="With Labora">Automatic SMS updates with a live ETA</td></tr><tr class="hm-tr" role="row"><th scope="row" class="hm-task" role="rowheader">Seeing the day's revenue</th><td class="hm-old" role="cell" data-label="Today">Totals added up from each center at night</td><td class="hm-new" role="cell" data-label="With Labora">Billed, collected, and dues by center as the day goes</td></tr></tbody>
    </table>
  </div>
</section>

<section class="section" id="customers" aria-labelledby="customers-title">
  <div class="container">
    <div class="split-head reveal">
      <h2 id="customers-title">From the people who run the lab every day</h2>
      <p>Lab directors, front-desk leads, and reporting doctors on what changed after the move to Labora.</p>
    </div>
    <!-- Testimonial slider: native scroll-snap (swipe/trackpad), arrow buttons and gentle autoplay in main.js -->
    <section class="t-slider reveal" data-slider aria-roledescription="carousel" aria-label="Client testimonials">
      <div class="t-track" tabindex="0">
        <div class="t-slide" role="group" aria-roledescription="slide" aria-label="1 of 3"><figure>
          <blockquote><p>Every morning started with calls asking if a report was ready. Now the signed PDF goes to the patient and the referring doctor the moment I approve it, and the phone stays quiet.</p></blockquote>
          <figcaption><strong>Dr. Priya Nair</strong>Lab director, three-center pathology lab</figcaption>
        </figure></div>
        <div class="t-slide" role="group" aria-roledescription="slide" aria-label="2 of 3"><figure>
          <blockquote><p>Every tube is scanned at the desk, so when a doctor asks, I can tell them the rack and slot in seconds instead of searching two refrigerators.</p></blockquote>
          <figcaption><strong>Daniel Reyes</strong>Operations manager, diagnostic center</figcaption>
        </figure></div>
        <div class="t-slide" role="group" aria-roledescription="slide" aria-label="3 of 3"><figure>
          <blockquote><p>Ultrasound templates sit next to the lab results for the same patient. I report, sign, and send from one screen.</p></blockquote>
          <figcaption><strong>Dr. Sarah Mitchell</strong>Consultant radiologist, imaging center</figcaption>
        </figure></div>
      </div>
      <div class="t-nav">
        <button class="t-btn t-prev" type="button" aria-label="Previous testimonial"><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></button>
        <span class="t-count" aria-live="polite">1 / 3</span>
        <button class="t-btn t-next" type="button" aria-label="Next testimonial"><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></button>
      </div>
    </section>
  </div>
</section>

<section class="section security" id="security" aria-labelledby="sec-title">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow"><span class="dot" aria-hidden="true"></span>Security and data integrity</p>
      <h2 id="sec-title">Know who touched every report, and when</h2>
      <p>Every action in Labora is attributed and recorded, and every report is released by an authorized doctor, so you can always answer who did what, and when.</p>
      <a class="link-arrow sec-link" href="<?php echo esc_url( home_url( '/security/' ) ); ?>">Visit the Trust Center <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="sec-grid">
      <ul class="sec-list">
        <li class="reveal"><span class="s-icon"><svg class="icon" aria-hidden="true"><use href="#i-users"/></svg></span><div><h3>Role-based access</h3><p>Front desk, technicians, doctors, and accounts each see what their role needs.</p></div></li>
        <li class="reveal"><span class="s-icon"><svg class="icon" aria-hidden="true"><use href="#i-history"/></svg></span><div><h3>Audit trails</h3><p>Every registration, edit, signature, and payment is recorded with user and time.</p></div></li>
        <li class="reveal"><span class="s-icon"><svg class="icon" aria-hidden="true"><use href="#i-edit"/></svg></span><div><h3>Digital signatures</h3><p>Reports are released only after an authorized doctor signs them.</p></div></li>
        <li class="reveal"><span class="s-icon"><svg class="icon" aria-hidden="true"><use href="#i-shield"/></svg></span><div><h3>Data integrity</h3><p>Structured records and controlled changes keep results consistent.</p></div></li>
        <li class="reveal"><span class="s-icon"><svg class="icon" aria-hidden="true"><use href="#i-cloud"/></svg></span><div><h3>Secure cloud infrastructure</h3><p>Hosted on secured, monitored cloud infrastructure.</p></div></li>
        <li class="reveal"><span class="s-icon"><svg class="icon" aria-hidden="true"><use href="#i-activity"/></svg></span><div><h3>Activity tracking</h3><p>See recent activity across users, centers, and cases.</p></div></li>
        <li class="reveal"><span class="s-icon"><svg class="icon" aria-hidden="true"><use href="#i-refresh"/></svg></span><div><h3>Backup and recovery</h3><p>Regular backups help protect your records against loss.</p></div></li>
      </ul>
      <div class="audit reveal" aria-hidden="true">
        <div class="audit-head"><div class="mh4"><svg class="icon" aria-hidden="true"><use href="#i-history"/></svg>Audit trail, case LB-24817</div><span class="live">Recording</span></div>
        <ol>
          <li><span class="a-av">SM</span><span class="a-txt"><b>Dr. S. Mitchell</b> signed and sent report<small>Role: Pathologist</small></span><span class="a-time">10:31:08</span></li>
          <li><span class="a-av">RK</span><span class="a-txt"><b>R. Kumar</b> validated CBC results<small>Hematology</small></span><span class="a-time">10:12:44</span></li>
          <li><span class="a-av">LB</span><span class="a-txt"><b>Labora</b> sent turnaround alert<small>Urgent priority</small></span><span class="a-time">09:58:02</span></li>
          <li><span class="a-av">AM</span><span class="a-txt"><b>A. Mehta</b> stored sample in Rack B-04, slot B4<small>Barcode scan</small></span><span class="a-time">09:24:19</span></li>
          <li><span class="a-av">JO</span><span class="a-txt"><b>J. Ortiz</b> collected EDTA sample<small>Front desk</small></span><span class="a-time">09:10:37</span></li>
        </ol>
        <div class="audit-foot"><span>Immutable history</span><span>User attribution</span><span>Signed releases</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section hm-start" id="getting-started" aria-labelledby="start-title">
  <div class="container">
    <div class="hm-head hm-head--split reveal">
      <div>
        <p class="hm-label">Getting started</p>
        <h2 class="hm-h2" id="start-title">A rollout your team can keep working through</h2>
      </div>
      <p>Changing lab software is a big decision. This is what happens after you choose Labora, one center at a time.</p>
    </div>
    <ol class="hm-steps">
      <li class="reveal"><span class="hm-num">01</span><h3>Review your setup</h3><p>We go through your test menu, price list, report templates, centers, and the analyzers you use, so connections are planned before anything changes.</p></li><li class="reveal"><span class="hm-num">02</span><h3>Move your data</h3><p>Tests, price lists, report templates, and referring doctors are migrated for you. You start with your own setup, not a blank system.</p></li><li class="reveal"><span class="hm-num">03</span><h3>Train every role</h3><p>Front desk, phlebotomists, technicians, and pathologists each learn the screens they use every day.</p></li><li class="reveal"><span class="hm-num">04</span><h3>Go live, center by center</h3><p>The first center goes live while the others keep working. The rest follow when your team is ready.</p></li>
    </ol>
    <p class="hm-note reveal">Which analyzers connect depends on your equipment, so we review the list with you in the demo. <a class="link-arrow" href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>">See how plans work <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a></p>
  </div>
</section>

<section class="section demo" id="demo" aria-labelledby="demo-title">
  <div class="container demo-box">
    <div class="demo-copy reveal">
      <h2 id="demo-title">See Labora with your own test menu</h2>
      <p>A 20-minute walkthrough set up with your tests, departments, and price list, so you see your own lab day in Labora, not a generic demo.</p>
      <ul class="demo-points">
        <li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>A walkthrough built around your tests, departments, and centers</li>
        <li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>Answers on analyzers, data migration, and setup</li>
        <li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>A clear plan for how your team would get started</li>
      </ul>
      <div class="demo-alt"><span>Prefer to talk first?</span><a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Talk to an expert</a></div>
    </div>
    <div class="form-card reveal">
      <!-- Set data-endpoint to your form handler. Without it, the form validates, confirms, and goes to data-thanks (demo mode). -->
      <form id="demo-form" novalidate data-endpoint="" data-thanks="thank-you/" aria-describedby="form-error">
        <h3>Book a demo</h3>
        <p>Tell us a little about your lab and we'll be in touch to schedule a time.</p>
        <div class="form-grid">
          <div class="field"><label for="f-name">Full name</label><input id="f-name" name="name" type="text" autocomplete="name" required><span class="err" id="f-name-err">Enter your name.</span></div>
          <div class="field"><label for="f-email">Work email</label><input id="f-email" name="email" type="email" autocomplete="email" required><span class="err" id="f-email-err">Enter a valid work email.</span></div>
          <div class="field"><label for="f-company">Lab or organization</label><input id="f-company" name="company" type="text" autocomplete="organization" required><span class="err" id="f-company-err">Enter your lab or organization.</span></div>
          <div class="field"><label for="f-lab">Type of lab</label>
            <select id="f-lab" name="lab_type" required><option value="">Select one</option><option>Pathology lab</option><option>Diagnostic and imaging center</option><option>Multi-center lab chain</option><option>Hospital laboratory</option><option>Home collection service</option><option>Cardiology or ECG clinic</option><option>Other</option></select>
            <span class="err" id="f-lab-err">Choose a type of lab.</span></div>
          <div class="field field--full"><label for="f-size">Number of centers</label>
            <select id="f-size" name="centers" required><option value="">Select one</option><option>1</option><option>2–5</option><option>6–20</option><option>More than 20</option></select>
            <span class="err" id="f-size-err">Choose the number of centers.</span></div>
          <div class="field field--full"><label for="f-msg">What would you like to see? <span class="opt">(optional)</span></label><textarea id="f-msg" name="message" rows="3"></textarea></div>
        </div>
        <div class="hp" aria-hidden="true"><label for="f-website">Leave this field empty</label><input id="f-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <div class="form-submit"><button class="btn btn--primary btn--lg" type="submit">Book a demo</button></div>
        <p class="form-error" id="form-error" role="alert"></p>
        <p class="form-note">By submitting, you agree to our <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">privacy policy</a>.</p>
      </form>
      <div class="form-status" id="form-status" role="status" aria-live="polite" tabindex="-1">
        <span class="ok-icon"><svg class="icon icon-lg" aria-hidden="true"><use href="#i-check"/></svg></span>
        <h3>Demo request received</h3>
        <p>Thanks. We'll email you shortly to schedule a time that works for your team.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="faq" aria-labelledby="faq-title">
  <div class="container faq-wrap">
    <div class="faq-aside reveal">
      <h2 id="faq-title">Laboratory management software, answered</h2>
      <p>Common questions about running a diagnostic lab on one connected platform.</p>
      <a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Ask another question</a>
      <a class="link-arrow faq-all" href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">See all questions <svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="faq-list">
      <details class="faq-item"><summary><h3>What is laboratory management software?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>Laboratory management software runs the daily operations of a lab in one system: patient registration, sample collection and tracking, testing, report writing and approval, delivery to patients and doctors, and billing. For diagnostic labs it replaces paper registers, spreadsheets, and separate tools for pathology, radiology, and accounts.</p></div></details>
      <details class="faq-item"><summary><h3>What is the difference between LIMS, LIS, and laboratory management software?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>A LIS (laboratory information system) is usually patient-centered and built for clinical and diagnostic labs. A LIMS is usually sample-centered and common in research and industrial labs. Labora is laboratory management software for diagnostic labs: it covers what a LIS does and adds radiology reporting, home collection, multi-center management, and business insights.</p></div></details>
      <details class="faq-item"><summary><h3>Can Labora handle both pathology and radiology?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>Yes. Pathology, ultrasound, X-ray, CT, MRI, ECG, 2D echo, and treadmill reporting all happen in the same system, on the same patient case, so a patient with a blood test and a scan has one record and one bill.</p></div></details>
      <details class="faq-item"><summary><h3>How does Labora track samples?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>Each tube gets a barcode. Scanning it matches the tube to the right patient and test, records each step (registered, collected, received, in testing, reported), and shows the exact rack and slot where it is stored. Samples that cannot be used, for example hemolysed ones, are held or rejected with the reason recorded.</p></div></details>
      <details class="faq-item"><summary><h3>How does turnaround time (TAT) tracking work?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>Every case has a target time. Labora shows the time elapsed against that target, flags cases that are at risk or past target, and sends alerts so the team can act. Delays are logged with a reason, and you can see the share of reports delivered on time.</p></div></details>
      <details class="faq-item"><summary><h3>Can I manage multiple centers and collection points?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>Yes. Branches, collection centers, and home collection run in one system. You can switch between centers and compare activity and revenue across all of them.</p></div></details>
      <details class="faq-item"><summary><h3>Can Labora connect with lab analyzers and other systems?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>Labora is designed to connect with lab instruments and other systems through its integration layer and API. Which connections are available depends on your analyzers and setup, so the best next step is to review your equipment list with our team in a demo.</p></div></details>
      <details class="faq-item"><summary><h3>Can Labora be configured for my lab&#x27;s tests and workflows?</h3><span class="plus" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></span></summary>
        <div class="faq-answer"><p>Yes. Tests, report templates, turnaround targets, centers, user roles, and permissions are configured to match how your lab works, and the setup can grow as you add centers and services.</p></div></details>
    </div>
  </div>
</section>

<section class="final-cta" aria-labelledby="final-title">
  <div class="container">
    <div class="final-box reveal">
      <div>
        <h2 id="final-title">Start with one center. Add the rest when you are ready.</h2>
        <p>Rollout is guided center by center, with your test menu, price list, and report templates moved over for you.</p>
        <div class="ctas">
          <a class="btn btn--primary btn--lg" href="#demo">Book a demo</a>
          <a class="btn btn--outline-light btn--lg" href="#product-tour">Watch the tour</a>
        </div>
      </div>
      <div class="final-aside">
        <p class="final-label">On the demo call</p>
        <ol class="final-list">
          <li>Your tests, departments, and price list, set up in Labora</li>
          <li>Answers on analyzers, data migration, and setup</li>
          <li>A plan for which center goes live first</li>
        </ol>
      </div>
    </div>
  </div>
</section>


</main>

<div class="mobile-cta" id="mobile-cta">
  <a class="btn btn--primary" href="#demo">Book a demo</a>
  <a class="btn btn--ghost" href="#product-tour">Watch tour</a>
</div>

<?php
get_footer();
