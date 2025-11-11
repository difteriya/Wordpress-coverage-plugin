# Map Coverage Plugin

**Creator:** Khudiyev  
**Version:** 1.0  
**Website:** [xudiyev.com](https://xudiyev.com)

Advanced WordPress plugin to create and manage coverage areas with interactive maps and intelligent address search functionality in Azerbaijani language.

## Features

### 🗺️ **Interactive Coverage Management**
- Custom post type `coverage_area` for admin-managed coverage items
- Admin meta box with OpenLayers map to draw Points, Polygons and Circles
- City taxonomy for organizing coverage areas by location
- Advanced address management with street names and house numbers

### 🔍 **Smart Frontend Search**
- Frontend shortcode `[map_coverage]` - displays full map with search functionality
- Frontend shortcode `[map_coverage_search]` - displays search form only with redirect capability
- Intelligent autocomplete for street addresses from database
- House number dropdown showing available numbers from coverage data
- Real-time search filtering and suggestions

### ⚙️ **Admin Features**
- Settings page for contact us page configuration
- Repeatable UI for adding streets and house numbers
- Color-coded coverage areas with custom styling
- Modern gradient UI with glass morphism effects

### 🎨 **Elementor Integration**
- Custom Elementor widget "Əhatə Xəritəsi" (Coverage Map)
- Two widget modes: Full Map + Search, Search Only
- Configurable styling and redirect options
- Responsive design optimized for mobile

### 🌐 **Localization**
- Fully translated to Azerbaijani language
- User-friendly error messages with contact page integration
- Professional UI with modern design patterns

## Installation

1. Copy the `map-coverage-plugin` folder into `wp-content/plugins/`
2. Activate the plugin in the WordPress admin
3. Configure contact page in Coverage Areas → Settings
4. Create coverage areas in Coverage Areas → Add New
5. Add cities in Coverage Areas → Cities
6. Use shortcodes `[map_coverage]` or `[map_coverage_search]` on frontend

## Usage

### Admin Setup
1. **Add Cities:** Go to Coverage Areas → Cities and create city categories
2. **Create Coverage Areas:** 
   - Add new coverage area with title
   - Assign to a city category
   - Draw geometry on the map (points, polygons, circles)
   - Add street addresses with house numbers using the repeatable interface
   - Set custom colors and properties

### Frontend Implementation
```php
// Full map with search
[map_coverage]

// Search form only (redirects to coverage page)
[map_coverage_search]

// Search form with custom redirect
[map_coverage_search redirect_page="/custom-page"]
```

### Elementor Widget
- Available in Elementor panel under "General" category
- Choose between "Tam Xəritə + Axtarış" or "Yalnız Axtarış Formu"
- Configure styling options and redirect settings

## Technical Features

### 🔧 **Advanced Functionality**
- AJAX-powered street autocomplete with debounced search
- Database-driven house number suggestions
- Responsive grid layouts with mobile optimization
- Modern CSS with gradient backgrounds and glass morphism
- Cross-browser compatible autocomplete functionality

### 🛡️ **Security & Performance**
- Sanitized inputs and secure data handling
- Optimized database queries
- Proper WordPress hooks and actions
- Nonce verification for admin operations

### 🎯 **User Experience**
- Real-time address validation
- Intelligent error messages with contact links
- Smooth animations and transitions
- Professional dropdown styling with hover effects
- Mobile-first responsive design

## Address Management

Each coverage area supports sophisticated address management:

- **Streets:** Add multiple street names per coverage area
- **House Numbers:** Specify exact house numbers for each street
- **Autocomplete:** Frontend shows intelligent suggestions from database
- **Validation:** System checks if address exists in coverage data
- **Error Handling:** Professional error messages with contact page links

## Styling & Design

- Modern gradient backgrounds with customizable colors
- Glass morphism effects with backdrop blur
- Professional dropdown styling with dark text on light background
- Purple hover effects with smooth slide animations
- Custom scrollbar styling for better UX
- Fully responsive design for all screen sizes

## Next Steps / Improvements

- Add server-side REST endpoints for large data sets
- Provide import/export for GeoJSON files
- Add bulk address import functionality
- Enhanced analytics and reporting features
- Multi-language support expansion

---

**Developed by Khudiyev**  
For support and updates, visit: [xudiyev.com](https://xudiyev.com)
