(function ($) {
    'use strict';

    const ShopFilters = {
        init() {
            this.cacheDom();
            this.bindEvents();
            this.initPriceSlider();
        },

        cacheDom() {
            this.$container = $('.wc-ultra-filters');
            this.$products = $('ul.products');
            this.$pagination = $('.woocommerce-pagination');
            this.$slider = $('.wc-ultra-price-slider');
        },

        bindEvents() {
            // Checkbox changes
            this.$container.on('change', 'input[type="checkbox"]', () => {
                this.applyFilters();
            });

            // Browser back/forward buttons
            window.onpopstate = (event) => {
                if (event.state) {
                    this.fetchProducts(window.location.href);
                }
            };
        },

        initPriceSlider() {
            if (!this.$slider.length) return;

            const $range = this.$slider.find('.wc-ultra-slider-range');
            const $handles = this.$slider.find('.wc-ultra-slider-handle');
            const $minHandle = $handles.eq(0);
            const $maxHandle = $handles.eq(1);
            const $labels = this.$slider.next().find('span');

            let minPrice = parseInt(wcUltraSuiteShop.minPrice) || 0;
            let maxPrice = parseInt(wcUltraSuiteShop.maxPrice) || 1000;
            let currentMin = minPrice;
            let currentMax = maxPrice;

            // Helper to update UI
            const updateUI = () => {
                const minPercent = ((currentMin - minPrice) / (maxPrice - minPrice)) * 100;
                const maxPercent = ((currentMax - minPrice) / (maxPrice - minPrice)) * 100;

                $minHandle.css('left', minPercent + '%');
                $maxHandle.css('left', maxPercent + '%');
                $range.css('left', minPercent + '%').css('width', (maxPercent - minPercent) + '%');

                $labels.eq(0).text(wcUltraSuiteShop.currency + Math.round(currentMin));
                $labels.eq(1).text(wcUltraSuiteShop.currency + Math.round(currentMax) + (currentMax === maxPrice ? '+' : ''));
            };

            // Drag logic
            let isDragging = false;
            let activeHandle = null;

            $handles.on('mousedown touchstart', function (e) {
                isDragging = true;
                activeHandle = $(this);
                e.preventDefault();
            });

            $(document).on('mousemove touchmove', (e) => {
                if (!isDragging || !activeHandle) return;

                const sliderRect = this.$slider[0].getBoundingClientRect();
                const pageX = e.type === 'touchmove' ? e.touches[0].pageX : e.pageX;
                let percent = (pageX - sliderRect.left) / sliderRect.width * 100;

                percent = Math.max(0, Math.min(100, percent));

                const value = minPrice + (percent / 100) * (maxPrice - minPrice);

                if (activeHandle.is($minHandle)) {
                    if (value < currentMax) {
                        currentMin = value;
                        updateUI();
                    }
                } else {
                    if (value > currentMin) {
                        currentMax = value;
                        updateUI();
                    }
                }
            });

            $(document).on('mouseup touchend', () => {
                if (isDragging) {
                    isDragging = false;
                    activeHandle = null;
                    this.applyFilters(); // Trigger filter on drop
                }
            });

            // Initial UI update
            updateUI();

            // Expose values for filter function
            this.getPriceRange = () => {
                return { min: Math.round(currentMin), max: Math.round(currentMax) };
            };
        },

        applyFilters() {
            const params = new URLSearchParams(window.location.search);

            // 1. Categories
            const selectedCats = [];
            $('input[name="product_cat"]:checked').each(function () {
                selectedCats.push($(this).val());
            });
            if (selectedCats.length) {
                params.set('product_cat', selectedCats.join(','));
            } else {
                params.delete('product_cat');
            }

            // 2. Attributes
            // Find all attribute inputs
            const attributeGroups = {};
            $('input[name^="filter_"]:checked').each(function () {
                const name = $(this).attr('name'); // filter_color
                const val = $(this).val();
                if (!attributeGroups[name]) attributeGroups[name] = [];
                attributeGroups[name].push(val);
            });

            // Clear existing attribute params first
            for (const key of params.keys()) {
                if (key.startsWith('filter_')) {
                    params.delete(key);
                }
            }

            // Add new attribute params
            for (const [key, values] of Object.entries(attributeGroups)) {
                params.set(key, values.join(','));
            }

            // 3. Price
            if (this.getPriceRange) {
                const price = this.getPriceRange();
                if (price.min > 0) params.set('min_price', price.min);
                else params.delete('min_price');

                if (price.max < 1000) params.set('max_price', price.max);
                else params.delete('max_price');
            }

            // Reset pagination
            params.delete('paged');

            const newUrl = window.location.pathname + '?' + params.toString();

            // Update URL and fetch
            window.history.pushState({ path: newUrl }, '', newUrl);
            this.fetchProducts(newUrl);
        },

        initCarousel() {
            $(document).on('mouseenter', '.wc-ultra-product-image-wrapper', function () {
                const $carousel = $(this).find('.wc-ultra-carousel');
                if (!$carousel.length) return;

                // Auto scroll logic
                let scrollInterval = setInterval(() => {
                    const maxScroll = $carousel[0].scrollWidth - $carousel.width();
                    if ($carousel.scrollLeft() >= maxScroll) {
                        $carousel.scrollLeft(0);
                    } else {
                        $carousel.scrollLeft($carousel.scrollLeft() + 2);
                    }
                }, 30);

                $(this).data('scrollInterval', scrollInterval);
            }).on('mouseleave', '.wc-ultra-product-image-wrapper', function () {
                clearInterval($(this).data('scrollInterval'));
                $(this).find('.wc-ultra-carousel').stop().animate({ scrollLeft: 0 }, 300);
            });
        },

        initButtons() {
            // Wishlist
            $(document).on('click', '.wc-ultra-wishlist-btn', function (e) {
                e.preventDefault();
                $(this).toggleClass('active');
                const productId = $(this).data('product-id');

                // Save to local storage
                let wishlist = JSON.parse(localStorage.getItem('wc_ultra_wishlist') || '[]');
                if ($(this).hasClass('active')) {
                    if (!wishlist.includes(productId)) wishlist.push(productId);
                } else {
                    wishlist = wishlist.filter(id => id !== productId);
                }
                localStorage.setItem('wc_ultra_wishlist', JSON.stringify(wishlist));
            });

            // Restore wishlist state
            this.restoreWishlist();
        },

        restoreWishlist() {
            const wishlist = JSON.parse(localStorage.getItem('wc_ultra_wishlist') || '[]');
            $('.wc-ultra-wishlist-btn').each(function () {
                if (wishlist.includes($(this).data('product-id'))) {
                    $(this).addClass('active');
                }
            });
        },

        fetchProducts(url) {
            $('.products, .woocommerce-pagination').css('opacity', '0.5');

            $.get(url, (response) => {
                const $html = $(response);
                const $newProducts = $html.find('ul.products');
                const $newPagination = $html.find('.woocommerce-pagination');

                if ($newProducts.length) {
                    $('ul.products').replaceWith($newProducts);
                } else {
                    $('ul.products').html('<p class="woocommerce-info">No products found matching your selection.</p>');
                }

                if ($newPagination.length) {
                    $('.woocommerce-pagination').replaceWith($newPagination);
                } else {
                    $('.woocommerce-pagination').remove();
                }

                // Re-initialize plugins
                this.restoreWishlist();

                $('.products, .woocommerce-pagination').css('opacity', '1');

                // Scroll to top of products
                $('html, body').animate({
                    scrollTop: $('.products').offset().top - 100
                }, 500);
            });
        }
    };

    $(document).ready(() => {
        ShopFilters.init();
        ShopFilters.initCarousel();
        ShopFilters.initButtons();

        // Fix Duplicate Images (Aggressive)
        $('.wc-ultra-product-image-wrapper').each(function () {
            $(this).siblings('img').remove();
            $(this).siblings('.onsale').remove();
            // Remove images inside the link that are not our carousel
            $(this).closest('.product').find('a > img').not('.wc-ultra-carousel-img, .wc-ultra-main-img').remove();
        });
    });

})(jQuery);
