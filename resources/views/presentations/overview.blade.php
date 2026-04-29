<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kingdom Vitals — Church Management Made Simple</title>
    <meta name="description" content="Kingdom Vitals is a web-based church management & ministry intelligence platform. A product of MawuRapha." />

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />

    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500&display=swap" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@5.1.0/dist/reset.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@5.1.0/dist/reveal.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@5.1.0/dist/theme/black.css" id="theme" />

    <style>
        :root {
            --kv-emerald: #009866;
            --kv-emerald-400: #34d399;
            --kv-emerald-600: #007a52;
            --kv-lime: #ccff00;
            --kv-lime-dark: #a3cc00;
            --kv-obsidian-base: #000000;
            --kv-obsidian-surface: #0c0c0c;
            --kv-obsidian-elevated: #141414;
            --kv-obsidian-subtle: #1a1a1a;
            --kv-text: #ebebeb;
            --kv-text-secondary: rgba(235, 235, 235, 0.6);
            --kv-text-muted: rgba(235, 235, 235, 0.3);
            --kv-border: rgba(255, 255, 255, 0.1);
            --kv-glass-bg: rgba(255, 255, 255, 0.03);
        }

        html, body {
            background: var(--kv-obsidian-base);
        }

        .reveal {
            font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            font-weight: 400;
            color: var(--kv-text);
            font-size: 28px;
        }

        .reveal .slides {
            text-align: left;
        }

        .reveal .slides section {
            padding: 0;
            height: 100%;
        }

        .reveal h1, .reveal h2, .reveal h3, .reveal h4 {
            font-family: 'Space Grotesk', sans-serif;
            text-transform: none;
            letter-spacing: -0.04em;
            color: var(--kv-text);
            line-height: 1.05;
            margin: 0 0 0.6em 0;
        }

        .reveal h1 {
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 300;
            letter-spacing: -0.06em;
            line-height: 0.95;
        }

        .reveal h2 {
            font-size: clamp(1.8rem, 3.5vw, 2.8rem);
            font-weight: 400;
        }

        .reveal h3 {
            font-size: 1.4rem;
            font-weight: 500;
        }

        .reveal p {
            line-height: 1.55;
            color: var(--kv-text-secondary);
            margin: 0 0 1em 0;
        }

        .reveal ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .reveal ul li {
            position: relative;
            padding-left: 1.5em;
            margin: 0.45em 0;
            color: var(--kv-text-secondary);
            line-height: 1.5;
        }

        .reveal ul li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.65em;
            width: 0.5em;
            height: 1px;
            background: var(--kv-emerald-400);
        }

        .reveal a {
            color: var(--kv-emerald-400);
            text-decoration: none;
        }

        /* ============== KV BRAND PRIMITIVES ============== */
        .kv-stage {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5vh 7vw;
            overflow: hidden;
            background: var(--kv-obsidian-base);
        }

        .kv-stage--center { align-items: center; text-align: center; }

        .kv-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        .kv-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
            z-index: 0;
        }

        .kv-glow--emerald {
            background: rgba(16, 185, 129, 0.4);
            width: 480px;
            height: 480px;
        }

        .kv-glow--lime {
            background: rgba(204, 255, 0, 0.25);
            width: 380px;
            height: 380px;
        }

        .kv-glow.tr { top: -180px; right: -120px; }
        .kv-glow.bl { bottom: -180px; left: -120px; }
        .kv-glow.tl { top: -160px; left: -100px; }
        .kv-glow.br { bottom: -160px; right: -100px; }

        .kv-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: clamp(8rem, 22vw, 22rem);
            color: rgba(255, 255, 255, 0.025);
            letter-spacing: -0.05em;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            line-height: 1;
        }

        .kv-watermark--roman {
            font-size: clamp(20rem, 50vw, 40rem);
            font-weight: 300;
        }

        .kv-content {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .kv-glass {
            background: var(--kv-glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--kv-border);
            border-radius: 1.5rem;
            padding: 2rem;
        }

        .kv-glass--lg { padding: 2.5rem; border-radius: 2rem; }

        .kv-label {
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: var(--kv-emerald-400);
        }

        .kv-label--muted { color: var(--kv-text-muted); }

        .kv-gradient {
            background: linear-gradient(135deg, var(--kv-emerald-400) 0%, var(--kv-lime) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .kv-corner-label {
            position: absolute;
            top: 4vh;
            right: 5vw;
            z-index: 3;
        }

        .kv-corner-logo {
            position: absolute;
            top: 4vh;
            left: 5vw;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
            color: var(--kv-text);
        }

        .kv-corner-logo img {
            height: 36px;
            width: auto;
            display: block;
        }

        .kv-hero-logo {
            display: block;
            margin: 0 auto 2.5rem;
            height: clamp(56px, 7vw, 88px);
            width: auto;
        }

        .kv-hero-wordmark {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 300;
            font-size: clamp(2.5rem, 5vw, 4rem);
            letter-spacing: -0.05em;
            line-height: 1;
            margin: 0.75rem 0 2rem;
        }

        .kv-corner-logo .kv-mark {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--kv-emerald) 0%, var(--kv-lime) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--kv-obsidian-base);
            font-weight: 700;
            font-size: 0.95rem;
        }

        .kv-foot {
            position: absolute;
            bottom: 4vh;
            left: 5vw;
            right: 5vw;
            z-index: 3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: var(--kv-text-muted);
        }

        /* ============== LAYOUT HELPERS ============== */
        .kv-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        .kv-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .kv-grid-4 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .kv-feature-row {
            display: grid;
            grid-template-columns: 64px 1fr;
            gap: 1.5rem;
            align-items: start;
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--kv-border);
        }

        .kv-feature-row:last-child { border-bottom: none; }

        .kv-icon-tile {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: var(--kv-glass-bg);
            border: 1px solid var(--kv-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--kv-emerald-400);
            flex-shrink: 0;
        }

        .kv-feature-row h3 {
            margin: 0 0 0.4em 0;
            font-size: 1.2rem;
            color: var(--kv-text);
        }

        .kv-feature-row p {
            margin: 0;
            font-size: 0.95rem;
            color: var(--kv-text-secondary);
        }

        .kv-feature-card {
            text-align: left;
        }

        .kv-feature-card .kv-icon-tile {
            margin-bottom: 1rem;
        }

        .kv-feature-card h3 {
            font-size: 1.15rem;
            margin: 0 0 0.5rem 0;
            color: var(--kv-text);
        }

        .kv-feature-card p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--kv-text-secondary);
            line-height: 1.5;
        }

        /* ============== PRICING ============== */
        .kv-price-card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 280px;
        }

        .kv-price-card.featured {
            border: 1px solid var(--kv-emerald);
            box-shadow: 0 0 40px rgba(0, 152, 102, 0.25);
        }

        .kv-price-badge {
            position: absolute;
            top: -0.7rem;
            left: 50%;
            transform: translateX(-50%);
            background: var(--kv-lime);
            color: var(--kv-obsidian-base);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .kv-price-card h3 {
            font-size: 1.4rem;
            margin: 0 0 0.4rem 0;
        }

        .kv-price-card .kv-price-sub {
            font-size: 0.85rem;
            color: var(--kv-text-muted);
            font-family: 'JetBrains Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 1.5rem;
        }

        .kv-price-card ul li {
            font-size: 0.9rem;
            margin: 0.4em 0;
        }

        /* ============== TIMELINE ============== */
        .kv-timeline {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0;
            position: relative;
            margin-top: 2rem;
        }

        .kv-timeline-step {
            position: relative;
            padding: 0 0.75rem;
            text-align: center;
        }

        .kv-step-num {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--kv-obsidian-elevated);
            border: 1px solid var(--kv-emerald-400);
            color: var(--kv-emerald-400);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        .kv-timeline-step h3 {
            font-size: 1rem;
            margin: 0 0 0.4rem 0;
        }

        .kv-timeline-step p {
            font-size: 0.85rem;
            margin: 0;
            color: var(--kv-text-muted);
        }

        .kv-timeline::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 10%;
            right: 10%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--kv-emerald) 20%, var(--kv-emerald) 80%, transparent);
            z-index: 1;
        }

        /* ============== CTA ============== */
        .kv-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.95rem 1.6rem;
            background: var(--kv-emerald);
            color: var(--kv-obsidian-base) !important;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 999px;
            box-shadow: 0 0 30px rgba(0, 152, 102, 0.45);
            text-decoration: none;
            font-family: 'Space Grotesk', sans-serif;
        }

        .kv-cta--lime {
            background: var(--kv-lime);
            box-shadow: 0 0 30px rgba(204, 255, 0, 0.35);
        }

        /* ============== CHART ============== */
        .kv-chart {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
            height: 120px;
            margin-top: 1rem;
        }

        .kv-chart-bar {
            flex: 1;
            background: linear-gradient(to top, var(--kv-emerald) 0%, var(--kv-lime) 100%);
            border-radius: 4px 4px 0 0;
            opacity: 0.85;
        }

        .kv-chart-legend {
            display: flex;
            justify-content: space-between;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            color: var(--kv-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-top: 0.6rem;
        }

        /* ============== UTILITIES ============== */
        .kv-eyebrow {
            display: inline-block;
            margin-bottom: 1.25rem;
        }

        .kv-mt-sm { margin-top: 1rem; }
        .kv-mt-md { margin-top: 2rem; }
        .kv-mt-lg { margin-top: 3rem; }

        .kv-divider-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--kv-text-muted);
            letter-spacing: 0.2em;
        }

        .kv-icon { width: 28px; height: 28px; stroke-width: 1.5; }
        .kv-icon-lg { width: 32px; height: 32px; stroke-width: 1.5; }

        /* Reveal print tweaks */
        @media print {
            .reveal .slide-background { background: var(--kv-obsidian-base) !important; }
        }
    </style>
