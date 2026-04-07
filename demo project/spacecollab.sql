-- ============================================================
-- SpaceCollab Database Setup
-- Run this file in phpMyAdmin or MySQL CLI:
--   mysql -u root -p < spacecollab.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS spacecollab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spacecollab;

-- ─────────────────────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,         -- bcrypt hash
    avatar      VARCHAR(255) DEFAULT NULL,
    bio         TEXT DEFAULT NULL,
    role        ENUM('student','admin') DEFAULT 'student',
    points      INT DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS login_otps (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    otp_hash    VARCHAR(255) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- PROJECTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS projects (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    description  TEXT NOT NULL,
    objectives   TEXT,
    tags         VARCHAR(255),               -- comma-separated tags
    creator_id   INT NOT NULL,
    status       ENUM('pending','approved','rejected') DEFAULT 'approved',
    cover_image  VARCHAR(255) DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- PROJECT MEMBERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_members (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    joined_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- PROJECT FILES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_files (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    uploader_id INT NOT NULL,
    filename    VARCHAR(255) NOT NULL,
    original    VARCHAR(255) NOT NULL,
    file_type   VARCHAR(50),
    file_size   INT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES users(id)    ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- EXPERIMENTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS experiments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    steps       TEXT,
    media       VARCHAR(255) DEFAULT NULL,    -- image/video filename
    tags        VARCHAR(255),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- COMMENTS (polymorphic: projects or experiments)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    entity_type  ENUM('project','experiment','forum') NOT NULL,
    entity_id    INT NOT NULL,
    content      TEXT NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- LIKES (polymorphic)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS likes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    entity_type ENUM('experiment','forum_post') NOT NULL,
    entity_id   INT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, entity_type, entity_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- FORUM CATEGORIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS forum_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon        VARCHAR(50) DEFAULT '🚀'
);

-- ─────────────────────────────────────────────────────────────
-- FORUM THREADS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS forum_threads (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    content     TEXT NOT NULL,
    views       INT DEFAULT 0,
    is_pinned   TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)            ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- FORUM REPLIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS forum_replies (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id   INT NOT NULL,
    content   TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)         ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) DEFAULT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- SEED DATA
-- ─────────────────────────────────────────────────────────────

-- Admin user  (password: admin123)
INSERT INTO users (name, email, password, role, points) VALUES
('Mission Control', 'admin@spacecollab.io',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 9999);

-- Demo students  (password: password)
INSERT INTO users (name, email, password, points) VALUES
('Yuri Cosmos',   'yuri@spacecollab.io',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 340),
('Luna Starfield', 'luna@spacecollab.io',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 280),
('Orion Drake',   'orion@spacecollab.io',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 210),
('Nova Chen',     'nova@spacecollab.io',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 175);

-- Forum categories
INSERT INTO forum_categories (name, description, icon) VALUES
('Black Holes',      'Singularities, event horizons, Hawking radiation',   '🕳️'),
('Rockets & Propulsion', 'Launch systems, fuel, orbital mechanics',        '🚀'),
('NASA Missions',    'Past, present & future NASA exploration programs',   '🛸'),
('Exoplanets',       'Worlds beyond our solar system',                     '🌍'),
('Mars Colonization','Planning humanity\'s second home',                   '🔴'),
('Astronomy & Astrophysics', 'Stars, galaxies, cosmology',                 '⭐');

-- Sample projects
INSERT INTO projects (title, description, objectives, tags, creator_id, status) VALUES
('Mars Rover Navigation AI',
 'Build an AI model that can navigate Mars terrain using simulated sensor data from NASA open datasets.',
 'Simulate Martian surface · Implement pathfinding · Test obstacle avoidance · Publish findings',
 'Mars,AI,Robotics,Navigation',
 2, 'approved'),
('CubeSat Communication Array',
 'Design a low-cost communication array for CubeSat satellites using software-defined radio.',
 'RF signal simulation · Ground station software · Data link protocol · Power budget analysis',
 'Satellite,SDR,Hardware,CubeSat',
 3, 'approved'),
('Solar Wind Visualizer',
 'Create an interactive 3D visualisation of solar wind particle interactions with planetary magnetospheres.',
 'Real-time data from NOAA · 3D rendering with WebGL · Educational tool for students',
 'Solar,Visualization,WebGL,Space Weather',
 4, 'approved');

-- Project members
INSERT INTO project_members (project_id, user_id) VALUES
(1, 2), (1, 3), (1, 5),
(2, 3), (2, 4),
(3, 4), (3, 5), (3, 2);

-- Sample experiments
INSERT INTO experiments (user_id, title, description, steps, tags) VALUES
(2, 'Spectroscopy of Common Household Lights',
 'Using a DIY spectrometer to analyse the emission spectra of LED, CFL, and incandescent bulbs and compare with stellar spectra.',
 '1. Build DVD-grating spectrometer\n2. Capture spectra of each bulb\n3. Record wavelength peaks\n4. Compare with stellar classification charts\n5. Document findings',
 'Spectroscopy,Light,DIY,Stellar'),
(3, 'Simulating Gravity Assist Maneuvers',
 'Python simulation of gravity assist (slingshot) maneuvers using real orbital data from the Voyager missions.',
 '1. Install Astropy & Matplotlib\n2. Fetch Voyager trajectory data\n3. Simulate Jupiter flyby\n4. Calculate delta-v gain\n5. Visualise trajectory change',
 'Gravity,Python,Orbital,Simulation'),
(4, 'Detecting Cosmic Rays with a Cloud Chamber',
 'Build a diffusion cloud chamber to visualise cosmic ray tracks and measure their frequency at different altitudes.',
 '1. Build dry-ice cloud chamber\n2. Prepare isopropyl alcohol felt\n3. Cool chamber to -40°C\n4. Observe particle tracks\n5. Record and classify tracks',
 'Cosmic Rays,Cloud Chamber,DIY,Particle Physics');

-- Sample forum threads
INSERT INTO forum_threads (category_id, user_id, title, content, views) VALUES
(1, 2, 'What would happen if you fell into a black hole?',
 'I''ve been reading about spaghettification and tidal forces. Would you actually see the universe''s timeline speed up before crossing the event horizon? Let''s discuss the physics!',
 142),
(2, 3, 'SpaceX Starship vs SLS – which will reach the Moon first?',
 'Both vehicles are targeting lunar missions in the coming years. Starship has a much higher payload capacity but SLS has NASA backing. What do you all think?',
 98),
(5, 4, 'What should the first permanent Mars base look like?',
 'Subsurface lava tubes seem like the most radiation-shielded option. But surface habitats with regolith shielding are easier to build. Let''s debate the merits of each approach.',
 76);

-- Sample replies
INSERT INTO forum_replies (thread_id, user_id, content) VALUES
(1, 3, 'From the observer''s frame outside the black hole, you''d appear to slow down and redshift. From your own frame, you''d cross the event horizon without noticing — until tidal forces tear you apart!'),
(1, 5, 'The Penrose diagram is the best way to visualise this. The event horizon is actually a lightlike surface, not a physical barrier.'),
(2, 5, 'Starship''s in-orbit refuelling changes everything. If SpaceX can pull that off reliably, SLS becomes very expensive by comparison.'),
(3, 2, 'Lava tubes all the way. Natural radiation shielding, temperature stability, and no need to haul shielding material from Earth.');

-- Sample likes
INSERT INTO likes (user_id, entity_type, entity_id) VALUES
(3, 'experiment', 1), (4, 'experiment', 1), (5, 'experiment', 1),
(2, 'experiment', 2), (4, 'experiment', 2),
(2, 'experiment', 3), (3, 'experiment', 3);

-- Sample notifications
INSERT INTO notifications (user_id, message, link) VALUES
(2, 'Luna Starfield joined your project "Mars Rover Navigation AI"', '/spacecollab/projects/view.php?id=1'),
(2, 'Orion Drake liked your experiment "Spectroscopy of Common Household Lights"', '/spacecollab/experiments/'),
(3, 'Nova Chen replied to your forum thread', '/spacecollab/forum/thread.php?id=2');
