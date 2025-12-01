/**
 * WC Ultra Suite - Admin Application
 * SPA-like dashboard with dynamic content loading
 */

(function ($) {
    'use strict';

    const UltraSuite = {
        currentView: 'dashboard',

        /**
         * Initialize the application
         */
        init() {
            this.bindEvents();
            const initialView = wcUltraSuite.initialView || 'dashboard';
            this.switchView(initialView);
        },

        /**
         * Bind event listeners
         */
        /**
         * Bind event listeners
         */
        bindEvents() {
            // Navigation clicks
            $('.nav-item').on('click', (e) => {
                e.stopPropagation(); // Prevent bubbling
                const view = $(e.currentTarget).data('view');
                if (view) {
                    this.switchView(view);
                }
            });

            // Navigation Group Toggles
            $('.nav-item-header').on('click', function () {
                $(this).parent('.nav-group').toggleClass('active');
            });
        },

        /**
         * Switch between views
         */
        switchView(view) {
            $('.nav-item').removeClass('active');
            const $item = $(`.nav-item[data-view="${view}"]`);
            $item.addClass('active');

            // Handle Navigation Groups
            const $parentGroup = $item.closest('.nav-group');
            if ($parentGroup.length) {
                // If item is in a group, ensure group is open
                $('.nav-group').not($parentGroup).removeClass('active');
                $parentGroup.addClass('active');
            } else {
                // If top-level item, close all groups
                $('.nav-group').removeClass('active');
            }

            this.currentView = view;
            this.loadView(view);
        },

        /**
         * Load view content
         */
        loadView(view) {
            const $content = $('#wc-ultra-suite-content');
            $content.html('<div class="loading-screen"><div class="loading-spinner"></div><p>Loading...</p></div>');

            switch (view) {
                case 'dashboard':
                    this.loadDashboard();
                    break;
                case 'products':
                    this.loadProducts();
                    break;
                case 'orders':
                    this.loadOrders();
                    break;
                case 'customers':
                    this.loadCustomers();
                    break;
                case 'product-page':
                    this.loadProductPageCustomizer();
                    break;
                case 'shop-page':
                    this.loadShopPageCustomizer();
                    break;
                case 'settings':
                    this.loadSettings();
                    break;
            }
        },

        /**
         * Load Dashboard view
         */
        loadDashboard() {
            const $content = $('#wc-ultra-suite-content');

            // Fetch analytics data
            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_analytics',
                    nonce: wcUltraSuite.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderDashboard(response.data);
                    }
                },
                error: () => {
                    $content.html('<p class="error">Failed to load analytics data</p>');
                }
            });

            // Fetch chart data
            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_chart_data',
                    nonce: wcUltraSuite.nonce,
                    days: 30
                },
                success: (response) => {
                    if (response.success) {
                        this.renderChart(response.data);
                    }
                }
            });
        },

        /**
         * Render Dashboard
         */
        renderDashboard(data) {
            const html = `
                <div class="dashboard-view">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h1 style="font-size: 2rem; font-weight: 700; margin: 0; color: var(--wc-ultra-text);">
                            📊 Dashboard Overview
                        </h1>
                        
                        <div class="date-range-filter">
                            <button class="filter-btn active" data-days="7">Last 7 Days</button>
                            <button class="filter-btn" data-days="30">Last 30 Days</button>
                            <button class="filter-btn" data-days="90">Last 90 Days</button>
                            <button class="filter-btn" data-custom="true">Custom Range</button>
                        </div>
                    </div>
                    
                    <div class="custom-date-range" style="display: none; margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 1rem; align-items: center; background: white; padding: 1rem; border-radius: 0.75rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <label style="font-weight: 600;">From:</label>
                            <input type="date" id="dateFrom" class="date-input" />
                            <label style="font-weight: 600;">To:</label>
                            <input type="date" id="dateTo" class="date-input" />
                            <button class="btn btn-primary" id="applyCustomRange">Apply</button>
                            <button class="btn btn-secondary" id="cancelCustomRange">Cancel</button>
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Total Revenue</span>
                                <span class="stat-card-icon">💰</span>
                            </div>
                            <div class="stat-card-value">${this.formatCurrency(data.revenue)}</div>
                            <div class="stat-card-change positive">↗ ${data.profit_margin}% Profit Margin</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Net Profit</span>
                                <span class="stat-card-icon">📈</span>
                            </div>
                            <div class="stat-card-value">${this.formatCurrency(data.profit)}</div>
                            <div class="stat-card-change">Cost: ${this.formatCurrency(data.cost)}</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Total Orders</span>
                                <span class="stat-card-icon">🛒</span>
                            </div>
                            <div class="stat-card-value">${data.orders}</div>
                            <div class="stat-card-change">Last 30 days</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Avg Order Value</span>
                                <span class="stat-card-icon">💳</span>
                            </div>
                            <div class="stat-card-value">${this.formatCurrency(data.aov)}</div>
                            <div class="stat-card-change">Per order</div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h2 class="chart-title">Sales & Profit Trend</h2>
                        </div>
                        <div id="salesChart"></div>
                    </div>
                    
                    <div class="data-table-container">
                        <table class="data-table product-sales-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Product</th>
                                    <th>Quantity Sold</th>
                                    <th>Revenue</th>
                                    <th>Cost</th>
                                    <th>Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.top_products.map((product, index) => {
                const hasVariations = product.has_variations && product.variations && product.variations.length > 0;
                const expandIcon = hasVariations ? '▶' : '';

                let html = `
                                        <tr class="product-row ${hasVariations ? 'has-variations' : ''}" data-product-id="${product.id}">
                                            <td class="expand-cell">${expandIcon ? `<span class="expand-icon">${expandIcon}</span>` : ''}</td>
                                            <td><strong>${product.name}</strong>${hasVariations ? ` <span class="variation-count">(${product.variations.length} variations)</span>` : ''}</td>
                                            <td>${product.quantity}</td>
                                            <td>${this.formatCurrency(product.revenue)}</td>
                                            <td>${this.formatCurrency(product.cost)}</td>
                                            <td><strong style="color: var(--wc-ultra-success);">${this.formatCurrency(product.profit)}</strong></td>
                                        </tr>
                                    `;

                // Add variation rows (hidden by default)
                if (hasVariations) {
                    product.variations.forEach(variation => {
                        html += `
                                                <tr class="variation-row" data-parent-id="${product.id}" style="display: none;">
                                                    <td></td>
                                                    <td style="padding-left: 2rem;">
                                                        <span style="color: var(--wc-ultra-text-muted);">↳ ${variation.variation_name || 'Variation'}</span>
                                                    </td>
                                                    <td>${variation.quantity}</td>
                                                    <td>${this.formatCurrency(variation.revenue)}</td>
                                                    <td>${this.formatCurrency(variation.cost)}</td>
                                                    <td><strong style="color: var(--wc-ultra-success);">${this.formatCurrency(variation.profit)}</strong></td>
                                                </tr>
                                            `;
                    });
                }

                return html;
            }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            $('#wc-ultra-suite-content').html(html);

            // Add click handlers for expandable rows
            $('.product-row.has-variations').on('click', function () {
                const productId = $(this).data('product-id');
                const $variationRows = $(`.variation-row[data-parent-id="${productId}"]`);
                const $expandIcon = $(this).find('.expand-icon');

                if ($variationRows.is(':visible')) {
                    $variationRows.hide();
                    $expandIcon.text('▶');
                } else {
                    $variationRows.show();
                    $expandIcon.text('▼');
                }
            });

            // Date range filter handlers
            $('.filter-btn').on('click', function () {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                if ($(this).data('custom')) {
                    $('.custom-date-range').slideDown();
                } else {
                    $('.custom-date-range').slideUp();
                    const days = $(this).data('days');
                    UltraSuite.refreshDashboard(days);
                }
            });

            $('#applyCustomRange').on('click', function () {
                const dateFrom = $('#dateFrom').val();
                const dateTo = $('#dateTo').val();

                if (!dateFrom || !dateTo) {
                    alert('Please select both start and end dates');
                    return;
                }

                $('.custom-date-range').slideUp();
                UltraSuite.refreshDashboard(null, dateFrom, dateTo);
            });

            $('#cancelCustomRange').on('click', function () {
                $('.custom-date-range').slideUp();
                $('.filter-btn').removeClass('active');
                $('.filter-btn[data-days="30"]').addClass('active');
            });
        },

        /**
         * Refresh dashboard with new date range
         */
        refreshDashboard(days = 30, dateFrom = null, dateTo = null) {
            const $content = $('#wc-ultra-suite-content');

            // Show loading
            $content.find('.stats-grid, .chart-container, .data-table-container').css('opacity', '0.5');

            // Fetch analytics data
            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_analytics',
                    nonce: wcUltraSuite.nonce,
                    date_from: dateFrom,
                    date_to: dateTo
                },
                success: (response) => {
                    if (response.success) {
                        this.updateDashboardData(response.data);
                    }
                    $content.find('.stats-grid, .chart-container, .data-table-container').css('opacity', '1');
                }
            });

            // Fetch chart data
            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_chart_data',
                    nonce: wcUltraSuite.nonce,
                    days: days || 30
                },
                success: (response) => {
                    if (response.success) {
                        this.renderChart(response.data);
                    }
                }
            });
        },

        /**
         * Update dashboard data without full re-render
         */
        updateDashboardData(data) {
            // Update stat cards
            $('.stat-card').eq(0).find('.stat-card-value').text(this.formatCurrency(data.revenue));
            $('.stat-card').eq(0).find('.stat-card-change').text(`↗ ${data.profit_margin}% Profit Margin`);

            $('.stat-card').eq(1).find('.stat-card-value').text(this.formatCurrency(data.profit));
            $('.stat-card').eq(1).find('.stat-card-change').text(`Cost: ${this.formatCurrency(data.cost)}`);

            $('.stat-card').eq(2).find('.stat-card-value').text(data.orders);

            $('.stat-card').eq(3).find('.stat-card-value').text(this.formatCurrency(data.aov));

            // Update products table
            const tableBody = $('.product-sales-table tbody');
            tableBody.empty();

            data.top_products.forEach((product) => {
                const hasVariations = product.has_variations && product.variations && product.variations.length > 0;
                const expandIcon = hasVariations ? '▶' : '';

                let html = `
                    <tr class="product-row ${hasVariations ? 'has-variations' : ''}" data-product-id="${product.id}">
                        <td class="expand-cell">${expandIcon ? `<span class="expand-icon">${expandIcon}</span>` : ''}</td>
                        <td><strong>${product.name}</strong>${hasVariations ? ` <span class="variation-count">(${product.variations.length} variations)</span>` : ''}</td>
                        <td>${product.quantity}</td>
                        <td>${this.formatCurrency(product.revenue)}</td>
                        <td>${this.formatCurrency(product.cost)}</td>
                        <td><strong style="color: var(--wc-ultra-success);">${this.formatCurrency(product.profit)}</strong></td>
                    </tr>
                `;

                if (hasVariations) {
                    product.variations.forEach(variation => {
                        html += `
                            <tr class="variation-row" data-parent-id="${product.id}" style="display: none;">
                                <td></td>
                                <td style="padding-left: 2rem;">
                                    <span style="color: var(--wc-ultra-text-muted);">↳ ${variation.variation_name || 'Variation'}</span>
                                </td>
                                <td>${variation.quantity}</td>
                                <td>${this.formatCurrency(variation.revenue)}</td>
                                <td>${this.formatCurrency(variation.cost)}</td>
                                <td><strong style="color: var(--wc-ultra-success);">${this.formatCurrency(variation.profit)}</strong></td>
                            </tr>
                        `;
                    });
                }

                tableBody.append(html);
            });

            // Re-bind expandable row handlers
            $('.product-row.has-variations').off('click').on('click', function () {
                const productId = $(this).data('product-id');
                const $variationRows = $(`.variation-row[data-parent-id="${productId}"]`);
                const $expandIcon = $(this).find('.expand-icon');

                if ($variationRows.is(':visible')) {
                    $variationRows.hide();
                    $expandIcon.text('▶');
                } else {
                    $variationRows.show();
                    $expandIcon.text('▼');
                }
            });
        },

        /**
         * Render Chart
         */
        renderChart(data) {
            // Wait for DOM to be ready
            setTimeout(() => {
                const chartContainer = document.getElementById('salesChart');
                if (!chartContainer) {
                    console.log('Chart container not found');
                    return;
                }

                if (!data || data.length === 0) {
                    chartContainer.innerHTML = '<p style="text-align: center; padding: 2rem; color: var(--wc-ultra-text-muted);">No chart data available</p>';
                    return;
                }

                // Simple SVG-based chart
                const maxValue = Math.max(...data.map(d => Math.max(d.revenue, d.profit)));
                const chartHeight = 300;
                const chartWidth = chartContainer.offsetWidth || 800;
                const barWidth = Math.min(40, (chartWidth / data.length) - 10);
                const barSpacing = chartWidth / data.length;

                let svgBars = '';
                let svgLabels = '';

                data.forEach((point, index) => {
                    const x = index * barSpacing + (barSpacing - barWidth) / 2;

                    // Revenue bar
                    const revenueHeight = (point.revenue / maxValue * (chartHeight - 40));
                    svgBars += `<rect x="${x}" y="${chartHeight - 40 - revenueHeight}" width="${barWidth / 2 - 2}" height="${revenueHeight}" fill="url(#revenueGradient)" rx="4"/>`;

                    // Profit bar
                    const profitHeight = (point.profit / maxValue * (chartHeight - 40));
                    svgBars += `<rect x="${x + barWidth / 2}" y="${chartHeight - 40 - profitHeight}" width="${barWidth / 2 - 2}" height="${profitHeight}" fill="url(#profitGradient)" rx="4"/>`;

                    // Label
                    svgLabels += `<text x="${x + barWidth / 2}" y="${chartHeight - 10}" text-anchor="middle" font-size="10" fill="#64748b">${point.date}</text>`;
                });

                const svg = `
                    <svg width="${chartWidth}" height="${chartHeight}" style="width: 100%;">
                        <defs>
                            <linearGradient id="revenueGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="profitGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        ${svgBars}
                        ${svgLabels}
                    </svg>
                    <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 16px; height: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 4px;"></div>
                            <span style="font-size: 0.875rem; color: var(--wc-ultra-text-muted);">Revenue</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 16px; height: 16px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 4px;"></div>
                            <span style="font-size: 0.875rem; color: var(--wc-ultra-text-muted);">Profit</span>
                        </div>
                    </div>
                `;

                chartContainer.innerHTML = svg;
            }, 100);
        },

        /**
         * Load Products view
         */
        loadProducts() {
            const $content = $('#wc-ultra-suite-content');

            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_products',
                    nonce: wcUltraSuite.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderProducts(response.data);
                    }
                },
                error: () => {
                    $content.html('<p class="error">Failed to load products</p>');
                }
            });
        },

        /**
         * Render Products
         */
        renderProducts(products) {
            const html = `
                <div class="products-view">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h1 style="font-size: 2rem; font-weight: 700; margin: 0; color: var(--wc-ultra-text);">
                            📦 Products (${products.length})
                        </h1>
                        <a href="${wcUltraSuite.pluginUrl}../../../wp-admin/post-new.php?post_type=product" class="btn btn-primary">
                            + Add Product
                        </a>
                    </div>
                    
                    <div class="products-grid">
                        ${products.map(product => `
                            <div class="product-card">
                                <img src="${product.image || 'https://via.placeholder.com/280x200?text=No+Image'}" alt="${product.name}" class="product-image">
                                <div class="product-body">
                                    <h3 class="product-name">${product.name}</h3>
                                    <div class="product-price">${this.formatCurrency(product.price)}</div>
                                    <div class="product-meta">
                                        <span>Stock: ${product.stock || 'N/A'}</span>
                                        <span>Cost: ${this.formatCurrency(product.cost_price)}</span>
                                    </div>
                                    ${product.addons_count > 0 ? `<div class="mt-2"><span class="badge badge-info">${product.addons_count} Addons</span></div>` : ''}
                                    <div class="mt-2">
                                        <a href="${wcUltraSuite.pluginUrl}../../../wp-admin/post.php?post=${product.id}&action=edit" class="btn btn-secondary" style="width: 100%;">
                                            Edit Product
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            $('#wc-ultra-suite-content').html(html);
        },

        /**
         * Load Orders view
         */
        loadOrders() {
            const $content = $('#wc-ultra-suite-content');

            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_orders',
                    nonce: wcUltraSuite.nonce,
                    view: 'list'
                },
                success: (response) => {
                    if (response.success) {
                        this.renderOrders(response.data);
                    }
                },
                error: () => {
                    $content.html('<p class="error">Failed to load orders</p>');
                }
            });
        },

        /**
         * Render Orders
         */
        renderOrders(orders) {
            const html = `
                <div class="orders-view">
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem; color: var(--wc-ultra-text);">
                        🛒 Recent Orders (${orders.length})
                    </h1>
                    
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${orders.map(order => `
                                    <tr>
                                        <td><strong>#${order.order_number}</strong></td>
                                        <td>${order.customer_name}</td>
                                        <td>${this.getStatusBadge(order.status)}</td>
                                        <td><strong>${this.formatCurrency(order.total)}</strong></td>
                                        <td>${this.formatDate(order.date_created)}</td>
                                        <td>${order.items_count}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            $('#wc-ultra-suite-content').html(html);
        },

        /**
         * Load Customers view
         */
        loadCustomers() {
            const $content = $('#wc-ultra-suite-content');

            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_customers',
                    nonce: wcUltraSuite.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderCustomers(response.data);
                    }
                },
                error: () => {
                    $content.html('<p class="error">Failed to load customers</p>');
                }
            });
        },

        /**
         * Render Customers
         */
        renderCustomers(customers) {
            const html = `
                <div class="customers-view">
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem; color: var(--wc-ultra-text);">
                        👥 Top Customers (${customers.length})
                    </h1>
                    
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Orders</th>
                                    <th>Total Spent (LTV)</th>
                                    <th>Avg Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${customers.map(customer => `
                                    <tr>
                                        <td><strong>${customer.name}</strong></td>
                                        <td>${customer.email}</td>
                                        <td>${customer.orders}</td>
                                        <td><strong style="color: var(--wc-ultra-success);">${this.formatCurrency(customer.ltv)}</strong></td>
                                        <td>${this.formatCurrency(customer.aov)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            $('#wc-ultra-suite-content').html(html);
        },

        /**
         * Load Settings view
         */
        loadSettings() {
            // Fetch current settings
            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_settings',
                    nonce: wcUltraSuite.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderSettings(response.data);
                    }
                }
            });
        },

        renderSettings(settings) {
            const colors = settings.theme_colors || { primary: '#6366f1', secondary: '#8b5cf6', accent: '#10b981' };

            const html = `
                <div class="settings-view">
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem; color: var(--wc-ultra-text);">
                        🎨 Theme Customizer
                    </h1>
                    
                    <!-- Theme Presets -->
                    <div class="theme-presets-container">
                        <h3 style="margin-bottom: 1rem;">Quick Themes</h3>
                        <div class="theme-presets">
                            <div class="preset-card" data-preset="default">
                                <div class="preset-colors">
                                    <span style="background: #6366f1;"></span>
                                    <span style="background: #8b5cf6;"></span>
                                    <span style="background: #10b981;"></span>
                                </div>
                                <span class="preset-name">Default</span>
                            </div>
                            <div class="preset-card" data-preset="ocean">
                                <div class="preset-colors">
                                    <span style="background: #0ea5e9;"></span>
                                    <span style="background: #06b6d4;"></span>
                                    <span style="background: #14b8a6;"></span>
                                </div>
                                <span class="preset-name">Ocean</span>
                            </div>
                            <div class="preset-card" data-preset="sunset">
                                <div class="preset-colors">
                                    <span style="background: #f97316;"></span>
                                    <span style="background: #ec4899;"></span>
                                    <span style="background: #eab308;"></span>
                                </div>
                                <span class="preset-name">Sunset</span>
                            </div>
                            <div class="preset-card" data-preset="forest">
                                <div class="preset-colors">
                                    <span style="background: #22c55e;"></span>
                                    <span style="background: #84cc16;"></span>
                                    <span style="background: #10b981;"></span>
                                </div>
                                <span class="preset-name">Forest</span>
                            </div>
                            <div class="preset-card" data-preset="royal">
                                <div class="preset-colors">
                                    <span style="background: #7c3aed;"></span>
                                    <span style="background: #a855f7;"></span>
                                    <span style="background: #d946ef;"></span>
                                </div>
                                <span class="preset-name">Royal</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Colors -->
                    <div class="color-customizer">
                        <h3 style="margin-bottom: 1.5rem;">Custom Colors</h3>
                        
                        <div class="color-pickers-grid">
                            <div class="color-picker-card">
                                <div class="color-preview" style="background: ${colors.primary};">
                                    <div class="color-overlay"></div>
                                </div>
                                <div class="color-info">
                                    <label>Primary Color</label>
                                    <div class="color-input-group">
                                        <input type="color" id="primaryColor" value="${colors.primary}" class="color-input">
                                        <input type="text" id="primaryColorText" value="${colors.primary}" class="color-text">
                                    </div>
                                    <p class="color-description">Main brand color, used for buttons and highlights</p>
                                </div>
                            </div>
                            
                            <div class="color-picker-card">
                                <div class="color-preview" style="background: ${colors.secondary};">
                                    <div class="color-overlay"></div>
                                </div>
                                <div class="color-info">
                                    <label>Secondary Color</label>
                                    <div class="color-input-group">
                                        <input type="color" id="secondaryColor" value="${colors.secondary}" class="color-input">
                                        <input type="text" id="secondaryColorText" value="${colors.secondary}" class="color-text">
                                    </div>
                                    <p class="color-description">Complementary color for gradients</p>
                                </div>
                            </div>
                            
                            <div class="color-picker-card">
                                <div class="color-preview" style="background: ${colors.accent};">
                                    <div class="color-overlay"></div>
                                </div>
                                <div class="color-info">
                                    <label>Accent Color</label>
                                    <div class="color-input-group">
                                        <input type="color" id="accentColor" value="${colors.accent}" class="color-input">
                                        <input type="text" id="accentColorText" value="${colors.accent}" class="color-text">
                                    </div>
                                    <p class="color-description">Success states and positive actions</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Live Preview -->
                        <div class="live-preview-section">
                            <h3 style="margin-bottom: 1rem;">Live Preview</h3>
                            <div class="preview-container">
                                <button class="preview-btn preview-primary">Primary Button</button>
                                <button class="preview-btn preview-secondary">Secondary</button>
                                <div class="preview-badge">Success</div>
                                <div class="preview-card">
                                    <div class="preview-card-header"></div>
                                    <div class="preview-card-body">
                                        <div class="preview-text">Sample Card</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="settings-actions">
                            <button class="btn btn-primary" id="saveThemeColors">
                                💾 Save Theme
                            </button>
                            <button class="btn btn-secondary" id="resetThemeColors">
                                🔄 Reset to Default
                            </button>
                        </div>
                    </div>
                    
                    <!-- White Label Settings -->
                    <div class="settings-section" style="margin-top: 2rem; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                        <h3 style="margin-bottom: 1.5rem;">White Label Settings</h3>
                        
                        <div class="setting-item">
                            <label>Enable White Label</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="enableWhiteLabel" ${settings.white_label_enabled ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="setting-item" id="whiteLabelNameGroup" style="display: ${settings.white_label_enabled ? 'block' : 'none'}; margin-top: 1rem;">
                            <label>Plugin Name</label>
                            <input type="text" class="setting-input" id="whiteLabelName" value="${settings.white_label_name || 'Ultra Suite'}">
                            <p class="help-text" style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">This name will replace "Ultra Suite" in the admin menu.</p>
                        </div>
                        
                        <div class="setting-item" style="margin-top: 1rem;">
                            <label>Hide WooCommerce Menus</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="hideWcMenus" ${settings.hide_wc_menus ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="help-text" style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">Simplify the admin interface for your clients.</p>
                        </div>
                        
                        <div class="settings-actions" style="margin-top: 1.5rem;">
                            <button class="btn btn-primary" id="saveWhiteLabelSettings">
                                💾 Save White Label Settings
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('#wc-ultra-suite-content').html(html);

            // Bind color picker events
            this.bindColorPickerEvents();
        },

        bindColorPickerEvents() {
            const self = this;

            // Preset theme selection
            $('.preset-card').on('click', function () {
                $('.preset-card').removeClass('active');
                $(this).addClass('active');

                const preset = $(this).data('preset');
                const presets = {
                    default: { primary: '#6366f1', secondary: '#8b5cf6', accent: '#10b981' },
                    ocean: { primary: '#0ea5e9', secondary: '#06b6d4', accent: '#14b8a6' },
                    sunset: { primary: '#f97316', secondary: '#ec4899', accent: '#eab308' },
                    forest: { primary: '#22c55e', secondary: '#84cc16', accent: '#10b981' },
                    royal: { primary: '#7c3aed', secondary: '#a855f7', accent: '#d946ef' }
                };

                const colors = presets[preset];
                $('#primaryColor, #primaryColorText').val(colors.primary);
                $('#secondaryColor, #secondaryColorText').val(colors.secondary);
                $('#accentColor, #accentColorText').val(colors.accent);

                self.updateColorPreviews();
            });

            // Color input sync
            $('#primaryColor').on('input', function () {
                $('#primaryColorText').val($(this).val());
                self.updateColorPreviews();
            });

            $('#primaryColorText').on('input', function () {
                $('#primaryColor').val($(this).val());
                self.updateColorPreviews();
            });

            $('#secondaryColor').on('input', function () {
                $('#secondaryColorText').val($(this).val());
                self.updateColorPreviews();
            });

            $('#secondaryColorText').on('input', function () {
                $('#secondaryColor').val($(this).val());
                self.updateColorPreviews();
            });

            $('#accentColor').on('input', function () {
                $('#accentColorText').val($(this).val());
                self.updateColorPreviews();
            });

            $('#accentColorText').on('input', function () {
                $('#accentColor').val($(this).val());
                self.updateColorPreviews();
            });

            // Save theme
            $('#saveThemeColors').on('click', function () {
                self.saveThemeColors();
            });

            // Reset theme
            $('#resetThemeColors').on('click', function () {
                if (confirm('Reset to default theme colors?')) {
                    $('#primaryColor, #primaryColorText').val('#6366f1');
                    $('#secondaryColor, #secondaryColorText').val('#8b5cf6');
                    $('#accentColor, #accentColorText').val('#10b981');
                    self.updateColorPreviews();
                    self.saveThemeColors();
                }
            });

            // White Label Events
            $('#enableWhiteLabel').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#whiteLabelNameGroup').slideDown();
                } else {
                    $('#whiteLabelNameGroup').slideUp();
                }
            });

            $('#saveWhiteLabelSettings').on('click', function () {
                const settings = {
                    white_label_enabled: $('#enableWhiteLabel').is(':checked'),
                    white_label_name: $('#whiteLabelName').val(),
                    hide_wc_menus: $('#hideWcMenus').is(':checked')
                };

                $.ajax({
                    url: wcUltraSuite.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wc_ultra_suite_save_settings',
                        nonce: wcUltraSuite.nonce,
                        settings: settings
                    },
                    success: (response) => {
                        if (response.success) {
                            const $btn = $('#saveWhiteLabelSettings');
                            const originalText = $btn.text();
                            $btn.text('✓ Saved!').prop('disabled', true);

                            setTimeout(() => {
                                $btn.text(originalText).prop('disabled', false);
                                location.reload(); // Reload to apply menu changes
                            }, 1500);
                        }
                    }
                });
            });
        },

        updateColorPreviews() {
            const primary = $('#primaryColor').val();
            const secondary = $('#secondaryColor').val();
            const accent = $('#accentColor').val();

            // Update color preview cards
            $('.color-picker-card').eq(0).find('.color-preview').css('background', primary);
            $('.color-picker-card').eq(1).find('.color-preview').css('background', secondary);
            $('.color-picker-card').eq(2).find('.color-preview').css('background', accent);

            // Update live preview
            $('.preview-primary').css({
                'background': `linear-gradient(135deg, ${primary}, ${secondary})`,
                'box-shadow': `0 4px 12px ${primary}40`
            });

            $('.preview-badge').css('background', accent);
            $('.preview-card-header').css('background', `linear-gradient(90deg, ${primary}, ${secondary})`);
        },

        saveThemeColors() {
            const colors = {
                primary: $('#primaryColor').val(),
                secondary: $('#secondaryColor').val(),
                accent: $('#accentColor').val()
            };

            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_save_settings',
                    nonce: wcUltraSuite.nonce,
                    settings: {
                        theme_colors: colors
                    }
                },
                success: (response) => {
                    if (response.success) {
                        // Show success message
                        const $btn = $('#saveThemeColors');
                        const originalText = $btn.text();
                        $btn.text('✓ Saved!').prop('disabled', true);

                        setTimeout(() => {
                            $btn.text(originalText).prop('disabled', false);
                            // Reload page to apply new colors
                            location.reload();
                        }, 1500);
                    }
                }
            });
        },

        /**
         * Load Product Page Customizer
         */
        loadProductPageCustomizer() {
            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_product_page_settings',
                    nonce: wcUltraSuite.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderProductPageCustomizer(response.data);
                    }
                }
            });
        },

        renderProductPageCustomizer(settings) {
            const html = `
                <div class="customizer-view">
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem;">
                        🛍️ Product Page Customizer
                    </h1>
                    <div class="customizer-container">
                        <div class="customizer-settings">
                            <div class="settings-section">
                                <h3>Layout & Style</h3>
                                <div class="setting-item">
                                    <label>Image Position</label>
                                    <select class="setting-select" id="prodImagePosition">
                                        <option value="left" ${settings.image_position === 'left' ? 'selected' : ''}>Left (Default)</option>
                                        <option value="right" ${settings.image_position === 'right' ? 'selected' : ''}>Right</option>
                                    </select>
                                </div>
                                <div class="setting-item">
                                    <label>Gallery Style</label>
                                    <select class="setting-select" id="prodGalleryStyle">
                                        <option value="thumbnails" ${settings.gallery_style === 'thumbnails' ? 'selected' : ''}>Thumbnails</option>
                                        <option value="slider" ${settings.gallery_style === 'slider' ? 'selected' : ''}>Slider</option>
                                    </select>
                                </div>
                                <div class="setting-item">
                                    <label>Add to Cart Button Style</label>
                                    <select class="setting-select" id="prodButtonStyle">
                                        <option value="default" ${settings.button_style === 'default' ? 'selected' : ''}>Default</option>
                                        <option value="rounded" ${settings.button_style === 'rounded' ? 'selected' : ''}>Rounded</option>
                                        <option value="square" ${settings.button_style === 'square' ? 'selected' : ''}>Square</option>
                                    </select>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h3>Visibility</h3>
                                <div class="setting-item">
                                    <label>Show Quantity Field</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="prodShowQuantity" ${settings.show_quantity ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Show SKU</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="prodShowSku" ${settings.show_sku ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Show Categories</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="prodShowCategories" ${settings.show_categories ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Show Tags</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="prodShowTags" ${settings.show_tags ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h3>Related Products</h3>
                                <div class="setting-item">
                                    <label>Show Related Products</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="prodShowRelated" ${settings.show_related ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Related Products Count</label>
                                    <input type="number" class="setting-input" id="prodRelatedCount" value="${settings.related_count}" min="2" max="12">
                                </div>
                            </div>
                            
                            <div class="customizer-actions">
                                <button class="btn btn-primary" id="saveProductPageSettings">💾 Save Changes</button>
                                <button class="btn btn-secondary" id="resetProductPageSettings">🔄 Reset</button>
                            </div>
                        </div>
                        <div class="customizer-preview">
                            <div class="preview-header">
                                <h3>Live Preview</h3>
                                <span class="preview-badge">Frontend</span>
                            </div>
                            <div class="preview-frame" style="height: 600px; overflow: hidden; border-radius: 12px; border: 1px solid #e2e8f0;">
                                ${wcUltraSuite.productUrl ?
                    `<iframe src="${wcUltraSuite.productUrl}" style="width: 100%; height: 100%; border: none;"></iframe>` :
                    `<div class="preview-placeholder"><p>No products found for preview</p></div>`
                }
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#wc-ultra-suite-content').html(html);

            $('#saveProductPageSettings').on('click', () => {
                this.saveProductPageSettings();
            });

            $('#resetProductPageSettings').on('click', () => {
                if (confirm('Reset product page settings?')) {
                    this.loadProductPageCustomizer();
                }
            });
        },

        saveProductPageSettings() {
            const settings = {
                image_position: $('#prodImagePosition').val(),
                gallery_style: $('#prodGalleryStyle').val(),
                button_style: $('#prodButtonStyle').val(),
                show_quantity: $('#prodShowQuantity').is(':checked'),
                show_sku: $('#prodShowSku').is(':checked'),
                show_categories: $('#prodShowCategories').is(':checked'),
                show_tags: $('#prodShowTags').is(':checked'),
                show_related: $('#prodShowRelated').is(':checked'),
                related_count: parseInt($('#prodRelatedCount').val())
            };

            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_save_product_page_settings',
                    nonce: wcUltraSuite.nonce,
                    settings: settings
                },
                success: (response) => {
                    if (response.success) {
                        const $btn = $('#saveProductPageSettings');
                        const originalText = $btn.text();
                        $btn.text('✓ Saved!').prop('disabled', true);

                        // Reload iframe
                        const $iframe = $('.customizer-preview iframe');
                        if ($iframe.length) {
                            $iframe.attr('src', $iframe.attr('src'));
                        }

                        setTimeout(() => {
                            $btn.text(originalText).prop('disabled', false);
                        }, 1500);
                    }
                }
            });
        },

        /**
         * Load Shop Page Customizer
         */
        loadShopPageCustomizer() {
            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_get_shop_page_settings',
                    nonce: wcUltraSuite.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderShopPageCustomizer(response.data);
                    }
                }
            });
        },

        renderShopPageCustomizer(settings) {
            const html = `
                <div class="customizer-view">
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem;">
                        🏪 Shop Page Customizer
                    </h1>
                    <div class="customizer-container">
                        <div class="customizer-settings">
                            <div class="settings-section">
                                <h3>Grid Layout</h3>
                                <div class="setting-item">
                                    <label>Products Per Row</label>
                                    <select class="setting-select" id="shopColumns">
                                        <option value="2" ${settings.columns == 2 ? 'selected' : ''}>2 Columns</option>
                                        <option value="3" ${settings.columns == 3 ? 'selected' : ''}>3 Columns</option>
                                        <option value="4" ${settings.columns == 4 ? 'selected' : ''}>4 Columns</option>
                                    </select>
                                </div>
                                <div class="setting-item">
                                    <label>Products Per Page</label>
                                    <input type="number" class="setting-input" id="shopProductsPerPage" value="${settings.products_per_page}" min="4" max="48">
                                </div>
                                <div class="setting-item">
                                    <label>Grid Spacing</label>
                                    <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                                        <input type="range" class="setting-range" id="shopGridSpacing" min="0" max="40" value="${settings.grid_spacing}">
                                        <span id="gridSpacingValue">${settings.grid_spacing}px</span>
                                    </div>
                                </div>
                            </div>
                            <div class="settings-section">
                                <h3>Product Card Design</h3>
                                <div class="setting-item">
                                    <label>Card Style</label>
                                    <select class="setting-select" id="shopCardStyle">
                                        <option value="default" ${settings.card_style === 'default' ? 'selected' : ''}>Default</option>
                                        <option value="minimal" ${settings.card_style === 'minimal' ? 'selected' : ''}>Minimal</option>
                                        <option value="modern" ${settings.card_style === 'modern' ? 'selected' : ''}>Modern</option>
                                    </select>
                                </div>
                                <div class="setting-item">
                                    <label>Image Carousel</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="shopEnableCarousel" ${settings.enable_image_carousel ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Wishlist Button</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="shopEnableWishlist" ${settings.enable_wishlist ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Buy Now Button</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="shopEnableBuyNow" ${settings.enable_buy_now ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Show Rating</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="shopShowRating" ${settings.show_rating ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Show Add to Cart</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="shopShowAddToCart" ${settings.show_add_to_cart ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>Show Sale Badge</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="shopShowSaleBadge" ${settings.show_sale_badge ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h3>Advanced Filtering</h3>
                                <div class="setting-item">
                                    <label>Enable Filters</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="shopEnableFilters" ${settings.enable_filters ? 'checked' : ''}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="filter-options" style="margin-left: 1rem; border-left: 2px solid #e2e8f0; padding-left: 1rem; display: ${settings.enable_filters ? 'block' : 'none'};">
                                    <div class="setting-item">
                                        <label>Filter Style</label>
                                        <select class="setting-select" id="shopFilterStyle">
                                            <option value="sidebar-cards" ${settings.filter_style === 'sidebar-cards' ? 'selected' : ''}>Sidebar Cards</option>
                                            <option value="sidebar-list" ${settings.filter_style === 'sidebar-list' ? 'selected' : ''}>Sidebar List</option>
                                        </select>
                                    </div>
                                    <div class="setting-item">
                                        <label>Price Filter</label>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="shopEnablePriceFilter" ${settings.enable_price_filter ? 'checked' : ''}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="setting-item">
                                        <label>Category Filter</label>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="shopEnableCategoryFilter" ${settings.enable_category_filter ? 'checked' : ''}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="setting-item">
                                        <label>Attribute Filter</label>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="shopEnableAttributeFilter" ${settings.enable_attribute_filter ? 'checked' : ''}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div id="filter-attributes-container" style="margin-top: 1rem; padding-left: 0.5rem; display: ${settings.enable_attribute_filter ? 'block' : 'none'};">
                                        <p style="font-size: 0.9rem; color: #64748b;">Loading attributes...</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h3>Custom Template</h3>
                                <div class="setting-item">
                                    <label>Enable Custom Shop Template</label>
                                    <select class="setting-select" id="shopPaginationStyle">
                                        <option value="numbers" ${settings.pagination_style === 'numbers' ? 'selected' : ''}>Numbers</option>
                                        <option value="load-more" ${settings.pagination_style === 'load-more' ? 'selected' : ''}>Load More Button</option>
                                        <option value="infinite" ${settings.pagination_style === 'infinite' ? 'selected' : ''}>Infinite Scroll</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="customizer-actions">
                                <button class="btn btn-primary" id="saveShopPageSettings">💾 Save Changes</button>
                                <button class="btn btn-secondary" id="resetShopPageSettings">🔄 Reset</button>
                            </div>
                        </div>
                        <div class="customizer-preview">
                            <div class="preview-header">
                                <h3>Live Preview</h3>
                                <span class="preview-badge">Frontend</span>
                            </div>
                            <div class="preview-frame" style="height: 600px; overflow: hidden; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <iframe src="${wcUltraSuite.shopUrl}" style="width: 100%; height: 100%; border: none;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#wc-ultra-suite-content').html(html);

            $('#shopGridSpacing').on('input', function () {
                $('#gridSpacingValue').text($(this).val() + 'px');
            });

            $('#shopEnableFilters').on('change', function () {
                if ($(this).is(':checked')) {
                    $('.filter-options').slideDown();
                } else {
                    $('.filter-options').slideUp();
                }
            });

            $('#saveShopPageSettings').on('click', () => {
                this.saveShopPageSettings();
            });

            $('#resetShopPageSettings').on('click', () => {
                if (confirm('Reset shop page settings?')) {
                    this.loadShopPageCustomizer();
                }
            });
            // Toggle attribute container visibility
            $('#shopEnableAttributeFilter').on('change', function () {
                $('#filter-attributes-container').toggle($(this).is(':checked'));
            });

            // Fetch attributes
            $.post(wcUltraSuite.ajaxUrl, {
                action: 'wc_ultra_suite_get_attributes',
                nonce: wcUltraSuite.nonce
            }, (response) => {
                if (response.success) {
                    const attributes = response.data;
                    const enabledFilters = settings.enabled_filters || [];

                    if (attributes.length === 0) {
                        $('#filter-attributes-container').html('<p style="font-size: 0.9rem; color: #64748b;">No global attributes found.</p>');
                        return;
                    }

                    let attributeHtml = '<label style="display:block; margin-bottom:0.5rem; font-weight:600; font-size:0.9rem;">Select Attributes to Show:</label>';
                    attributes.forEach(attr => {
                        const isChecked = enabledFilters.includes(attr.name) ? 'checked' : '';
                        attributeHtml += `
                            <label class="checkbox-label" style="display:flex; align-items:center; margin-bottom:0.5rem; cursor:pointer;">
                                <input type="checkbox" class="filter-attribute-checkbox" value="${attr.name}" ${isChecked} style="margin-right:0.5rem;">
                                ${attr.label}
                            </label>
                        `;
                    });

                    $('#filter-attributes-container').html(attributeHtml);
                }
            });

            // Generate Dummy Products
            $('#generateDummyProducts').on('click', function () {
                const $btn = $(this);
                $btn.prop('disabled', true).text('Generating...');

                $.post(wcUltraSuite.ajaxUrl, {
                    action: 'wc_ultra_suite_generate_dummy_products',
                    nonce: wcUltraSuite.nonce
                }, (response) => {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                    $btn.prop('disabled', false).text('📦 Generate 10 Dummy Products');
                });
            });
        },

        saveShopPageSettings() {
            const settings = {
                columns: parseInt($('#shopColumns').val()),
                products_per_page: parseInt($('#shopProductsPerPage').val()),
                grid_spacing: parseInt($('#shopGridSpacing').val()),
                card_style: $('#shopCardStyle').val(),
                enable_image_carousel: $('#shopEnableCarousel').is(':checked'),
                enable_wishlist: $('#shopEnableWishlist').is(':checked'),
                enable_buy_now: $('#shopEnableBuyNow').is(':checked'),
                show_rating: $('#shopShowRating').is(':checked'),
                show_add_to_cart: $('#shopShowAddToCart').is(':checked'),
                show_sale_badge: $('#shopShowSaleBadge').is(':checked'),
                show_sorting: true,
                show_result_count: true,
                enable_filters: $('#shopEnableFilters').is(':checked'),
                filter_style: $('#shopFilterStyle').val(),
                enable_price_filter: $('#shopEnablePriceFilter').is(':checked'),
                enable_category_filter: $('#shopEnableCategoryFilter').is(':checked'),
                enable_attribute_filter: $('#shopEnableAttributeFilter').is(':checked'),
                enabled_filters: (function () {
                    const filters = [];
                    $('.filter-attribute-checkbox:checked').each(function () {
                        filters.push($(this).val());
                    });
                    return filters;
                })(),
                enable_custom_template: $('#shopEnableCustomTemplate').is(':checked'),
                pagination_style: $('#shopPaginationStyle').val()
            };

            $.ajax({
                url: wcUltraSuite.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_ultra_suite_save_shop_page_settings',
                    nonce: wcUltraSuite.nonce,
                    settings: settings
                },
                success: (response) => {
                    if (response.success) {
                        const $btn = $('#saveShopPageSettings');
                        const originalText = $btn.text();
                        $btn.text('✓ Saved!').prop('disabled', true);

                        // Reload iframe
                        const $iframe = $('.customizer-preview iframe');
                        $iframe.attr('src', $iframe.attr('src'));

                        setTimeout(() => {
                            $btn.text(originalText).prop('disabled', false);
                            alert('Shop page settings saved! Visit your shop page to see changes.');
                        }, 1500);
                    }
                }
            });
        },

        /**
         * Utility: Format currency
         */
        formatCurrency(amount) {
            const symbol = wcUltraSuite.currencySymbol || '$';
            const formatted = parseFloat(amount).toFixed(2);
            return `${symbol}${formatted} `;
        },

        /**
         * Utility: Format date
         */
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        },

        /**
         * Utility: Get status badge
         */
        getStatusBadge(status) {
            const statusMap = {
                'completed': 'badge-success',
                'processing': 'badge-info',
                'pending': 'badge-warning',
                'failed': 'badge-danger',
                'cancelled': 'badge-danger',
                'on-hold': 'badge-warning'
            };

            const badgeClass = statusMap[status] || 'badge-info';
            return `< span class="badge ${badgeClass}" > ${status}</span > `;
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        UltraSuite.init();
    });

})(jQuery);
