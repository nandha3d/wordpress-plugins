<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="wc-ultra-suite-wrapper">
        <!-- Navigation -->
        <nav class="wc-ultra-suite-nav">
            <div class="nav-header">
                <h1 class="nav-logo">
                    <span class="logo-icon">⚡</span>
                    <span class="logo-text">Ultra Suite</span>
                </h1>
            </div>
            
            <ul class="nav-menu">
                <!-- Sales Module -->
                <li class="nav-group active" id="nav-group-sales">
                    <div class="nav-item-header" onclick="UltraSuite.toggleNavGroup('sales')">
                        <span class="nav-icon">📊</span>
                        <span class="nav-label"><?php _e('Sales', 'wc-ultra-suite'); ?></span>
                        <span class="nav-arrow">▼</span>
                    </div>
                    <ul class="nav-submenu">
                        <li class="nav-item active" data-view="dashboard">
                            <span class="nav-label"><?php _e('Overview', 'wc-ultra-suite'); ?></span>
                        </li>
                        <li class="nav-item" data-view="products">
                            <span class="nav-label"><?php _e('Products', 'wc-ultra-suite'); ?></span>
                        </li>
                        <li class="nav-item" data-view="orders">
                            <span class="nav-label"><?php _e('Orders', 'wc-ultra-suite'); ?></span>
                        </li>
                        <li class="nav-item" data-view="customers">
                            <span class="nav-label"><?php _e('Customers', 'wc-ultra-suite'); ?></span>
                        </li>
                        <li class="nav-item" data-view="settings">
                            <span class="nav-label"><?php _e('Settings', 'wc-ultra-suite'); ?></span>
                        </li>
                    </ul>
                </li>

                <!-- Shop Page Module -->
                <li class="nav-item top-level" data-view="shop-page">
                    <span class="nav-icon">🏪</span>
                    <span class="nav-label"><?php _e('Shop Page', 'wc-ultra-suite'); ?></span>
                </li>

                <!-- Product Page Module -->
                <li class="nav-item top-level" data-view="product-page">
                    <span class="nav-icon">🛍️</span>
                    <span class="nav-label"><?php _e('Product Page', 'wc-ultra-suite'); ?></span>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="wc-ultra-suite-main">
            <div id="wc-ultra-suite-content">
                <!-- Content will be loaded here via JavaScript -->
                <div class="loading-screen">
                    <div class="loading-spinner"></div>
                    <p><?php _e('Loading...', 'wc-ultra-suite'); ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