</head>
<body>

<div class="reveal">
    <div class="slides">

        <!-- ============== SLIDE 1: COVER ============== -->
        <section data-background-color="#000000" data-transition="fade">
            <div class="kv-stage kv-stage--center">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--emerald tr"></div>
                <div class="kv-glow kv-glow--lime bl"></div>
                <div class="kv-watermark">KINGDOM VITALS</div>

                <div class="kv-corner-logo">
                    <img src="{{ asset('images/logo-white.svg') }}" alt="Kingdom Vitals" />
                </div>

                <div class="kv-corner-label">
                    <span class="kv-label kv-label--muted">v1.0 · 2026</span>
                </div>

                <div class="kv-content" style="text-align: center; max-width: 1100px; margin: 0 auto;">
                    <span class="kv-label kv-eyebrow">A Product of MawuRapha</span>
                    <div class="kv-hero-wordmark">Kingdom <span class="kv-gradient">Vitals</span></div>
                    <h1>Church Management,<br/><span class="kv-gradient">Made Simple.</span></h1>
                    <p style="font-size: 1.4rem; color: var(--kv-text-secondary); max-width: 720px; margin: 1rem auto 0;">
                        A web-based platform for organizing operations, strengthening pastoral care, and making data-informed decisions.
                    </p>
                </div>

                <div class="kv-foot">
                    <span>Kingdom Vitals — Overview</span>
                    <span>Press → to begin</span>
                </div>
            </div>
            <aside class="notes">
                Open with warmth. Introduce yourself, name the audience's context (pastor, denomination lead, ministry coordinator), and frame the next 15 minutes: why Kingdom Vitals exists and what it unlocks. Don't dwell here — move to the problem.
            </aside>
        </section>

        <!-- ============== SLIDE 2: THE PROBLEM ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--emerald tl" style="opacity: 0.6;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">02 / 16 · The Problem</span></div>

                <div class="kv-content" style="max-width: 1100px;">
                    <span class="kv-label kv-eyebrow">The Reality</span>
                    <h2>Ministry runs on relationships —<br/>but the <span class="kv-gradient">data is everywhere.</span></h2>

                    <div class="kv-grid-3 kv-mt-lg">
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="folder-x" class="kv-icon"></i></div>
                            <h3>Scattered records</h3>
                            <p>Member info lives in spreadsheets, notebooks, and three different group chats.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="user-minus" class="kv-icon"></i></div>
                            <h3>Missed follow-ups</h3>
                            <p>First-time visitors and drifting members slip through the cracks.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="eye-off" class="kv-icon"></i></div>
                            <h3>Blind decisions</h3>
                            <p>Leaders make calls on attendance, giving, and growth without real data.</p>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                Land each pain point with a real example from your audience. Pause after "blind decisions" — this is where curiosity peaks. Transition: "What if all of this lived in one place, and quietly told you what mattered?"
            </aside>
        </section>

        <!-- ============== SLIDE 3: WHAT IS KINGDOM VITALS ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--lime br" style="opacity: 0.5;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">03 / 16 · Overview</span></div>

                <div class="kv-content" style="max-width: 1200px;">
                    <span class="kv-label kv-eyebrow">What is Kingdom Vitals?</span>
                    <h2>One unified platform.<br/><span class="kv-gradient">Three pillars of ministry.</span></h2>
                    <p style="max-width: 820px;">
                        Kingdom Vitals combines administration, finance, communication, and spiritual tracking into a single web-based system — built with the realities of ministry in mind.
                    </p>

                    <div class="kv-grid-3 kv-mt-lg">
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="building-2" class="kv-icon"></i></div>
                            <h3>Administration</h3>
                            <p>Membership, finance, assets, and events — organized in one place.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="heart-handshake" class="kv-icon"></i></div>
                            <h3>Pastoral Care</h3>
                            <p>Smart follow-ups for visitors, new members, and absentees — no one falls through.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="sparkles" class="kv-icon"></i></div>
                            <h3>Ministry Intelligence</h3>
                            <p>Real-time insights and automation that surface what leaders need to act on.</p>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                Emphasize "built with the realities of ministry in mind" — this isn't generic CRM. The three pillars are how every feature in the deck maps back. Set expectation: the next slides walk through the twelve concrete capabilities.
            </aside>
        </section>

        <!-- ============== SLIDE 4: SECTION DIVIDER — FEATURES ============== -->
        <section data-background-color="#000000" data-transition="fade">
            <div class="kv-stage kv-stage--center">
                <div class="kv-watermark kv-watermark--roman">I</div>
                <div class="kv-glow kv-glow--emerald tr" style="opacity: 0.5;"></div>
                <div class="kv-glow kv-glow--lime bl" style="opacity: 0.4;"></div>

                <div class="kv-content" style="text-align: center;">
                    <span class="kv-label kv-eyebrow">Section 01</span>
                    <h1 class="kv-gradient" style="font-size: clamp(3.5rem, 8vw, 6.5rem);">Features</h1>
                    <p style="font-size: 1.3rem; color: var(--kv-text-secondary); margin-top: 1rem;">
                        Twelve capabilities. One platform.
                    </p>
                </div>
            </div>
            <aside class="notes">
                Quick beat. Tell the audience the next four slides group twelve features into four themes: People, Operations, Reach & Insight, Scale & Trust.
            </aside>
        </section>

        <!-- ============== SLIDE 5: PEOPLE — MEMBERSHIP & ENGAGEMENT ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--emerald tl" style="opacity: 0.5;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">05 / 16 · People</span></div>

                <div class="kv-content" style="max-width: 1100px;">
                    <span class="kv-label kv-eyebrow">Theme 01 · People</span>
                    <h2>Know your people.<br/><span class="kv-gradient">Care for them on time.</span></h2>

                    <div class="kv-glass kv-glass--lg kv-mt-md">
                        <div class="kv-feature-row">
                            <div class="kv-icon-tile"><i data-lucide="users" class="kv-icon"></i></div>
                            <div>
                                <h3>Membership Management</h3>
                                <p>One-time comprehensive member capture · family and group categorization · ministry involvement tracking.</p>
                            </div>
                        </div>
                        <div class="kv-feature-row">
                            <div class="kv-icon-tile"><i data-lucide="bell-ring" class="kv-icon"></i></div>
                            <div>
                                <h3>Intelligent Follow-Up System</h3>
                                <p>Automated messages for first-time visitors, new members, and absentees — plus smart alerts for inactive or drifting members.</p>
                            </div>
                        </div>
                        <div class="kv-feature-row">
                            <div class="kv-icon-tile"><i data-lucide="activity" class="kv-icon"></i></div>
                            <div>
                                <h3>Attendance & Engagement Tracking</h3>
                                <p>Monitor service attendance, track participation across ministries, and identify growth or decline patterns.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                Anchor on the smart-alert idea — most ChMS tools are passive databases; this one nudges leaders before someone drifts. Use a story: "A pastor told me he reconnected with three families in the first month because the system flagged them."
            </aside>
        </section>

        <!-- ============== SLIDE 6: OPERATIONS — FINANCE, ASSETS, EVENTS ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--lime br" style="opacity: 0.4;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">06 / 16 · Operations</span></div>

                <div class="kv-content" style="max-width: 1100px;">
                    <span class="kv-label kv-eyebrow">Theme 02 · Operations</span>
                    <h2>Run the church<br/><span class="kv-gradient">like a ministry, not a mess.</span></h2>

                    <div class="kv-glass kv-glass--lg kv-mt-md">
                        <div class="kv-feature-row">
                            <div class="kv-icon-tile"><i data-lucide="coins" class="kv-icon"></i></div>
                            <div>
                                <h3>Financial Management & Giving</h3>
                                <p>Record tithes, offerings, and donations · integrated mobile money & bank payments · accountability-ready financial reports.</p>
                            </div>
                        </div>
                        <div class="kv-feature-row">
                            <div class="kv-icon-tile"><i data-lucide="package" class="kv-icon"></i></div>
                            <div>
                                <h3>Asset Management</h3>
                                <p>Track properties, equipment, and resources — improving accountability and maintenance planning.</p>
                            </div>
                        </div>
                        <div class="kv-feature-row">
                            <div class="kv-icon-tile"><i data-lucide="calendar-days" class="kv-icon"></i></div>
                            <div>
                                <h3>Events & Program Management</h3>
                                <p>Schedule services and special events · registration & attendance tracking · volunteer coordination.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                Lead with the giving integration — mobile money matters for African and global-south contexts. Asset management is often overlooked but is a strong differentiator for medium and large churches.
            </aside>
        </section>

        <!-- ============== SLIDE 7: REACH & INSIGHT ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--emerald tr" style="opacity: 0.5;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">07 / 16 · Reach & Insight</span></div>

                <div class="kv-content" style="max-width: 1200px;">
                    <span class="kv-label kv-eyebrow">Theme 03 · Reach & Insight</span>
                    <h2>Speak to the right people.<br/><span class="kv-gradient">See what the numbers say.</span></h2>

                    <div class="kv-grid-2 kv-mt-md">
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="megaphone" class="kv-icon"></i></div>
                            <h3>Communication Tools</h3>
                            <p>Bulk messaging across SMS and email · targeted communication by group, ministry, or attendance status — every message reaches who it should.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="bar-chart-3" class="kv-icon"></i></div>
                            <h3>Visual Analytics Dashboard</h3>
                            <p>Charts for attendance trends, financial performance, and member engagement — easy-to-read insights for leadership decisions.</p>
                            <div class="kv-chart">
                                <div class="kv-chart-bar" style="height: 35%;"></div>
                                <div class="kv-chart-bar" style="height: 55%;"></div>
                                <div class="kv-chart-bar" style="height: 45%;"></div>
                                <div class="kv-chart-bar" style="height: 70%;"></div>
                                <div class="kv-chart-bar" style="height: 65%;"></div>
                                <div class="kv-chart-bar" style="height: 85%;"></div>
                                <div class="kv-chart-bar" style="height: 78%;"></div>
                                <div class="kv-chart-bar" style="height: 95%;"></div>
                            </div>
                            <div class="kv-chart-legend">
                                <span>Q1</span><span>Q2</span><span>Q3</span><span>Q4</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                The chart is illustrative, not real data. Use it as a prop: "Imagine seeing your church's growth at a glance — not buried in a spreadsheet you'll never open."
            </aside>
        </section>

        <!-- ============== SLIDE 8: SCALE & TRUST ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--lime bl" style="opacity: 0.4;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">08 / 16 · Scale & Trust</span></div>

                <div class="kv-content" style="max-width: 1200px;">
                    <span class="kv-label kv-eyebrow">Theme 04 · Scale & Trust</span>
                    <h2>Built for the<br/><span class="kv-gradient">realities of ministry.</span></h2>

                    <div class="kv-grid-4 kv-mt-md">
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="network" class="kv-icon"></i></div>
                            <h3>Multi-Branch Management</h3>
                            <p>Central control for denominations or networks — monitor and compare branches from one dashboard.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="wifi-off" class="kv-icon"></i></div>
                            <h3>Offline Capability</h3>
                            <p>Continue data entry without internet · automatic sync when connection is restored.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="shield-check" class="kv-icon"></i></div>
                            <h3>Roles & Access Control</h3>
                            <p>Pastor, admin, leaders, volunteers — role-based access protects sensitive data and ensures accountability.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="lock" class="kv-icon"></i></div>
                            <h3>Data Security & Backup</h3>
                            <p>Secure cloud-based system · controlled permissions · automatic backup and recovery.</p>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                Offline capability is the headline here — most churches in Africa, Asia, and Latin America deal with unreliable connectivity. Pair it with security to land trust as the closing note before pricing.
            </aside>
        </section>

        <!-- ============== SLIDE 9: SECTION DIVIDER — AUDIENCE ============== -->
        <section data-background-color="#000000" data-transition="fade">
            <div class="kv-stage kv-stage--center">
                <div class="kv-watermark kv-watermark--roman">II</div>
                <div class="kv-glow kv-glow--emerald br" style="opacity: 0.5;"></div>

                <div class="kv-content" style="text-align: center;">
                    <span class="kv-label kv-eyebrow">Section 02</span>
                    <h1 class="kv-gradient" style="font-size: clamp(3.5rem, 8vw, 6.5rem);">Audience</h1>
                    <p style="font-size: 1.3rem; color: var(--kv-text-secondary); margin-top: 1rem;">
                        Built for every shape of ministry.
                    </p>
                </div>
            </div>
            <aside class="notes">
                Quick transition. Use this to acknowledge that the audience may not be a single local church — Kingdom Vitals scales from a 50-member parish to a denomination of hundreds of branches.
            </aside>
        </section>

        <!-- ============== SLIDE 10: TARGET USERS ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--lime tl" style="opacity: 0.4;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">10 / 16 · Who It's For</span></div>

                <div class="kv-content" style="max-width: 1200px;">
                    <span class="kv-label kv-eyebrow">Target Users</span>
                    <h2>From a single parish<br/>to a <span class="kv-gradient">global network.</span></h2>

                    <div class="kv-grid-4 kv-mt-md">
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="church" class="kv-icon"></i></div>
                            <h3>Local Churches</h3>
                            <p>Of every size — from house churches to mega-churches.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="git-branch" class="kv-icon"></i></div>
                            <h3>Denominations & Networks</h3>
                            <p>Coordinate dozens or hundreds of branches with central oversight.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="graduation-cap" class="kv-icon"></i></div>
                            <h3>Campus Ministries</h3>
                            <p>Track students through semesters, events, and graduation.</p>
                        </div>
                        <div class="kv-glass kv-feature-card">
                            <div class="kv-icon-tile"><i data-lucide="hand-heart" class="kv-icon"></i></div>
                            <h3>Para-Church Organizations</h3>
                            <p>Mission orgs, ministries, and Christian non-profits.</p>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                Adapt this slide live: ask "which one are you?" and tailor the rest of the conversation. If you're presenting to a denomination lead, emphasize multi-branch from slide 8.
            </aside>
        </section>

        <!-- ============== SLIDE 11: SECTION DIVIDER — PRICING ============== -->
        <section data-background-color="#000000" data-transition="fade">
            <div class="kv-stage kv-stage--center">
                <div class="kv-watermark kv-watermark--roman">III</div>
                <div class="kv-glow kv-glow--emerald tl" style="opacity: 0.5;"></div>
                <div class="kv-glow kv-glow--lime br" style="opacity: 0.4;"></div>

                <div class="kv-content" style="text-align: center;">
                    <span class="kv-label kv-eyebrow">Section 03</span>
                    <h1 class="kv-gradient" style="font-size: clamp(3.5rem, 8vw, 6.5rem);">Plans</h1>
                    <p style="font-size: 1.3rem; color: var(--kv-text-secondary); margin-top: 1rem;">
                        Pricing that scales with your ministry.
                    </p>
                </div>
            </div>
            <aside class="notes">
                Set the frame: Kingdom Vitals is priced by membership size, feature access, and number of branches — so the smallest plan is genuinely affordable for small churches, and large networks pay proportionally.
            </aside>
        </section>

        <!-- ============== SLIDE 12: PRICING TIERS ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">12 / 16 · Pricing</span></div>

                <div class="kv-content" style="max-width: 1200px;">
                    <span class="kv-label kv-eyebrow">Pricing Plans</span>
                    <h2>Three tiers.<br/><span class="kv-gradient">One platform.</span></h2>

                    <div class="kv-grid-3 kv-mt-md" style="margin-top: 2.5rem;">
                        <div class="kv-glass kv-glass--lg kv-price-card">
                            <h3>Basic</h3>
                            <div class="kv-price-sub">For small churches</div>
                            <ul>
                                <li>Core membership management</li>
                                <li>Attendance tracking</li>
                                <li>Basic financial records</li>
                                <li>Communication tools</li>
                                <li>Single branch</li>
                            </ul>
                        </div>
                        <div class="kv-glass kv-glass--lg kv-price-card featured">
                            <span class="kv-price-badge">Most Popular</span>
                            <h3>Standard</h3>
                            <div class="kv-price-sub">For growing churches</div>
                            <ul>
                                <li>Everything in Basic</li>
                                <li>Intelligent follow-up automation</li>
                                <li>Asset & event management</li>
                                <li>Visual analytics dashboard</li>
                                <li>Up to a few branches</li>
                            </ul>
                        </div>
                        <div class="kv-glass kv-glass--lg kv-price-card">
                            <h3>Premium</h3>
                            <div class="kv-price-sub">Multi-branch & large ministries</div>
                            <ul>
                                <li>Everything in Standard</li>
                                <li>Unlimited branches</li>
                                <li>Cross-branch reporting</li>
                                <li>Priority support & onboarding</li>
                                <li>Advanced security controls</li>
                            </ul>
                        </div>
                    </div>

                    <p style="text-align: center; margin-top: 2rem; font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.18em; color: var(--kv-text-muted);">
                        Pricing based on membership size · feature access · number of branches
                    </p>
                </div>
            </div>
            <aside class="notes">
                Don't quote dollar figures — final pricing is determined by membership size and branch count. If asked, say: "We tailor the plan to your church's actual scale — let's talk after this and I'll give you a number." Highlight the Standard tier as the right fit for most.
            </aside>
        </section>

        <!-- ============== SLIDE 13: ONBOARDING ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--emerald tr" style="opacity: 0.5;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">13 / 16 · Onboarding</span></div>

                <div class="kv-content" style="max-width: 1200px;">
                    <span class="kv-label kv-eyebrow">Get Started</span>
                    <h2>Up and running in<br/><span class="kv-gradient">five steps.</span></h2>

                    <div class="kv-timeline">
                        <div class="kv-timeline-step">
                            <div class="kv-step-num">01</div>
                            <h3>Register</h3>
                            <p>Create your church account.</p>
                        </div>
                        <div class="kv-timeline-step">
                            <div class="kv-step-num">02</div>
                            <h3>Set up profile</h3>
                            <p>Branding, branches, structure.</p>
                        </div>
                        <div class="kv-timeline-step">
                            <div class="kv-step-num">03</div>
                            <h3>Import data</h3>
                            <p>Bring in members from CSV or enter directly.</p>
                        </div>
                        <div class="kv-timeline-step">
                            <div class="kv-step-num">04</div>
                            <h3>Assign roles</h3>
                            <p>Pastor, admin, ministry leaders.</p>
                        </div>
                        <div class="kv-timeline-step">
                            <div class="kv-step-num">05</div>
                            <h3>Start managing</h3>
                            <p>You're live — begin tracking, communicating, deciding.</p>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="notes">
                Underscore: most churches go from signup to first real use in under a day. Mention guided onboarding support — we hold their hand through import and role setup.
            </aside>
        </section>

        <!-- ============== SLIDE 14: FREE TRIAL ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage kv-stage--center">
                <div class="kv-grid"></div>
                <div class="kv-glow kv-glow--lime tr" style="opacity: 0.6;"></div>
                <div class="kv-glow kv-glow--emerald bl" style="opacity: 0.5;"></div>

                <div class="kv-corner-label"><span class="kv-label kv-label--muted">14 / 16 · Free Trial</span></div>

                <div class="kv-content" style="text-align: center; max-width: 880px;">
                    <span class="kv-label kv-eyebrow">Free Trial</span>
                    <h1 style="margin-bottom: 0.5em;">30 days. <span class="kv-gradient">Full access.</span><br/>No commitment.</h1>

                    <div class="kv-glass kv-glass--lg kv-mt-md" style="text-align: left; max-width: 640px; margin: 2rem auto;">
                        <ul>
                            <li>Every feature unlocked from day one</li>
                            <li>Guided onboarding support included</li>
                            <li>No credit card required to start</li>
                            <li>Keep your data if you decide to subscribe</li>
                        </ul>
                    </div>

                    <a href="#" class="kv-cta kv-cta--lime kv-mt-sm">
                        Start Your Free Trial
                        <i data-lucide="arrow-right" class="kv-icon"></i>
                    </a>
                </div>
            </div>
            <aside class="notes">
                This is your soft close. Stress "no commitment" and "guided onboarding" — these are the two objections that block most pastors. If you're presenting live, this is a good moment to ask "what would stop you from trying it for 30 days?"
            </aside>
        </section>

        <!-- ============== SLIDE 15: CTA / CONTACT ============== -->
        <section data-background-color="#000000">
            <div class="kv-stage kv-stage--center">
                <div class="kv-watermark">KINGDOM VITALS</div>
                <div class="kv-glow kv-glow--emerald tr" style="opacity: 0.5;"></div>
                <div class="kv-glow kv-glow--lime bl" style="opacity: 0.4;"></div>

                <div class="kv-corner-logo">
                    <span class="kv-mark">KV</span>
                    <span>Kingdom Vitals</span>
                </div>

                <div class="kv-content" style="text-align: center; max-width: 980px;">
                    <span class="kv-label kv-eyebrow">Take the next step</span>
                    <h1>Bring clarity to<br/>your <span class="kv-gradient">ministry.</span></h1>
                    <p style="font-size: 1.25rem; color: var(--kv-text-secondary); margin-top: 1rem;">
                        Let's get your church on Kingdom Vitals.
                    </p>

                    <div class="kv-grid-3 kv-mt-lg" style="max-width: 720px; margin: 2.5rem auto;">
                        <div class="kv-glass">
                            <span class="kv-label" style="display: block; margin-bottom: 0.6rem;">Email</span>
                            <p style="margin: 0; color: var(--kv-text);">hello@kingdomvitals.app</p>
                        </div>
                        <div class="kv-glass">
                            <span class="kv-label" style="display: block; margin-bottom: 0.6rem;">Web</span>
                            <p style="margin: 0; color: var(--kv-text);">kingdomvitals.app</p>
                        </div>
                        <div class="kv-glass">
                            <span class="kv-label" style="display: block; margin-bottom: 0.6rem;">Phone</span>
                            <p style="margin: 0; color: var(--kv-text);">+233 — — — — — — —</p>
                        </div>
                    </div>

                    <a href="#" class="kv-cta">
                        Start Your Free Trial
                        <i data-lucide="arrow-right" class="kv-icon"></i>
                    </a>
                </div>

                <div class="kv-foot">
                    <span>A product of MawuRapha</span>
                    <span>Kingdom Vitals — © 2026</span>
                </div>
            </div>
            <aside class="notes">
                Replace the placeholder phone, email, and URL with your real contact details before presenting. Close with a direct ask: "Can we book a 20-minute setup call this week?"
            </aside>
        </section>

        <!-- ============== SLIDE 16: THANK YOU ============== -->
        <section data-background-color="#000000" data-transition="fade">
            <div class="kv-stage kv-stage--center">
                <div class="kv-watermark">THANK YOU</div>
                <div class="kv-glow kv-glow--emerald tr" style="opacity: 0.4;"></div>

                <div class="kv-content" style="text-align: center;">
                    <h1>Thank <span class="kv-gradient">you.</span></h1>
                    <p style="font-size: 1.2rem; color: var(--kv-text-secondary); margin-top: 1.2rem;">
                        Kingdom Vitals — a product of <span style="color: var(--kv-emerald-400);">MawuRapha</span>.
                    </p>
                    <span class="kv-label kv-mt-md" style="display: block;">Questions?</span>
                </div>
            </div>
            <aside class="notes">
                Hold here while taking questions. Have your laptop open to the Kingdom Vitals dashboard so you can demo live if anyone asks "can I see it?"
            </aside>
        </section>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/reveal.js@5.1.0/dist/reveal.js"></script>
<script src="https://cdn.jsdelivr.net/npm/reveal.js@5.1.0/plugin/notes/notes.js"></script>
<script src="https://cdn.jsdelivr.net/npm/reveal.js@5.1.0/plugin/highlight/highlight.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
    Reveal.initialize({
        hash: true,
        controls: true,
        progress: true,
        slideNumber: false,
        center: false,
        transition: 'slide',
        backgroundTransition: 'fade',
        width: 1280,
        height: 720,
        margin: 0,
        plugins: [RevealNotes, RevealHighlight]
    }).then(() => {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });

    // Re-render icons when slides change (Reveal sometimes clones DOM)
    Reveal.on('slidechanged', () => {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>
</body>
</html>
