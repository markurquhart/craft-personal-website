# Personal Website - Craft CMS

A modern personal website built with Craft CMS 5, featuring a sophisticated content architecture for showcasing photography, creative projects, blog posts, and travel experiences.

## Features

### Content Types
- **Photography** - Three-level hierarchical photography organization
  - Categories (Sports, City, Wildlife, Outdoors, etc.) - Level 1
  - Albums (with name, date, location) - Level 2
  - Individual photos with detailed EXIF data (camera, lens, ISO, aperture, shutter speed) - Level 3
  - Albums can be related to blog posts and travel entries
- **Freelance Work** - Client projects and freelance work
  - Project name, type, customer/client
  - Delivery date, description
  - Featured image and project image gallery
- **Thoughts (Blog)** - Personal blog with categories and related content
- **Travels** - Travel journal with destinations, dates, photo galleries, and trip types
- **Static Pages** - Home, About, and Contact pages

### Key Features
- **Location-based organization** - Hierarchical location system (Country → State/Province → City) that links all content by place
- Bi-directional content relationships (link blog posts, photography albums, and travels)
- Category taxonomies for photography and blog posts
- Photo galleries with metadata
- Featured images for all content types
- Responsive, minimal design
- Project configuration via YAML (easy deployment)

## Tech Stack

- **CMS**: Craft CMS 5.8.18
- **Language**: PHP 8.4
- **Database**: MySQL 9.4
- **Package Manager**: Composer 2.8
- **Templating**: Twig

## Installation

### Prerequisites
- PHP 8.4+ with extensions: intl, pdo_mysql, gd, json, zip
- MySQL 8.0+ or MariaDB 10.4+
- Composer 2.x

### Setup

1. **Clone the repository**
   ```bash
   git clone git@github.com:markurquhart/craft-personal-website.git
   cd craft-personal-website
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Create environment file**
   ```bash
   cp .env.example.dev .env
   ```

4. **Configure database**
   Edit `.env` and set your database credentials:
   ```
   CRAFT_DB_SERVER=127.0.0.1
   CRAFT_DB_PORT=3306
   CRAFT_DB_DATABASE=your_database_name
   CRAFT_DB_USER=your_database_user
   CRAFT_DB_PASSWORD=your_database_password
   ```

5. **Generate security key**
   ```bash
   php craft setup/security-key
   ```

6. **Run installation**
   ```bash
   php craft install
   ```

7. **Run content architecture migration**
   ```bash
   php craft migrate/up --track=content
   ```

8. **Start the development server**
   ```bash
   php craft serve --port=8080
   ```

9. **Access the site**
   - Frontend: http://localhost:8080
   - Control Panel: http://localhost:8080/admin

## Project Structure

```
craft-personal-website/
├── config/              # Craft CMS configuration
│   ├── general.php     # General config settings
│   ├── app.php         # Application config
│   └── project/        # Project config (YAML)
├── migrations/          # Content migrations
├── templates/           # Twig templates
│   ├── _layout.twig    # Base layout
│   ├── home/           # Homepage
│   ├── about/          # About page
│   ├── contact/        # Contact page
│   ├── creative/       # Creative projects
│   ├── thoughts/       # Blog
│   └── travels/        # Travel journal
├── web/                 # Web root
└── storage/            # Runtime files, logs, backups
```

## Content Architecture

### Sections
- **Home** (Single) - Homepage with featured content
- **About** (Single) - About me page
- **Contact** (Single) - Contact information
- **Photography** (Structure) - 3-level hierarchical structure (Category → Album → Photo)
- **Freelance Work** (Channel) - Client projects and deliverables
- **Thoughts** (Channel) - Blog posts
- **Travels** (Channel) - Travel experiences

### Custom Fields
- **Locations** - Hierarchical categories (Country → State/Province → City) used across all content types
- Photography metadata (Date Taken, Location, Camera, Lens, ISO, Aperture, Shutter Speed)
- Freelance work fields (Project Type, Customer, Delivery Date, Project Images)
- Travel details (Destination, Start Date, End Date, Travel Type)
- Media (Featured Image, Gallery)
- Relationships (Related Blog Posts, Related Travels, Related Photography Albums)

### Location System
The site features a powerful 3-level location taxonomy:
- **Level 1**: Countries (USA, Netherlands, Japan, etc.)
- **Level 2**: States/Provinces (Massachusetts, North Holland, etc.)
- **Level 3**: Cities (Boston, Amsterdam, Tokyo, etc.)

All content types (Photography Albums, Freelance Projects, Blog Posts, Travels) can be tagged with locations. This creates an amazing browsing experience where users can:
- Visit `/locations/usa/massachusetts/boston` to see all content from Boston
- Browse all countries, then drill down to states and cities
- See everything you've created in a specific place across all content types

Example: A blog post about a trip to Boston links to photography albums from Boston, which link to travel entries, creating a rich, interconnected experience organized by place.

### Entry Types
- **Genre** - Photography genres like Sports, City, Wildlife, Outdoors (Level 1 in Photography structure)
- **Album** - Photo album collections with location and date (Level 2 in Photography structure)
- **Photo** - Individual photos with EXIF data (Level 3 in Photography structure)
- **Freelance Project** - Client work and deliverables
- **Blog Post** - Blog articles
- **Travel Entry** - Travel experiences

## Development

### Creating Content
1. Log in to the Control Panel at `/admin`
2. Create entries in each section
3. Add categories for organization
4. Upload images and build galleries
5. Link related content together using relationship fields

### Deployment
The project uses Craft's Project Config for easy deployment:
1. Make changes in development
2. Commit the YAML files in `config/project/`
3. Deploy to production
4. Run `php craft project-config/apply` to sync changes

## License

Private project - all rights reserved.

## Author

Mark Urquhart
