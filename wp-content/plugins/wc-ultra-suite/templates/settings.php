<?php
/**
 * Settings Page Template
 * Dedicated settings page for theme customization
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wc-ultra-suite-wrapper">
    <!-- Navigation Sidebar -->
    <nav class="wc-ultra-suite-nav">
        <div class="nav-header">
            <h2 class="nav-logo">
                <span class="logo-icon">⚡</span>
                Ultra Suite
            </h2>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item" data-view="settings">
                <span class="nav-icon">⚙️</span>
                <span class="nav-label">Settings</span>
            </li>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <main class="wc-ultra-suite-main">
        <div id="wc-ultra-suite-content">
            <div class="loading-screen">
                <div class="loading-spinner"></div>
                <p>Loading Settings...</p>
            </div>
        </div>
    </main>
</div>
