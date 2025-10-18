# Personal Website - Craft CMS

A modern personal website built with Craft CMS 5, featuring a sophisticated content architecture for showcasing photography, creative projects, blog posts, and travel experiences.

## Features

### Content Types
- **Photography** - Photo albums with hierarchical structure
  - Albums (parent entries) with featured images, locations, and dates
  - Individual photos (child entries) with detailed EXIF data (camera, lens, ISO, aperture, shutter speed)
  - Albums can be related to blog posts and travel entries
- **Creative Projects** - Showcase web development, graphic design, and other creative work
  - Website projects with technology stack and live URLs
  - Graphic design projects with tools and software used
  - Other creative projects
- **Thoughts (Blog)** - Personal blog with categories and related content
- **Travels** - Travel journal with destinations, dates, photo galleries, and trip types
- **Static Pages** - Home, About, and Contact pages

### Key Features
- Bi-directional content relationships (link blog posts to projects and travels)
- Category taxonomies for both creative work and blog posts
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
- **Photography** (Structure) - Photo albums with hierarchical album/photo structure
- **Creative Projects** (Channel) - Portfolio with 3 entry types
- **Thoughts** (Channel) - Blog posts
- **Travels** (Channel) - Travel experiences

### Custom Fields
- Photography metadata (Date Taken, Location, Camera, Lens, ISO, Aperture, Shutter Speed)
- Project information (Client, Project Date, Technologies, Project URL)
- Travel details (Destination, Start Date, End Date, Travel Type)
- Media (Featured Image, Gallery)
- Relationships (Related Creative Projects, Related Blog Posts, Related Travels)

### Entry Types
- **Album** - Photo album collections (parent entries in Photography structure)
- **Photo** - Individual photos with EXIF data (child entries in Photography structure)
- **Website Project** - Web development work
- **Graphic Design Project** - Design projects
- **Other** - Other creative projects
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
