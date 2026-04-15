# ScholarVault 📚
### RDIE Repository System

**ScholarVault** is a modern, high-performance repository system designed for Research, Development, Innovation, and Extension (RDIE) management. Built with a focus on visual excellence and user experience, it streamlines the lifecycle of scholarly works from submission to final approval.

---

## 🚀 Features

- **Dynamic Analytics Dashboard**: Visual insights into scholarly works using Chart.js.
- **Multi-Step Evaluation Pipeline**: Real-time progress tracking for all submissions.
- **Advanced User Roles**: Tailored interfaces for Proponents, Evaluators, and Administrators.
- **Premium UI/UX**: Glassmorphism design, native Dark/Light mode, and smooth micro-animations.
- **Real-Time Notifications**: Instant updates on account status and work evaluations.
- **Security First**: Live registration validation, password strength metering, and role-based access control.

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL (PDO for secure transactions)
- **Frontend**: HTML5, CSS3 (Vanilla), ES6+ JavaScript
- **Visualization**: Chart.js
- **Icons**: FontAwesome 6, Google Material Symbols
- **Typography**: Google Sans / Inter

---

## ⚙️ Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/index.html) or any WAMP/LAMP stack.
- PHP 8.0 or higher.
- MySQL Server.

### Steps
1. **Clone/Download the Repository**
   Place the project folder in your local server directory (e.g., `C:/xampp/htdocs/ScholarVault`).

2. **Database Setup**
   - Open **phpMyAdmin**.
   - Create a new database named `data`.
   - Import the SQL schema located in `/database/schema.sql` (if available) or relevant SQL files in the `/database` directory.

3. **Configure Connection**
   - Navigate to `config/db.php`.
   - Update the database credentials to match your local setup:
     ```php
     $host = 'localhost';
     $db   = 'data';
     $user = 'root';
     $pass = ''; // Your MySQL password
     ```

4. **Launch the Application**
   - Open your browser and navigate to `http://localhost/ScholarVault`.

---

## 📂 Project Structure

- `/api`: Backend API endpoints for authentication, uploads, and data fetching.
- `/assets`: Frontend resources (CSS, JS, Fonts, Images).
- `/config`: Database and global configuration files.
- `/database`: SQL scripts and schema documentation.
- `/includes`: Reusable PHP fragments (Auth helpers, headers).
- `/views`: Role-specific page components and modules.
- `/uploads`: Storage for user documents and profile pictures.

---

## 📄 License

&copy; 2024 ScholarVault RDIE Repository System. All rights reserved.

---

> [!TIP]
> **Pro Tip**: Use the theme toggle in the sidebar to switch between Dark and Light mode for the best viewing experience!
