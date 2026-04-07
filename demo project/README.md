# 🚀 SpaceCollab — Collaborative Space Learning Platform

A full-stack PHP + MySQL web application for students to collaborate on space science projects, share experiments, discuss topics, and compete on a leaderboard.

---

## 📁 Folder Structure

```
spacecollab/
├── index.php               ← Entry point (redirects)
├── login.php               ← Student/admin login
├── register.php            ← New account registration
├── logout.php              ← Session destroyer
├── dashboard.php           ← Personalized home dashboard
├── spacecollab.sql         ← Database schema + seed data
│
├── includes/
│   ├── db.php              ← PDO database connection
│   ├── auth.php            ← Session helpers, point awards
│   ├── header.php          ← Shared HTML shell (top + sidebar)
│   └── footer.php          ← Shared HTML shell (bottom)
│
├── assets/
│   ├── css/main.css        ← Full design system (dark space theme)
│   └── js/
│       ├── main.js         ← AJAX likes, comments, replies, toasts
│       └── search.js       ← Search autocomplete dropdown
│
├── projects/
│   ├── index.php           ← Browse & search projects
│   ├── create.php          ← Create new project
│   ├── view.php            ← Project detail + files + comments
│   └── join.php            ← POST handler to join project
│
├── experiments/
│   ├── index.php           ← Browse experiments with likes
│   ├── create.php          ← Share new experiment
│   └── view.php            ← Experiment detail + comments
│
├── forum/
│   ├── index.php           ← Forum overview with categories
│   ├── category.php        ← Thread list per category
│   ├── thread.php          ← Thread detail + AJAX replies
│   └── create.php          ← New thread form
│
├── leaderboard/
│   └── index.php           ← Points rankings + podium
│
├── admin/
│   └── index.php           ← Admin: users, projects, moderation
│
├── api/
│   ├── like.php            ← AJAX toggle like
│   ├── comment.php         ← AJAX post comment
│   ├── reply.php           ← AJAX post forum reply
│   ├── notifications.php   ← AJAX fetch/mark notifications
│   └── search.php          ← AJAX global search
│
└── uploads/
    ├── projects/           ← Project cover images & files
    └── experiments/        ← Experiment images & videos
```

---

## ⚙️ Installation

### 1. Prerequisites
- **XAMPP / WAMP / MAMP** (or any Apache + PHP 8.0+ + MySQL stack)
- PHP extensions: `pdo_mysql`, `fileinfo`

### 2. Place files
Copy the `spacecollab/` folder into your web server root:
- XAMPP: `C:/xampp/htdocs/spacecollab/`
- WAMP:  `C:/wamp64/www/spacecollab/`
- Linux: `/var/www/html/spacecollab/`

### 3. Create the database
Open **phpMyAdmin** → click **Import** → select `spacecollab.sql` → click **Go**

Or via command line:
```bash
mysql -u root -p < spacecollab.sql
```

### 4. Configure database credentials
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'spacecollab');
define('BASE_URL', '/spacecollab'); // adjust if deployed differently
```

### 5. Set upload permissions (Linux/Mac)
```bash
chmod -R 775 uploads/
```

### 6. Access the app
Open your browser: `http://localhost/spacecollab/`

---

## 🔑 Demo Accounts

| Role    | Email                    | Password   |
|---------|--------------------------|------------|
| Admin   | admin@spacecollab.io     | password   |
| Student | yuri@spacecollab.io      | password   |
| Student | luna@spacecollab.io      | password   |
| Student | orion@spacecollab.io     | password   |
| Student | nova@spacecollab.io      | password   |

---

## 🌌 Features

| Feature | Description |
|---------|-------------|
| **Auth** | Register, login, logout with bcrypt passwords & sessions |
| **Dashboard** | Stats, joined projects, recent experiments, notifications |
| **Projects** | Create, browse, join, upload files, comment |
| **Experiments** | Share with steps + media, like, comment |
| **Forum** | Category-based threads, AJAX replies (no page reload) |
| **Leaderboard** | Points system with podium for top 3 |
| **Notifications** | Bell icon with unread count, AJAX panel |
| **Search** | Global autocomplete across projects, experiments, threads |
| **Admin Panel** | User ban/delete, project approve/reject, thread moderation |

---

## 🏆 Points System

| Action | Points |
|--------|--------|
| Create a project | +50 |
| Join a project | +10 |
| Share experiment | +30 |
| Upload a file | +20 |
| Forum thread | +15 |
| Forum reply | +10 |
| Post comment | +5 |
| Receive a like | +2 |

---

## 🔒 Security Features

- **Prepared statements** (PDO) throughout — no SQL injection possible
- **bcrypt** password hashing (`password_hash` / `password_verify`)
- **Session regeneration** on login
- **Email-based OTP verification** for secure second-factor login
- **File upload validation**: MIME type check + size limit + random filename
- **XSS prevention**: all output wrapped in `htmlspecialchars()` via `e()`
- **CSRF**: form submissions are session-authenticated
- **Role-based access**: `requireLogin()` and `requireAdmin()` guards

---

## 🎨 Design System

- **Font Display**: Orbitron (NASA-inspired monospace)
- **Font Body**: Sora (clean, modern)
- **Theme**: Deep space dark with cyan + purple + amber accents
- **Animations**: Twinkling stars, glowing buttons, smooth transitions
- **Responsive**: Mobile-first grid with sidebar hidden on small screens

---

## 🚀 Extending the App

- **Real-time**: Replace AJAX polling with WebSockets (Ratchet PHP)
- **Email notifications**: Add PHPMailer for email on key events  
- **OAuth**: Add Google login with a library like `league/oauth2-client`
- **Image resizing**: Add GD/Imagick thumbnail generation on upload
- **Full-text search**: Switch to MySQL FULLTEXT indexes for better search
