# Exam Portal Web Application

## Prerequisites
- PHP 7.4+
- XAMPP or equivalent local server
- Node.js and npm

## Tailwind CSS Setup
1. Install dependencies:
   ```bash
   npm install
   ```

2. Build CSS:
   ```bash
   npm run build:css
   ```

3. For development with live reloading:
   ```bash
   npm run watch:css
   ```

## Project Structure
- `login.php`: Login page with modern Tailwind design
- `input.css`: Tailwind base styles
- `tailwind.config.js`: Tailwind configuration
- `output.css`: Generated production CSS

## Color Palette
- Primary: Blue (#3B82F6)
- Secondary: Green (#10B981)
- Accent: Indigo (#6366F1)
- Background: Light Gray (#F3F4F6)
- Text: Dark Gray (#1F2937)

## Responsive Design
The application uses Tailwind's responsive utilities to ensure a great experience across devices.

## Customization
Modify `tailwind.config.js` to adjust theme, colors, and add custom utilities.

## Features

- **User Authentication**: Login system with role-based access
- **Admin Dashboard**: Manage users, view exam results
- **Formateur Dashboard**: Create and manage exams, grade submissions
- **Stagiaire Dashboard**: Take exams and view results
- **Exam Management**: Support for QCM (multiple choice) and open-ended questions

## Installation

1. Clone this repository to your web server
2. Import the database schema from `database/schema.sql`
3. Configure your database connection in `config/config.php`
4. Access the application through your web browser

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
