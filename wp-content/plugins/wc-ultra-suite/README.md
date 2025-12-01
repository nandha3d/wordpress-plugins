# WC Ultra Suite

**The Complete WooCommerce Management Solution**

Transform your WooCommerce store with a premium, all-in-one management plugin designed for agencies and their clients.

## 🚀 Features

### 📊 Advanced Analytics Dashboard
- **Real-time Metrics**: Revenue, Profit, Orders, and Average Order Value
- **Profit Tracking**: Automatic profit calculation (Revenue - Cost of Goods)
- **Visual Charts**: Beautiful sales and profit trend visualizations
- **Top Products**: See your best-performing products by profit

### 📦 Product Hub
- **Cost Tracking**: Add "Cost Price" to products for profit calculations
- **Product Addons**: Build custom product fields with additional costs
  - Checkbox, Text Input, Textarea, Select Dropdown, Radio Buttons
  - Set prices for each addon
  - Mark fields as required
  - Professional frontend display
- **Grid View**: Beautiful product cards with images and quick stats
- **Quick Edit**: Update prices and stock directly from the dashboard

### 🛒 Order Management
- **List View**: Clean table view of all orders
- **Status Tracking**: Visual status badges
- **Customer Info**: Quick access to customer details

### 👥 Customer CRM
- **Lifetime Value (LTV)**: Track total customer spend
- **Average Order Value**: See customer purchasing patterns
- **Order History**: Complete purchase history per customer
- **Top Customers**: Sorted by total spend

### ⚙️ White Label Ready
- **Client Mode**: Customize plugin name for your clients
- **Hide WC Menus**: Simplify the interface for end users

## 📋 Installation

1. Upload the `wc-ultra-suite` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Ultra Suite** in the WordPress admin menu
4. Start managing your store!

## 🎨 Design Philosophy

WC Ultra Suite uses a modern, premium design system:
- **Glassmorphism** effects for depth
- **Gradient accents** for visual interest
- **Smooth animations** for better UX
- **Responsive layout** works on all devices
- **Professional typography** using Inter font family

## 💡 How to Use

### Adding Product Addons

1. Edit any product
2. Go to the **Product Addons** tab
3. Click **Add Addon**
4. Configure:
   - **Label**: e.g., "Gift Wrap"
   - **Type**: Checkbox, Text, Select, etc.
   - **Price**: Additional cost (e.g., 5.00)
   - **Required**: Make it mandatory
5. Save the product
6. Visit the product page to see the addon fields

### Tracking Profit

1. Edit a product
2. In the **General** tab, find **Cost Price**
3. Enter your cost of goods (e.g., if you sell for $100 and it costs you $60, enter 60)
4. Save the product
5. View profit analytics in the **Ultra Suite Dashboard**

### Viewing Analytics

1. Go to **Ultra Suite** → **Dashboard**
2. See real-time stats for:
   - Total Revenue
   - Net Profit
   - Total Orders
   - Average Order Value
3. View the **Sales & Profit Trend** chart
4. Check **Top Products** by profit

## 🔧 Technical Details

- **WordPress**: 5.8+
- **PHP**: 7.4+
- **WooCommerce**: 5.0+
- **No external dependencies**: Pure vanilla JS and CSS

## 📁 File Structure

```
wc-ultra-suite/
├── assets/
│   ├── css/
│   │   ├── admin-style.css      # Premium admin dashboard styles
│   │   └── frontend-style.css   # Product addon frontend styles
│   └── js/
│       ├── admin-app.js          # SPA-like admin application
│       └── frontend-addons.js    # Frontend addon functionality
├── includes/
│   ├── class-core.php            # Core plugin functionality
│   └── modules/
│       ├── class-analytics.php   # Analytics and profit tracking
│       ├── class-products.php    # Product management and addons
│       ├── class-orders.php      # Order management
│       └── class-crm.php         # Customer relationship management
├── templates/
│   └── dashboard.php             # Main dashboard template
└── wc-ultra-suite.php            # Main plugin file
```

## 🎯 Perfect For

- **Agencies**: Deploy to multiple clients with white-label options
- **Store Owners**: Get professional insights and management tools
- **Product Customization**: Sell products with paid add-ons
- **Profit Tracking**: Know your real margins

## 🆘 Support

For support, feature requests, or bug reports, please contact your plugin provider.

## 📄 License

This plugin is proprietary software. All rights reserved.

## 🔄 Changelog

### Version 1.0.0
- Initial release
- Analytics dashboard with profit tracking
- Product addons system
- Order management
- Customer CRM
- White label settings

---

**Made with ❤️ for WooCommerce**
